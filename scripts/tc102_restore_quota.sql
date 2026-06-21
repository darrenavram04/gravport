-- TC-102: Restore quota after testing
-- Run: sudo -u postgres psql -d geoportal -f /var/www/geoportal/scripts/tc102_restore_quota.sql

DELETE FROM geoportal.download_transactions
WHERE dataset_code = 'faa_l1'
  AND download_type = 'vector'
  AND status = 'completed'
  AND downloaded_at >= date_trunc('week', now())
  AND acc_id IN (
      SELECT acc_id FROM geoportal.api_keys
      WHERE key_prefix LIKE 'gp_%'
      ORDER BY created_at DESC
      LIMIT 1
  );

SELECT 'Quota restored.' AS info;
