-- Migrate dataset_metadata_xml to composite PK (jenis_data, provinsi, level_data)
-- Run as postgres: sudo -u postgres psql -d geoportal -f scripts/migrate_metadata_schema.sql

\echo 'Step 1: Create table if not exists (new schema)...'

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
);

\echo 'Step 2: Migrate old schema if needed...'

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'geoportal'
          AND table_name   = 'dataset_metadata_xml'
          AND column_name  = 'jenis_data'
    ) THEN
        RAISE NOTICE 'Schema lama terdeteksi, menjalankan migrasi...';

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
        RAISE NOTICE 'Schema sudah up-to-date.';
    END IF;
END $$;

\echo 'Step 3: Grant privileges to geoportal_user...'

GRANT SELECT, INSERT, UPDATE, DELETE ON geoportal.dataset_metadata_xml TO geoportal_user;

\echo 'Step 4: Cek isi tabel...'

SELECT jenis_data, provinsi, level_data FROM geoportal.dataset_metadata_xml ORDER BY provinsi, jenis_data, level_data;

\echo 'Done.'
