-- ============================================================
-- MIGRATE DATA to new geoportal schema
-- ============================================================

BEGIN;

-- ─────────────────────────────────────────────────────────
-- 1. ACCOUNTS  ←  auth.users
--    Mapping: id→acc_id, full_name→acc_name, email→acc_email,
--             password_hash→acc_password
-- ─────────────────────────────────────────────────────────
INSERT INTO geoportal.accounts (acc_id, acc_name, acc_email, acc_password, created_at, is_admin)
SELECT
    id,
    LEFT(COALESCE(full_name, split_part(email,'@',1)), 20),
    email,
    password_hash,
    COALESCE(created_at::timestamptz, NOW()),
    false
FROM auth.users
ON CONFLICT (acc_email) DO NOTHING;

-- Update sequence after explicit inserts
SELECT setval(pg_get_serial_sequence('geoportal.accounts','acc_id'),
              COALESCE((SELECT MAX(acc_id) FROM geoportal.accounts), 1));

-- Mark superadmin by email
UPDATE geoportal.accounts
SET is_admin = true
WHERE acc_email = 'gravportadmin@gmail.com';

-- ─────────────────────────────────────────────────────────
-- 2. ORGANIZATIONS  ←  gravport.organizations
--    org_id=GENERATED ALWAYS → use OVERRIDING SYSTEM VALUE
-- ─────────────────────────────────────────────────────────
INSERT INTO geoportal.organizations (org_id, org_name, org_email)
OVERRIDING SYSTEM VALUE
SELECT
    organization_id,
    organization_name,
    COALESCE(organization_email, 'noemail_' || organization_id || '@placeholder.id')
FROM gravport.organizations
ON CONFLICT (org_email) DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.organizations','org_id'),
              COALESCE((SELECT MAX(org_id) FROM geoportal.organizations), 1));

-- ─────────────────────────────────────────────────────────
-- 3. SUBSCRIPTIONS  ←  gravport.subscriptions
--    Tier mapping:  old 8 (lite)→1, old 9 (pro)→2,
--                   old 10 (team)→3, others→1
--    payment_status: active→'S', else→'E'
--    payment_cycle: monthly→'M', annual→'A'
-- ─────────────────────────────────────────────────────────
INSERT INTO geoportal.subscriptions
    (subs_id, tier_id, midtrans_order_id, payment_status, payment_cycle,
     remaining_download_byte, total_acc, created_at, paid_at, end_at)
OVERRIDING SYSTEM VALUE
SELECT
    subscription_id,
    CASE tier_id
        WHEN 8  THEN 1   -- lite  → Lite
        WHEN 9  THEN 2   -- pro   → Pro
        WHEN 10 THEN 3   -- team  → Enterprise
        WHEN 2  THEN 3   -- enterprise → Enterprise
        ELSE 1
    END,
    COALESCE(payment_ref, 'MIGRATED-' || subscription_id::text),
    CASE status WHEN 'active' THEN 'S' ELSE 'E' END,
    'M',
    CASE tier_id
        WHEN 8  THEN 2147483648    -- lite: 2 GB
        WHEN 9  THEN 107374182400  -- pro: 100 GB
        ELSE         107374182400
    END,
    1,
    created_at,
    created_at,
    end_date
FROM gravport.subscriptions;

SELECT setval(pg_get_serial_sequence('geoportal.subscriptions','subs_id'),
              COALESCE((SELECT MAX(subs_id) FROM geoportal.subscriptions), 1));

-- Link accounts to their subscriptions
-- user_id in old subscriptions maps to acc_id in new accounts
UPDATE geoportal.accounts a
SET subs_id = s.subs_id
FROM gravport.subscriptions gs
JOIN geoportal.subscriptions s ON s.midtrans_order_id = COALESCE(gs.payment_ref, 'MIGRATED-' || gs.subscription_id::text)
WHERE a.acc_id = gs.user_id;

-- ─────────────────────────────────────────────────────────
-- 4. DATASETS  ←  synthetic from gravity data groups
--    FAA L1, CBA L1, FAA L2 raster, CBA L2 raster
--    geom = ST_Multi(ST_ConvexHull(ST_Collect(all points)))
-- ─────────────────────────────────────────────────────────
INSERT INTO geoportal.datasets_grav_anom (dataset_name, acc_id, dataset_level, dataset_anom_type, geom)
OVERRIDING SYSTEM VALUE
SELECT
    'FAA Level 1 - Jawa Bali',
    (SELECT acc_id FROM geoportal.accounts ORDER BY acc_id LIMIT 1),
    1,
    'FAA',
    ST_Multi(ST_ConvexHull(ST_Collect(geom)))
FROM gravport.anomaly_gravity_point_data
WHERE point_anom_type = 'FAA' AND data_level = 1;

INSERT INTO geoportal.datasets_grav_anom (dataset_name, acc_id, dataset_level, dataset_anom_type, geom)
OVERRIDING SYSTEM VALUE
SELECT
    'CBA Level 1 - Jawa Bali',
    (SELECT acc_id FROM geoportal.accounts ORDER BY acc_id LIMIT 1),
    1,
    'CBA',
    ST_Multi(ST_ConvexHull(ST_Collect(geom)))
FROM gravport.anomaly_gravity_point_data
WHERE point_anom_type = 'CBA' AND data_level = 1;

INSERT INTO geoportal.datasets_grav_anom (dataset_name, acc_id, dataset_level, dataset_anom_type, geom)
OVERRIDING SYSTEM VALUE
SELECT
    'FAA Level 2 Raster - Jawa Bali',
    (SELECT acc_id FROM geoportal.accounts ORDER BY acc_id LIMIT 1),
    2,
    'FAA',
    ST_Multi(ST_ConvexHull(ST_Collect(grid_geom)))
FROM gravport.raster_tiles rt
JOIN gravport.anomaly_gravity_raster_data ard ON ard.raster_id = rt.raster_id
WHERE ard.raster_anom_type = 'FAA';

INSERT INTO geoportal.datasets_grav_anom (dataset_name, acc_id, dataset_level, dataset_anom_type, geom)
OVERRIDING SYSTEM VALUE
SELECT
    'CBA Level 2 Raster - Jawa Bali',
    (SELECT acc_id FROM geoportal.accounts ORDER BY acc_id LIMIT 1),
    2,
    'CBA',
    ST_Multi(ST_ConvexHull(ST_Collect(grid_geom)))
FROM gravport.raster_tiles rt
JOIN gravport.anomaly_gravity_raster_data ard ON ard.raster_id = rt.raster_id
WHERE ard.raster_anom_type = 'CBA';

-- ─────────────────────────────────────────────────────────
-- 5. GRAVITY POINTS  ←  gravport.anomaly_gravity_point_data
--    628,403 rows — maps point_anom_type+data_level → dataset_id
-- ─────────────────────────────────────────────────────────
INSERT INTO geoportal.point_grav_anom (dataset_id, point_value, point_obs_type, geom)
SELECT
    d.dataset_id,
    ROUND(p.point_value::numeric, 3),
    p.point_obs_type,
    p.geom
FROM gravport.anomaly_gravity_point_data p
JOIN geoportal.datasets_grav_anom d
    ON d.dataset_anom_type = p.point_anom_type
    AND d.dataset_level    = p.data_level
WHERE p.status = 'active';

-- ─────────────────────────────────────────────────────────
-- 6. RASTER DATA  ←  gravport.raster_tiles + anomaly_gravity_raster_data
--    9,588 raster tiles
-- ─────────────────────────────────────────────────────────
INSERT INTO geoportal.raster_grav_anom (dataset_id, raster_resolution_deg, rast)
SELECT
    d.dataset_id,
    COALESCE(ard.raster_resolution::real, 0.01),
    rt.rast
FROM gravport.raster_tiles rt
JOIN gravport.anomaly_gravity_raster_data ard ON ard.raster_id = rt.raster_id
JOIN geoportal.datasets_grav_anom d
    ON d.dataset_anom_type = ard.raster_anom_type
    AND d.dataset_level    = ard.data_level;

COMMIT;
