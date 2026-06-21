-- TC-102: Exhaust weekly quota for testing
-- Run: sudo -u postgres psql -d geoportal -f /var/www/geoportal/scripts/tc102_exhaust_quota.sql

-- Step 1: Find acc_id and weekly limit for the test API key
SELECT k.acc_id, t.tier_name, s.download_limit_bytes_week
FROM geoportal.api_keys k
JOIN geoportal.subscriptions s ON s.acc_id = k.acc_id AND s.payment_status = 'S'
JOIN geoportal.subscriptions_tier t ON t.tier_id = s.tier_id
WHERE k.key_prefix LIKE 'gp_%'
ORDER BY k.created_at DESC
LIMIT 3;

-- Step 2: Insert a fake download_transaction to exhaust quota
-- Replace 999999999 with acc_id from Step 1 result
-- Replace 999999999 (bytes) with download_limit_bytes_week value from Step 1
INSERT INTO geoportal.download_transactions
    (acc_id, dataset_code, download_type, download_size_bytes, status, downloaded_at)
SELECT
    acc_id,
    'faa_l1',
    'vector',
    COALESCE(s.download_limit_bytes_week, 104857600),
    'completed',
    now()
FROM geoportal.api_keys k
JOIN geoportal.subscriptions s ON s.acc_id = k.acc_id AND s.payment_status = 'S'
WHERE k.key_prefix LIKE 'gp_%'
ORDER BY k.created_at DESC
LIMIT 1;

SELECT 'Quota exhausted. Test TC-102 now, then run tc102_restore_quota.sql' AS info;
