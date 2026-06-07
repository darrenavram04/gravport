-- =============================================================
-- GRAVPORT — Schema Migration
-- Target : database `gravport`, schema `gravport`
-- Source : `gravport`.`testing` (data), existing user data
-- Run    : psql -h 127.0.0.1 -p 5433 -U postgres -d gravport
--              -f migrate_to_gravport_schema.sql
-- =============================================================

BEGIN;

-- ─────────────────────────────────────────────────────────────
-- 0. EXTENSIONS
-- ─────────────────────────────────────────────────────────────
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS postgis_raster;

-- ─────────────────────────────────────────────────────────────
-- 1. SCHEMA
-- ─────────────────────────────────────────────────────────────
CREATE SCHEMA IF NOT EXISTS gravport;

-- ─────────────────────────────────────────────────────────────
-- 2. ORGANIZATIONS
--    ERD : organization_id, organization_name, organization_email
--    Tambahan : org_type, created_at
-- ─────────────────────────────────────────────────────────────
CREATE TABLE gravport.organizations (
    organization_id    BIGSERIAL    PRIMARY KEY,
    organization_name  TEXT         NOT NULL,
    organization_email TEXT         UNIQUE,
    org_type           VARCHAR(30)  NOT NULL DEFAULT 'data_partner'
        CHECK (org_type IN (
            'data_partner',    -- BIG, Badan Geologi, BMKG, BRIN (kontributor data)
            'subscriber_gov',  -- instansi pemerintah proyek berbayar
            'subscriber_com',  -- tambang, oil & gas
            'subscriber_edu',  -- universitas / akademik
            'internal'         -- tim Gravport sendiri
        )),
    created_at         TIMESTAMPTZ  NOT NULL DEFAULT now()
);

INSERT INTO gravport.organizations (organization_name, organization_email, org_type)
VALUES
    ('Badan Informasi Geospasial (BIG)', 'info@big.go.id',         'data_partner'),
    ('Badan Geologi ESDM',               'info@geologi.esdm.go.id','data_partner'),
    ('Gravport Internal',                'admin@gravport.id',       'internal');

-- ─────────────────────────────────────────────────────────────
-- 3. USERS
--    ERD : user_id, user_name, user_email, password_hash,
--          date_created, role, is_active, organization_id
--    Tambahan : updated_at
-- ─────────────────────────────────────────────────────────────
CREATE TABLE gravport.users (
    user_id         BIGSERIAL    PRIMARY KEY,
    organization_id BIGINT       REFERENCES gravport.organizations(organization_id)
                                 ON DELETE SET NULL,
    user_name       VARCHAR(80)  NOT NULL,
    user_email      VARCHAR(160) NOT NULL UNIQUE,
    password_hash   TEXT         NOT NULL,
    role            VARCHAR(20)  NOT NULL DEFAULT 'user'
        CHECK (role IN ('superadmin', 'admin', 'user')),
    is_active       BOOLEAN      NOT NULL DEFAULT true,
    date_created    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ
);

INSERT INTO gravport.users
    (user_name, user_email, password_hash, role, is_active, date_created, organization_id)
VALUES
    ('superadmin',
     'admin@gravport.test',
     '$2y$10$96GRUbSLX64NVoCvQal0auM7G/p9WA5W9jMuFF3t.HddlIGqMdieS',
     'superadmin', true, '2026-01-29 17:19:49',
     (SELECT organization_id FROM gravport.organizations WHERE org_type = 'internal')),
    ('client_demo',
     'client@gravport.test',
     '$2y$10$FnhSIny.TfhMdCArSjIXkuFrakffM7Jr45h4qRU0ugOhsb5xoK1rS',
     'user', true, '2026-01-29 17:19:49',
     NULL),
    ('Chan Waltz',
     'chanwaltz0404@gmail.com',
     '$2a$12$PA4CXYCgTgEYBYrDTYI0juogsZt54vxZN1mOq.A.ov4WZfpu/soqG',
     'user', true, '2026-05-09 19:29:03',
     NULL);

-- ─────────────────────────────────────────────────────────────
-- 4. LAND ADMINISTRATIVE AREA  (filter level: nasional & provinsi)
--    ERD : adm_id, adm_name, geom
--    Tambahan : adm_level, adm_code (kode BPS)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE gravport.land_administrative_areas (
    adm_id    BIGSERIAL   PRIMARY KEY,
    adm_name  TEXT        NOT NULL,
    adm_level SMALLINT    NOT NULL DEFAULT 1
        CHECK (adm_level IN (0, 1)),
        -- 0 = nasional, 1 = provinsi
    adm_code  VARCHAR(10),   -- kode BPS (misal '32' = Jawa Barat)
    geom      geometry(MultiPolygon, 4326)
);

CREATE INDEX ON gravport.land_administrative_areas USING GIST (geom);
CREATE INDEX ON gravport.land_administrative_areas (adm_level);

-- Seed provinsi di Jawa-Bali (boundary diisi manual / dari shapefile)
INSERT INTO gravport.land_administrative_areas (adm_name, adm_level, adm_code)
VALUES
    ('Indonesia',          0, '00'),
    ('DKI Jakarta',        1, '31'),
    ('Jawa Barat',         1, '32'),
    ('Jawa Tengah',        1, '33'),
    ('DI Yogyakarta',      1, '34'),
    ('Jawa Timur',         1, '35'),
    ('Banten',             1, '36'),
    ('Bali',               1, '51');

-- ─────────────────────────────────────────────────────────────
-- 5. ANOMALY GRAVITY POINT DATA
--    ERD : point_id, organization_id, point_value, point_anom_type,
--          point_obs_type, point_metadata, geom
--    Tambahan : data_level, source_file, created_by, created_at, status
-- ─────────────────────────────────────────────────────────────
CREATE TABLE gravport.anomaly_gravity_point_data (
    point_id        BIGSERIAL        PRIMARY KEY,
    organization_id BIGINT           NOT NULL
                    REFERENCES gravport.organizations(organization_id)
                    ON DELETE RESTRICT,
    point_value     DOUBLE PRECISION NOT NULL,
    point_anom_type VARCHAR(10)      NOT NULL
        CHECK (point_anom_type IN ('FAA', 'CBA', 'SBA', 'BA', 'RAW')),
        -- FAA=Free-Air Anomaly, CBA=Complete Bouguer Anomaly,
        -- SBA=Simple Bouguer, BA=Bouguer, RAW=observed/absolute
    point_obs_type  VARCHAR(20)      NOT NULL DEFAULT 'terrestrial'
        CHECK (point_obs_type IN ('airborne', 'terrestrial', 'satellite')),
    data_level      SMALLINT         NOT NULL DEFAULT 1
        CHECK (data_level IN (1, 2)),
        -- 1 = scattered raw, 2 = processed/corrected
    point_metadata  JSONB,
    source_file     TEXT,
    geom            geometry(Point, 4326) NOT NULL,
    created_by      BIGINT  REFERENCES gravport.users(user_id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT now(),
    status          VARCHAR(20)  NOT NULL DEFAULT 'active'
        CHECK (status IN ('active', 'archived', 'deprecated'))
);

CREATE INDEX ON gravport.anomaly_gravity_point_data USING GIST (geom);
CREATE INDEX ON gravport.anomaly_gravity_point_data (point_anom_type);
CREATE INDEX ON gravport.anomaly_gravity_point_data (organization_id);
CREATE INDEX ON gravport.anomaly_gravity_point_data (data_level);
CREATE INDEX ON gravport.anomaly_gravity_point_data (status);

-- ─────────────────────────────────────────────────────────────
-- 6. ANOMALY GRAVITY RASTER DATA  (metadata per dataset)
--    ERD : raster_id, organization_id, raster_resolution,
--          raster_metadata, raster_path
--    Tambahan : raster_anom_type (TIDAK ADA di ERD — wajib ditambah),
--               data_level, tile_count, source_file, created_by, status
-- ─────────────────────────────────────────────────────────────
CREATE TABLE gravport.anomaly_gravity_raster_data (
    raster_id         BIGSERIAL   PRIMARY KEY,
    organization_id   BIGINT      NOT NULL
                      REFERENCES gravport.organizations(organization_id)
                      ON DELETE RESTRICT,
    raster_anom_type  VARCHAR(10) NOT NULL
        CHECK (raster_anom_type IN ('FAA', 'CBA', 'SBA', 'BA')),
    raster_resolution NUMERIC,       -- resolusi dalam arc-second
    raster_metadata   JSONB,
    raster_path       TEXT,          -- path file; NULL jika disimpan in-DB
    data_level        SMALLINT    NOT NULL DEFAULT 2
        CHECK (data_level IN (1, 2)),
    tile_count        INTEGER,       -- jumlah tile PostGIS (untuk in-DB)
    source_file       TEXT,
    created_by        BIGINT  REFERENCES gravport.users(user_id) ON DELETE SET NULL,
    created_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
    status            VARCHAR(20) NOT NULL DEFAULT 'active'
        CHECK (status IN ('active', 'archived', 'deprecated'))
);

CREATE INDEX ON gravport.anomaly_gravity_raster_data (raster_anom_type);
CREATE INDEX ON gravport.anomaly_gravity_raster_data (organization_id);

-- ─────────────────────────────────────────────────────────────
-- 7. RASTER TILES  (PostGIS raster — in-DB storage)
--    Tabel bantu; satu metadata record bisa punya ribuan tiles.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE gravport.raster_tiles (
    tile_id   BIGSERIAL PRIMARY KEY,
    raster_id BIGINT    NOT NULL
              REFERENCES gravport.anomaly_gravity_raster_data(raster_id)
              ON DELETE CASCADE,
    rast      RASTER    NOT NULL,
    grid_geom geometry(Polygon, 4326)
);

CREATE INDEX ON gravport.raster_tiles USING GIST (grid_geom);
CREATE INDEX ON gravport.raster_tiles (raster_id);

-- ─────────────────────────────────────────────────────────────
-- 8. JUNCTION TABLES  (relasi M-M "Contains" dari ERD)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE gravport.point_administrative_areas (
    point_id BIGINT NOT NULL
             REFERENCES gravport.anomaly_gravity_point_data(point_id)
             ON DELETE CASCADE,
    adm_id   BIGINT NOT NULL
             REFERENCES gravport.land_administrative_areas(adm_id)
             ON DELETE CASCADE,
    PRIMARY KEY (point_id, adm_id)
);

CREATE TABLE gravport.raster_administrative_areas (
    raster_id BIGINT NOT NULL
              REFERENCES gravport.anomaly_gravity_raster_data(raster_id)
              ON DELETE CASCADE,
    adm_id    BIGINT NOT NULL
              REFERENCES gravport.land_administrative_areas(adm_id)
              ON DELETE CASCADE,
    PRIMARY KEY (raster_id, adm_id)
);

-- ─────────────────────────────────────────────────────────────
-- 9. STAGING POINT  (admin upload → antri review superadmin)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE gravport.staging_gravity_points (
    staged_point_id  BIGSERIAL    PRIMARY KEY,
    organization_id  BIGINT       NOT NULL
                     REFERENCES gravport.organizations(organization_id)
                     ON DELETE RESTRICT,
    point_value      DOUBLE PRECISION NOT NULL,
    point_anom_type  VARCHAR(10)  NOT NULL
        CHECK (point_anom_type IN ('FAA', 'CBA', 'SBA', 'BA', 'RAW')),
    point_obs_type   VARCHAR(20)  NOT NULL DEFAULT 'terrestrial'
        CHECK (point_obs_type IN ('airborne', 'terrestrial', 'satellite')),
    data_level       SMALLINT     NOT NULL DEFAULT 1
        CHECK (data_level IN (1, 2)),
    point_metadata   JSONB,
    source_file      TEXT,
    geom             geometry(Point, 4326) NOT NULL,
    -- Workflow
    staged_by        BIGINT       NOT NULL
                     REFERENCES gravport.users(user_id) ON DELETE RESTRICT,
    staged_at        TIMESTAMPTZ  NOT NULL DEFAULT now(),
    review_status    VARCHAR(20)  NOT NULL DEFAULT 'pending'
        CHECK (review_status IN ('pending', 'approved', 'rejected')),
    reviewed_by      BIGINT  REFERENCES gravport.users(user_id) ON DELETE SET NULL,
    reviewed_at      TIMESTAMPTZ,
    review_notes     TEXT
);

CREATE INDEX ON gravport.staging_gravity_points USING GIST (geom);
CREATE INDEX ON gravport.staging_gravity_points (review_status);
CREATE INDEX ON gravport.staging_gravity_points (staged_by);

-- ─────────────────────────────────────────────────────────────
-- 10. STAGING RASTER
-- ─────────────────────────────────────────────────────────────
CREATE TABLE gravport.staging_gravity_rasters (
    staged_raster_id  BIGSERIAL   PRIMARY KEY,
    organization_id   BIGINT      NOT NULL
                      REFERENCES gravport.organizations(organization_id)
                      ON DELETE RESTRICT,
    raster_anom_type  VARCHAR(10) NOT NULL
        CHECK (raster_anom_type IN ('FAA', 'CBA', 'SBA', 'BA')),
    raster_resolution NUMERIC,
    raster_metadata   JSONB,
    raster_path       TEXT,
    data_level        SMALLINT    NOT NULL DEFAULT 2
        CHECK (data_level IN (1, 2)),
    source_file       TEXT,
    -- Workflow
    staged_by         BIGINT      NOT NULL
                      REFERENCES gravport.users(user_id) ON DELETE RESTRICT,
    staged_at         TIMESTAMPTZ NOT NULL DEFAULT now(),
    review_status     VARCHAR(20) NOT NULL DEFAULT 'pending'
        CHECK (review_status IN ('pending', 'approved', 'rejected')),
    reviewed_by       BIGINT  REFERENCES gravport.users(user_id) ON DELETE SET NULL,
    reviewed_at       TIMESTAMPTZ,
    review_notes      TEXT
);

CREATE INDEX ON gravport.staging_gravity_rasters (review_status);
CREATE INDEX ON gravport.staging_gravity_rasters (staged_by);

-- ─────────────────────────────────────────────────────────────
-- 11. MIGRATE DATA : testing → gravport
-- ─────────────────────────────────────────────────────────────

-- 11a. Metadata record per source_file untuk raster CBA
INSERT INTO gravport.anomaly_gravity_raster_data
    (organization_id, raster_anom_type, raster_metadata,
     data_level, tile_count, source_file, created_by, status)
SELECT
    (SELECT organization_id FROM gravport.organizations
     WHERE organization_name LIKE '%BIG%'),
    'CBA',
    jsonb_build_object('migrated_from', 'testing.cba_l2_raster',
                       'migration_date', now()::text),
    2,
    COUNT(*),
    source_file,
    (SELECT user_id FROM gravport.users WHERE role = 'superadmin' LIMIT 1),
    'active'
FROM testing.cba_l2_raster
GROUP BY source_file;

-- 11b. Metadata record per source_file untuk raster FAA
INSERT INTO gravport.anomaly_gravity_raster_data
    (organization_id, raster_anom_type, raster_metadata,
     data_level, tile_count, source_file, created_by, status)
SELECT
    (SELECT organization_id FROM gravport.organizations
     WHERE organization_name LIKE '%BIG%'),
    'FAA',
    jsonb_build_object('migrated_from', 'testing.faa_l2_raster',
                       'migration_date', now()::text),
    2,
    COUNT(*),
    source_file,
    (SELECT user_id FROM gravport.users WHERE role = 'superadmin' LIMIT 1),
    'active'
FROM testing.faa_l2_raster
GROUP BY source_file;

-- 11c. Migrate tiles CBA
INSERT INTO gravport.raster_tiles (raster_id, rast, grid_geom)
SELECT rd.raster_id, t.rast, t.grid_geom
FROM testing.cba_l2_raster t
JOIN gravport.anomaly_gravity_raster_data rd
    ON rd.raster_anom_type = 'CBA' AND rd.source_file = t.source_file;

-- 11d. Migrate tiles FAA
INSERT INTO gravport.raster_tiles (raster_id, rast, grid_geom)
SELECT rd.raster_id, t.rast, t.grid_geom
FROM testing.faa_l2_raster t
JOIN gravport.anomaly_gravity_raster_data rd
    ON rd.raster_anom_type = 'FAA' AND rd.source_file = t.source_file;

-- 11e. Migrate CBA Level 1 points (210,262 rows)
--      'terestris' (ejaan Indonesia) → 'terrestrial'
INSERT INTO gravport.anomaly_gravity_point_data
    (organization_id, point_value, point_anom_type, point_obs_type,
     data_level, point_metadata, source_file, geom, created_at, status)
SELECT
    (SELECT organization_id FROM gravport.organizations
     WHERE organization_name LIKE '%BIG%'),
    anomaly_value,
    'CBA',
    CASE survey_mode
        WHEN 'airborne'   THEN 'airborne'
        WHEN 'terestris'  THEN 'terrestrial'
        ELSE                   'terrestrial'   -- fallback
    END,
    1,
    jsonb_build_object(
        'orthometric_height', orthometric_height,
        'latitude',           latitude,
        'longitude',          longitude,
        'migrated_from',      'testing.cba_l1_points'
    ),
    source_file,
    geom,
    imported_at,
    'active'
FROM testing.cba_l1_points;

-- 11f. Migrate FAA Level 1 points (418,110 rows)
INSERT INTO gravport.anomaly_gravity_point_data
    (organization_id, point_value, point_anom_type, point_obs_type,
     data_level, point_metadata, source_file, geom, created_at, status)
SELECT
    (SELECT organization_id FROM gravport.organizations
     WHERE organization_name LIKE '%BIG%'),
    anomaly_value,
    'FAA',
    CASE survey_mode
        WHEN 'airborne'   THEN 'airborne'
        WHEN 'terestris'  THEN 'terrestrial'
        ELSE                   'terrestrial'
    END,
    1,
    jsonb_build_object(
        'orthometric_height', orthometric_height,
        'latitude',           latitude,
        'longitude',          longitude,
        'migrated_from',      'testing.faa_l1_points'
    ),
    source_file,
    geom,
    imported_at,
    'active'
FROM testing.faa_l1_points;

-- ─────────────────────────────────────────────────────────────
-- 12. VERIFIKASI
-- ─────────────────────────────────────────────────────────────
DO $$
DECLARE
    v_orgs    INT; v_users  INT; v_areas INT;
    v_pts     INT; v_raster INT; v_tiles INT;
    v_stg_pt  INT; v_stg_rs INT;
BEGIN
    SELECT COUNT(*) INTO v_orgs    FROM gravport.organizations;
    SELECT COUNT(*) INTO v_users   FROM gravport.users;
    SELECT COUNT(*) INTO v_areas   FROM gravport.land_administrative_areas;
    SELECT COUNT(*) INTO v_pts     FROM gravport.anomaly_gravity_point_data;
    SELECT COUNT(*) INTO v_raster  FROM gravport.anomaly_gravity_raster_data;
    SELECT COUNT(*) INTO v_tiles   FROM gravport.raster_tiles;
    SELECT COUNT(*) INTO v_stg_pt  FROM gravport.staging_gravity_points;
    SELECT COUNT(*) INTO v_stg_rs  FROM gravport.staging_gravity_rasters;

    RAISE NOTICE '=========== MIGRATION VERIFICATION ===========';
    RAISE NOTICE 'organizations                  : %', v_orgs;
    RAISE NOTICE 'users                          : %', v_users;
    RAISE NOTICE 'land_administrative_areas      : %', v_areas;
    RAISE NOTICE 'anomaly_gravity_point_data     : %', v_pts;
    RAISE NOTICE 'anomaly_gravity_raster_data    : %', v_raster;
    RAISE NOTICE 'raster_tiles                   : %', v_tiles;
    RAISE NOTICE 'staging_gravity_points (kosong): %', v_stg_pt;
    RAISE NOTICE 'staging_gravity_rasters(kosong): %', v_stg_rs;
    RAISE NOTICE '===============================================';

    IF v_pts < 600000 THEN
        RAISE EXCEPTION 'Point migration tidak lengkap: % baris (expected ~628K)', v_pts;
    END IF;
    IF v_tiles < 9000 THEN
        RAISE EXCEPTION 'Tile migration tidak lengkap: % tiles (expected ~9.5K)', v_tiles;
    END IF;

    RAISE NOTICE 'Migration SELESAI dan terverifikasi.';
END $$;

COMMIT;
