-- ============================================================
-- MIGRASI FINAL: gravport.* + auth.* → geoportal.*
-- Semua dalam satu database: geoportal
-- Idempotent: aman dijalankan ulang (ON CONFLICT DO NOTHING)
-- ============================================================
-- URUTAN EKSEKUSI (parent → child):
--   1. subscriptions_tier  (master, no FK)
--   2. accounts            (no FK)
--   3. subscriptions       (FK: tier_id → subscriptions_tier)
--   4. organizations       (FK: subs_id → subscriptions)
--   5. accounts UPDATE     (link subs_id, org_id)
--   6. datasets_grav_anom  (FK: acc_id → accounts)
--   7. point_grav_anom     (FK: dataset_id → datasets_grav_anom)
--   8. raster_grav_anom    (FK: dataset_id → datasets_grav_anom)
--   9. proposed_data       (FK: acc_id → accounts — kosong)
-- ============================================================

BEGIN;

-- ─────────────────────────────────────────────────────────
-- 1. SUBSCRIPTIONS_TIER
--    Sumber: gravport.subscription_tiers (is_active=true)
--    Pemetaan tier_id: lite(8)→1, pro(9)→2, team(10)→3
--    Seed awal sudah ada (Lite=1,Pro=2,Enterprise=3)
-- ─────────────────────────────────────────────────────────
INSERT INTO geoportal.subscriptions_tier
    (tier_id, tier_name, price_monthly, price_annualy, max_acc, download_quota_byte)
VALUES
    (1, 'Lite',        99000,  399000,  1, 2147483648),
    (2, 'Pro',        199000,  699000,  1, NULL),
    (3, 'Enterprise', 599000,  999000, 10, NULL)
ON CONFLICT (tier_id) DO UPDATE SET
    price_monthly        = EXCLUDED.price_monthly,
    price_annualy        = EXCLUDED.price_annualy,
    max_acc              = EXCLUDED.max_acc,
    download_quota_byte  = EXCLUDED.download_quota_byte;

-- ─────────────────────────────────────────────────────────
-- 2. ACCOUNTS  ←  auth.users
--    FK nanti: subs_id, org_id (diisi di langkah 5)
-- ─────────────────────────────────────────────────────────
INSERT INTO geoportal.accounts
    (acc_id, acc_name, acc_email, acc_password, created_at, is_admin)
SELECT
    u.id,
    LEFT(COALESCE(NULLIF(TRIM(u.full_name),''), SPLIT_PART(u.email,'@',1)), 20),
    u.email,
    u.password_hash,
    COALESCE(u.created_at::timestamptz, NOW()),
    EXISTS (
        SELECT 1 FROM auth.user_roles ur
        JOIN auth.roles r ON r.id = ur.role_id
        WHERE ur.user_id = u.id AND r.name IN ('superadmin','admin')
    )
FROM auth.users u
ON CONFLICT (acc_email) DO UPDATE SET
    acc_name     = EXCLUDED.acc_name,
    acc_password = EXCLUDED.acc_password,
    is_admin     = EXCLUDED.is_admin;

-- Reset sequence
SELECT setval(pg_get_serial_sequence('geoportal.accounts','acc_id'),
    COALESCE((SELECT MAX(acc_id) FROM geoportal.accounts), 1));

-- ─────────────────────────────────────────────────────────
-- 3. SUBSCRIPTIONS  ←  gravport.subscriptions
--    Tier mapping: 8→1(Lite), 9→2(Pro), 10→3(Ent), else→1
-- ─────────────────────────────────────────────────────────
INSERT INTO geoportal.subscriptions
    (subs_id, tier_id, midtrans_order_id, payment_status,
     payment_cycle, remaining_download_byte, total_acc, created_at, paid_at, end_at)
OVERRIDING SYSTEM VALUE
SELECT
    gs.subscription_id,
    CASE gs.tier_id
        WHEN 8  THEN 1  -- lite → Lite
        WHEN 9  THEN 2  -- pro  → Pro
        WHEN 10 THEN 3  -- team → Enterprise
        WHEN 2  THEN 3  -- enterprise → Enterprise
        ELSE 1
    END,
    COALESCE(NULLIF(TRIM(gs.payment_ref),''),
             'MIGRATED-' || gs.subscription_id::text),
    CASE gs.status
        WHEN 'active' THEN 'S'
        ELSE 'E'
    END,
    'M',
    COALESCE(
        CASE gs.tier_id
            WHEN 8 THEN 2147483648::bigint   -- lite: 2GB
            ELSE NULL
        END,
        107374182400::bigint                 -- pro+: 100GB
    ),
    1,
    gs.created_at,
    gs.created_at,
    gs.end_date
FROM gravport.subscriptions gs
ON CONFLICT (midtrans_order_id) DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.subscriptions','subs_id'),
    COALESCE((SELECT MAX(subs_id) FROM geoportal.subscriptions), 1));

-- ─────────────────────────────────────────────────────────
-- 4. ORGANIZATIONS  ←  gravport.organizations
-- ─────────────────────────────────────────────────────────
INSERT INTO geoportal.organizations (org_id, org_name, org_email)
OVERRIDING SYSTEM VALUE
SELECT
    go.organization_id,
    go.organization_name,
    COALESCE(NULLIF(TRIM(go.organization_email),''),
             'noemail_' || go.organization_id || '@placeholder.id')
FROM gravport.organizations go
ON CONFLICT (org_email) DO UPDATE SET
    org_name = EXCLUDED.org_name;

SELECT setval(pg_get_serial_sequence('geoportal.organizations','org_id'),
    COALESCE((SELECT MAX(org_id) FROM geoportal.organizations), 1));

-- ─────────────────────────────────────────────────────────
-- 5. UPDATE ACCOUNTS: link subs_id dan org_id
-- ─────────────────────────────────────────────────────────
-- subs_id: berdasarkan user_id pada subscriptions lama
UPDATE geoportal.accounts a
SET subs_id = new_s.subs_id
FROM gravport.subscriptions gs
JOIN geoportal.subscriptions new_s
    ON new_s.midtrans_order_id =
       COALESCE(NULLIF(TRIM(gs.payment_ref),''),
                'MIGRATED-' || gs.subscription_id::text)
WHERE a.acc_id = gs.user_id
  AND a.subs_id IS DISTINCT FROM new_s.subs_id;

-- org_id: berdasarkan organization_members lama
UPDATE geoportal.accounts a
SET org_id = gom.organization_id
FROM gravport.organization_members gom
WHERE gom.user_id = a.acc_id
  AND a.org_id IS DISTINCT FROM gom.organization_id;

-- ─────────────────────────────────────────────────────────
-- 6. DATASETS_GRAV_ANOM  ←  SYNTHETIC dari anomaly data
--    4 dataset: FAA L1, CBA L1, FAA L2 raster, CBA L2 raster
--    geom: ST_Multi(ST_ConvexHull) dari semua titik/raster
-- ─────────────────────────────────────────────────────────
-- Pastikan 4 dataset ada (idempotent via ON CONFLICT pada nama+level+type)
-- Karena tidak ada unique constraint, pakai DELETE+INSERT berdasarkan dataset_name
DELETE FROM geoportal.datasets_grav_anom
WHERE dataset_name IN (
    'FAA Level 1 - Jawa Bali',
    'CBA Level 1 - Jawa Bali',
    'FAA Level 2 Raster - Jawa Bali',
    'CBA Level 2 Raster - Jawa Bali'
);

-- FAA Level 1
INSERT INTO geoportal.datasets_grav_anom
    (dataset_name, acc_id, dataset_level, dataset_anom_type, geom)
SELECT
    'FAA Level 1 - Jawa Bali',
    (SELECT acc_id FROM geoportal.accounts ORDER BY acc_id LIMIT 1),
    1, 'FAA',
    ST_Multi(ST_ConvexHull(ST_Collect(geom)))
FROM gravport.anomaly_gravity_point_data
WHERE point_anom_type = 'FAA' AND data_level = 1 AND status = 'active';

-- CBA Level 1
INSERT INTO geoportal.datasets_grav_anom
    (dataset_name, acc_id, dataset_level, dataset_anom_type, geom)
SELECT
    'CBA Level 1 - Jawa Bali',
    (SELECT acc_id FROM geoportal.accounts ORDER BY acc_id LIMIT 1),
    1, 'CBA',
    ST_Multi(ST_ConvexHull(ST_Collect(geom)))
FROM gravport.anomaly_gravity_point_data
WHERE point_anom_type = 'CBA' AND data_level = 1 AND status = 'active';

-- FAA Level 2 (raster extent)
INSERT INTO geoportal.datasets_grav_anom
    (dataset_name, acc_id, dataset_level, dataset_anom_type, geom)
SELECT
    'FAA Level 2 Raster - Jawa Bali',
    (SELECT acc_id FROM geoportal.accounts ORDER BY acc_id LIMIT 1),
    2, 'FAA',
    ST_Multi(ST_ConvexHull(ST_Collect(rt.grid_geom)))
FROM gravport.raster_tiles rt
JOIN gravport.anomaly_gravity_raster_data ard ON ard.raster_id = rt.raster_id
WHERE ard.raster_anom_type = 'FAA' AND rt.grid_geom IS NOT NULL;

-- CBA Level 2 (raster extent)
INSERT INTO geoportal.datasets_grav_anom
    (dataset_name, acc_id, dataset_level, dataset_anom_type, geom)
SELECT
    'CBA Level 2 Raster - Jawa Bali',
    (SELECT acc_id FROM geoportal.accounts ORDER BY acc_id LIMIT 1),
    2, 'CBA',
    ST_Multi(ST_ConvexHull(ST_Collect(rt.grid_geom)))
FROM gravport.raster_tiles rt
JOIN gravport.anomaly_gravity_raster_data ard ON ard.raster_id = rt.raster_id
WHERE ard.raster_anom_type = 'CBA' AND rt.grid_geom IS NOT NULL;

-- ─────────────────────────────────────────────────────────
-- 7. POINT_GRAV_ANOM  ←  gravport.anomaly_gravity_point_data
--    628,372 baris — hanya insert jika tabel kosong
--    Hindari duplikasi: truncate + re-insert jika perlu
-- ─────────────────────────────────────────────────────────
DO $$
DECLARE
    existing_count bigint;
    source_count   bigint;
BEGIN
    SELECT COUNT(*) INTO existing_count FROM geoportal.point_grav_anom;
    SELECT COUNT(*) INTO source_count
    FROM gravport.anomaly_gravity_point_data WHERE status = 'active';

    IF existing_count < source_count THEN
        RAISE NOTICE 'Melakukan migrasi % titik gravity...', source_count;
        TRUNCATE geoportal.point_grav_anom RESTART IDENTITY CASCADE;

        INSERT INTO geoportal.point_grav_anom
            (dataset_id, point_value, point_obs_type, geom)
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

        RAISE NOTICE 'Selesai migrasi titik gravity.';
    ELSE
        RAISE NOTICE 'Titik gravity sudah ada (%), skip.', existing_count;
    END IF;
END;
$$;

-- ─────────────────────────────────────────────────────────
-- 8. RASTER_GRAV_ANOM  ←  gravport.raster_tiles
--    9,588 baris
-- ─────────────────────────────────────────────────────────
DO $$
DECLARE
    existing_count bigint;
    source_count   bigint;
BEGIN
    SELECT COUNT(*) INTO existing_count FROM geoportal.raster_grav_anom;
    SELECT COUNT(*) INTO source_count FROM gravport.raster_tiles;

    IF existing_count < source_count THEN
        RAISE NOTICE 'Melakukan migrasi % tile raster...', source_count;
        TRUNCATE geoportal.raster_grav_anom RESTART IDENTITY;

        INSERT INTO geoportal.raster_grav_anom
            (dataset_id, raster_resolution_deg, rast)
        SELECT
            d.dataset_id,
            COALESCE(ard.raster_resolution::real, 0.01),
            rt.rast
        FROM gravport.raster_tiles rt
        JOIN gravport.anomaly_gravity_raster_data ard
            ON ard.raster_id = rt.raster_id
        JOIN geoportal.datasets_grav_anom d
            ON d.dataset_anom_type = ard.raster_anom_type
            AND d.dataset_level    = ard.data_level;

        RAISE NOTICE 'Selesai migrasi raster.';
    ELSE
        RAISE NOTICE 'Raster sudah ada (%), skip.', existing_count;
    END IF;
END;
$$;

-- ─────────────────────────────────────────────────────────
-- 9. PROPOSED_DATA  ←  gravport.staging_gravity_points
--    Kosong sekarang — siap untuk data baru
-- ─────────────────────────────────────────────────────────
-- Tidak ada data untuk dimigrasikan (0 baris di staging)

COMMIT;

-- ─────────────────────────────────────────────────────────
-- VERIFIKASI
-- ─────────────────────────────────────────────────────────
SELECT 'geoportal.accounts'        AS tabel, COUNT(*) AS baris FROM geoportal.accounts
UNION ALL
SELECT 'geoportal.organizations',           COUNT(*) FROM geoportal.organizations
UNION ALL
SELECT 'geoportal.subscriptions_tier',      COUNT(*) FROM geoportal.subscriptions_tier
UNION ALL
SELECT 'geoportal.subscriptions',           COUNT(*) FROM geoportal.subscriptions
UNION ALL
SELECT 'geoportal.datasets_grav_anom',      COUNT(*) FROM geoportal.datasets_grav_anom
UNION ALL
SELECT 'geoportal.point_grav_anom',         COUNT(*) FROM geoportal.point_grav_anom
UNION ALL
SELECT 'geoportal.raster_grav_anom',        COUNT(*) FROM geoportal.raster_grav_anom
UNION ALL
SELECT 'geoportal.proposed_data',           COUNT(*) FROM geoportal.proposed_data
ORDER BY tabel;
