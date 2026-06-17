-- ============================================================
-- fix_blackbox_v5.sql
-- Perbaikan database untuk hasil Black Box Testing v5
-- Jalankan sebagai superuser di PostgreSQL (port 5433):
--   psql -h localhost -p 5433 -U postgres -d geoportal -f fix_blackbox_v5.sql
-- ============================================================

BEGIN;

-- ─────────────────────────────────────────────────────────────
-- 1. Pastikan kolom level2_access ada di subscriptions_tier
--    (mungkin sudah ada dari migration sebelumnya)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE geoportal.subscriptions_tier
    ADD COLUMN IF NOT EXISTS level2_access boolean NOT NULL DEFAULT false;

-- ─────────────────────────────────────────────────────────────
-- 2. Pastikan tier 'lite' ada di subscriptions_tier
--    dengan level2_access=TRUE (sama seperti pro).
--    Lite vs Pro hanya beda di kuota download, bukan akses data.
-- ─────────────────────────────────────────────────────────────
INSERT INTO geoportal.subscriptions_tier
    (tier_name, max_downloads, api_access, price_monthly, download_quota_byte,
     max_acc, level2_access, wms_wfs_access)
VALUES
    ('lite', 50, false, 99000, 10737418240, 1, true, false)  -- kuota 10 GB/minggu
ON CONFLICT (tier_name) DO UPDATE
    SET level2_access      = true,
        download_quota_byte = EXCLUDED.download_quota_byte,
        price_monthly       = EXCLUDED.price_monthly;

-- ─────────────────────────────────────────────────────────────
-- 3. Pastikan tier 'pro' dan tier-tier lain juga punya
--    level2_access=TRUE (seharusnya sudah, tapi pastikan)
-- ─────────────────────────────────────────────────────────────
UPDATE geoportal.subscriptions_tier
SET    level2_access = true
WHERE  tier_name IN ('pro', 'team', 'enterprise', 'government');

-- ─────────────────────────────────────────────────────────────
-- 4. Perbaiki user yang sudah di-approve sebagai 'lite' tapi
--    tidak punya subscription aktif (akibat bug TC-078).
--
--    Cek: akun yang ada di accounts tapi tidak punya subscription
--    aktif, padahal pending_registration-nya sudah approved sebagai lite.
-- ─────────────────────────────────────────────────────────────
DO $$
DECLARE
    v_lite_tier_id   integer;
    v_pending        record;
    v_acc_id         integer;
    v_end_date       date;
BEGIN
    SELECT tier_id INTO v_lite_tier_id
    FROM   geoportal.subscriptions_tier
    WHERE  tier_name = 'lite'
    LIMIT  1;

    IF v_lite_tier_id IS NULL THEN
        RAISE NOTICE 'Tier lite tidak ditemukan setelah INSERT — batalkan.';
        RETURN;
    END IF;

    -- Cari pending registrations yang sudah approved (lite) tapi user tidak punya sub aktif
    FOR v_pending IN
        SELECT pr.pending_id, pr.email, pr.billing_cycle
        FROM   geoportal.pending_registrations pr
        JOIN   geoportal.accounts a ON lower(a.acc_email) = lower(pr.email)
        WHERE  pr.tier_name  = 'lite'
          AND  pr.status     = 'approved'
          AND  NOT EXISTS (
              SELECT 1 FROM geoportal.subscriptions s
              WHERE  s.acc_id = a.acc_id
                AND  s.payment_status = 'S'
                AND  (s.end_at IS NULL OR s.end_at >= current_date)
          )
    LOOP
        SELECT acc_id INTO v_acc_id
        FROM   geoportal.accounts
        WHERE  lower(acc_email) = lower(v_pending.email)
        LIMIT  1;

        IF v_acc_id IS NULL THEN CONTINUE; END IF;

        v_end_date := CASE v_pending.billing_cycle
            WHEN 'annual' THEN current_date + interval '1 year'
            ELSE               current_date + interval '1 month'
        END;

        -- Cancel existing non-active subscriptions untuk akun ini
        UPDATE geoportal.subscriptions
        SET    payment_status = 'E'
        WHERE  acc_id = v_acc_id AND payment_status != 'S';

        -- Insert subscription lite
        WITH ins AS (
            INSERT INTO geoportal.subscriptions
                (acc_id, tier_id, midtrans_order_id, payment_status, payment_cycle,
                 remaining_download_byte, total_acc, paid_at, end_at, start_date)
            VALUES
                (v_acc_id, v_lite_tier_id,
                 'ADMIN-FIX-' || v_acc_id || '-' || extract(epoch from now())::bigint,
                 'S',
                 CASE v_pending.billing_cycle WHEN 'annual' THEN 'A' ELSE 'M' END,
                 10737418240, 1, now(), v_end_date, current_date)
            RETURNING subs_id
        )
        UPDATE geoportal.accounts SET subs_id = (SELECT subs_id FROM ins)
        WHERE  acc_id = v_acc_id;

        RAISE NOTICE 'Subscription lite dibuat untuk acc_id=% (email=%)', v_acc_id, v_pending.email;
    END LOOP;
END
$$;

-- ─────────────────────────────────────────────────────────────
-- 5. Index spatial pada faa_l1_points dan cba_l1_points
--    jika belum ada (penting untuk province filter performance TC-037)
-- ─────────────────────────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_faa_l1_geom ON geoportal.faa_l1_points USING GIST (geom);
CREATE INDEX IF NOT EXISTS idx_cba_l1_geom ON geoportal.cba_l1_points USING GIST (geom);

-- ─────────────────────────────────────────────────────────────
-- 6. Pastikan kolom downloaded_at ada di download_transactions
--    (mungkin sudah ada, ini idempoten)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE geoportal.download_transactions
    ADD COLUMN IF NOT EXISTS downloaded_at timestamptz NOT NULL DEFAULT now();

COMMIT;

-- Verifikasi hasil
SELECT tier_id, tier_name, level2_access, download_quota_byte, price_monthly
FROM   geoportal.subscriptions_tier
ORDER  BY tier_id;
