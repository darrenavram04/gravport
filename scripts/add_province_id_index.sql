-- =============================================================================
-- Pre-compute province_id for each data point
-- Run ONCE on the server. May take 5-20 minutes depending on table size.
-- After this completes, pull the PHP changes so queries use the index.
-- =============================================================================

-- Step 1: Materialized view so we can GiST-index the province polygons
CREATE MATERIALIZED VIEW IF NOT EXISTS geoportal.mv_province_geom AS
SELECT adm_id, adm_name, geom
FROM geoportal.polygon_adm_province;

CREATE UNIQUE INDEX IF NOT EXISTS idx_mv_prov_adm_id
    ON geoportal.mv_province_geom (adm_id);

CREATE INDEX IF NOT EXISTS idx_mv_prov_geom
    ON geoportal.mv_province_geom USING GIST (geom);

-- Step 2: Add province_id column to point tables
ALTER TABLE geoportal.faa_l1_points
    ADD COLUMN IF NOT EXISTS province_id INTEGER;

ALTER TABLE geoportal.cba_l1_points
    ADD COLUMN IF NOT EXISTS province_id INTEGER;

-- Step 3: Populate via spatial join (uses GiST index on both sides)
UPDATE geoportal.faa_l1_points p
SET province_id = prov.adm_id
FROM geoportal.mv_province_geom prov
WHERE ST_Intersects(p.geom, prov.geom)
  AND p.province_id IS NULL;

UPDATE geoportal.cba_l1_points p
SET province_id = prov.adm_id
FROM geoportal.mv_province_geom prov
WHERE ST_Intersects(p.geom, prov.geom)
  AND p.province_id IS NULL;

-- Step 4: Index + analyze
CREATE INDEX IF NOT EXISTS idx_faa_l1_province_id
    ON geoportal.faa_l1_points (province_id);

CREATE INDEX IF NOT EXISTS idx_cba_l1_province_id
    ON geoportal.cba_l1_points (province_id);

ANALYZE geoportal.faa_l1_points;
ANALYZE geoportal.cba_l1_points;

-- Verify result
SELECT 'faa_l1_points' AS tabel,
       COUNT(*) AS total,
       COUNT(province_id) AS dengan_province_id,
       COUNT(*) - COUNT(province_id) AS tanpa_province_id
FROM geoportal.faa_l1_points
UNION ALL
SELECT 'cba_l1_points',
       COUNT(*),
       COUNT(province_id),
       COUNT(*) - COUNT(province_id)
FROM geoportal.cba_l1_points;
