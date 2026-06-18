<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\DatasetImportService;
use App\Libraries\FilteredMetadataExporter;
use App\Libraries\GeoportalDatasetRegistry;
use App\Libraries\MarketplaceService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Database;

class Catalog extends BaseController
{
    private GeoportalDatasetRegistry $registry;
    private FilteredMetadataExporter $metadataExporter;
    private DatasetImportService $importer;
    private MarketplaceService $marketplace;

    public function __construct()
    {
        $this->registry    = new GeoportalDatasetRegistry();
        $this->metadataExporter = new FilteredMetadataExporter();
        $this->importer    = new DatasetImportService();
        $this->marketplace = new MarketplaceService();
    }

    public function index()
    {
        $request = $this->request;

        $search = trim((string) ($request->getGet('q') ?? ''));
        $perPage = max(1, (int) ($request->getGet('per_page') ?? 20));
        $page = max(1, (int) ($request->getGet('page') ?? 1));

        $downloadable = $request->getGet('downloadable') === '1';
        $viewable = $request->getGet('viewable') === '1';

        $scopes = $request->getGet('scope');
        if (is_string($scopes)) {
            $scopes = [$scopes];
        }
        if (!is_array($scopes)) {
            $scopes = [];
        }

        $anomalies = $request->getGet('anomaly');
        if (is_string($anomalies)) {
            $anomalies = [$anomalies];
        }
        if (!is_array($anomalies)) {
            $anomalies = [];
        }
        $anomalies = array_values(array_filter(array_map(
            static fn ($value): string => strtolower(trim((string) $value)),
            $anomalies
        )));

        $levels = $request->getGet('level');
        if (is_string($levels)) {
            $levels = [$levels];
        }
        if (!is_array($levels)) {
            $levels = [];
        }
        $levels = array_values(array_filter(array_map(
            static fn ($value): string => strtolower(trim((string) $value)),
            $levels
        )));

        $selectedOrgs = $request->getGet('organization');
        if (is_string($selectedOrgs)) $selectedOrgs = [$selectedOrgs];
        if (!is_array($selectedOrgs)) $selectedOrgs = [];
        $selectedOrgs = array_values(array_filter(array_map('strval', $selectedOrgs)));

        $filters = [
            'q' => $search,
            'downloadable' => $downloadable,
            'viewable' => $viewable,
            'spatial_scope' => $scopes,
            'anomaly' => $anomalies,
            'level' => $levels,
            'organization_ids' => $selectedOrgs,
        ];

        $allEntries = $this->registry->filterCatalogEntries($filters);

        // Build organisation list for filter UI
        $db = Database::connect();
        $organizations = $db->query(
            'SELECT org_id AS id, org_name AS name FROM geoportal.organizations WHERE is_active = true ORDER BY org_name'
        )->getResultArray();

        $total = count($allEntries);
        $totalPages = (int) max(1, ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        $datasets = array_slice($allEntries, $offset, $perPage);

        $quota = null;
        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId > 0 && !in_array($this->userRole(), ['admin', 'superadmin'], true)) {
            $quota = $this->marketplace->checkQuota($userId);
        }

        return view('v_catalog', [
            'datasets' => $datasets,
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'downloadable' => $downloadable,
            'viewable' => $viewable,
            'scopes' => $scopes,
            'anomalies' => $anomalies,
            'levels' => $levels,
            'selectedOrgs' => $selectedOrgs,
            'organizations' => $organizations,
            'countryCode' => 'ID',
            'countryName' => 'Indonesia',
            'quota' => $quota,
        ]);
    }

    public function view(int $id)
    {
        $dataset = $this->catalogEntry($id);

        if (empty($dataset['is_viewable'])) {
            return $this->response->setStatusCode(403)->setBody('This dataset is not viewable.');
        }

        $dataset['download_label'] = $dataset['type'] === 'raster' ? 'Download GeoTIFF' : 'Download CSV';
        $dataset['metadata_label'] = 'Download Metadata XML';

        $initialBbox = null;
        if (!empty($dataset['province_id'])) {
            $db = Database::connect();
            $row = $db->query('
                SELECT
                    ST_XMin(ST_Envelope(geom)) AS west,
                    ST_YMin(ST_Envelope(geom)) AS south,
                    ST_XMax(ST_Envelope(geom)) AS east,
                    ST_YMax(ST_Envelope(geom)) AS north
                FROM geoportal.polygon_adm_province
                WHERE adm_id = ?
                LIMIT 1
            ', [(int) $dataset['province_id']])->getRowArray();
            if ($row) {
                $initialBbox = [
                    'west'  => (float) $row['west'],
                    'south' => (float) $row['south'],
                    'east'  => (float) $row['east'],
                    'north' => (float) $row['north'],
                ];
            }
        }

        return view('v_dataset_view', [
            'dataset'     => $dataset,
            'initialBbox' => $initialBbox,
        ]);
    }

    public function download(int $id)
    {
        $dataset = $this->catalogEntry($id);

        if (!$this->isLoggedIn()) {
            session()->setFlashdata('error', 'Silakan login untuk mengunduh data.');
            return redirect()->to(site_url('login'));
        }

        $role = $this->userRole();
        if (!in_array($role, ['admin', 'user', 'superadmin'], true)) {
            session()->setFlashdata('error', 'Anda memerlukan langganan aktif untuk mengunduh data. Lihat paket yang tersedia.');
            return redirect()->to(site_url('signup'));
        }

        if (empty($dataset['is_downloadable'])) {
            return $this->response->setStatusCode(403)->setBody('Dataset ini tidak dapat diunduh.');
        }

        $userId = (int) (session()->get('user_id') ?? 0);
        $isPrivileged = in_array($role, ['admin', 'superadmin'], true);

        // ── Level-2 gate (semua tier berbayar boleh) ─────────────────
        $datasetCode = $dataset['dataset_code'] ?? '';
        if (str_ends_with($datasetCode, '_l2') && !$isPrivileged) {
            $tier = $this->marketplace->userTier($userId);
            if (in_array($tier, ['none', 'free'], true)) {
                session()->setFlashdata('error', 'Dataset Level 2 (GeoTIFF) memerlukan paket berbayar (Lite, Pro, atau Team).');
                return redirect()->to(site_url('signup'));
            }
        }

        // ── Quota check ───────────────────────────────────────────────
        if ($userId > 0 && !$isPrivileged) {
            $quota = $this->marketplace->checkQuota($userId);
            if (!$quota['allowed']) {
                if (($quota['reason'] ?? '') === 'no_subscription') {
                    session()->setFlashdata('error', 'Anda belum memiliki langganan aktif. Daftar paket untuk mulai mengunduh data.');
                    return redirect()->to(site_url('signup'));
                }
                session()->setFlashdata('error', 'Kuota unduhan mingguan Anda telah habis. Tunggu reset di hari Senin atau upgrade ke paket Pro/Team.');
                return redirect()->to(site_url('catalog'));
            }
        }

        // ── Perform download ──────────────────────────────────────────
        $rowCount = null;

        if ($dataset['type'] === 'vector') {
            $export   = $this->exportVectorCsv($dataset);
            $rowCount = $export['row_count'] ?? null;
            $size     = is_file((string)($export['path'] ?? '')) ? filesize($export['path']) : null;

            $this->logCatalogDownload($userId, $dataset, 'vector', $rowCount, $size ?: null);

            return $this->response->download($export['path'], null)->setFileName($export['filename']);
        }

        $originalRaster = $this->originalRasterPath($dataset['dataset_code']);
        if ($dataset['spatial_scope'] === 'national' && $originalRaster !== null) {
            $size = is_file($originalRaster) ? filesize($originalRaster) : null;
            $this->logCatalogDownload($userId, $dataset, 'raster', null, $size ?: null);

            return $this->response->download($originalRaster, null)
                ->setFileName($this->safeFilename($dataset['title']) . '.tif');
        }

        $binary = $this->exportRasterBinary($dataset);
        if ($binary === null) {
            return $this->response->setStatusCode(404)->setBody('Raster tidak ditemukan untuk dataset ini.');
        }

        $path = $this->writeBinaryExport($binary, $this->safeFilename($dataset['title']) . '.tif', 'data');
        $size = is_file($path) ? filesize($path) : null;
        $this->logCatalogDownload($userId, $dataset, 'raster', null, $size ?: null);

        return $this->response->download($path, null)->setFileName(basename($path));
    }

    private function logCatalogDownload(int $userId, array $dataset, string $type, ?int $rowCount, ?int $sizeBytes = null): void
    {
        try {
            $this->marketplace->logDownload([
                'user_id'             => $userId ?: null,
                'dataset_code'        => $dataset['dataset_code'] ?? '',
                'dataset_type'        => $type,
                'filter_params'       => [],
                'row_count'           => $rowCount,
                'download_size_bytes' => $sizeBytes,
                'user_agent'          => $this->request->getUserAgent()->getAgentString(),
            ]);
        } catch (\Throwable) {}
    }

    public function downloadMetadata(int $id)
    {
        if (!$this->isLoggedIn()) {
            session()->setFlashdata('error', 'Silakan login untuk mengunduh metadata.');
            return redirect()->to(site_url('login'));
        }

        $userId = (int) (session()->get('user_id') ?? 0);
        $role   = $this->userRole();
        $isPrivileged = in_array($role, ['admin', 'superadmin'], true);

        if (!$isPrivileged) {
            $tier = $this->marketplace->userTier($userId);
            if (in_array($tier, ['none', 'free'], true)) {
                session()->setFlashdata('error', 'Metadata XML memerlukan langganan aktif (Lite, Pro, atau Team).');
                return redirect()->to(site_url('signup'));
            }
        }

        $dataset     = $this->catalogEntry($id);
        $datasetCode = $dataset['dataset_code'] ?? '';

        // Prefer the official CatMD XML uploaded by superadmin for this specific dataset
        $db  = Database::connect();
        $row = $db->query(
            "SELECT raw_xml, file_identifier, title FROM geoportal.dataset_metadata_xml WHERE dataset_code = ? LIMIT 1",
            [$datasetCode]
        )->getRowArray();

        if (!empty($row['raw_xml'])) {
            $safeTitle = preg_replace('/[^A-Za-z0-9_\-]/', '_', $datasetCode);
            $filename  = 'metadata_' . $safeTitle . '.xml';
            return $this->response
                ->setHeader('Content-Type', 'application/xml; charset=utf-8')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setBody($row['raw_xml']);
        }

        // Fallback: generate metadata XML dynamically
        $filters = [
            'province_id'   => $dataset['province_id'],
            'province_name' => $dataset['province_name'],
            'bounds'        => null,
            'geometry_type' => null,
        ];
        $export = $this->metadataExporter->export($dataset, $filters);

        return $this->response->download($export['path'], null)->setFileName($export['filename']);
    }

    public function geojson(int $id)
    {
        $dataset = $this->catalogEntry($id);

        if (empty($dataset['is_viewable'])) {
            return $this->response->setStatusCode(403)->setBody('This dataset is not viewable.');
        }

        $bounds = $this->boundsFromRequest();
        $zoom = (int) ($this->request->getGet('zoom') ?? 0);

        $payload = $dataset['type'] === 'vector'
            ? $this->vectorPreview($dataset, $bounds, $zoom)
            : $this->rasterPreview($dataset, $bounds);

        return $this->response->setJSON($payload);
    }

    private function catalogEntry(int $id): array
    {
        $entry = $this->registry->catalogEntry($id);

        if ($entry === null) {
            throw PageNotFoundException::forPageNotFound('Dataset not found');
        }

        return $entry;
    }

    private function vectorPreview(array $entry, ?array $bounds, int $zoom): array
    {
        if ($this->shouldAggregateVectorPreview($bounds, $zoom) && $this->vectorPreviewSourceCount($entry, $bounds) > 500) {
            return $this->aggregatedVectorPreview($entry, $bounds, $zoom);
        }

        $dataset = $this->registry->dataset((string) $entry['dataset_code']);
        $db = Database::connect();
        [$sql, $params] = $this->entrySpatialSql('t.geom', $entry, $bounds);
        $db->query("BEGIN");
        $db->query("SET LOCAL statement_timeout = '30000'");
        try {
            $rows = $db->query('
                SELECT
                    t.id,
                    ROUND((t.latitude)::numeric, 6) AS latitude,
                    ROUND((t.longitude)::numeric, 6) AS longitude,
                    ROUND((t.orthometric_height)::numeric, 3) AS elevation_m,
                    ROUND((t.anomaly_value)::numeric, 3) AS anomaly_value,
                    t.source_file,
                    t.survey_mode,
                    ST_AsGeoJSON(t.geom) AS geojson
                FROM ' . $dataset['table'] . ' t
                WHERE 1=1
                ' . $sql . '
            ', $params)->getResultArray();
            $db->query("COMMIT");
        } catch (\Throwable $e) {
            try { $db->query("ROLLBACK"); } catch (\Throwable) {}
            throw $e;
        }

        $features = array_map(static function (array $row) use ($dataset): array {
            return [
                'type' => 'Feature',
                'geometry' => json_decode($row['geojson'], true),
                'properties' => [
                    'feature_id' => (string) $row['id'],
                    'title' => $dataset['code'] === 'faa_l1' ? 'FAA Point #' . $row['id'] : 'CBA Point #' . $row['id'],
                    'summary_label' => $dataset['code'] === 'faa_l1' ? 'Anomali' : 'Nilai CBA',
                    'summary_value' => (float) $row['anomaly_value'],
                    'summary_unit' => $dataset['summary_unit'],
                    'latitude' => (float) $row['latitude'],
                    'longitude' => (float) $row['longitude'],
                    'elevation_m' => (float) $row['elevation_m'],
                    'source_file' => (string) $row['source_file'],
                    'survey_mode' => (string) $row['survey_mode'],
                    'is_aggregate' => false,
                ],
            ];
        }, $rows);

        return [
            'dataset' => $entry,
            'meta' => [
                'mode' => 'points',
                'zoom' => $zoom,
                'feature_count' => count($features),
            ],
            'collection' => [
                'type' => 'FeatureCollection',
                'features' => $features,
            ],
        ];
    }

    private function aggregatedVectorPreview(array $entry, ?array $bounds, int $zoom): array
    {
        $dataset = $this->registry->dataset((string) $entry['dataset_code']);
        $db = Database::connect();
        [$sql, $params] = $this->entrySpatialSql('t.geom', $entry, $bounds);
        $gridSize = $this->aggregateGridSize($bounds, $zoom);

        $rows = $db->query('
            WITH src AS (
                SELECT
                    t.anomaly_value,
                    t.geom
                FROM ' . $dataset['table'] . ' t
                WHERE 1=1
                ' . $sql . '
            ),
            binned AS (
                SELECT
                    FLOOR(ST_X(t.geom) / ?) * ? AS cell_x,
                    FLOOR(ST_Y(t.geom) / ?) * ? AS cell_y,
                    COUNT(*) AS point_count,
                    AVG(t.anomaly_value) AS mean_val,
                    MIN(t.anomaly_value) AS min_val,
                    MAX(t.anomaly_value) AS max_val
                FROM src t
                GROUP BY 1, 2
            )
            SELECT
                ROW_NUMBER() OVER (ORDER BY cell_y, cell_x) AS bin_id,
                point_count,
                ROUND((SUM(point_count) OVER ())::numeric, 0) AS source_count,
                ROUND((mean_val)::numeric, 3) AS mean_val,
                ROUND((min_val)::numeric, 3) AS min_val,
                ROUND((max_val)::numeric, 3) AS max_val,
                ST_AsGeoJSON(
                    ST_SetSRID(
                        ST_MakePoint(cell_x + (? / 2.0), cell_y + (? / 2.0)),
                        4326
                    )
                ) AS centroid_geojson
            FROM binned
            ORDER BY point_count DESC, bin_id ASC
            LIMIT 1200
        ', array_merge($params, [
            $gridSize,
            $gridSize,
            $gridSize,
            $gridSize,
            $gridSize,
            $gridSize,
        ]))->getResultArray();

        $features = [];
        $sourceCount = 0;

        foreach ($rows as $row) {
            $sourceCount = (int) ($row['source_count'] ?? $sourceCount);
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['centroid_geojson'], true),
                'properties' => [
                    'feature_id' => 'agg-' . $row['bin_id'],
                    'title' => 'Ringkasan sel #' . $row['bin_id'],
                    'summary_label' => 'Mean',
                    'summary_value' => (float) $row['mean_val'],
                    'summary_unit' => $dataset['summary_unit'],
                    'point_count' => (int) $row['point_count'],
                    'min_val' => (float) $row['min_val'],
                    'max_val' => (float) $row['max_val'],
                    'is_aggregate' => true,
                ],
            ];
        }

        return [
            'dataset' => $entry,
            'meta' => [
                'mode' => 'aggregated_points',
                'zoom' => $zoom,
                'feature_count' => count($features),
                'source_feature_count' => $sourceCount,
                'grid_size_deg' => $gridSize,
            ],
            'collection' => [
                'type' => 'FeatureCollection',
                'features' => $features,
            ],
        ];
    }

    private function rasterPreview(array $entry, ?array $bounds): array
    {
        $dataset = $this->registry->dataset((string) $entry['dataset_code']);
        $db = Database::connect();
        [$sql, $params] = $this->entrySpatialSql('geom', $entry, $bounds);

        $rows = $db->query('
            WITH src AS (
                SELECT
                    rid,
                    rast,
                    COALESCE(grid_geom, ST_Envelope(rast)::geometry(Polygon, 4326)) AS geom,
                    ST_SummaryStats(rast, 1, true) AS stats
                FROM ' . $dataset['table'] . '
            ),
            tiles AS (
                SELECT
                    rid,
                    geom,
                    ST_Width(rast) AS width_px,
                    ST_Height(rast) AS height_px,
                    ROUND((ST_XMax(geom) - ST_XMin(geom))::numeric, 6) AS grid_width_deg,
                    ROUND((ST_YMax(geom) - ST_YMin(geom))::numeric, 6) AS grid_height_deg,
                    ROUND(((stats).mean)::numeric, 3) AS mean_val
                FROM src
            )
            SELECT
                rid,
                width_px,
                height_px,
                grid_width_deg,
                grid_height_deg,
                mean_val,
                ST_AsGeoJSON(geom) AS geojson
            FROM tiles
            WHERE 1=1
            ' . $sql . '
            ORDER BY rid ASC
        ', $params)->getResultArray();

        $features = array_map(static function (array $row) use ($dataset): array {
            return [
                'type' => 'Feature',
                'geometry' => json_decode($row['geojson'], true),
                'properties' => [
                    'feature_id' => (string) $row['rid'],
                    'title' => 'Grid #' . $row['rid'],
                    'summary_label' => 'Mean',
                    'summary_value' => (float) $row['mean_val'],
                    'summary_unit' => $dataset['summary_unit'],
                    'is_aggregate' => false,
                ],
            ];
        }, $rows);

        return [
            'dataset' => $entry,
            'meta' => [
                'mode' => 'grid',
            ],
            'collection' => [
                'type' => 'FeatureCollection',
                'features' => $features,
            ],
        ];
    }

    private function entrySpatialSql(string $geomColumn, array $entry, ?array $bounds): array
    {
        $clauses = [];
        $params = [];

        if (!empty($entry['province_id'])) {
            // Province IS the spatial extent; don't also apply viewport bounds.
            $clauses[] = 'ST_Intersects(' . $geomColumn . ', (SELECT geom FROM geoportal.polygon_adm_province WHERE adm_id = ' . (int) $entry['province_id'] . ' LIMIT 1))';
        } elseif ($bounds !== null) {
            $clauses[] = 'ST_Intersects(' . $geomColumn . ', ST_MakeEnvelope(?, ?, ?, ?, 4326))';
            $params[] = $bounds['west'];
            $params[] = $bounds['south'];
            $params[] = $bounds['east'];
            $params[] = $bounds['north'];
        }

        if ($clauses === []) {
            return ['', []];
        }

        return [' AND ' . implode(' AND ', $clauses) . ' ', $params];
    }

    private function boundsFromRequest(): ?array
    {
        $raw = $this->request->getGet('bbox');
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $parts = array_map('trim', explode(',', $raw));
        if (count($parts) !== 4) {
            return null;
        }

        foreach ($parts as $part) {
            if (!is_numeric($part)) {
                return null;
            }
        }

        $bounds = [
            'west' => (float) $parts[0],
            'south' => (float) $parts[1],
            'east' => (float) $parts[2],
            'north' => (float) $parts[3],
        ];

        if ($bounds['west'] >= $bounds['east'] || $bounds['south'] >= $bounds['north']) {
            return null;
        }

        if (
            $bounds['west'] < -180 || $bounds['west'] > 180
            || $bounds['east'] < -180 || $bounds['east'] > 180
            || $bounds['south'] < -90 || $bounds['south'] > 90
            || $bounds['north'] < -90 || $bounds['north'] > 90
        ) {
            return null;
        }

        return $bounds;
    }

    private function shouldAggregateVectorPreview(?array $bounds, int $zoom): bool
    {
        if ($zoom <= 7) {
            return true;
        }

        if ($bounds === null) {
            return true;
        }

        $span = max(
            abs((float) $bounds['east'] - (float) $bounds['west']),
            abs((float) $bounds['north'] - (float) $bounds['south'])
        );

        return $zoom <= 9 && $span >= 1.2;
    }

    private function aggregateGridSize(?array $bounds, int $zoom): float
    {
        $preset = match (true) {
            $zoom <= 5 => 0.35,
            $zoom <= 6 => 0.22,
            $zoom <= 7 => 0.14,
            $zoom <= 8 => 0.08,
            default => 0.04,
        };

        if ($bounds !== null) {
            $dynamic = max(
                abs((float) $bounds['east'] - (float) $bounds['west']) / 28,
                abs((float) $bounds['north'] - (float) $bounds['south']) / 18
            );
            $preset = max($preset, $dynamic);
        }

        return round(max(0.02, min(0.45, $preset)), 6);
    }

    private function vectorPreviewSourceCount(array $entry, ?array $bounds): int
    {
        $dataset = $this->registry->dataset((string) $entry['dataset_code']);
        $db = Database::connect();
        [$sql, $params] = $this->entrySpatialSql('t.geom', $entry, $bounds);
        try {
            $db->query("BEGIN");
            $db->query("SET LOCAL statement_timeout = '8000'");
            $row = $db->query('
                SELECT COUNT(*) AS total
                FROM ' . $dataset['table'] . ' t
                WHERE 1=1
                ' . $sql . '
            ', $params)->getRowArray();
            $db->query("COMMIT");
            return (int) ($row['total'] ?? 0);
        } catch (\Throwable) {
            try { $db->query("ROLLBACK"); } catch (\Throwable) {}
            return 9999;
        }
    }

    private function exportVectorCsv(array $entry): array
    {
        $dataset = $this->registry->dataset((string) $entry['dataset_code']);
        $conn = $this->rawConnection();
        $directory = $this->exportDirectory('data');
        $filename = $this->safeFilename($entry['title']) . '.csv';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new \RuntimeException('File export CSV tidak dapat dibuat.');
        }

        $sql = '
            SELECT
                id,
                latitude,
                longitude,
                orthometric_height,
                anomaly_value,
                source_file,
                survey_mode
            FROM ' . $dataset['table'] . ' t
            WHERE 1=1
            ' . $this->entryProvinceSql($entry, 't.geom') . '
            ORDER BY id ASC
        ';

        $result = pg_query($conn, $sql);
        if ($result === false) {
            fclose($handle);
            throw new \RuntimeException(pg_last_error($conn) ?: 'Export CSV PostgreSQL gagal dijalankan.');
        }

        fputcsv($handle, ['id', 'latitude', 'longitude', 'orthometric_height', 'anomaly_value', 'source_file', 'survey_mode']);
        while ($row = pg_fetch_assoc($result)) {
            fputcsv($handle, [
                $row['id'] ?? '',
                $row['latitude'] ?? '',
                $row['longitude'] ?? '',
                $row['orthometric_height'] ?? '',
                $row['anomaly_value'] ?? '',
                $row['source_file'] ?? '',
                $row['survey_mode'] ?? '',
            ]);
        }
        fclose($handle);

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    private function exportRasterBinary(array $entry): ?string
    {
        $dataset = $this->registry->dataset((string) $entry['dataset_code']);
        $db = Database::connect();

        if (empty($entry['province_id'])) {
            $row = $db->query('
                SELECT ST_AsTIFF(ST_Union(rast)) AS tif
                FROM ' . $dataset['table'] . '
            ')->getRowArray();
        } else {
            $row = $db->query('
                WITH province AS (
                    SELECT geom
                    FROM geoportal.polygon_adm_province
                    WHERE adm_id = ?
                    LIMIT 1
                ),
                clipped AS (
                    SELECT ST_Clip(r.rast, p.geom, true) AS rast
                    FROM ' . $dataset['table'] . ' r
                    CROSS JOIN province p
                    WHERE ST_Intersects(COALESCE(r.grid_geom, ST_Envelope(r.rast)::geometry(Polygon, 4326)), p.geom)
                )
                SELECT ST_AsTIFF(ST_Union(rast)) AS tif
                FROM clipped
            ', [(int) $entry['province_id']])->getRowArray();
        }

        if (!$row || empty($row['tif'])) {
            return null;
        }

        return $this->decodeBytea((string) $row['tif']);
    }

    private function originalRasterPath(string $datasetCode): ?string
    {
        $summary = $this->importer->summarizeLatestPackage();
        if ($summary === null) {
            return null;
        }

        $key = str_starts_with($datasetCode, 'faa_') ? 'faa_tif' : 'cba_tif';
        $path = $summary['level2'][$key] ?? null;

        if (!is_string($path) || $path === '') {
            return null;
        }

        $fullPath = ROOTPATH . str_replace('/', DIRECTORY_SEPARATOR, $path);

        return is_file($fullPath) ? $fullPath : null;
    }

    private function rawConnection()
    {
        $db = Database::connect();
        $conn = $db->connID;

        if (!$conn) {
            $db->initialize();
            $conn = $db->connID;
        }

        if (!$conn) {
            throw new \RuntimeException('Koneksi PostgreSQL gravport tidak tersedia.');
        }

        return $conn;
    }

    private function entryProvinceSql(array $entry, string $geomColumn): string
    {
        if (empty($entry['province_id'])) {
            return '';
        }

        return ' AND ST_Intersects(' . $geomColumn . ', (SELECT geom FROM geoportal.polygon_adm_province WHERE adm_id = ' . (int) $entry['province_id'] . ' LIMIT 1)) ';
    }

    private function writeBinaryExport(string $binary, string $filename, string $folder): string
    {
        $directory = $this->exportDirectory($folder);
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        if (file_put_contents($path, $binary) === false) {
            throw new \RuntimeException('File export biner tidak dapat ditulis.');
        }

        return $path;
    }

    private function exportDirectory(string $folder): string
    {
        $directory = WRITEPATH . 'exports' . DIRECTORY_SEPARATOR . $folder;
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException('Folder export tidak dapat dibuat.');
        }

        return $directory;
    }

    private function decodeBytea(string $value): string
    {
        if (str_starts_with($value, '\\x')) {
            $hex = substr($value, 2);
            if ($hex === '' || strlen($hex) % 2 !== 0) {
                return '';
            }
            $binary = hex2bin($hex);
            return $binary === false ? '' : $binary;
        }

        return pg_unescape_bytea($value);
    }

    private function safeFilename(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '_', $value);
        $value = trim((string) $value, '_');

        return $value !== '' ? $value : 'dataset';
    }

    private function userRole(): string
    {
        return auth_current_role();
    }

    private function isLoggedIn(): bool
    {
        return auth_is_logged_in();
    }
}
