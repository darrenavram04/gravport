-- =============================================================================
-- migrate_tiers_v2.sql
-- Revisi skema bisnis GravPort: tier baru (Solo/Pro/Team), pending registrations,
-- organizations, dan organization_members.
-- Jalankan sekali: psql -U postgres -d gravport -f migrate_tiers_v2.sql
-- =============================================================================

BEGIN;

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. Tambah kolom baru ke subscription_tiers
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE gravport.subscription_tiers
    ADD COLUMN IF NOT EXISTS download_limit_bytes_week BIGINT         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS max_seats                 INT            NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS annual_fee                NUMERIC(12,2)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS level2_access             BOOLEAN        NOT NULL DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS is_active                 BOOLEAN        NOT NULL DEFAULT TRUE;

-- Drop check constraint lama pada tier_name (hanya izinkan nilai tier lama).
-- Tier baru (solo, pro, team) akan divalidasi di lapisan aplikasi.
ALTER TABLE gravport.subscription_tiers
    DROP CONSTRAINT IF EXISTS subscription_tiers_tier_name_check;

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. Masukkan tier baru Solo / Pro / Team
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO gravport.subscription_tiers
    (tier_name, max_downloads, download_limit_bytes_week, max_seats,
     api_access, level2_access, monthly_fee, annual_fee, is_active)
VALUES
    -- Solo: 2 GB/minggu, Level 1 saja, 1 akun
    ('solo', NULL, 2147483648, 1, FALSE, FALSE, 99000.00,  990000.00,  TRUE),
    -- Pro: unlimited, Level 1+2, 1 akun
    ('pro',  NULL, NULL,        1, TRUE,  TRUE,  299000.00, 2990000.00, TRUE),
    -- Team: unlimited, Level 1+2, hingga 10 akun
    ('team', NULL, NULL,       10, TRUE,  TRUE,  999000.00, 9990000.00, TRUE)
ON CONFLICT (tier_name) DO UPDATE SET
    download_limit_bytes_week = EXCLUDED.download_limit_bytes_week,
    max_seats                 = EXCLUDED.max_seats,
    level2_access             = EXCLUDED.level2_access,
    monthly_fee               = EXCLUDED.monthly_fee,
    annual_fee                = EXCLUDED.annual_fee,
    is_active                 = TRUE;

-- Nonaktifkan tier lama (jaga integritas data historis, jangan hapus)
UPDATE gravport.subscription_tiers
SET    is_active = FALSE
WHERE  tier_name IN ('free', 'enterprise', 'government');

-- ─────────────────────────────────────────────────────────────────────────────
-- 3. pending_registrations — individu (Solo/Pro) menunggu pembayaran
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS gravport.pending_registrations (
    pending_id     SERIAL        PRIMARY KEY,
    full_name      VARCHAR(120)  NOT NULL,
    email          VARCHAR(160)  NOT NULL,
    password_hash  TEXT          NOT NULL,
    tier_name      VARCHAR(30)   NOT NULL CHECK (tier_name IN ('solo', 'pro')),
    billing_cycle  VARCHAR(10)   NOT NULL DEFAULT 'monthly'
                       CHECK (billing_cycle IN ('monthly', 'annual')),
    status         VARCHAR(20)   NOT NULL DEFAULT 'pending_payment'
                       CHECK (status IN ('pending_payment', 'approved', 'rejected', 'expired')),
    rejection_note TEXT,
    reviewed_by    INT,
    expires_at     TIMESTAMPTZ   NOT NULL DEFAULT NOW() + INTERVAL '7 days',
    created_at     TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    updated_at     TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

-- Hanya boleh 1 pending aktif per email
CREATE UNIQUE INDEX IF NOT EXISTS idx_pending_reg_active_email
    ON gravport.pending_registrations (email)
    WHERE status = 'pending_payment';

-- ─────────────────────────────────────────────────────────────────────────────
-- 4. pending_organizations — bisnis/tim (Team) menunggu pembayaran
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS gravport.pending_organizations (
    pending_id    SERIAL        PRIMARY KEY,
    org_name      VARCHAR(200)  NOT NULL,
    org_email     VARCHAR(160)  NOT NULL,
    contact_name  VARCHAR(120)  NOT NULL,
    seat_count    INT           NOT NULL DEFAULT 5
                      CHECK (seat_count BETWEEN 1 AND 100),
    billing_cycle VARCHAR(10)   NOT NULL DEFAULT 'monthly'
                      CHECK (billing_cycle IN ('monthly', 'annual')),
    status        VARCHAR(20)   NOT NULL DEFAULT 'pending_payment'
                      CHECK (status IN ('pending_payment', 'approved', 'rejected', 'expired')),
    rejection_note TEXT,
    reviewed_by   INT,
    expires_at    TIMESTAMPTZ   NOT NULL DEFAULT NOW() + INTERVAL '7 days',
    created_at    TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_pending_org_active_email
    ON gravport.pending_organizations (org_email)
    WHERE status = 'pending_payment';

-- ─────────────────────────────────────────────────────────────────────────────
-- 5. Tambah kolom ke tabel organizations yang sudah ada
--    (tabel ini sudah ada untuk data partners, kita reuse untuk subscriber teams)
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE gravport.organizations
    ADD COLUMN IF NOT EXISTS seat_count INT     NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS is_active  BOOLEAN NOT NULL DEFAULT TRUE;

-- ─────────────────────────────────────────────────────────────────────────────
-- 6. organization_members — peta user ↔ organisasi subscriber
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS gravport.organization_members (
    member_id       SERIAL       PRIMARY KEY,
    organization_id BIGINT       NOT NULL REFERENCES gravport.organizations(organization_id) ON DELETE CASCADE,
    user_id         INT          NOT NULL,   -- merujuk auth.users.id di DB MockUp
    is_admin        BOOLEAN      NOT NULL DEFAULT FALSE,
    joined_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    UNIQUE (organization_id, user_id)
);

COMMIT;

-- Verifikasi hasil
SELECT tier_name, monthly_fee, annual_fee, download_limit_bytes_week,
       max_seats, level2_access, is_active
FROM   gravport.subscription_tiers
ORDER  BY tier_id;
