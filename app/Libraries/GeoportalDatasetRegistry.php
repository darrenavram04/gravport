<?php

namespace App\Libraries;

use Config\Database;
use InvalidArgumentException;

class GeoportalDatasetRegistry
{
    private ?array $provinceCache = null;
    private ?array $catalogCache  = null;

    public function definitions(): array
    {
        return [
            'faa_l1' => [
                'code'               => 'faa_l1',
                'label'              => 'Free Air Anomaly Level 1',
                'type'               => 'vector',
                'db'                 => 'default',
                'table'              => 'geoportal.faa_l1_points',
                'id_column'          => 'id',
                'geom_column'        => 'geom',
                'organization'       => 'ITB',
                'availability'       => 'live',
                'metadata_level'     => 'level1',
                'summary_unit'       => 'mGal',
                'download_extension' => 'csv',
                'note'               => 'View geoportal.faa_l1_points dari point_grav_anom WHERE dataset FAA L1.',
            ],
            'cba_l1' => [
                'code'               => 'cba_l1',
                'label'              => 'Complete Bouguer Anomaly Level 1',
                'type'               => 'vector',
                'db'                 => 'default',
                'table'              => 'geoportal.cba_l1_points',
                'id_column'          => 'id',
                'geom_column'        => 'geom',
                'organization'       => 'ITB',
                'availability'       => 'live',
                'metadata_level'     => 'level1',
                'summary_unit'       => 'mGal',
                'download_extension' => 'csv',
                'note'               => 'View geoportal.cba_l1_points dari point_grav_anom WHERE dataset CBA L1.',
            ],
            'faa_l2' => [
                'code'               => 'faa_l2',
                'label'              => 'Free Air Anomaly Level 2',
                'type'               => 'raster',
                'db'                 => 'default',
                'table'              => 'geoportal.faa_l2_raster',
                'id_column'          => 'rid',
                'raster_column'      => 'rast',
                'geom_column'        => 'grid_geom',
                'organization'       => 'ITB',
                'availability'       => 'live',
                'metadata_level'     => 'level2',
                'summary_unit'       => 'mGal',
                'download_extension' => 'tif',
                'note'               => 'View geoportal.faa_l2_raster dari raster_grav_anom WHERE dataset FAA L2.',
            ],
            'cba_l2' => [
                'code'               => 'cba_l2',
                'label'              => 'Complete Bouguer Anomaly Level 2',
                'type'               => 'raster',
                'db'                 => 'default',
                'table'              => 'geoportal.cba_l2_raster',
                'id_column'          => 'rid',
                'raster_column'      => 'rast',
                'geom_column'        => 'grid_geom',
                'organization'       => 'ITB',
                'availability'       => 'live',
                'metadata_level'     => 'level2',
                'summary_unit'       => 'mGal',
                'download_extension' => 'tif',
                'note'               => 'View geoportal.cba_l2_raster dari raster_grav_anom WHERE dataset CBA L2.',
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

        $db   = Database::connect();
        $rows = $db->query('
            SELECT adm_id, adm_name, adm_code
            FROM geoportal.polygon_adm_province
            ORDER BY adm_name ASC
        ')->getResultArray();

        $this->provinceCache = array_map(static fn (array $row): array => [
            'id'      => (int)    $row['adm_id'],
            'name'    => (string) $row['adm_name'],
            'gid_1'   => (string) $row['adm_code'],
            'country' => 'Indonesia',
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

        usort($entries, static fn (array $l, array $r): int => strcmp($l['title'], $r['title']));

        foreach ($entries as $i => &$entry) {
            $entry['id'] = $i + 1;
        }
        unset($entry);

        $this->catalogCache = $entries;
        return $this->catalogCache;
    }

    public function catalogEntry(int $id): ?array
    {
        foreach ($this->catalogEntries() as $entry) {
            if ((int) $entry['id'] === $id) return $entry;
        }
        return null;
    }

    public function filterCatalogEntries(array $filters): array
    {
        $entries = $this->catalogEntries();

        if (!empty($filters['q'])) {
            $needle  = trim(mb_strtolower((string) $filters['q']));
            $entries = array_values(array_filter($entries, static function (array $entry) use ($needle): bool {
                $haystack = mb_strtolower(implode(' ', [
                    (string) ($entry['title']         ?? ''),
                    (string) ($entry['description']   ?? ''),
                    (string) ($entry['province_name'] ?? ''),
                    (string) ($entry['dataset_code']  ?? ''),
                ]));
                return str_contains($haystack, $needle);
            }));
        }

        if (!empty($filters['downloadable'])) {
            $entries = array_values(array_filter($entries, static fn (array $e): bool => !empty($e['is_downloadable'])));
        }
        if (!empty($filters['viewable'])) {
            $entries = array_values(array_filter($entries, static fn (array $e): bool => !empty($e['is_viewable'])));
        }
        if (!empty($filters['spatial_scope']) && is_array($filters['spatial_scope'])) {
            $allowed = array_map('strval', $filters['spatial_scope']);
            $entries = array_values(array_filter($entries, static fn (array $e): bool => in_array((string) ($e['spatial_scope'] ?? ''), $allowed, true)));
        }
        if (!empty($filters['anomaly']) && is_array($filters['anomaly'])) {
            $allowed = array_map(static fn ($v): string => strtolower(trim((string) $v)), $filters['anomaly']);
            $entries = array_values(array_filter($entries, static fn (array $e): bool => in_array(strtolower((string) ($e['anomaly_key'] ?? '')), $allowed, true)));
        }
        if (!empty($filters['level']) && is_array($filters['level'])) {
            $allowed = array_map(static fn ($v): string => strtolower(trim((string) $v)), $filters['level']);
            $entries = array_values(array_filter($entries, static fn (array $e): bool => in_array(strtolower((string) ($e['level_key'] ?? '')), $allowed, true)));
        }

        return $entries;
    }

    private function catalogEntryPayload(array $dataset, ?array $province): array
    {
        $scope     = $province === null ? 'national' : 'regional';
        $coverage  = $province['name'] ?? 'Jawa-Bali';
        $tableName = str_contains($dataset['table'], '.') ? explode('.', $dataset['table'], 2)[1] : $dataset['table'];

        return [
            'id'                => 0,
            'dataset_code'      => $dataset['code'],
            'title'             => $dataset['label'] . ' - ' . $coverage,
            'country_code'      => 'ID',
            'country_name'      => 'Indonesia',
            'spatial_scope'     => $scope,
            'is_viewable'       => true,
            'is_downloadable'   => true,
            'items_count'       => null,
            'backend_type'      => $dataset['type'] === 'vector' ? 'PostGIS point table' : 'PostGIS raster grid',
            'data_schema'       => 'geoportal',
            'data_table'        => $tableName,
            'geom_column'       => $dataset['geom_column'] ?? null,
            'organization'      => $dataset['organization'],
            'availability'      => $dataset['availability'],
            'type'              => $dataset['type'],
            'metadata_level'    => $dataset['metadata_level'],
            'anomaly_key'       => str_starts_with($dataset['code'], 'faa_') ? 'faa' : 'cba',
            'level_key'         => $dataset['metadata_level'],
            'download_extension'=> $dataset['download_extension'],
            'province_id'       => $province['id']   ?? null,
            'province_name'     => $province['name'] ?? null,
            'description'       => $province === null
                ? $dataset['label'] . ' untuk cakupan operasional AOI Jawa-Bali.'
                : $dataset['label'] . ' untuk area ' . $province['name'] . '.',
            'note'              => $dataset['note'],
        ];
    }
}
