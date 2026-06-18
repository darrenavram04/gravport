-- Fix province spatial query performance
-- Jalankan: sudo -u postgres psql -d geoportal -f /var/www/geoportal/scripts/fix_province_spatial.sql

CREATE INDEX IF NOT EXISTS idx_province_geom
    ON geoportal.polygon_adm_province USING GIST (geom);

-- Verify
SELECT schemaname, tablename, indexname
FROM pg_indexes
WHERE tablename IN ('polygon_adm_province', 'faa_l1_points', 'cba_l1_points')
  AND indexdef ILIKE '%gist%'
ORDER BY tablename, indexname;
