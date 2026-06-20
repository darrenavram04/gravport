<?php

/**
 * Migrate dataset_metadata_xml table to composite PK (jenis_data, provinsi, level_data).
 * Safe to run multiple times (idempotent).
 *
 * Usage (from geoportal root):
 *   php scripts/migrate_metadata_schema.php
 */

define('GEOPORTAL_ROOT', dirname(__DIR__));

// ── Read DB credentials from .env (overrides) then fall back to Config/Database.php defaults ──
$cfg = [
    'host'   => 'localhost',
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
        [$key, $val] = array_map('trim', explode('=', $line, 2));
        $val = trim($val, '"\'');
        match ($key) {
            'database.default.hostname' => $cfg['host']   = $val,
            'database.default.port'     => $cfg['port']   = (int) $val,
            'database.default.database' => $cfg['dbname'] = $val,
            'database.default.username' => $cfg['user']   = $val,
            'database.default.password' => $cfg['pass']   = $val,
            default                     => null,
        };
    }
}

echo "Connecting to {$cfg['dbname']}@{$cfg['host']}:{$cfg['port']} as {$cfg['user']} ...\n";

$dsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']}";
try {
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo "ERROR: Koneksi gagal — " . $e->getMessage() . "\n";
    exit(1);
}
echo "OK.\n\n";

// ── Step 1: Create table if not exists (new schema) ─────────────────────────
echo "Step 1: Pastikan tabel ada dengan schema baru ...\n";
$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS geoportal.dataset_metadata_xml (
    jenis_data           text NOT NULL,
    provinsi             text NOT NULL,
    level_data           text NOT NULL,
    dataset_code         text,
    metadata_level       text,
    source_path          text NOT NULL DEFAULT '',
    file_identifier      text,
    parent_identifier    text,
    hierarchy_level_name text,
    metadata_date        date,
    language_code        text,
    character_set        text,
    title                text,
    abstract             text,
    individual_name      text,
    organisation_name    text,
    position_name        text,
    voice                text,
    delivery_point       text,
    city                 text,
    administrative_area  text,
    postal_code          text,
    country              text,
    emails_json          jsonb,
    contact_role         text,
    raw_xml              xml NOT NULL DEFAULT '<empty/>',
    imported_at          timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (jenis_data, provinsi, level_data)
)
SQL);
echo "OK.\n\n";

// ── Step 2: Migrate old schema if needed ─────────────────────────────────────
echo "Step 2: Migrasi schema lama (jika perlu) ...\n";
$pdo->exec(<<<SQL
DO \$\$
BEGIN
    -- Only run if jenis_data column is missing (old schema)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'geoportal'
          AND table_name   = 'dataset_metadata_xml'
          AND column_name  = 'jenis_data'
    ) THEN
        RAISE NOTICE 'Schema lama terdeteksi — menjalankan migrasi...';

        ALTER TABLE geoportal.dataset_metadata_xml
            ADD COLUMN jenis_data text,
            ADD COLUMN provinsi   text,
            ADD COLUMN level_data text;

        DELETE FROM geoportal.dataset_metadata_xml;

        ALTER TABLE geoportal.dataset_metadata_xml
            DROP CONSTRAINT IF EXISTS dataset_metadata_xml_pkey;

        ALTER TABLE geoportal.dataset_metadata_xml
            ALTER COLUMN dataset_code DROP NOT NULL,
            ALTER COLUMN jenis_data   SET NOT NULL,
            ALTER COLUMN provinsi     SET NOT NULL,
            ALTER COLUMN level_data   SET NOT NULL;

        ALTER TABLE geoportal.dataset_metadata_xml
            ADD PRIMARY KEY (jenis_data, provinsi, level_data);

        RAISE NOTICE 'Migrasi selesai.';
    ELSE
        RAISE NOTICE 'Schema sudah up-to-date, tidak ada yang perlu dimigrasi.';
    END IF;
END \$\$
SQL);
echo "OK.\n\n";

// ── Step 3: Report current state ─────────────────────────────────────────────
echo "Step 3: Cek isi tabel ...\n";
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM geoportal.dataset_metadata_xml");
$total = (int) $stmt->fetchColumn();

if ($total === 0) {
    echo "Tabel kosong — belum ada metadata yang diimport.\n";
    echo "Selanjutnya: upload 28 XML via Admin Hub → Upload Metadata XML.\n";
} else {
    echo "Tabel berisi {$total} baris:\n";
    $rows = $pdo->query(
        "SELECT jenis_data, provinsi, level_data FROM geoportal.dataset_metadata_xml ORDER BY provinsi, jenis_data, level_data"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "  {$r['jenis_data']} | {$r['provinsi']} | {$r['level_data']}\n";
    }
}

echo "\nDone.\n";
