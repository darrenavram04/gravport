<?php

/**
 * Migrate dataset_metadata_xml table to composite PK (jenis_data, provinsi, level_data)
 * and bulk-import all XML files from the Hasil XML Metadata folder.
 *
 * Usage (from geoportal root):
 *   php scripts/import_metadata_xml.php [/path/to/xml/folder]
 *
 * If no folder argument is given, defaults to the Windows local path.
 * DB credentials are read from .env (database.default.*), falling back to
 * app/Config/Database.php defaults.
 */

define('GEOPORTAL_ROOT', dirname(__DIR__));
chdir(GEOPORTAL_ROOT);

// ── DB credentials: read from .env, fall back to Config/Database.php defaults ──
$cfg = [
    'host'   => '127.0.0.1',
    'port'   => 5433,
    'dbname' => 'geoportal',
    'user'   => 'postgres',
    'pass'   => 'yayaya123',
];

$envFile = GEOPORTAL_ROOT . DIRECTORY_SEPARATOR . '.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $v = trim($v, '"\'');
        match ($k) {
            'database.default.hostname' => $cfg['host']   = $v,
            'database.default.port'     => $cfg['port']   = (int) $v,
            'database.default.database' => $cfg['dbname'] = $v,
            'database.default.username' => $cfg['user']   = $v,
            'database.default.password' => $cfg['pass']   = $v,
            default                     => null,
        };
    }
}

$dbHost = $cfg['host'];
$dbPort = $cfg['port'];
$dbName = $cfg['dbname'];
$dbUser = $cfg['user'];
$dbPass = $cfg['pass'];

// ── Source folder: CLI arg → env var → default Windows path ─────────────────
$sourceDir = $argv[1]
    ?? getenv('SOURCE_DIR')
    ?: 'C:\\Users\\dpandasig\\Downloads\\Hasil XML Metadata';

// Province name normalisation: XML filename segment → canonical provinsi value
// matching the dropdown list in v_metadata.php and v_admin_manage.php
$provinceMap = [
    'Jakarta'      => 'DKI Jakarta',
    'Yogyakarta'   => 'DI Yogyakarta',
    // all others match directly (Bali, Banten, Jawa Barat, Jawa Tengah, Jawa Timur)
];

// Jenis data normalisation: XML filename segment → canonical value
$jenisMap = [
    'Terestris' => 'Terestrial',
    'Airborne'  => 'Airborne',
];

// ── Connect ─────────────────────────────────────────────────────────────────
$dsn  = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName}";
$pdo  = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "Connected to {$dbName}@{$dbHost}:{$dbPort}\n\n";

// ── Migrate table schema ─────────────────────────────────────────────────────
echo "Checking / migrating dataset_metadata_xml schema...\n";

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS geoportal.dataset_metadata_xml (
    jenis_data text NOT NULL,
    provinsi   text NOT NULL,
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
SQL);

$pdo->exec(<<<SQL
DO \$\$
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
END \$\$
SQL);

echo "Schema OK.\n\n";

// ── Import files ─────────────────────────────────────────────────────────────
$files = glob($sourceDir . DIRECTORY_SEPARATOR . 'Metadata_*.xml');
if (!$files) {
    echo "No XML files found in: {$sourceDir}\n";
    exit(1);
}

sort($files);
$ok  = 0;
$err = 0;

foreach ($files as $xmlPath) {
    $basename = basename($xmlPath, '.xml');   // e.g. Metadata_Airborne_Bali_Level_1

    // Parse: Metadata_{JenisData}_{Province}_{Level}_{N}
    $withoutPrefix = substr($basename, strlen('Metadata_'));

    // jenis_data = first segment before first underscore
    $firstUs  = strpos($withoutPrefix, '_');
    if ($firstUs === false) {
        echo "  SKIP (can't parse jenis_data): {$basename}\n";
        $err++;
        continue;
    }
    $jenisRaw = substr($withoutPrefix, 0, $firstUs);
    $rest     = substr($withoutPrefix, $firstUs + 1);

    // level_data = trailing _Level_N
    if (!preg_match('/^(.+)_Level_(\d+)$/', $rest, $m)) {
        echo "  SKIP (can't parse level): {$basename}\n";
        $err++;
        continue;
    }
    $provinsiRaw = $m[1];
    $levelNum    = $m[2];

    $jenisData = $jenisMap[$jenisRaw]   ?? $jenisRaw;
    $provinsi  = $provinceMap[$provinsiRaw] ?? $provinsiRaw;
    $levelData = "Level {$levelNum}";
    $metaLevel = $levelNum === '1' ? 'level1' : 'level2';

    echo "  Importing {$jenisData} | {$provinsi} | {$levelData} ... ";

    $rawXml = file_get_contents($xmlPath);
    if ($rawXml === false) {
        echo "ERROR: cannot read file\n";
        $err++;
        continue;
    }

    // Parse XML fields
    $data = parseMetadataXml($xmlPath);

    try {
        $stmt = $pdo->prepare(<<<SQL
INSERT INTO geoportal.dataset_metadata_xml (
    jenis_data, provinsi, level_data,
    metadata_level, source_path, file_identifier, parent_identifier,
    hierarchy_level_name, metadata_date, language_code, character_set,
    title, abstract, individual_name, organisation_name, position_name,
    voice, delivery_point, city, administrative_area, postal_code,
    country, emails_json, contact_role, raw_xml, imported_at
) VALUES (
    :jenis_data, :provinsi, :level_data,
    :metadata_level, :source_path, :file_identifier, :parent_identifier,
    :hierarchy_level_name, NULLIF(:metadata_date, '')::date, :language_code, :character_set,
    :title, :abstract, :individual_name, :organisation_name, :position_name,
    :voice, :delivery_point, :city, :administrative_area, :postal_code,
    :country, :emails_json::jsonb, :contact_role, :raw_xml::xml, now()
)
ON CONFLICT (jenis_data, provinsi, level_data) DO UPDATE SET
    metadata_level       = EXCLUDED.metadata_level,
    source_path          = EXCLUDED.source_path,
    file_identifier      = EXCLUDED.file_identifier,
    parent_identifier    = EXCLUDED.parent_identifier,
    hierarchy_level_name = EXCLUDED.hierarchy_level_name,
    metadata_date        = EXCLUDED.metadata_date,
    language_code        = EXCLUDED.language_code,
    character_set        = EXCLUDED.character_set,
    title                = EXCLUDED.title,
    abstract             = EXCLUDED.abstract,
    individual_name      = EXCLUDED.individual_name,
    organisation_name    = EXCLUDED.organisation_name,
    position_name        = EXCLUDED.position_name,
    voice                = EXCLUDED.voice,
    delivery_point       = EXCLUDED.delivery_point,
    city                 = EXCLUDED.city,
    administrative_area  = EXCLUDED.administrative_area,
    postal_code          = EXCLUDED.postal_code,
    country              = EXCLUDED.country,
    emails_json          = EXCLUDED.emails_json,
    contact_role         = EXCLUDED.contact_role,
    raw_xml              = EXCLUDED.raw_xml,
    imported_at          = now()
SQL);

        $stmt->execute([
            ':jenis_data'          => $jenisData,
            ':provinsi'            => $provinsi,
            ':level_data'          => $levelData,
            ':metadata_level'      => $metaLevel,
            ':source_path'         => $xmlPath,
            ':file_identifier'     => $data['file_identifier'],
            ':parent_identifier'   => $data['parent_identifier'],
            ':hierarchy_level_name'=> $data['hierarchy_level_name'],
            ':metadata_date'       => $data['metadata_date'],
            ':language_code'       => $data['language_code'],
            ':character_set'       => $data['character_set'],
            ':title'               => $data['title'],
            ':abstract'            => $data['abstract'],
            ':individual_name'     => $data['individual_name'],
            ':organisation_name'   => $data['organisation_name'],
            ':position_name'       => $data['position_name'],
            ':voice'               => $data['voice'],
            ':delivery_point'      => $data['delivery_point'],
            ':city'                => $data['city'],
            ':administrative_area' => $data['administrative_area'],
            ':postal_code'         => $data['postal_code'],
            ':country'             => $data['country'],
            ':emails_json'         => json_encode($data['emails'], JSON_UNESCAPED_UNICODE),
            ':contact_role'        => $data['contact_role'],
            ':raw_xml'             => $rawXml,
        ]);
        echo "OK ({$data['file_identifier']})\n";
        $ok++;
    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        $err++;
    }
}

echo "\nDone. {$ok} imported, {$err} errors.\n";

// ── XML parser ───────────────────────────────────────────────────────────────
function parseMetadataXml(string $path): array
{
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    if (!$dom->load($path)) {
        throw new RuntimeException("Invalid XML: {$path}");
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('gmd', 'http://www.isotc211.org/2005/gmd');
    $xpath->registerNamespace('gco', 'http://www.isotc211.org/2005/gco');

    $emails = [];
    $nodes  = $xpath->query('//gmd:contact//gmd:electronicMailAddress/gco:CharacterString');
    if ($nodes) {
        foreach ($nodes as $node) {
            $v = trim($node->textContent);
            if ($v !== '') $emails[] = $v;
        }
    }

    $role = trim((string) $xpath->evaluate('string((//gmd:identificationInfo//gmd:pointOfContact//gmd:CI_RoleCode/@codeListValue)[1])'));
    if ($role === '') {
        $role = trim((string) $xpath->evaluate('string((//gmd:contact//gmd:CI_RoleCode/@codeListValue)[1])'));
    }

    return [
        'file_identifier'      => trim((string) $xpath->evaluate('string(//gmd:fileIdentifier/gco:CharacterString)')),
        'parent_identifier'    => trim((string) $xpath->evaluate('string(//gmd:parentIdentifier/gco:CharacterString)')),
        'hierarchy_level_name' => trim((string) $xpath->evaluate('string(//gmd:hierarchyLevelName/gco:CharacterString)')),
        'metadata_date'        => trim((string) $xpath->evaluate('string(//gmd:dateStamp/gco:Date)')),
        'language_code'        => trim((string) $xpath->evaluate('string((//gmd:language/gmd:LanguageCode/@codeListValue)[1])')),
        'character_set'        => trim((string) $xpath->evaluate('string((//gmd:characterSet//gmd:MD_CharacterSetCode/@codeListValue)[1])')),
        'title'                => trim((string) $xpath->evaluate('string((//gmd:identificationInfo//gmd:citation//gmd:title/gco:CharacterString)[1])')),
        'abstract'             => trim((string) $xpath->evaluate('string((//gmd:identificationInfo//gmd:abstract/gco:CharacterString)[1])')),
        'individual_name'      => trim((string) $xpath->evaluate('string((//gmd:contact//gmd:individualName/gco:CharacterString)[1])')),
        'organisation_name'    => trim((string) $xpath->evaluate('string((//gmd:contact//gmd:organisationName/gco:CharacterString)[1])')),
        'position_name'        => trim((string) $xpath->evaluate('string((//gmd:contact//gmd:positionName/gco:CharacterString)[1])')),
        'voice'                => trim((string) $xpath->evaluate('string((//gmd:contact//gmd:voice/gco:CharacterString)[1])')),
        'delivery_point'       => trim((string) $xpath->evaluate('string((//gmd:contact//gmd:deliveryPoint/gco:CharacterString)[1])')),
        'city'                 => trim((string) $xpath->evaluate('string((//gmd:contact//gmd:city/gco:CharacterString)[1])')),
        'administrative_area'  => trim((string) $xpath->evaluate('string((//gmd:contact//gmd:administrativeArea/gco:CharacterString)[1])')),
        'postal_code'          => trim((string) $xpath->evaluate('string((//gmd:contact//gmd:postalCode/gco:CharacterString)[1])')),
        'country'              => trim((string) $xpath->evaluate('string((//gmd:contact//gmd:country/gco:CharacterString)[1])')),
        'emails'               => array_values(array_unique($emails)),
        'contact_role'         => $role,
    ];
}
