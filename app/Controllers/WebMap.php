<?php

namespace App\Controllers;

use App\Libraries\FilteredMetadataExporter;
use App\Libraries\GeoportalDatasetRegistry;
use CodeIgniter\API\ResponseTrait;
use Config\Database;
use InvalidArgumentException;

class WebMap extends BaseController
{
    use ResponseTrait;

    private GeoportalDatasetRegistry $registry;
    private FilteredMetadataExporter $metadataExporter;

    public function __construct()
    {
        $this->registry = new GeoportalDatasetRegistry();
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
            $input = $this->inputPayload();
            $datasetCode = (string) ($input['dataset'] ?? 'faa_l1');
            $dataset = $this->dataset($datasetCode);

            if ($dataset['type'] !== 'vector') {
                throw new InvalidArgumentException('Dataset ini bukan vektor.');
            }

            $filters = $this->filtersFromInput($input);
            if ($this->shouldAggregateVector($filters)) {
                throw new InvalidArgumentException('Persempit area atau aktifkan filter provinsi/draw/upload terlebih dulu agar unduhan Level 1 tetap presisi dan ringan.');
            }
            $csvExport = $this->exportFilteredVectorCsvFile($datasetCode, $filters);

            $archive = $this->buildDatasetPackage(
                $datasetCode . '_vector_' . date('Ymd_His'),
                $datasetCode,
                $filters,
                [
                    ['name' => $csvExport['filename'], 'path' => $csvExport['path']],
                ]
            );

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
            $input = $this->inputPayload();
            $datasetCode = (string) ($input['dataset'] ?? 'faa_l2');
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

            return $this->response->download($archive['path'], null)->setFileName($archive['filename']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setBody($e->getMessage());
        }
    }

    public function downloadRasterProvince(int $provinceId)
    {
        $datasetCode = (string) ($this->request->getGet('dataset') ?? 'faa_l2');

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
        $db = Database::connect('gravport');

        $rows = $db->query('
            SELECT
                id,
                name_1,
                gid_1,
                country,
                ST_AsGeoJSON(geom) AS geojson
            FROM testing."AOI Jawa_Bali"
            ORDER BY name_1 ASC
        ')->getResultArray();

        $features = [];

        foreach ($rows as $row) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geojson'], true),
                'properties' => [
                    'feature_id' => (string) $row['id'],
                    'title' => (string) $row['name_1'],
                    'gid_1' => (string) $row['gid_1'],
                    'country' => (string) $row['country'],
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
        ', $params)->getResultArray();

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
        $dataset = $this->dataset($datasetCode);
        $db = Database::connect($dataset['db']);
        [$sqlBoundary, $params] = $this->spatialSql('t.' . $dataset['geom_column'], $filters);
        $gridSize = $this->aggregateGridSize($filters);

        $rows = $db->query('
            WITH src AS (
                SELECT
                    t.anomaly_value,
                    t.geom
                FROM ' . $dataset['table'] . ' t
                WHERE 1=1
                ' . $sqlBoundary . '
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
        $zoom = (int) ($filters['zoom'] ?? 0);
        $preset = match (true) {
            $zoom <= 5 => 0.35,
            $zoom <= 6 => 0.22,
            $zoom <= 7 => 0.14,
            $zoom <= 8 => 0.08,
            default => 0.04,
        };

        $bounds = $filters['bounds'] ?? null;
        if (is_array($bounds)) {
            $dynamic = max(
                abs((float) $bounds['east'] - (float) $bounds['west']) / 28,
                abs((float) $bounds['north'] - (float) $bounds['south']) / 18
            );
            $preset = max($preset, $dynamic);
        }

        return round(max(0.02, min(0.45, $preset)), 6);
    }

    private function vectorSourceCount(string $datasetCode, array $filters): int
    {
        $dataset = $this->dataset($datasetCode);
        $db = Database::connect($dataset['db']);
        [$sqlBoundary, $params] = $this->spatialSql('t.' . $dataset['geom_column'], $filters);
        $row = $db->query('
            SELECT COUNT(*) AS total
            FROM ' . $dataset['table'] . ' t
            WHERE 1=1
            ' . $sqlBoundary . '
        ', $params)->getRowArray();

        return (int) ($row['total'] ?? 0);
    }

    private function exportFilteredVectorCsvFile(string $datasetCode, array $filters): array
    {
        $dataset = $this->dataset($datasetCode);
        $db = Database::connect($dataset['db']);
        $directory = $this->exportDirectory('data');
        $filename = $datasetCode . '_filtered_' . date('Ymd_His') . '.csv';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new \RuntimeException('File export CSV tidak dapat dibuat.');
        }

        [$sqlBoundary, $params] = $this->spatialSql('t.' . $dataset['geom_column'], array_merge($filters, [
            'force_detail' => true,
        ]));
        $sql = '
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
        ';

        fputcsv($handle, ['id', 'latitude', 'longitude', 'orthometric_height', 'anomaly_value', 'source_file', 'survey_mode']);
        foreach ($db->query($sql, $params)->getResultArray() as $row) {
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

        $row = $db->query($sql, $boundaryParams)->getRowArray();

        if (!$row || empty($row['tif'])) {
            return null;
        }

        return $this->decodeBytea((string) $row['tif']);
    }

    private function boundaryPayload(array $filters): ?array
    {
        if (!empty($filters['province_id'])) {
            $db = Database::connect('gravport');
            $row = $db->query('
                SELECT
                    ST_AsGeoJSON(geom) AS geojson,
                    ST_GeometryType(geom) AS geom_type
                FROM testing."AOI Jawa_Bali"
                WHERE id = ?
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
        $boundary = $this->boundaryPayload($filters);
        $buffer = max(0, (int) ($filters['buffer_meters'] ?? 0));

        if ($boundary !== null) {
            if ($boundary['geometry_type'] === 'Point' && $buffer > 0) {
                $clauses[] = 'ST_DWithin(' . $geomColumn . '::geography, ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography, ?)';
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
}
