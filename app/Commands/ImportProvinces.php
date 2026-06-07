<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class ImportProvinces extends BaseCommand
{
    protected $group       = 'GravPort';
    protected $name        = 'import:provinces';
    protected $description = 'Import province polygon boundaries from GeoJSON into land_administrative_areas.';
    protected $usage       = 'import:provinces [geojson_path]';
    protected $arguments   = [
        'geojson_path' => 'Path to GeoJSON file (default: ROOTPATH + "2. polygon_adm_provinces.geojson")',
    ];

    public function run(array $params)
    {
        $path = $params[0] ?? ROOTPATH . '2. polygon_adm_provinces.geojson';

        if (! is_file($path)) {
            CLI::error("GeoJSON file not found: {$path}");
            return EXIT_ERROR;
        }

        $json = json_decode(file_get_contents($path), true);
        if (! is_array($json) || ($json['type'] ?? '') !== 'FeatureCollection') {
            CLI::error('Invalid GeoJSON file.');
            return EXIT_ERROR;
        }

        $db = Database::connect();

        // Name normalisation map: GeoJSON uppercase → DB adm_name values
        $nameMap = [
            'DKI JAKARTA'   => 'DKI Jakarta',
            'JAWA BARAT'    => 'Jawa Barat',
            'JAWA TENGAH'   => 'Jawa Tengah',
            'DI YOGYAKARTA' => 'DI Yogyakarta',
            'JAWA TIMUR'    => 'Jawa Timur',
            'BANTEN'        => 'Banten',
            'BALI'          => 'Bali',
        ];

        $updated = 0;
        $skipped = 0;

        foreach ($json['features'] as $feature) {
            $rawName = trim((string) ($feature['properties']['name'] ?? ''));
            $geomArr = $feature['geometry'] ?? null;

            if ($rawName === '' || $geomArr === null) {
                $skipped++;
                continue;
            }

            $dbName = $nameMap[strtoupper($rawName)] ?? null;
            if ($dbName === null) {
                CLI::write("  SKIP: no mapping for '{$rawName}'", 'yellow');
                $skipped++;
                continue;
            }

            $geojsonStr = json_encode($geomArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $result = $db->query(
                "UPDATE geoportal.land_administrative_areas
                 SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)
                 WHERE adm_name = ? AND adm_level = 1",
                [$geojsonStr, $dbName]
            );

            $affected = $db->affectedRows();
            if ($affected > 0) {
                CLI::write("  OK: {$dbName}", 'green');
                $updated++;
            } else {
                CLI::write("  MISS: '{$dbName}' not found in DB", 'yellow');
                $skipped++;
            }
        }

        CLI::write("\nDone. Updated: {$updated}, Skipped/missed: {$skipped}", 'green');
        return EXIT_SUCCESS;
    }
}
