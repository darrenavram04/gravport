<?php

namespace App\Libraries;

use Config\Database;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;

class DatasetImportService
{
    private const IMPORT_ROOT = WRITEPATH . 'imports';
    private const BIG_GRID_DEGREES = 0.125;

    public function latestPackageName(): ?string
    {
        $packages = glob(self::IMPORT_ROOT . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];

        if ($packages === []) {
            return null;
        }

        rsort($packages, SORT_NATURAL);

        return basename($packages[0]);
    }

    public function summarizeLatestPackage(): ?array
    {
        $latest = $this->latestPackageName();

        return $latest ? $this->summarizePackage($latest) : null;
    }

    public function summarizePackage(?string $package = null): array
    {
        $packageName = $package ?: $this->latestPackageName();

        if (!$packageName) {
            throw new RuntimeException('Folder import belum tersedia di writable/imports.');
        }

        $path = $this->packagePath($packageName);

        return [
            'package' => $packageName,
            'path' => $path,
            'level1' => [
                'metadata' => $this->relativePath($path . DIRECTORY_SEPARATOR . 'level1' . DIRECTORY_SEPARATOR . 'Metadata_Gravimetri_Level_1.xml'),
                'faa_csv' => $this->relativePaths(glob($path . DIRECTORY_SEPARATOR . 'level1' . DIRECTORY_SEPARATOR . 'faa' . DIRECTORY_SEPARATOR . '*.csv') ?: []),
                'faa_archives' => $this->relativePaths(glob($path . DIRECTORY_SEPARATOR . 'level1' . DIRECTORY_SEPARATOR . 'faa' . DIRECTORY_SEPARATOR . '*.rar') ?: []),
                'cba_csv' => $this->relativePaths(glob($path . DIRECTORY_SEPARATOR . 'level1' . DIRECTORY_SEPARATOR . 'cba' . DIRECTORY_SEPARATOR . '*.csv') ?: []),
                'cba_archives' => $this->relativePaths(glob($path . DIRECTORY_SEPARATOR . 'level1' . DIRECTORY_SEPARATOR . 'cba' . DIRECTORY_SEPARATOR . '*.rar') ?: []),
            ],
            'level2' => [
                'metadata' => $this->relativePath($path . DIRECTORY_SEPARATOR . 'level2' . DIRECTORY_SEPARATOR . 'Metadata_Gravimetri_Level_2.xml'),
                'faa_tif' => $this->relativePath($path . DIRECTORY_SEPARATOR . 'level2' . DIRECTORY_SEPARATOR . 'faa' . DIRECTORY_SEPARATOR . 'FAA.tif'),
                'cba_tif' => $this->relativePath($path . DIRECTORY_SEPARATOR . 'level2' . DIRECTORY_SEPARATOR . 'cba' . DIRECTORY_SEPARATOR . 'CBA.tif'),
            ],
        ];
    }

    public function importPackage(?string $package = null): array
    {
        $packageName = $package ?: $this->latestPackageName();

        if (!$packageName) {
            throw new RuntimeException('Folder import belum tersedia di writable/imports.');
        }

        $packagePath = $this->packagePath($packageName);
        $db = Database::connect();
        $conn = $db->connID;

        if (!$conn) {
            $db->initialize();
            $conn = $db->connID;
        }

        if (!$conn) {
            throw new RuntimeException('Koneksi PostgreSQL gravport tidak tersedia.');
        }

        set_time_limit(0);

        $report = [
            'package' => $packageName,
            'path' => $packagePath,
            'started_at' => date(DATE_ATOM),
        ];

        $this->query($conn, 'BEGIN');

        try {
            $this->ensureMetadataTable($conn);
            $this->ensureLevel1Table($conn, 'geoportal.faa_l1_points');
            $this->ensureLevel1Table($conn, 'geoportal.cba_l1_points');
            $this->ensureRasterTable($conn, 'geoportal.faa_l2_raster');
            $this->ensureRasterTable($conn, 'geoportal.cba_l2_raster');

            $report['metadata'] = [
                'faa_l1' => $this->importMetadataXml(
                    $conn,
                    'faa_l1',
                    $packagePath . DIRECTORY_SEPARATOR . 'level1' . DIRECTORY_SEPARATOR . 'Metadata_Gravimetri_Level_1.xml'
                ),
                'faa_l2' => $this->importMetadataXml(
                    $conn,
                    'faa_l2',
                    $packagePath . DIRECTORY_SEPARATOR . 'level2' . DIRECTORY_SEPARATOR . 'Metadata_Gravimetri_Level_2.xml'
                ),
            ];

            $report['level1'] = [
                'faa' => $this->importLevel1Group(
                    $conn,
                    'geoportal.faa_l1_points',
                    glob($packagePath . DIRECTORY_SEPARATOR . 'level1' . DIRECTORY_SEPARATOR . 'faa' . DIRECTORY_SEPARATOR . '*.csv') ?: [],
                    'FAA'
                ),
                'cba' => $this->importLevel1Group(
                    $conn,
                    'geoportal.cba_l1_points',
                    glob($packagePath . DIRECTORY_SEPARATOR . 'level1' . DIRECTORY_SEPARATOR . 'cba' . DIRECTORY_SEPARATOR . '*.csv') ?: [],
                    'CBA'
                ),
            ];

            $report['level2'] = [
                'faa' => $this->importRaster(
                    $conn,
                    'geoportal.faa_l2_raster',
                    $packagePath . DIRECTORY_SEPARATOR . 'level2' . DIRECTORY_SEPARATOR . 'faa' . DIRECTORY_SEPARATOR . 'FAA.tif'
                ),
                'cba' => $this->importRaster(
                    $conn,
                    'geoportal.cba_l2_raster',
                    $packagePath . DIRECTORY_SEPARATOR . 'level2' . DIRECTORY_SEPARATOR . 'cba' . DIRECTORY_SEPARATOR . 'CBA.tif'
                ),
            ];

            $this->query($conn, 'COMMIT');
        } catch (\Throwable $e) {
            $this->query($conn, 'ROLLBACK');
            throw $e;
        }

        $report['finished_at'] = date(DATE_ATOM);

        return $report;
    }

    private function packagePath(string $package): string
    {
        $package = trim($package);

        if ($package === '') {
            throw new InvalidArgumentException('Nama paket import tidak boleh kosong.');
        }

        $candidate = realpath(self::IMPORT_ROOT . DIRECTORY_SEPARATOR . $package);

        if ($candidate === false || !is_dir($candidate)) {
            throw new RuntimeException("Folder import {$package} tidak ditemukan.");
        }

        $root = realpath(self::IMPORT_ROOT);
        if ($root === false || (strpos($candidate, $root . DIRECTORY_SEPARATOR) !== 0 && $candidate !== $root)) {
            throw new RuntimeException('Folder import berada di luar direktori yang diizinkan.');
        }

        return $candidate;
    }

    private function ensureMetadataTable($conn): void
    {
        // Fresh-install: create with composite PK
        $this->query($conn, <<<SQL
CREATE TABLE IF NOT EXISTS geoportal.dataset_metadata_xml (
    jenis_data text NOT NULL,
    provinsi text NOT NULL,
    level_data text NOT NULL,
    dataset_code text,
    metadata_level text,
    source_path text NOT NULL,
    file_identifier text,
    parent_identifier text,
    hierarchy_level_name text,
    metadata_date date,
    language_code text,
    character_set text,
    title text,
    abstract text,
    individual_name text,
    organisation_name text,
    position_name text,
    voice text,
    delivery_point text,
    city text,
    administrative_area text,
    postal_code text,
    country text,
    emails_json jsonb,
    contact_role text,
    raw_xml xml NOT NULL,
    imported_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (jenis_data, provinsi, level_data)
)
SQL
        );

        // Migration for tables created with old dataset_code PRIMARY KEY schema
        $this->query($conn, <<<SQL
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'geoportal'
          AND table_name   = 'dataset_metadata_xml'
          AND column_name  = 'jenis_data'
    ) THEN
        ALTER TABLE geoportal.dataset_metadata_xml
            ADD COLUMN jenis_data text,
            ADD COLUMN provinsi   text,
            ADD COLUMN level_data text;
        DELETE FROM geoportal.dataset_metadata_xml;
        ALTER TABLE geoportal.dataset_metadata_xml
            DROP CONSTRAINT IF EXISTS dataset_metadata_xml_pkey;
        ALTER TABLE geoportal.dataset_metadata_xml
            ALTER COLUMN dataset_code DROP NOT NULL,
            ALTER COLUMN jenis_data SET NOT NULL,
            ALTER COLUMN provinsi   SET NOT NULL,
            ALTER COLUMN level_data SET NOT NULL;
        ALTER TABLE geoportal.dataset_metadata_xml
            ADD PRIMARY KEY (jenis_data, provinsi, level_data);
    END IF;
END $$
SQL
        );
    }

    private function ensureLevel1Table($conn, string $table): void
    {
        $this->query($conn, <<<SQL
CREATE TABLE IF NOT EXISTS {$table} (
    id bigserial PRIMARY KEY,
    source_file text NOT NULL,
    survey_mode text NOT NULL,
    latitude double precision NOT NULL,
    longitude double precision NOT NULL,
    orthometric_height double precision,
    anomaly_value double precision NOT NULL,
    geom geometry(Point, 4326) NOT NULL,
    imported_at timestamptz NOT NULL DEFAULT now()
)
SQL
        );

        $indexName = str_replace('.', '_', $table) . '_geom_idx';
        $this->query($conn, "CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} USING GIST (geom)");
    }

    private function ensureRasterTable($conn, string $table): void
    {
        $this->query($conn, <<<SQL
CREATE TABLE IF NOT EXISTS {$table} (
    rid serial PRIMARY KEY,
    source_file text NOT NULL,
    grid_geom geometry(Polygon, 4326),
    rast raster NOT NULL,
    imported_at timestamptz NOT NULL DEFAULT now()
)
SQL
        );

        $this->query($conn, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS grid_geom geometry(Polygon, 4326)");

        $indexName = str_replace('.', '_', $table) . '_grid_geom_idx';
        $this->query($conn, "CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} USING GIST (grid_geom)");
    }

    public function importMetadataXmlFile(string $jenisData, string $provinsi, string $levelData, string $xmlPath): array
    {
        $db   = Database::connect();
        $conn = $db->connID;
        if (!$conn) {
            $db->initialize();
            $conn = $db->connID;
        }
        $this->ensureMetadataTable($conn);
        return $this->importMetadataXml($conn, $jenisData, $provinsi, $levelData, $xmlPath);
    }

    private function importMetadataXml($conn, string $jenisData, string $provinsi, string $levelData, string $xmlPath): array
    {
        if (!is_file($xmlPath)) {
            throw new RuntimeException("File metadata XML tidak ditemukan: {$xmlPath}");
        }

        $rawXml = file_get_contents($xmlPath);
        if ($rawXml === false) {
            throw new RuntimeException("Gagal membaca file metadata XML: {$xmlPath}");
        }

        $data = $this->parseMetadataXml($xmlPath);

        $metadataLevel = $levelData === 'Level 1' ? 'level1' : 'level2';

        $this->queryParams($conn, <<<SQL
INSERT INTO geoportal.dataset_metadata_xml (
    jenis_data,
    provinsi,
    level_data,
    metadata_level,
    source_path,
    file_identifier,
    parent_identifier,
    hierarchy_level_name,
    metadata_date,
    language_code,
    character_set,
    title,
    abstract,
    individual_name,
    organisation_name,
    position_name,
    voice,
    delivery_point,
    city,
    administrative_area,
    postal_code,
    country,
    emails_json,
    contact_role,
    raw_xml,
    imported_at
) VALUES (
    $1, $2, $3, $4, $5, $6, $7, $8, NULLIF($9, '')::date, $10, $11, $12, $13, $14, $15, $16,
    $17, $18, $19, $20, $21, $22::jsonb, $23, $24::xml, now()
)
ON CONFLICT (jenis_data, provinsi, level_data) DO UPDATE SET
    metadata_level = EXCLUDED.metadata_level,
    source_path = EXCLUDED.source_path,
    file_identifier = EXCLUDED.file_identifier,
    parent_identifier = EXCLUDED.parent_identifier,
    hierarchy_level_name = EXCLUDED.hierarchy_level_name,
    metadata_date = EXCLUDED.metadata_date,
    language_code = EXCLUDED.language_code,
    character_set = EXCLUDED.character_set,
    title = EXCLUDED.title,
    abstract = EXCLUDED.abstract,
    individual_name = EXCLUDED.individual_name,
    organisation_name = EXCLUDED.organisation_name,
    position_name = EXCLUDED.position_name,
    voice = EXCLUDED.voice,
    delivery_point = EXCLUDED.delivery_point,
    city = EXCLUDED.city,
    administrative_area = EXCLUDED.administrative_area,
    postal_code = EXCLUDED.postal_code,
    country = EXCLUDED.country,
    emails_json = EXCLUDED.emails_json,
    contact_role = EXCLUDED.contact_role,
    raw_xml = EXCLUDED.raw_xml,
    imported_at = now()
SQL,
            [
                $jenisData,
                $provinsi,
                $levelData,
                $metadataLevel,
                $this->relativePath($xmlPath),
                $data['file_identifier'],
                $data['parent_identifier'],
                $data['hierarchy_level_name'],
                $data['metadata_date'],
                $data['language_code'],
                $data['character_set'],
                $data['title'],
                $data['abstract'],
                $data['individual_name'],
                $data['organisation_name'],
                $data['position_name'],
                $data['voice'],
                $data['delivery_point'],
                $data['city'],
                $data['administrative_area'],
                $data['postal_code'],
                $data['country'],
                json_encode($data['emails'], JSON_UNESCAPED_UNICODE),
                $data['contact_role'],
                $rawXml,
            ]
        );

        return [
            'file_identifier' => $data['file_identifier'],
            'title' => $data['title'],
            'date' => $data['metadata_date'],
            'source_path' => $this->relativePath($xmlPath),
        ];
    }

    private function importLevel1Group($conn, string $table, array $csvFiles, string $kind): array
    {
        if ($csvFiles === []) {
            throw new RuntimeException("File CSV {$kind} tidak ditemukan.");
        }

        $this->query($conn, "TRUNCATE TABLE {$table} RESTART IDENTITY");

        $totalRows = 0;
        $totalSkipped = 0;
        $filesSummary = [];

        foreach ($csvFiles as $csvPath) {
            $result = $this->importCsvFile($conn, $table, $csvPath, $kind);
            $totalRows += $result['rows'];
            $totalSkipped += $result['skipped_rows'];
            $filesSummary[] = $result;
        }

        return [
            'table' => $table,
            'rows' => $totalRows,
            'skipped_rows' => $totalSkipped,
            'files' => $filesSummary,
        ];
    }

    private function importCsvFile($conn, string $table, string $csvPath, string $kind): array
    {
        $handle = fopen($csvPath, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Gagal membuka file CSV: {$csvPath}");
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new RuntimeException("Header CSV kosong: {$csvPath}");
        }

        $map = $this->buildCsvColumnMap($header, $kind, $csvPath);
        $batch = [];
        $rows = 0;
        $skippedRows = 0;
        $surveyMode = $this->detectSurveyMode($csvPath);

        try {
            while (($raw = fgetcsv($handle)) !== false) {
                if ($this->rowIsEmpty($raw)) {
                    continue;
                }

                try {
                    $row = $this->mapCsvRow($raw, $map, $csvPath, $kind);
                } catch (RuntimeException $e) {
                    $skippedRows++;
                    continue;
                }

                $batch[] = [
                    'source_file' => basename($csvPath),
                    'survey_mode' => $surveyMode,
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'orthometric_height' => $row['orthometric_height'],
                    'anomaly_value' => $row['anomaly_value'],
                ];
                $rows++;

                if (count($batch) >= 2000) {
                    $this->insertLevel1Batch($conn, $table, $batch);
                    $batch = [];
                }
            }
        } finally {
            fclose($handle);
        }

        if ($batch !== []) {
            $this->insertLevel1Batch($conn, $table, $batch);
        }

        return [
            'file' => basename($csvPath),
            'rows' => $rows,
            'skipped_rows' => $skippedRows,
            'survey_mode' => $surveyMode,
            'path' => $this->relativePath($csvPath),
        ];
    }

    private function insertLevel1Batch($conn, string $table, array $rows): void
    {
        $values = [];
        $params = [];
        $paramIndex = 1;

        foreach ($rows as $row) {
            $values[] = sprintf(
                '($%d, $%d, $%d, $%d, $%d, $%d, ST_SetSRID(ST_MakePoint($%d, $%d), 4326))',
                $paramIndex,
                $paramIndex + 1,
                $paramIndex + 2,
                $paramIndex + 3,
                $paramIndex + 4,
                $paramIndex + 5,
                $paramIndex + 3,
                $paramIndex + 2
            );

            $params[] = $row['source_file'];
            $params[] = $row['survey_mode'];
            $params[] = $row['latitude'];
            $params[] = $row['longitude'];
            $params[] = $row['orthometric_height'];
            $params[] = $row['anomaly_value'];

            $paramIndex += 6;
        }

        $sql = <<<SQL
INSERT INTO {$table} (
    source_file,
    survey_mode,
    latitude,
    longitude,
    orthometric_height,
    anomaly_value,
    geom
) VALUES
SQL;
        $sql .= implode(",\n", $values);

        $this->queryParams($conn, $sql, $params);
    }

    private function importRaster($conn, string $table, string $tifPath): array
    {
        if (!is_file($tifPath)) {
            throw new RuntimeException("File TIFF tidak ditemukan: {$tifPath}");
        }

        $bytes = file_get_contents($tifPath);
        if ($bytes === false) {
            throw new RuntimeException("Gagal membaca file TIFF: {$tifPath}");
        }

        $this->query($conn, "TRUNCATE TABLE {$table} RESTART IDENTITY");

        $escaped = pg_escape_bytea($conn, $bytes);
        $gridSize = number_format(self::BIG_GRID_DEGREES, 6, '.', '');

        $this->queryParams(
            $conn,
            <<<SQL
WITH src AS (
    SELECT ST_FromGDALRaster('{$escaped}'::bytea) AS rast
),
envelope AS (
    SELECT ST_Envelope(rast)::geometry(Polygon, 4326) AS geom
    FROM src
),
bounds AS (
    SELECT
        floor((ST_XMin(geom)::numeric) / {$gridSize}) * {$gridSize} AS xmin,
        floor((ST_YMin(geom)::numeric) / {$gridSize}) * {$gridSize} AS ymin,
        ceil((ST_XMax(geom)::numeric) / {$gridSize}) * {$gridSize} AS xmax,
        ceil((ST_YMax(geom)::numeric) / {$gridSize}) * {$gridSize} AS ymax
    FROM envelope
),
grid AS (
    SELECT ST_MakeEnvelope(
        x::double precision,
        y::double precision,
        (x + {$gridSize}::numeric)::double precision,
        (y + {$gridSize}::numeric)::double precision,
        4326
    )::geometry(Polygon, 4326) AS geom
    FROM bounds
    CROSS JOIN generate_series(bounds.xmin, bounds.xmax - {$gridSize}::numeric, {$gridSize}::numeric) AS x
    CROSS JOIN generate_series(bounds.ymin, bounds.ymax - {$gridSize}::numeric, {$gridSize}::numeric) AS y
)
INSERT INTO {$table} (source_file, grid_geom, rast)
SELECT
    $1,
    g.geom,
    clipped.rast
FROM src
CROSS JOIN grid g
CROSS JOIN LATERAL (
    SELECT ST_Clip(src.rast, g.geom, true) AS rast
) clipped
WHERE ST_Intersects(src.rast, g.geom)
  AND ST_Count(clipped.rast, 1, true) > 0
SQL,
            [basename($tifPath)]
        );

        $summary = $this->fetchOne($conn, <<<SQL
SELECT
    COUNT(*)::int AS rows,
    MIN(ST_Width(rast))::int AS min_width_px,
    MAX(ST_Width(rast))::int AS max_width_px,
    MIN(ST_Height(rast))::int AS min_height_px,
    MAX(ST_Height(rast))::int AS max_height_px,
    ROUND(MIN((ST_XMax(grid_geom) - ST_XMin(grid_geom)))::numeric, 6) AS min_grid_width_deg,
    ROUND(MAX((ST_XMax(grid_geom) - ST_XMin(grid_geom)))::numeric, 6) AS max_grid_width_deg,
    ROUND(MIN((ST_YMax(grid_geom) - ST_YMin(grid_geom)))::numeric, 6) AS min_grid_height_deg,
    ROUND(MAX((ST_YMax(grid_geom) - ST_YMin(grid_geom)))::numeric, 6) AS max_grid_height_deg,
    MIN(ST_SRID(rast))::int AS srid,
    MIN(ST_NumBands(rast))::int AS num_bands
FROM {$table}
SQL
        );

        return [
            'table' => $table,
            'rows' => (int) ($summary['rows'] ?? 0),
            'min_width_px' => (int) ($summary['min_width_px'] ?? 0),
            'max_width_px' => (int) ($summary['max_width_px'] ?? 0),
            'min_height_px' => (int) ($summary['min_height_px'] ?? 0),
            'max_height_px' => (int) ($summary['max_height_px'] ?? 0),
            'min_grid_width_deg' => (float) ($summary['min_grid_width_deg'] ?? 0),
            'max_grid_width_deg' => (float) ($summary['max_grid_width_deg'] ?? 0),
            'min_grid_height_deg' => (float) ($summary['min_grid_height_deg'] ?? 0),
            'max_grid_height_deg' => (float) ($summary['max_grid_height_deg'] ?? 0),
            'srid' => (int) ($summary['srid'] ?? 0),
            'num_bands' => (int) ($summary['num_bands'] ?? 0),
            'grid_step_deg' => self::BIG_GRID_DEGREES,
            'file' => basename($tifPath),
            'path' => $this->relativePath($tifPath),
        ];
    }

    private function parseMetadataXml(string $xmlPath): array
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        if (!$dom->load($xmlPath)) {
            throw new RuntimeException("XML metadata tidak valid: {$xmlPath}");
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('gmd', 'http://www.isotc211.org/2005/gmd');
        $xpath->registerNamespace('gco', 'http://www.isotc211.org/2005/gco');

        return [
            'file_identifier' => $this->cleanText($xpath->evaluate('string(//gmd:fileIdentifier/gco:CharacterString)')),
            'parent_identifier' => $this->cleanText($xpath->evaluate('string(//gmd:parentIdentifier/gco:CharacterString)')),
            'hierarchy_level_name' => $this->cleanText($xpath->evaluate('string(//gmd:hierarchyLevelName/gco:CharacterString)')),
            'metadata_date' => $this->cleanText($xpath->evaluate('string(//gmd:dateStamp/gco:Date)')),
            'language_code' => $this->cleanText($xpath->evaluate('string((//gmd:language/gmd:LanguageCode/@codeListValue)[1])')),
            'character_set' => $this->cleanText($xpath->evaluate('string((//gmd:characterSet//gmd:MD_CharacterSetCode/@codeListValue)[1])')),
            'title' => $this->cleanText($xpath->evaluate('string((//gmd:identificationInfo//gmd:citation//gmd:title/gco:CharacterString)[1])')),
            'abstract' => $this->cleanText($xpath->evaluate('string((//gmd:identificationInfo//gmd:abstract/gco:CharacterString)[1])')),
            'individual_name' => $this->cleanText($xpath->evaluate('string((//gmd:contact//gmd:individualName/gco:CharacterString)[1])')),
            'organisation_name' => $this->cleanText($xpath->evaluate('string((//gmd:contact//gmd:organisationName/gco:CharacterString)[1])')),
            'position_name' => $this->cleanText($xpath->evaluate('string((//gmd:contact//gmd:positionName/gco:CharacterString)[1])')),
            'voice' => $this->cleanText($xpath->evaluate('string((//gmd:contact//gmd:voice/gco:CharacterString)[1])')),
            'delivery_point' => $this->cleanText($xpath->evaluate('string((//gmd:contact//gmd:deliveryPoint/gco:CharacterString)[1])')),
            'city' => $this->cleanText($xpath->evaluate('string((//gmd:contact//gmd:city/gco:CharacterString)[1])')),
            'administrative_area' => $this->cleanText($xpath->evaluate('string((//gmd:contact//gmd:administrativeArea/gco:CharacterString)[1])')),
            'postal_code' => $this->cleanText($xpath->evaluate('string((//gmd:contact//gmd:postalCode/gco:CharacterString)[1])')),
            'country' => $this->cleanText($xpath->evaluate('string((//gmd:contact//gmd:country/gco:CharacterString)[1])')),
            'emails' => $this->extractEmails($xpath),
            'contact_role' => $this->cleanText($xpath->evaluate('string((//gmd:identificationInfo//gmd:pointOfContact//gmd:CI_RoleCode/@codeListValue)[1])'))
                ?: $this->cleanText($xpath->evaluate('string((//gmd:contact//gmd:CI_RoleCode/@codeListValue)[1])')),
        ];
    }

    private function extractEmails(DOMXPath $xpath): array
    {
        $nodes = $xpath->query('//gmd:electronicMailAddress/gco:CharacterString');
        $emails = [];

        if ($nodes) {
            foreach ($nodes as $node) {
                $value = $this->cleanText($node->textContent);
                if ($value !== '') {
                    $emails[] = $value;
                }
            }
        }

        return array_values(array_unique($emails));
    }

    private function buildCsvColumnMap(array $header, string $kind, string $csvPath): array
    {
        $normalized = [];
        foreach ($header as $index => $column) {
            $normalized[$this->normalizeColumnName((string) $column)] = $index;
        }

        $anomalyKey = strtolower($kind);

        $map = [
            'latitude' => $normalized['lintang'] ?? null,
            'longitude' => $normalized['bujur'] ?? null,
            'orthometric_height' => $normalized['tinggiortometrik'] ?? $normalized['tinggiort'] ?? null,
            'anomaly_value' => $normalized[$anomalyKey] ?? null,
        ];

        foreach ($map as $field => $index) {
            if ($index === null) {
                throw new RuntimeException("Kolom {$field} tidak ditemukan pada {$csvPath}.");
            }
        }

        return $map;
    }

    private function mapCsvRow(array $row, array $map, string $csvPath, string $kind): array
    {
        $latitude = $this->toFloat($row[$map['latitude']] ?? null, 'Lintang', $csvPath);
        $longitude = $this->toFloat($row[$map['longitude']] ?? null, 'Bujur', $csvPath);
        $height = $this->toNullableFloat($row[$map['orthometric_height']] ?? null);
        $anomaly = $this->toFloat($row[$map['anomaly_value']] ?? null, $kind, $csvPath);

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'orthometric_height' => $height,
            'anomaly_value' => $anomaly,
        ];
    }

    private function detectSurveyMode(string $path): string
    {
        $name = strtolower(basename($path));

        if (str_contains($name, 'airborne')) {
            return 'airborne';
        }

        if (str_contains($name, 'terestris')) {
            return 'terestris';
        }

        return 'unknown';
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function toFloat($value, string $column, string $csvPath): float
    {
        $value = trim((string) $value);

        if ($value === '' || $this->isInvalidNumericToken($value) || !is_numeric($value)) {
            throw new RuntimeException("Nilai {$column} tidak valid pada {$csvPath}: {$value}");
        }

        return (float) $value;
    }

    private function toNullableFloat($value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if ($this->isInvalidNumericToken($value)) {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function isInvalidNumericToken(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['nan', 'inf', '-inf', 'infinity', '-infinity'], true);
    }

    private function normalizeColumnName(string $column): string
    {
        $column = trim($column);
        $column = preg_replace('/[^a-z0-9]+/i', '', $column);

        return strtolower((string) $column);
    }

    private function cleanText(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function relativePath(string $path): string
    {
        $real = realpath($path) ?: $path;
        $root = realpath(ROOTPATH);

        if ($root && strpos($real, $root) === 0) {
            return ltrim(str_replace('\\', '/', substr($real, strlen($root))), '/');
        }

        return str_replace('\\', '/', $real);
    }

    private function relativePaths(array $paths): array
    {
        return array_map(fn (string $path): string => $this->relativePath($path), $paths);
    }

    private function fetchOne($conn, string $sql): array
    {
        $result = pg_query($conn, $sql);

        if ($result === false) {
            throw new RuntimeException(pg_last_error($conn) ?: 'Query PostgreSQL gagal dijalankan.');
        }

        $row = pg_fetch_assoc($result);

        return $row ?: [];
    }

    private function query($conn, string $sql): void
    {
        $result = pg_query($conn, $sql);

        if ($result === false) {
            throw new RuntimeException(pg_last_error($conn) ?: 'Query PostgreSQL gagal dijalankan.');
        }
    }

    private function queryParams($conn, string $sql, array $params): void
    {
        $result = pg_query_params($conn, $sql, $params);

        if ($result === false) {
            throw new RuntimeException(pg_last_error($conn) ?: 'Query PostgreSQL parameterized gagal dijalankan.');
        }
    }
}
