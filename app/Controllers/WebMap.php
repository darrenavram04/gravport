<?php

namespace App\Controllers;

use App\Libraries\FilteredMetadataExporter;
use App\Libraries\GeoportalDatasetRegistry;
use App\Libraries\MarketplaceService;
use CodeIgniter\API\ResponseTrait;
use Config\Database;
use InvalidArgumentException;

class WebMap extends BaseController
{
    use ResponseTrait;

    private GeoportalDatasetRegistry $registry;
    private FilteredMetadataExporter $metadataExporter;
    private MarketplaceService $marketplace;

    public function __construct()
    {
        ini_set('memory_limit', '-1');
        $this->registry    = new GeoportalDatasetRegistry();
        $this->marketplace = new MarketplaceService();
        $this->metadataExporter = new FilteredMetadataExporter();
    }

    public function index()
    {
        return view('v_webmap');
    }

    public function bootstrap()
    {
        $datasets = array_values(array_map(fn (array $item): array => $this->clientDataset($item), $this->registry->definitions()));

        return $this->respond([
            'defaultDataset' => 'faa_l1',
            'datasets' => $datasets,
            'supports' => [
                'province' => true,
                'draw' => true,
                'upload_geojson' => true,
                'upload_kml' => true,
                'point_buffer' => true,
                'viewport_loading' => true,
                'filtered_metadata_download' => true,
            ],
        ]);
    }

    public function provinces()
    {
        return $this->response->setJSON($this->provinceCollection());
    }

    public function aoi()
    {
        return $this->provinces();
    }

    public function faa()
    {
        $payload = $this->vectorLayer('faa_l1', ['bounds' => null, 'zoom' => 6]);

        return $this->response->setJSON($payload['collection']);
    }

    public function layer()
    {
        ini_set('memory_limit', '-1'); // No cap — aggregation is capped by TABLESAMPLE + LIMIT 1200
        // Release session lock immediately — province queries can block for up to 15s.
        // Without this, any second request from the same browser session blocks at session_start()
        // until the first request finishes, causing two heavy queries to run back-to-back.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        try {
            $input = $this->inputPayload();
            $datasetCode = (string) ($input['dataset'] ?? 'faa_l1');
            $dataset = $this->dataset($datasetCode);
            $filters = $this->filtersFromInput($input);

            $payload = $dataset['type'] === 'vector'
                ? $this->vectorLayer($datasetCode, $filters)
                : $this->rasterGridLayer($datasetCode, $filters);

            return $this->respond($payload);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function featureMeta(string $datasetCode, string $featureId)
    {
        try {
            $dataset = $this->dataset($datasetCode);

            $payload = $dataset['type'] === 'vector'
                ? $this->vectorFeatureMeta($datasetCode, $featureId)
                : $this->rasterFeatureMeta($datasetCode, $featureId);

            return $this->respond($payload);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function downloadVector()
    {
        try {
            // Quota check
            $userId = (int) (session()->get('user_id') ?? 0);
            $role   = auth_current_role();
            if (!in_array($role, ['admin', 'superadmin'], true)) {
                $quota = $this->marketplace->checkQuota($userId);
                if (!$quota['allowed']) {
                    return $this->response->setStatusCode(403)->setJSON([
                        'error' => $quota['reason'] === 'no_subscription'
                            ? 'Diperlukan langganan aktif untuk mengunduh data.'
                            : 'Kuota unduhan mingguan Anda telah habis.',
                    ]);
                }
            }

            $input = $this->inputPayload();
            $datasetCode = (string) ($input['dataset'] ?? 'faa_l1');
            $dataset = $this->dataset($datasetCode);

            if ($dataset['type'] !== 'vector') {
                throw new InvalidArgumentException('Dataset ini bukan vektor.');
            }

            $filters = $this->filtersFromInput($input);
            // Always force full detail for downloads — no area restriction
            $filters['force_detail'] = true;
            $csvExport = $this->exportFilteredVectorCsvFile($datasetCode, $filters);

            $archive = $this->buildDatasetPackage(
                $datasetCode . '_vector_' . date('Ymd_His'),
                $datasetCode,
                $filters,
                [
                    ['name' => $csvExport['filename'], 'path' => $csvExport['path']],
                ]
            );

            $sizeBytes = is_file($archive['path']) ? filesize($archive['path']) : null;
            $this->logWebMapDownload($datasetCode, 'vector', $filters, $csvExport['row_count'] ?? null, $sizeBytes ?: null);
            return $this->response->download($archive['path'], null)->setFileName($archive['filename']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function clipRaster()
    {
        try {
            // Quota check
            $userId = (int) (session()->get('user_id') ?? 0);
            $role   = auth_current_role();
            if (!in_array($role, ['admin', 'superadmin'], true)) {
                $quota = $this->marketplace->checkQuota($userId);
                if (!$quota['allowed']) {
                    return $this->response->setStatusCode(403)->setJSON([
                        'error' => $quota['reason'] === 'no_subscription'
                            ? 'Diperlukan langganan aktif untuk mengunduh data.'
                            : 'Kuota unduhan mingguan Anda telah habis.',
                    ]);
                }
            }

            $input = $this->inputPayload();
            $datasetCode = (string) ($input['dataset'] ?? 'faa_l2');

            if ($this->enterpriseGateFail($datasetCode)) {
                return $this->response->setStatusCode(403)->setJSON([
                    'error' => 'Dataset Level 2 (raster GeoTIFF) memerlukan paket berbayar (Lite, Pro, atau Team).',
                    'upgrade_url' => site_url('signup'),
                ]);
            }
            $binary = $this->rasterBinaryFromFilters($datasetCode, $this->filtersFromInput($input));

            if ($binary === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'error' => 'Raster tidak ditemukan untuk area aktif tersebut.',
                ]);
            }

            $archive = $this->buildDatasetPackage(
                $datasetCode . '_clip_' . date('Ymd_His'),
                $datasetCode,
                $this->filtersFromInput($input),
                [
                    ['name' => $datasetCode . '.tif', 'contents' => $binary],
                ]
            );

            $sizeBytes = is_file($archive['path']) ? filesize($archive['path']) : null;
            $this->logWebMapDownload($datasetCode, 'raster', $this->filtersFromInput($input), null, $sizeBytes ?: null);
            return $this->response->download($archive['path'], null)->setFileName($archive['filename']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function downloadRasterGrid(int $rid)
    {
        $datasetCode = (string) ($this->request->getGet('dataset') ?? 'faa_l2');

        // Quota check
        $userId = (int) (session()->get('user_id') ?? 0);
        $role   = auth_current_role();
        if (!in_array($role, ['admin', 'superadmin'], true)) {
            $quota = $this->marketplace->checkQuota($userId);
            if (!$quota['allowed']) {
                return $this->response->setStatusCode(403)->setJSON([
                    'error' => $quota['reason'] === 'no_subscription'
                        ? 'Diperlukan langganan aktif untuk mengunduh data.'
                        : 'Kuota unduhan mingguan Anda telah habis.',
                ]);
            }
        }

        if ($this->enterpriseGateFail($datasetCode)) {
            return $this->response->setStatusCode(403)->setJSON([
                'error' => 'Dataset Level 2 (raster GeoTIFF) memerlukan paket berbayar (Lite, Pro, atau Team).',
                'upgrade_url' => site_url('signup'),
            ]);
        }

        try {
            $dataset = $this->dataset($datasetCode);
            $binary = $this->rasterBinaryByGrid($datasetCode, $rid);

            if ($binary === null) {
                return $this->response->setStatusCode(404)->setBody('Raster grid tidak ditemukan.');
            }

            $archive = $this->buildDatasetPackage(
                $datasetCode . '_grid_' . $rid . '_' . date('Ymd_His'),
                $datasetCode,
                [
                    'province_id' => null,
                    'geometry' => null,
                    'buffer_meters' => 0,
                    'bounds' => null,
                    'zoom' => null,
                    'force_detail' => true,
                ],
                [
                    ['name' => $datasetCode . '_grid_' . $rid . '.tif', 'contents' => $binary],
                    ['name' => 'selection.json', 'contents' => json_encode([
                        'dataset' => $dataset['label'],
                        'grid_id' => $rid,
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ]
            );

            $sizeBytes = is_file($archive['path']) ? filesize($archive['path']) : null;
            $this->logWebMapDownload($datasetCode, 'raster', ['grid_id' => $rid], null, $sizeBytes ?: null);
            return $this->response->download($archive['path'], null)->setFileName($archive['filename']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setBody($e->getMessage());
        }
    }

    public function downloadRasterProvince(int $provinceId)
    {
        $datasetCode = (string) ($this->request->getGet('dataset') ?? 'faa_l2');

        // Quota check
        $userId = (int) (session()->get('user_id') ?? 0);
        $role   = auth_current_role();
        if (!in_array($role, ['admin', 'superadmin'], true)) {
            $quota = $this->marketplace->checkQuota($userId);
            if (!$quota['allowed']) {
                return $this->response->setStatusCode(403)->setJSON([
                    'error' => $quota['reason'] === 'no_subscription'
                        ? 'Diperlukan langganan aktif untuk mengunduh data.'
                        : 'Kuota unduhan mingguan Anda telah habis.',
                ]);
            }
        }

        if ($this->enterpriseGateFail($datasetCode)) {
            return $this->response->setStatusCode(403)->setJSON([
                'error' => 'Dataset Level 2 (raster GeoTIFF) memerlukan paket berbayar (Lite, Pro, atau Team).',
                'upgrade_url' => site_url('signup'),
            ]);
        }

        try {
            $dataset = $this->dataset($datasetCode);
            $binary = $this->rasterBinaryFromFilters($datasetCode, [
                'province_id' => $provinceId,
                'geometry' => null,
                'buffer_meters' => 0,
                'bounds' => null,
                'zoom' => null,
                'force_detail' => true,
            ]);

            if ($binary === null) {
                return $this->response->setStatusCode(404)->setBody('Raster tidak ditemukan pada provinsi tersebut.');
            }

            $archive = $this->buildDatasetPackage(
                $datasetCode . '_province_' . $provinceId . '_' . date('Ymd_His'),
                $datasetCode,
                [
                    'province_id' => $provinceId,
                    'geometry' => null,
                    'buffer_meters' => 0,
                    'bounds' => null,
                    'zoom' => null,
                    'force_detail' => true,
                ],
                [
                    ['name' => $datasetCode . '_province_' . $provinceId . '.tif', 'contents' => $binary],
                    ['name' => 'selection.json', 'contents' => json_encode([
                        'dataset' => $dataset['label'],
                        'province_id' => $provinceId,
                        'province_name' => $this->provinceName($provinceId),
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ]
            );

            $this->logWebMapDownload($datasetCode, 'raster', ['province_id' => $provinceId]);
            return $this->response->download($archive['path'], null)->setFileName($archive['filename']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setBody($e->getMessage());
        }
    }

    public function downloadRasterByAOI(int $aoiId)
    {
        return $this->downloadRasterProvince($aoiId);
    }

    public function downloadMetadata()
    {
        try {
            $input = $this->inputPayload();
            $datasetCode = (string) ($input['dataset'] ?? 'faa_l1');
            $dataset = $this->dataset($datasetCode);
            $filters = $this->filtersFromInput($input);
            $metadataDataset = array_merge($dataset, [
                'dataset_code' => $datasetCode,
                'title' => $dataset['label'],
                'spatial_scope' => $this->metadataScope($filters),
            ]);

            if (!empty($filters['province_id'])) {
                $metadataDataset['province_id'] = (int) $filters['province_id'];
                $metadataDataset['province_name'] = $this->provinceName((int) $filters['province_id']);
                $filters['province_name'] = $metadataDataset['province_name'];
            } elseif ($filters['geometry'] !== null) {
                $filters['geometry_type'] = $this->geometryType($filters['geometry']);
            }

            $export = $this->metadataExporter->export($metadataDataset, $filters);

            return $this->response->download($export['path'], null)->setFileName($export['filename']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function clientDataset(array $dataset): array
    {
        return [
            'code' => $dataset['code'],
            'label' => $dataset['label'],
            'type' => $dataset['type'],
            'organization' => $dataset['organization'],
            'availability' => $dataset['availability'],
            'note' => $dataset['note'],
            'metadataLevel' => $dataset['metadata_level'],
        ];
    }

    private function dataset(string $code): array
    {
        return $this->registry->dataset($code);
    }

    private function inputPayload(): array
    {
        $json = $this->request->getJSON(true);
        $raw = $this->request->getRawInput();

        return array_merge(
            is_array($this->request->getGet()) ? $this->request->getGet() : [],
            is_array($raw) ? $raw : [],
            is_array($json) ? $json : []
        );
    }

    private function filtersFromInput(array $input): array
    {
        return [
            'province_id' => isset($input['province_id']) && $input['province_id'] !== ''
                ? (int) $input['province_id']
                : null,
            'geometry' => $input['geometry'] ?? null,
            'buffer_meters' => isset($input['buffer_meters'])
                ? max(0, (int) $input['buffer_meters'])
                : 0,
            'bounds' => $this->normalizeBounds($input['bounds'] ?? null),
            'zoom' => isset($input['zoom']) && $input['zoom'] !== ''
                ? (int) $input['zoom']
                : null,
            'force_detail' => filter_var($input['force_detail'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    private function normalizeBounds($bounds): ?array
    {
        if ($bounds === null || $bounds === '') {
            return null;
        }

        if (is_string($bounds)) {
            $decoded = json_decode($bounds, true);
            if (is_array($decoded)) {
                $bounds = $decoded;
            } else {
                $parts = array_map('trim', explode(',', $bounds));
                if (count($parts) === 4) {
                    $bounds = [
                        'west' => $parts[0],
                        'south' => $parts[1],
                        'east' => $parts[2],
                        'north' => $parts[3],
                    ];
                } else {
                    throw new InvalidArgumentException('Format bounds tidak valid.');
                }
            }
        }

        if (!is_array($bounds)) {
            throw new InvalidArgumentException('Format bounds tidak valid.');
        }

        $required = ['west', 'south', 'east', 'north'];
        foreach ($required as $key) {
            if (!isset($bounds[$key]) || !is_numeric($bounds[$key])) {
                throw new InvalidArgumentException('Bounds harus berisi west, south, east, dan north numerik.');
            }
        }

        $normalized = [
            'west' => (float) $bounds['west'],
            'south' => (float) $bounds['south'],
            'east' => (float) $bounds['east'],
            'north' => (float) $bounds['north'],
        ];

        if (
            $normalized['west'] < -180
            || $normalized['west'] > 180
            || $normalized['east'] < -180
            || $normalized['east'] > 180
            || $normalized['south'] < -90
            || $normalized['south'] > 90
            || $normalized['north'] < -90
            || $normalized['north'] > 90
        ) {
            throw new InvalidArgumentException('Bounds berada di luar rentang koordinat geografis yang valid.');
        }

        if (
            $normalized['west'] >= $normalized['east']
            || $normalized['south'] >= $normalized['north']
        ) {
            throw new InvalidArgumentException('Bounds harus memiliki west < east dan south < north.');
        }

        return $normalized;
    }

    private function provinceCollection(): array
    {
        $db = Database::connect();

        $rows = $db->query('
            SELECT
                adm_id,
                adm_name,
                adm_code,
                ST_AsGeoJSON(ST_Simplify(geom, 0.01)) AS geojson
            FROM geoportal.polygon_adm_province
            ORDER BY adm_name ASC
        ')->getResultArray();

        $features = [];

        foreach ($rows as $row) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geojson'], true),
                'properties' => [
                    'feature_id' => (string) $row['adm_id'],
                    'title' => (string) $row['adm_name'],
                    'gid_1' => (string) $row['adm_code'],
                    'country' => 'Indonesia',
                ],
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    private function vectorLayer(string $datasetCode, array $filters): array
    {
        if ($this->shouldAggregateVector($filters) && $this->vectorSourceCount($datasetCode, $filters) > 500) {
            return $this->vectorAggregateLayer($datasetCode, $filters);
        }

        return $this->vectorDetailLayer($datasetCode, $filters);
    }

    private function vectorDetailLayer(string $datasetCode, array $filters): array
    {
        $dataset = $this->dataset($datasetCode);
        $db = Database::connect($dataset['db']);
        [$sqlBoundary, $params] = $this->spatialSql('t.' . $dataset['geom_column'], $filters);
        $hasProvince = !empty($filters['province_id']);
        if ($hasProvince) {
            $this->cancelPreviousProvinceQuery($db);
        }
        $timeout = $hasProvince ? '12000' : '15000';
        $db->query("BEGIN");
        $db->query("SET LOCAL statement_timeout = '{$timeout}'");
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
                ' . $sqlBoundary . '
                ORDER BY t.id ASC
                LIMIT 5000
            ', $params)->getResultArray();
            $db->query("COMMIT");
        } catch (\Throwable $e) {
            try { $db->query("ROLLBACK"); } catch (\Throwable) {}
            throw $e;
        }

        $features = [];

        foreach ($rows as $row) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geojson'], true),
                'properties' => [
                    'feature_id' => (string) $row['id'],
                    'title' => $datasetCode === 'faa_l1' ? 'FAA Point #' . $row['id'] : 'CBA Point #' . $row['id'],
                    'summary_label' => $datasetCode === 'faa_l1' ? 'Anomali' : 'Nilai CBA',
                    'summary_value' => (float) $row['anomaly_value'],
                    'summary_unit' => $dataset['summary_unit'],
                    'latitude' => (float) $row['latitude'],
                    'longitude' => (float) $row['longitude'],
                    'elevation_m' => (float) $row['elevation_m'],
                    'source_file' => (string) $row['source_file'],
                    'survey_mode' => (string) $row['survey_mode'],
                    'dataset_code' => $datasetCode,
                    'is_aggregate' => false,
                ],
            ];
        }

        return [
            'dataset' => $this->clientDataset($dataset),
            'meta' => [
                'mode' => 'points',
                'zoom' => (int) ($filters['zoom'] ?? 0),
                'feature_count' => count($features),
            ],
            'collection' => [
                'type' => 'FeatureCollection',
                'features' => $features,
            ],
        ];
    }

    private function vectorAggregateLayer(string $datasetCode, array $filters): array
    {
        $dataset  = $this->dataset($datasetCode);
        $db       = Database::connect($dataset['db']);
        [$sqlBoundary, $params] = $this->spatialSql('t.' . $dataset['geom_column'], $filters);
        $gridSize = $this->aggregateGridSize($filters);

        // TABLESAMPLE keeps memory flat. Without bounds AND without explicit spatial
        // filter (province/geometry), use aggressive 3% for global overview.
        // When province or custom geometry is active, treat it as bounded — sampling
        // 3% of the entire table would miss spatially-clustered province data.
        $zoom               = (int) ($filters['zoom'] ?? 0);
        $hasBounds          = is_array($filters['bounds'] ?? null);
        $hasProvince        = !empty($filters['province_id']);
        $hasExplicitSpatial = $hasProvince || !empty($filters['geometry']);
        // Province queries never use TABLESAMPLE — data is spatially clustered per province
        // so block-level sampling would skip the province entirely.
        $sample = match (true) {
            $hasProvince                        => '',
            !$hasBounds && !$hasExplicitSpatial => ' TABLESAMPLE SYSTEM(3)',
            $zoom <= 5                          => ' TABLESAMPLE SYSTEM(5)',
            $zoom <= 6                          => ' TABLESAMPLE SYSTEM(10)',
            $zoom <= 7                          => ' TABLESAMPLE SYSTEM(30)',
            $zoom <= 8                          => ' TABLESAMPLE SYSTEM(60)',
            default                             => '',
        };
        // Province queries cap the scan at 150k rows to prevent memory exhaustion.
        // Aggregation over 150k points still produces a representative visualization.
        $srcLimit = $hasProvince ? "\n                    LIMIT 150000" : '';

        if ($hasProvince) {
            $this->cancelPreviousProvinceQuery($db);
        }
        $timeout = $hasProvince ? '12000' : '15000';
        $db->query("BEGIN");
        $db->query("SET LOCAL statement_timeout = '{$timeout}'");
        try {
            $rows = $db->query('
                WITH src AS (
                    SELECT
                        t.anomaly_value,
                        t.geom
                    FROM ' . $dataset['table'] . ' t' . $sample . '
                    WHERE 1=1
                    ' . $sqlBoundary . $srcLimit . '
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
            $db->query("COMMIT");
        } catch (\Throwable $e) {
            try { $db->query("ROLLBACK"); } catch (\Throwable) {}
            throw $e;
        }

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
                    'aggregate_cell_size_deg' => $gridSize,
                    'dataset_code' => $datasetCode,
                    'is_aggregate' => true,
                ],
            ];
        }

        return [
            'dataset' => $this->clientDataset($dataset),
            'meta' => [
                'mode' => 'aggregated_points',
                'zoom' => (int) ($filters['zoom'] ?? 0),
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

    private function rasterGridLayer(string $datasetCode, array $filters): array
    {
        $dataset = $this->dataset($datasetCode);
        $db = Database::connect($dataset['db']);
        [$sqlBoundary, $params] = $this->spatialSql('geom', $filters);

        $sql = '
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
                    ROUND(((stats).min)::numeric, 3) AS min_val,
                    ROUND(((stats).max)::numeric, 3) AS max_val,
                    ROUND(((stats).mean)::numeric, 3) AS mean_val
                FROM src
            )
            SELECT
                rid,
                width_px,
                height_px,
                grid_width_deg,
                grid_height_deg,
                min_val,
                max_val,
                mean_val,
                ST_AsGeoJSON(geom) AS geojson
            FROM tiles
            WHERE 1=1
            ' . $sqlBoundary . '
            ORDER BY rid ASC
        ';

        $rows = $db->query($sql, $params)->getResultArray();
        $features = [];

        foreach ($rows as $row) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geojson'], true),
                'properties' => [
                    'feature_id' => (string) $row['rid'],
                    'title' => 'Grid #' . $row['rid'],
                    'summary_label' => 'Mean',
                    'summary_value' => (float) $row['mean_val'],
                    'summary_unit' => $dataset['summary_unit'],
                    'min_val' => (float) $row['min_val'],
                    'max_val' => (float) $row['max_val'],
                    'width_px' => (int) $row['width_px'],
                    'height_px' => (int) $row['height_px'],
                    'grid_width_deg' => (float) $row['grid_width_deg'],
                    'grid_height_deg' => (float) $row['grid_height_deg'],
                    'dataset_code' => $datasetCode,
                    'is_aggregate' => false,
                ],
            ];
        }

        return [
            'dataset' => $this->clientDataset($dataset),
            'meta' => [
                'mode' => 'grid',
            ],
            'collection' => [
                'type' => 'FeatureCollection',
                'features' => $features,
            ],
        ];
    }

    private function vectorFeatureMeta(string $datasetCode, string $featureId): array
    {
        $dataset = $this->dataset($datasetCode);
        $db = Database::connect($dataset['db']);

        $row = $db->query('
            SELECT
                id,
                ROUND((latitude)::numeric, 6) AS latitude,
                ROUND((longitude)::numeric, 6) AS longitude,
                ROUND((orthometric_height)::numeric, 3) AS elevation_m,
                ROUND((anomaly_value)::numeric, 3) AS anomaly_value,
                source_file,
                survey_mode
            FROM ' . $dataset['table'] . '
            WHERE id = ?
            LIMIT 1
        ', [$featureId])->getRowArray();

        if (!$row) {
            throw new InvalidArgumentException('Fitur vektor tidak ditemukan.');
        }

        $summaryLabel = $datasetCode === 'faa_l1' ? 'Anomali' : 'Nilai CBA';

        return [
            'dataset' => $this->clientDataset($dataset),
            'feature' => [
                'id' => (string) $row['id'],
                'title' => ($datasetCode === 'faa_l1' ? 'FAA Point #' : 'CBA Point #') . $row['id'],
                'summary' => [
                    ['label' => $summaryLabel, 'value' => $row['anomaly_value'] . ' ' . $dataset['summary_unit']],
                    ['label' => 'Elevasi', 'value' => $row['elevation_m'] . ' m'],
                ],
                'details' => [
                    ['label' => 'Latitude', 'value' => $row['latitude']],
                    ['label' => 'Longitude', 'value' => $row['longitude']],
                    ['label' => 'Source File', 'value' => $row['source_file']],
                    ['label' => 'Mode', 'value' => $row['survey_mode']],
                ],
                'note' => $dataset['note'],
            ],
        ];
    }

    private function rasterFeatureMeta(string $datasetCode, string $featureId): array
    {
        $dataset = $this->dataset($datasetCode);
        $db = Database::connect($dataset['db']);

        $row = $db->query('
            SELECT
                rid,
                ST_Width(rast) AS width_px,
                ST_Height(rast) AS height_px,
                ROUND((ST_XMax(geom) - ST_XMin(geom))::numeric, 6) AS grid_width_deg,
                ROUND((ST_YMax(geom) - ST_YMin(geom))::numeric, 6) AS grid_height_deg,
                ROUND(ST_XMin(geom)::numeric, 6) AS xmin,
                ROUND(ST_YMin(geom)::numeric, 6) AS ymin,
                ROUND(ST_XMax(geom)::numeric, 6) AS xmax,
                ROUND(ST_YMax(geom)::numeric, 6) AS ymax,
                ROUND(((stats).min)::numeric, 3) AS min_val,
                ROUND(((stats).max)::numeric, 3) AS max_val,
                ROUND(((stats).mean)::numeric, 3) AS mean_val
            FROM (
                SELECT
                    rid,
                    rast,
                    COALESCE(grid_geom, ST_Envelope(rast)::geometry(Polygon, 4326)) AS geom,
                    ST_SummaryStats(rast, 1, true) AS stats
                FROM ' . $dataset['table'] . '
                WHERE rid = ?
            ) src
            LIMIT 1
        ', [$featureId])->getRowArray();

        if (!$row) {
            throw new InvalidArgumentException('Grid raster tidak ditemukan.');
        }

        return [
            'dataset' => $this->clientDataset($dataset),
            'feature' => [
                'id' => (string) $row['rid'],
                'title' => 'Grid #' . $row['rid'],
                'summary' => [
                    ['label' => 'Mean', 'value' => $row['mean_val'] . ' ' . $dataset['summary_unit']],
                    ['label' => 'Min', 'value' => $row['min_val'] . ' ' . $dataset['summary_unit']],
                    ['label' => 'Max', 'value' => $row['max_val'] . ' ' . $dataset['summary_unit']],
                ],
                'details' => [
                    ['label' => 'Width (px)', 'value' => $row['width_px']],
                    ['label' => 'Height (px)', 'value' => $row['height_px']],
                    ['label' => 'Grid Size (deg)', 'value' => $row['grid_width_deg'] . ' x ' . $row['grid_height_deg']],
                    ['label' => 'Extent', 'value' => $row['xmin'] . ', ' . $row['ymin'] . ' to ' . $row['xmax'] . ', ' . $row['ymax']],
                    ['label' => 'Dataset', 'value' => $dataset['label']],
                ],
                'note' => $dataset['note'],
            ],
        ];
    }

    private function shouldAggregateVector(array $filters): bool
    {
        if (!empty($filters['force_detail'])) {
            return false;
        }

        $zoom = (int) ($filters['zoom'] ?? 0);
        $bounds = $filters['bounds'] ?? null;

        if ($zoom <= 7) {
            return true;
        }

        if (!is_array($bounds)) {
            return true;
        }

        $span = max(
            abs((float) $bounds['east'] - (float) $bounds['west']),
            abs((float) $bounds['north'] - (float) $bounds['south'])
        );

        return $zoom <= 9 && $span >= 1.2;
    }

    private function aggregateGridSize(array $filters): float
    {
        // No bounds = global overview, always use coarse 1° cells
        if (!is_array($filters['bounds'] ?? null)) {
            return 1.0;
        }

        $zoom = (int) ($filters['zoom'] ?? 0);
        return match (true) {
            $zoom <= 5  => 1.0,
            $zoom <= 6  => 0.5,
            $zoom <= 7  => 0.25,
            $zoom <= 8  => 0.125,
            default     => 0.0625,
        };
    }

    private function vectorSourceCount(string $datasetCode, array $filters): int
    {
        $dataset = $this->dataset($datasetCode);
        $db = Database::connect($dataset['db']);
        [$sqlBoundary, $params] = $this->spatialSql('t.' . $dataset['geom_column'], $filters);
        try {
            $db->query("BEGIN");
            $db->query("SET LOCAL statement_timeout = '8000'");
            // Use LIMIT 501 so the query stops as soon as we know there are > 500 rows,
            // instead of scanning the entire table for an exact count.
            $row = $db->query('
                SELECT COUNT(*) AS total
                FROM (
                    SELECT 1 FROM ' . $dataset['table'] . ' t
                    WHERE 1=1
                    ' . $sqlBoundary . '
                    LIMIT 501
                ) sub
            ', $params)->getRowArray();
            $db->query("COMMIT");
            return (int) ($row['total'] ?? 0);
        } catch (\Throwable) {
            try { $db->query("ROLLBACK"); } catch (\Throwable) {}
            return 9999;
        }
    }

    private function exportFilteredVectorCsvFile(string $datasetCode, array $filters): array
    {
        $dataset  = $this->dataset($datasetCode);
        $db       = Database::connect($dataset['db']);

        // Ensure we have the raw pgsql connection handle for row-by-row streaming.
        if (!$db->connID) {
            $db->initialize();
        }
        $conn = $db->connID;
        if (!$conn) {
            throw new \RuntimeException('Koneksi PostgreSQL tidak tersedia untuk streaming export.');
        }

        $directory = $this->exportDirectory('data');
        $filename  = $datasetCode . '_filtered_' . date('Ymd_His') . '.csv';
        $path      = $directory . DIRECTORY_SEPARATOR . $filename;
        $handle    = fopen($path, 'wb');

        if ($handle === false) {
            throw new \RuntimeException('File export CSV tidak dapat dibuat.');
        }

        [$sqlBoundary, $params] = $this->spatialSql('t.' . $dataset['geom_column'], array_merge($filters, [
            'force_detail' => true,
        ]));

        // Convert CI-style ? placeholders to PostgreSQL $N positional params.
        $pgSql = $this->toPositionalParams('
            SELECT
                t.id,
                t.latitude,
                t.longitude,
                t.orthometric_height,
                t.anomaly_value,
                t.source_file,
                t.survey_mode
            FROM ' . $dataset['table'] . ' t
            WHERE 1=1
            ' . $sqlBoundary . '
            ORDER BY t.id ASC
        ');

        set_time_limit(0);

        // Stream rows directly from PostgreSQL — zero per-row PHP memory overhead.
        $result = $params
            ? pg_query_params($conn, $pgSql, $params)
            : pg_query($conn, $pgSql);

        if ($result === false) {
            fclose($handle);
            throw new \RuntimeException(pg_last_error($conn) ?: 'Query streaming CSV gagal.');
        }

        fputcsv($handle, ['id', 'latitude', 'longitude', 'orthometric_height', 'anomaly_value', 'source_file', 'survey_mode']);
        $rowCount = 0;

        while (($row = pg_fetch_assoc($result)) !== false) {
            fputcsv($handle, [
                $row['id'] ?? '',
                $row['latitude'] ?? '',
                $row['longitude'] ?? '',
                $row['orthometric_height'] ?? '',
                $row['anomaly_value'] ?? '',
                $row['source_file'] ?? '',
                $row['survey_mode'] ?? '',
            ]);
            $rowCount++;
        }

        pg_free_result($result);
        fclose($handle);

        return [
            'path'      => $path,
            'filename'  => $filename,
            'row_count' => $rowCount,
        ];
    }

    /** Replace ? placeholders with PostgreSQL positional params ($1, $2, …). */
    private function toPositionalParams(string $sql): string
    {
        $i = 0;
        return preg_replace_callback('/\?/', static function () use (&$i): string {
            return '$' . ++$i;
        }, $sql);
    }

    private function buildDatasetPackage(string $baseName, string $datasetCode, array $filters, array $files): array
    {
        $dataset = $this->dataset($datasetCode);
        $metadataDataset = array_merge($dataset, [
            'dataset_code' => $datasetCode,
            'title' => $dataset['label'],
            'spatial_scope' => $this->metadataScope($filters),
        ]);

        if (!empty($filters['province_id'])) {
            $metadataDataset['province_id'] = (int) $filters['province_id'];
            $metadataDataset['province_name'] = $this->provinceName((int) $filters['province_id']);
            $filters['province_name'] = $metadataDataset['province_name'];
        } elseif (($filters['geometry'] ?? null) !== null) {
            $filters['geometry_type'] = $this->geometryType($filters['geometry']);
        }

        $metadataExport = $this->metadataExporter->export($metadataDataset, $filters);
        $directory = $this->exportDirectory('packages');
        $filename = $baseName . '.zip';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        if (is_file($path) && !unlink($path)) {
            throw new \RuntimeException('Package unduhan lama tidak dapat diganti.');
        }

        $archive = new \PharData($path);

        foreach ($files as $file) {
            if (!empty($file['path'])) {
                $archive->addFile($file['path'], $file['name']);
                continue;
            }

            $archive->addFromString($file['name'], $file['contents'] ?? '');
        }

        $archive->addFile($metadataExport['path'], 'metadata.xml');
        $archive->addFromString(
            'filters.json',
            json_encode($this->packageFiltersManifest($datasetCode, $filters), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                ?: '{}'
        );

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    private function packageFiltersManifest(string $datasetCode, array $filters): array
    {
        return [
            'dataset' => $datasetCode,
            'generated_at' => date(DATE_ATOM),
            'filters' => [
                'province_id' => $filters['province_id'] ?? null,
                'province_name' => $filters['province_name'] ?? null,
                'geometry_type' => $filters['geometry_type'] ?? $this->geometryType($filters['geometry'] ?? null),
                'bounds' => $filters['bounds'] ?? null,
                'buffer_meters' => $filters['buffer_meters'] ?? 0,
                'zoom' => $filters['zoom'] ?? null,
            ],
        ];
    }

    private function exportDirectory(string $folder): string
    {
        $directory = WRITEPATH . 'exports' . DIRECTORY_SEPARATOR . $folder;

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException('Folder export tidak dapat dibuat.');
        }

        return $directory;
    }

    private function rasterBinaryByGrid(string $datasetCode, int $rid): ?string
    {
        $dataset = $this->dataset($datasetCode);

        if ($dataset['type'] !== 'raster') {
            throw new InvalidArgumentException('Dataset ini bukan raster.');
        }

        $db = Database::connect($dataset['db']);
        $row = $db->query('
            SELECT ST_AsTIFF(' . $dataset['raster_column'] . ') AS tif
            FROM ' . $dataset['table'] . '
            WHERE ' . $dataset['id_column'] . ' = ?
            LIMIT 1
        ', [$rid])->getRowArray();

        if (!$row || empty($row['tif'])) {
            return null;
        }

        return $this->decodeBytea((string) $row['tif']);
    }

    private function rasterBinaryFromFilters(string $datasetCode, array $filters): ?string
    {
        $dataset = $this->dataset($datasetCode);

        if ($dataset['type'] !== 'raster') {
            throw new InvalidArgumentException('Dataset ini bukan raster.');
        }

        $boundary = $this->boundaryPayload($filters);

        if ($boundary === null) {
            throw new InvalidArgumentException('Untuk clip raster, pilih area aktif atau gunakan viewport peta.');
        }

        [$boundaryExpr, $boundaryParams] = $this->boundaryExpression($boundary, $filters);
        $db = Database::connect($dataset['db']);

        $sql = '
            WITH boundary AS (
                SELECT ' . $boundaryExpr . ' AS geom
            ),
            clipped AS (
                SELECT ST_Clip(r.' . $dataset['raster_column'] . ', b.geom, true) AS rast
                FROM ' . $dataset['table'] . ' r
                CROSS JOIN boundary b
                WHERE ST_Intersects(COALESCE(r.grid_geom, ST_Envelope(r.' . $dataset['raster_column'] . ')::geometry(Polygon, 4326)), b.geom)
            )
            SELECT ST_AsTIFF(ST_Union(rast)) AS tif
            FROM clipped
        ';

        try {
            $result = $db->query($sql, $boundaryParams);
        } catch (\Throwable $e) {
            log_message('error', '[WebMap::rasterBinaryFromFilters] Query gagal: ' . $e->getMessage());
            throw new \RuntimeException('Proses clip raster gagal. Coba persempit area aktif atau hubungi admin.');
        }

        if ($result === false) {
            throw new \RuntimeException('Query raster gagal — tidak ada hasil.');
        }

        $row = $result->getRowArray();

        if (!$row || empty($row['tif'])) {
            return null;
        }

        return $this->decodeBytea((string) $row['tif']);
    }

    private function boundaryPayload(array $filters): ?array
    {
        if (!empty($filters['province_id'])) {
            $db = Database::connect();
            $row = $db->query('
                SELECT
                    ST_AsGeoJSON(geom) AS geojson,
                    ST_GeometryType(geom) AS geom_type
                FROM geoportal.polygon_adm_province
                WHERE adm_id = ?
                LIMIT 1
            ', [$filters['province_id']])->getRowArray();

            if (!$row) {
                throw new InvalidArgumentException('Provinsi AOI tidak ditemukan.');
            }

            return [
                'geojson' => $row['geojson'],
                'geometry_type' => str_replace('ST_', '', (string) $row['geom_type']),
                'source' => 'province',
            ];
        }

        if (!empty($filters['geometry'])) {
            $geometry = is_array($filters['geometry'])
                ? $filters['geometry']
                : json_decode((string) $filters['geometry'], true);

            if (!is_array($geometry)) {
                throw new InvalidArgumentException('Boundary GeoJSON tidak valid.');
            }

            if (($geometry['type'] ?? '') === 'Feature') {
                $geometry = $geometry['geometry'] ?? [];
            }

            if (($geometry['type'] ?? '') === 'FeatureCollection') {
                $geometry = [
                    'type' => 'GeometryCollection',
                    'geometries' => array_values(array_filter(array_map(
                        fn ($feature) => $feature['geometry'] ?? null,
                        $geometry['features'] ?? []
                    ))),
                ];
            }

            if (empty($geometry['type'])) {
                throw new InvalidArgumentException('Geometri boundary tidak valid.');
            }

            return [
                'geojson' => json_encode($geometry, JSON_UNESCAPED_UNICODE),
                'geometry_type' => (string) $geometry['type'],
                'source' => 'geometry',
            ];
        }

        if (!empty($filters['bounds'])) {
            $bounds = $filters['bounds'];
            $geometry = [
                'type' => 'Polygon',
                'coordinates' => [[
                    [(float) $bounds['west'], (float) $bounds['south']],
                    [(float) $bounds['east'], (float) $bounds['south']],
                    [(float) $bounds['east'], (float) $bounds['north']],
                    [(float) $bounds['west'], (float) $bounds['north']],
                    [(float) $bounds['west'], (float) $bounds['south']],
                ]],
            ];

            return [
                'geojson' => json_encode($geometry, JSON_UNESCAPED_UNICODE),
                'geometry_type' => 'Polygon',
                'source' => 'bounds',
            ];
        }

        return null;
    }

    private function spatialSql(string $geomColumn, array $filters): array
    {
        $clauses = [];
        $params = [];
        $buffer = max(0, (int) ($filters['buffer_meters'] ?? 0));

        // Province: direct subquery avoids fetching + re-parsing large GeoJSON in PHP.
        // PostGIS can use the GiST index on both tables for an efficient nested-loop join.
        // Viewport bounds are intentionally NOT added here — the province polygon IS the
        // spatial extent; mixing bounds would exclude provinces outside the current viewport.
        if (!empty($filters['province_id'])) {
            $clauses[] = 'ST_Intersects(' . $geomColumn . ', (SELECT geom FROM geoportal.polygon_adm_province WHERE adm_id = ? LIMIT 1))';
            $params[] = (int) $filters['province_id'];
        } else {
            $boundary = $this->boundaryPayload($filters);
            if ($boundary !== null) {
                if ($boundary['geometry_type'] === 'Point' && $buffer > 0) {
                    $clauses[] = 'ST_DWithin(' . $geomColumn . '::geography, ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography, ?)';
                    $params[] = $boundary['geojson'];
                    $params[] = $buffer;
                } elseif ($buffer > 0 && in_array($boundary['geometry_type'], ['Polygon', 'MultiPolygon', 'LineString', 'MultiLineString'], true)) {
                    $clauses[] = 'ST_Intersects(' . $geomColumn . ', ST_Transform(ST_Buffer(ST_Transform(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326), 3857), ?), 4326))';
                    $params[] = $boundary['geojson'];
                    $params[] = $buffer;
                } else {
                    $clauses[] = 'ST_Intersects(' . $geomColumn . ', ST_SetSRID(ST_GeomFromGeoJSON(?), 4326))';
                    $params[] = $boundary['geojson'];
                }
            }

            if (!empty($filters['bounds']) && (($boundary['source'] ?? null) !== 'bounds')) {
                $clauses[] = 'ST_Intersects(' . $geomColumn . ', ST_MakeEnvelope(?, ?, ?, ?, 4326))';
                $params[] = $filters['bounds']['west'];
                $params[] = $filters['bounds']['south'];
                $params[] = $filters['bounds']['east'];
                $params[] = $filters['bounds']['north'];
            }
        }

        if ($clauses === []) {
            return ['', []];
        }

        return [' AND ' . implode(' AND ', $clauses) . ' ', $params];
    }

    private function boundaryExpression(array $boundary, array $filters): array
    {
        $buffer = max(0, (int) ($filters['buffer_meters'] ?? 0));

        if ($boundary['geometry_type'] === 'Point' && $buffer > 0) {
            return [
                'ST_Transform(ST_Buffer(ST_Transform(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326), 3857), ?), 4326)',
                [$boundary['geojson'], $buffer],
            ];
        }

        return [
            'ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)',
            [$boundary['geojson']],
        ];
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

    private function provinceName(int $provinceId): ?string
    {
        foreach ($this->registry->provinces() as $province) {
            if ((int) $province['id'] === $provinceId) {
                return (string) $province['name'];
            }
        }

        return null;
    }

    private function geometryType($geometry): ?string
    {
        if (is_string($geometry)) {
            $geometry = json_decode($geometry, true);
        }

        if (!is_array($geometry)) {
            return null;
        }

        if (($geometry['type'] ?? '') === 'Feature') {
            return $geometry['geometry']['type'] ?? null;
        }

        return $geometry['type'] ?? null;
    }

    private function metadataScope(array $filters): string
    {
        if (!empty($filters['province_id'])) {
            return 'regional';
        }

        if (!empty($filters['geometry'])) {
            return 'custom';
        }

        if (!empty($filters['bounds'])) {
            return 'viewport';
        }

        return 'national';
    }

    /**
     * Tag this PostgreSQL session as an active province query so the NEXT province
     * request can find and cancel it via pg_stat_activity. Then cancel any OTHER
     * province query that is currently active.
     *
     * Uses application_name instead of a PID file — avoids PID-recycling bugs where
     * a fast province (e.g. DKI Jakarta) finishes, its PID is reused by the next
     * connection, and pg_cancel_backend ends up canceling the new request itself.
     */
    private function cancelPreviousProvinceQuery(object $db): void
    {
        $row = $db->query('SELECT pg_backend_pid() AS pid')->getRowArray();
        $myPid = (int) ($row['pid'] ?? 0);
        if ($myPid <= 0) {
            return;
        }
        // Tag this session with a unique marker (province prefix + our PID).
        $db->query("SET application_name = 'gp_prov_" . $myPid . "'");
        // Cancel any OTHER active province backend (same prefix, different PID).
        $db->query("
            SELECT pg_cancel_backend(pid)
            FROM pg_stat_activity
            WHERE application_name LIKE 'gp_prov_%'
              AND state = 'active'
              AND pid != " . $myPid . "
        ");
    }

    /** Returns true when the dataset is Level 2 and the user lacks Enterprise access. */
    private function enterpriseGateFail(string $datasetCode): bool
    {
        if (!str_ends_with($datasetCode, '_l2')) {
            return false;
        }
        $role = auth_current_role();
        if (in_array($role, ['admin', 'superadmin'], true)) {
            return false;
        }
        $userId = (int) (session()->get('user_id') ?? 0);
        $tier = $this->marketplace->userTier($userId);
        // All paid tiers (lite, pro, team, enterprise, government) can access Level 2
        return in_array($tier, ['none', 'free'], true);
    }

    private function logWebMapDownload(string $datasetCode, string $datasetType, array $filterParams, ?int $rowCount = null, ?int $sizeBytes = null): void
    {
        try {
            $userId = (int) (session()->get('user_id') ?? 0);
            $this->marketplace->logDownload([
                'user_id'             => $userId ?: null,
                'dataset_code'        => $datasetCode,
                'dataset_type'        => $datasetType,
                'filter_params'       => $filterParams,
                'row_count'           => $rowCount,
                'download_size_bytes' => $sizeBytes,
                'user_agent'          => $this->request->getUserAgent()->getAgentString(),
            ]);
        } catch (\Throwable) {}
    }
}
