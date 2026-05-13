-- Setup testing schema untuk WebMap GravPort
-- Jalankan: sudo -u postgres psql -d geoportal -f /var/www/geoportal/build/setup_testing_schema.sql

CREATE SCHEMA IF NOT EXISTS testing;
GRANT ALL ON SCHEMA testing TO geoportal_user;

-- FAA Level 1 points
CREATE TABLE IF NOT EXISTS testing.faa_l1_points (
    id              SERIAL PRIMARY KEY,
    latitude        DOUBLE PRECISION,
    longitude       DOUBLE PRECISION,
    orthometric_height DOUBLE PRECISION,
    anomaly_value   DOUBLE PRECISION,
    source_file     TEXT,
    survey_mode     TEXT,
    geom            geometry(Point, 4326)
);
CREATE INDEX IF NOT EXISTS idx_faa_l1_geom ON testing.faa_l1_points USING GIST (geom);

-- CBA Level 1 points
CREATE TABLE IF NOT EXISTS testing.cba_l1_points (
    id              SERIAL PRIMARY KEY,
    latitude        DOUBLE PRECISION,
    longitude       DOUBLE PRECISION,
    orthometric_height DOUBLE PRECISION,
    anomaly_value   DOUBLE PRECISION,
    source_file     TEXT,
    survey_mode     TEXT,
    geom            geometry(Point, 4326)
);
CREATE INDEX IF NOT EXISTS idx_cba_l1_geom ON testing.cba_l1_points USING GIST (geom);

-- FAA Level 2 raster
CREATE TABLE IF NOT EXISTS testing.faa_l2_raster (
    rid             SERIAL PRIMARY KEY,
    rast            raster,
    grid_geom       geometry(Polygon, 4326)
);
CREATE INDEX IF NOT EXISTS idx_faa_l2_geom ON testing.faa_l2_raster USING GIST (grid_geom);

-- CBA Level 2 raster
CREATE TABLE IF NOT EXISTS testing.cba_l2_raster (
    rid             SERIAL PRIMARY KEY,
    rast            raster,
    grid_geom       geometry(Polygon, 4326)
);
CREATE INDEX IF NOT EXISTS idx_cba_l2_geom ON testing.cba_l2_raster USING GIST (grid_geom);

-- AOI / Province boundary
CREATE TABLE IF NOT EXISTS testing."AOI Jawa_Bali" (
    id              SERIAL PRIMARY KEY,
    name_1          TEXT,
    gid_1           TEXT,
    country         TEXT,
    geom            geometry(MultiPolygon, 4326)
);
CREATE INDEX IF NOT EXISTS idx_aoi_geom ON testing."AOI Jawa_Bali" USING GIST (geom);

-- Grant permissions
GRANT ALL ON ALL TABLES IN SCHEMA testing TO geoportal_user;
GRANT ALL ON ALL SEQUENCES IN SCHEMA testing TO geoportal_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA testing GRANT ALL ON TABLES TO geoportal_user;

\echo 'Testing schema setup selesai!'
