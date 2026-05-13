<?php

namespace App\Libraries;

use Config\Database;
use InvalidArgumentException;

class GeoportalDatasetRegistry
{
    private ?array $provinceCache = null;
    private ?array $catalogCache = null;

    public function definitions(): array
    {
        return [
            'faa_l1' => [
                'code' => 'faa_l1',
                'label' => 'Free Air Anomaly Level 1',
                'type' => 'vector',
                'db' => 'gravport',
                'table' => 'testing.faa_l1_points',
                'id_column' => 'id',
                'geom_column' => 'geom',
                'organization' => 'ITB',
                'availability' => 'live',
                'metadata_level' => 'level1',
                'summary_unit' => 'mGal',
                'download_extension' => 'csv',
                'note' => 'Sumber aktif dari PostgreSQL gravport.testing.faa_l1_points hasil impor paket terbaru.',
            ],
            'cba_l1' => [
                'code' => 'cba_l1',
                'label' => 'Complete Bouguer Anomaly Level 1',
                'type' => 'vector',
                'db' => 'gravport',
                'table' => 'testing.cba_l1_points',
                'id_column' => 'id',
                'geom_column' => 'geom',
                'organization' => 'ITB',
                'availability' => 'live',
                'metadata_level' => 'level1',
                'summary_unit' => 'mGal',
                'download_extension' => 'csv',
                'note' => 'Sumber aktif dari PostgreSQL gravport.testing.cba_l1_points hasil impor paket terbaru.',
            ],
            'faa_l2' => [
                'code' => 'faa_l2',
                'label' => 'Free Air Anomaly Level 2',
                'type' => 'raster',
                'db' => 'gravport',
                'table' => 'testing.faa_l2_raster',
                'id_column' => 'rid',
                'raster_column' => 'rast',
                'geom_column' => 'grid_geom',
                'organization' => 'ITB',
                'availability' => 'live',
                'metadata_level' => 'level2',
                'summary_unit' => 'mGal',
                'download_extension' => 'tif',
                'note' => 'Raster aktif diambil dari PostgreSQL gravport.testing.faa_l2_raster dan dipotong per grid 0.125 derajat x 0.125 derajat mengikuti indeks DEMNAS BIG.',
            ],
            'cba_l2' => [
                'code' => 'cba_l2',
                'label' => 'Complete Bouguer Anomaly Level 2',
                'type' => 'raster',
                'db' => 'gravport',
                'table' => 'testing.cba_l2_raster',
                'id_column' => 'rid',
                'raster_column' => 'rast',
                'geom_column' => 'grid_geom',
                'organization' => 'ITB',
                'availability' => 'live',
                'metadata_level' => 'level2',
                'summary_unit' => 'mGal',
                'download_extension' => 'tif',
                'note' => 'Raster aktif diambil dari PostgreSQL gravport.testing.cba_l2_raster dan dipotong per grid 0.125 derajat x 0.125 derajat mengikuti indeks DEMNAS BIG.',
            ],
        ];
    }

    public function dataset(string $code): array
    {
        $definitions = $this->definitions();

        if (!isset($definitions[$code])) {
            throw new InvalidArgumentException('Dataset tidak terdaftar.');
        }

        return $definitions[$code];
    }

    public function provinces(): array
    {
        if ($this->provinceCache !== null) {
            return $this->provinceCache;
        }

        $db = Database::connect('gravport');
        $rows = $db->query('
            SELECT
                id,
                name_1,
                gid_1,
                country
            FROM testing."AOI Jawa_Bali"
            ORDER BY name_1 ASC
        ')->getResultArray();

        $this->provinceCache = array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name_1'],
            'gid_1' => (string) $row['gid_1'],
            'country' => (string) $row['country'],
        ], $rows);

        return $this->provinceCache;
    }

    public function catalogEntries(): array
    {
        if ($this->catalogCache !== null) {
            return $this->catalogCache;
        }

        $entries = [];

        foreach ($this->definitions() as $dataset) {
            $entries[] = $this->catalogEntryPayload($dataset, null);

            foreach ($this->provinces() as $province) {
                $entries[] = $this->catalogEntryPayload($dataset, $province);
            }
        }

        usort($entries, static function (array $left, array $right): int {
            return strcmp($left['title'], $right['title']);
        });

        foreach ($entries as $index => &$entry) {
            $entry['id'] = $index + 1;
        }
        unset($entry);

        $this->catalogCache = $entries;

        return $this->catalogCache;
    }

    public function catalogEntry(int $id): ?array
    {
        foreach ($this->catalogEntries() as $entry) {
            if ((int) $entry['id'] === $id) {
                return $entry;
            }
        }

        return null;
    }

    public function filterCatalogEntries(array $filters): array
    {
        $entries = $this->catalogEntries();

        if (!empty($filters['q'])) {
            $needle = trim(mb_strtolower((string) $filters['q']));
            $entries = array_values(array_filter($entries, static function (array $entry) use ($needle): bool {
                $haystack = mb_strtolower(
                    implode(' ', [
                        (string) ($entry['title'] ?? ''),
                        (string) ($entry['description'] ?? ''),
                        (string) ($entry['province_name'] ?? ''),
                        (string) ($entry['dataset_code'] ?? ''),
                    ])
                );

                return str_contains($haystack, $needle);
            }));
        }

        if (!empty($filters['downloadable'])) {
            $entries = array_values(array_filter($entries, static fn (array $entry): bool => !empty($entry['is_downloadable'])));
        }

        if (!empty($filters['viewable'])) {
            $entries = array_values(array_filter($entries, static fn (array $entry): bool => !empty($entry['is_viewable'])));
        }

        if (!empty($filters['spatial_scope']) && is_array($filters['spatial_scope'])) {
            $allowed = array_map('strval', $filters['spatial_scope']);
            $entries = array_values(array_filter($entries, static fn (array $entry): bool => in_array((string) $entry['spatial_scope'], $allowed, true)));
        }

        if (!empty($filters['anomaly']) && is_array($filters['anomaly'])) {
            $allowed = array_map(
                static fn ($value): string => strtolower(trim((string) $value)),
                $filters['anomaly']
            );
            $entries = array_values(array_filter($entries, static function (array $entry) use ($allowed): bool {
                return in_array(strtolower((string) ($entry['anomaly_key'] ?? '')), $allowed, true);
            }));
        }

        if (!empty($filters['level']) && is_array($filters['level'])) {
            $allowed = array_map(
                static fn ($value): string => strtolower(trim((string) $value)),
                $filters['level']
            );
            $entries = array_values(array_filter($entries, static function (array $entry) use ($allowed): bool {
                return in_array(strtolower((string) ($entry['level_key'] ?? '')), $allowed, true);
            }));
        }

        return $entries;
    }

    private function catalogEntryPayload(array $dataset, ?array $province): array
    {
        $scope = $province === null ? 'national' : 'regional';
        $coverage = $province['name'] ?? 'Jawa-Bali';
        $backendType = $dataset['type'] === 'vector' ? 'PostGIS point table' : 'PostGIS raster grid';
        $tableName = str_contains($dataset['table'], '.')
            ? explode('.', $dataset['table'], 2)[1]
            : $dataset['table'];

        return [
            'id' => 0,
            'dataset_code' => $dataset['code'],
            'title' => $dataset['label'] . ' - ' . $coverage,
            'country_code' => 'ID',
            'country_name' => 'Indonesia',
            'spatial_scope' => $scope,
            'is_viewable' => true,
            'is_downloadable' => true,
            'items_count' => null,
            'backend_type' => $backendType,
            'data_schema' => 'testing',
            'data_table' => $tableName,
            'geom_column' => $dataset['geom_column'] ?? null,
            'organization' => $dataset['organization'],
            'availability' => $dataset['availability'],
            'type' => $dataset['type'],
            'metadata_level' => $dataset['metadata_level'],
            'anomaly_key' => str_starts_with($dataset['code'], 'faa_') ? 'faa' : 'cba',
            'level_key' => $dataset['metadata_level'],
            'download_extension' => $dataset['download_extension'],
            'province_id' => $province['id'] ?? null,
            'province_name' => $province['name'] ?? null,
            'description' => $province === null
                ? $dataset['label'] . ' untuk cakupan operasional AOI Jawa-Bali. Preview, unduhan, dan metadata terhubung langsung ke sumber aktif yang sama dengan WebMap.'
                : $dataset['label'] . ' untuk area ' . $province['name'] . '. Preview, unduhan, dan metadata mengikuti filter provinsi aktif dari sumber data WebMap.',
            'note' => $dataset['note'],
        ];
    }
}
