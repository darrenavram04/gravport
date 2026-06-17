-- ============================================================
-- fix_blackbox_v5b.sql  (versi perbaikan — tanpa ON CONFLICT)
-- Jalankan:
--   sudo -u postgres psql -d geoportal -f /var/www/geoportal/scripts/fix_blackbox_v5b.sql
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. Spatial index untuk province filter (TC-037)
--    Dijalankan di luar transaksi agar tidak rollback jika sudah ada
-- ─────────────────────────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_faa_l1_geom ON geoportal.faa_l1_points USING GIST (geom);
CREATE INDEX IF NOT EXISTS idx_cba_l1_geom ON geoportal.cba_l1_points USING GIST (geom);

-- ─────────────────────────────────────────────────────────────
-- 2. Pastikan level2_access = TRUE untuk semua tier berbayar
--    (Lite, Pro, Enterprise, dll — case-insensitive)
-- ─────────────────────────────────────────────────────────────
UPDATE geoportal.subscriptions_tier
SET    level2_access = true
WHERE  lower(tier_name) IN ('lite', 'solo', 'pro', 'team', 'enterprise', 'government');

-- ─────────────────────────────────────────────────────────────
-- 3. Perbaiki user Lite yang di-approve tapi tidak punya
--    subscription aktif (TC-078 — case-insensitive tier lookup)
-- ─────────────────────────────────────────────────────────────
DO $$
DECLARE
    v_lite_tier_id   integer;
    v_pending        record;
    v_acc_id         integer;
    v_end_date       date;
    v_new_subs_id    integer;
BEGIN
    SELECT tier_id INTO v_lite_tier_id
    FROM   geoportal.subscriptions_tier
    WHERE  lower(tier_name) = 'lite'
    LIMIT  1;

    IF v_lite_tier_id IS NULL THEN
        RAISE NOTICE 'Tier Lite tidak ditemukan di subscriptions_tier.';
        RETURN;
    END IF;

    FOR v_pending IN
        SELECT pr.pending_id, pr.email, pr.billing_cycle
        FROM   geoportal.pending_registrations pr
        JOIN   geoportal.accounts a ON lower(a.acc_email) = lower(pr.email)
        WHERE  lower(pr.tier_name) = 'lite'
          AND  pr.status = 'approved'
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

        INSERT INTO geoportal.subscriptions
            (acc_id, tier_id, midtrans_order_id, payment_status, payment_cycle,
             remaining_download_byte, total_acc, paid_at, end_at, start_date)
        VALUES
            (v_acc_id, v_lite_tier_id,
             'ADMIN-FIX-' || v_acc_id || '-' || extract(epoch from now())::bigint,
             'S',
             CASE v_pending.billing_cycle WHEN 'annual' THEN 'A' ELSE 'M' END,
             2147483648, 1, now(), v_end_date, current_date)
        RETURNING subs_id INTO v_new_subs_id;

        UPDATE geoportal.accounts SET subs_id = v_new_subs_id WHERE acc_id = v_acc_id;

        RAISE NOTICE 'Subscription Lite dibuat untuk acc_id=% email=%', v_acc_id, v_pending.email;
    END LOOP;
END
$$;

-- ─────────────────────────────────────────────────────────────
-- Verifikasi akhir
-- ─────────────────────────────────────────────────────────────
SELECT tier_id, tier_name, level2_access, download_quota_byte, price_monthly
FROM   geoportal.subscriptions_tier
ORDER  BY tier_id;
