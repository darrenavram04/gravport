-- ============================================================
-- GravPort – Geoportal Schema: PostGIS Migration
-- Run with:
--   psql -U postgres -p 5433 -d geoportal \
--        -c "SET search_path TO geoportal,public;" \
--        -f migration_geoportal_postgis.sql
-- ============================================================

-- 0. Ensure public schema exists (required for PostGIS installation)
CREATE SCHEMA IF NOT EXISTS public;
SET search_path TO geoportal, public;

-- 1. Install PostGIS extensions
CREATE EXTENSION IF NOT EXISTS postgis SCHEMA public;
CREATE EXTENSION IF NOT EXISTS postgis_raster SCHEMA public;

-- 2. Add coordinate columns to point_grav_anom
ALTER TABLE geoportal.point_grav_anom
    ADD COLUMN IF NOT EXISTS lat         double precision,
    ADD COLUMN IF NOT EXISTS lon         double precision,
    ADD COLUMN IF NOT EXISTS geom        geometry(Point, 4326),
    ADD COLUMN IF NOT EXISTS survey_mode text DEFAULT 'terrestrial';

-- Index for spatial queries
CREATE INDEX IF NOT EXISTS idx_point_grav_anom_geom
    ON geoportal.point_grav_anom USING GIST (geom);

-- 3. Add coordinate columns to staging_gravity_points
ALTER TABLE geoportal.staging_gravity_points
    ADD COLUMN IF NOT EXISTS lat  double precision,
    ADD COLUMN IF NOT EXISTS lon  double precision,
    ADD COLUMN IF NOT EXISTS geom geometry(Point, 4326);

-- 4. Add polygon geometry to land_administrative_areas
ALTER TABLE geoportal.land_administrative_areas
    ADD COLUMN IF NOT EXISTS geom geometry(MultiPolygon, 4326);

CREATE INDEX IF NOT EXISTS idx_adm_geom
    ON geoportal.land_administrative_areas USING GIST (geom);

-- 5. Populate geom from lat/lon in point_grav_anom
--    (Run after data is re-imported with lat/lon values)
-- UPDATE geoportal.point_grav_anom
--     SET geom = ST_SetSRID(ST_MakePoint(lon, lat), 4326)
-- WHERE lat IS NOT NULL AND lon IS NOT NULL AND geom IS NULL;

-- 6. Province boundary view (level 1 = provinsi)
CREATE OR REPLACE VIEW geoportal.polygon_adm_province AS
SELECT adm_id, adm_name, adm_code, geom
FROM geoportal.land_administrative_areas
WHERE adm_level = 1;

-- 7. NOTE: geoportal.faa_l1_points, cba_l1_points, faa_l2_raster, cba_l2_raster
--    are created automatically by DatasetImportService when importing a data package.
--    Do NOT create them manually here — the import service TRUNCATES them on re-import
--    and expects to own their schema.

DO $$
BEGIN
    RAISE NOTICE 'Migration complete. Next steps:';
    RAISE NOTICE '1. Run: php spark import:provinces   (imports 2. polygon_adm_provinces.geojson)';
    RAISE NOTICE '2. Re-import gravity CSV data with lat/lon columns populated.';
    RAISE NOTICE '3. Run: UPDATE geoportal.point_grav_anom SET geom = ST_SetSRID(ST_MakePoint(lon, lat), 4326) WHERE lat IS NOT NULL AND lon IS NOT NULL AND geom IS NULL;';
END $$;
