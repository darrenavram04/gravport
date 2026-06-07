-- ================================================================
-- GRAVPORT — Marketplace & Subscription Migration
-- Run: psql -U gravport -d gravport -p 5433 -f migrate_marketplace.sql
-- ================================================================

BEGIN;

-- ── 1. Subscription tiers (master data) ─────────────────────────
CREATE TABLE IF NOT EXISTS gravport.subscription_tiers (
    tier_id          serial        PRIMARY KEY,
    tier_name        varchar(32)   NOT NULL UNIQUE
                                   CHECK (tier_name IN ('free','enterprise','government')),
    monthly_fee      numeric(14,2) NOT NULL DEFAULT 0,
    annual_fee       numeric(14,2) NOT NULL DEFAULT 0,
    max_downloads    int           NULL,   -- NULL = unlimited
    api_access       boolean       NOT NULL DEFAULT false,
    wms_wfs_access   boolean       NOT NULL DEFAULT false,
    description      text,
    created_at       timestamptz   NOT NULL DEFAULT now()
);

INSERT INTO gravport.subscription_tiers
    (tier_name, monthly_fee, annual_fee, max_downloads, api_access, wms_wfs_access, description)
VALUES
    ('free',         0,          0,           500,  false, false,
     'Akses gratis: browse, visualisasi, download s/d 500 data/bulan'),
    ('enterprise',   10000000,   120000000,   NULL, true,  true,
     'Full access Level 1+2, REST API, WMS/WFS, SLA 99.9%. Ref: Esri ArcGIS Enterprise'),
    ('government',   0,          0,           NULL, true,  true,
     'Kontrak MoU/tahunan. Harga Rp 50-200 Juta/tahun per institusi')
ON CONFLICT (tier_name) DO NOTHING;

-- ── 2. User subscriptions ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS gravport.subscriptions (
    subscription_id  bigserial     PRIMARY KEY,
    user_id          bigint        NOT NULL,
    tier_id          int           NOT NULL REFERENCES gravport.subscription_tiers(tier_id),
    start_date       date          NOT NULL DEFAULT current_date,
    end_date         date          NOT NULL,
    status           varchar(16)   NOT NULL DEFAULT 'active'
                                   CHECK (status IN ('active','expired','cancelled')),
    payment_method   varchar(64),
    payment_ref      varchar(128),
    notes            text,
    created_by       bigint,       -- superadmin who assigned this
    created_at       timestamptz   NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_subscriptions_user_status
    ON gravport.subscriptions(user_id, status);
CREATE INDEX IF NOT EXISTS idx_subscriptions_end_date
    ON gravport.subscriptions(end_date) WHERE status = 'active';

-- ── 3. Data providers ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS gravport.data_providers (
    provider_id       serial        PRIMARY KEY,
    provider_name     varchar(256)  NOT NULL UNIQUE,
    provider_type     varchar(32)   NOT NULL DEFAULT 'government'
                                    CHECK (provider_type IN ('government','institution','academic','private')),
    contact_email     varchar(256),
    contact_person    varchar(128),
    revenue_share_pct numeric(5,2)  NOT NULL DEFAULT 75.00
                                    CHECK (revenue_share_pct BETWEEN 0 AND 100),
    -- Ref benchmark: App Store 30%, AWS Marketplace 20%, Spotify 30% to labels.
    -- Gravport retains 25% (100 - 75), more provider-friendly than App Store.
    bank_account      varchar(64),
    bank_name         varchar(64),
    is_active         boolean       NOT NULL DEFAULT true,
    notes             text,
    joined_at         timestamptz   NOT NULL DEFAULT now()
);

INSERT INTO gravport.data_providers
    (provider_name, provider_type, contact_email, revenue_share_pct, notes)
VALUES
    ('BIG - Badan Informasi Geospasial', 'government', 'info@big.go.id',   75.00,
     'Penyedia utama data gravimetri nasional'),
    ('BRIN - Badan Riset dan Inovasi',   'government', 'info@brin.go.id',  75.00,
     'Riset dan survei gravimetri regional'),
    ('ESDM - Badan Geologi',             'government', 'info@esdm.go.id',  75.00,
     'Data gravitasi wilayah eksplorasi mineral')
ON CONFLICT (provider_name) DO NOTHING;

-- Link existing gravity data to default provider (BIG)
ALTER TABLE gravport.anomaly_gravity_point_data
    ADD COLUMN IF NOT EXISTS provider_id int
    REFERENCES gravport.data_providers(provider_id) ON DELETE SET NULL;

ALTER TABLE gravport.anomaly_gravity_raster_data
    ADD COLUMN IF NOT EXISTS provider_id int
    REFERENCES gravport.data_providers(provider_id) ON DELETE SET NULL;

UPDATE gravport.anomaly_gravity_point_data
    SET provider_id = (SELECT provider_id FROM gravport.data_providers
                       WHERE provider_name = 'BIG - Badan Informasi Geospasial' LIMIT 1)
    WHERE provider_id IS NULL;

UPDATE gravport.anomaly_gravity_raster_data
    SET provider_id = (SELECT provider_id FROM gravport.data_providers
                       WHERE provider_name = 'BIG - Badan Informasi Geospasial' LIMIT 1)
    WHERE provider_id IS NULL;

-- ── 4. Download transactions (marketplace audit log) ─────────────
CREATE TABLE IF NOT EXISTS gravport.download_transactions (
    transaction_id       bigserial     PRIMARY KEY,
    user_id              bigint,       -- NULL for guest/anonymous
    provider_id          int           REFERENCES gravport.data_providers(provider_id) ON DELETE SET NULL,
    dataset_code         varchar(32)   NOT NULL,
    dataset_type         varchar(16)   NOT NULL
                                       CHECK (dataset_type IN ('vector','raster','metadata')),
    filter_params        jsonb         NOT NULL DEFAULT '{}',
    row_count            int,          -- number of points / tiles returned
    download_size_bytes  bigint,
    transaction_amount   numeric(14,2) NOT NULL DEFAULT 0,
    gravport_commission  numeric(14,2) NOT NULL DEFAULT 0, -- 25%
    provider_revenue     numeric(14,2) NOT NULL DEFAULT 0, -- 75%
    status               varchar(16)   NOT NULL DEFAULT 'completed'
                                       CHECK (status IN ('completed','failed','refunded')),
    user_agent           varchar(256),
    downloaded_at        timestamptz   NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_dl_user       ON gravport.download_transactions(user_id, downloaded_at);
CREATE INDEX IF NOT EXISTS idx_dl_provider   ON gravport.download_transactions(provider_id, downloaded_at);
CREATE INDEX IF NOT EXISTS idx_dl_date       ON gravport.download_transactions(downloaded_at);
CREATE INDEX IF NOT EXISTS idx_dl_dataset    ON gravport.download_transactions(dataset_code, downloaded_at);

-- ── 5. Revenue share aggregation (per provider per period) ───────
CREATE TABLE IF NOT EXISTS gravport.revenue_shares (
    revenue_id       bigserial     PRIMARY KEY,
    provider_id      int           NOT NULL REFERENCES gravport.data_providers(provider_id),
    period_start     date          NOT NULL,
    period_end       date          NOT NULL,
    total_downloads  int           NOT NULL DEFAULT 0,
    gross_revenue    numeric(18,2) NOT NULL DEFAULT 0,
    provider_share   numeric(18,2) NOT NULL DEFAULT 0, -- 75%
    platform_share   numeric(18,2) NOT NULL DEFAULT 0, -- 25%
    payment_status   varchar(16)   NOT NULL DEFAULT 'pending'
                                   CHECK (payment_status IN ('pending','paid','disputed')),
    paid_at          timestamptz,
    notes            text,
    created_at       timestamptz   NOT NULL DEFAULT now(),
    UNIQUE (provider_id, period_start, period_end)
);

COMMIT;
