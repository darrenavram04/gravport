-- Migration: Tambah kolom midtrans_order_id ke tabel pending registrations
-- Jalankan sekali di database gravport

ALTER TABLE gravport.pending_registrations
    ADD COLUMN IF NOT EXISTS midtrans_order_id VARCHAR(100);

ALTER TABLE gravport.pending_organizations
    ADD COLUMN IF NOT EXISTS midtrans_order_id VARCHAR(100);

-- Index untuk lookup cepat dari webhook
CREATE INDEX IF NOT EXISTS idx_pending_reg_order_id
    ON gravport.pending_registrations (midtrans_order_id)
    WHERE midtrans_order_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_pending_org_order_id
    ON gravport.pending_organizations (midtrans_order_id)
    WHERE midtrans_order_id IS NOT NULL;
