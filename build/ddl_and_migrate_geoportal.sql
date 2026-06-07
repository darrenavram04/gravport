-- ============================================================
-- DDL ADDITIONS + DATA MIGRATION
-- Semua ke schema geoportal — satu schema, satu database
-- Jalankan di: psql -d geoportal
-- ============================================================

BEGIN;

-- ════════════════════════════════════════════════════════════
-- BAGIAN 1: ALTER TABLE — tambah kolom yang hilang
-- ════════════════════════════════════════════════════════════

-- 1a. accounts: tambah is_active (auth-api butuh ini) + role
ALTER TABLE geoportal.accounts
    ADD COLUMN IF NOT EXISTS is_active boolean NOT NULL DEFAULT true,
    ADD COLUMN IF NOT EXISTS role      varchar(20) NOT NULL DEFAULT 'user';

-- Isi role dari auth.user_roles JOIN auth.roles
UPDATE geoportal.accounts a
SET role = COALESCE(
    (SELECT r.name
     FROM auth.user_roles ur
     JOIN auth.roles r ON r.id = ur.role_id
     WHERE ur.user_id = a.acc_id
     ORDER BY CASE r.name
         WHEN 'superadmin' THEN 1
         WHEN 'admin'      THEN 2
         ELSE 3
     END LIMIT 1),
    'user'
);
-- Sinkron is_admin dengan role
UPDATE geoportal.accounts SET is_admin = true  WHERE role IN ('superadmin','admin');
UPDATE geoportal.accounts SET is_admin = false WHERE role = 'user';
UPDATE geoportal.accounts SET is_active = true; -- semua user aktif

-- 1b. point_grav_anom: tambah source_file dan orthometric_height
ALTER TABLE geoportal.point_grav_anom
    ADD COLUMN IF NOT EXISTS source_file         text,
    ADD COLUMN IF NOT EXISTS orthometric_height  double precision;

-- 1c. subscriptions: tambah acc_id (FK ke accounts untuk query WHERE user_id = ?)
ALTER TABLE geoportal.subscriptions
    ADD COLUMN IF NOT EXISTS acc_id     bigint REFERENCES geoportal.accounts(acc_id) ON DELETE SET NULL,
    ADD COLUMN IF NOT EXISTS start_date date;

-- Isi acc_id dari gravport.subscriptions (subscription_id = subs_id, user_id = acc_id)
UPDATE geoportal.subscriptions gs
SET acc_id = gravs.user_id
FROM gravport.subscriptions gravs
WHERE gs.subs_id = gravs.subscription_id
  AND gs.acc_id IS NULL;

CREATE INDEX IF NOT EXISTS idx_subscriptions_acc_id ON geoportal.subscriptions(acc_id);

-- 1d. subscriptions_tier: tambah feature flags
ALTER TABLE geoportal.subscriptions_tier
    ADD COLUMN IF NOT EXISTS api_access      boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS level2_access   boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS wms_wfs_access  boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS max_downloads   integer,
    ADD COLUMN IF NOT EXISTS is_active       boolean NOT NULL DEFAULT true;

-- Isi flags dari gravport.subscription_tiers (mapping tier_id)
UPDATE geoportal.subscriptions_tier st
SET api_access     = COALESCE(gst.api_access, false),
    level2_access  = COALESCE(gst.level2_access, false),
    wms_wfs_access = COALESCE(gst.wms_wfs_access, false),
    max_downloads  = gst.max_downloads,
    is_active      = COALESCE(gst.is_active, true)
FROM (
    SELECT
        CASE tier_id WHEN 8 THEN 1 WHEN 9 THEN 2 WHEN 10 THEN 3 ELSE 1 END AS new_tier_id,
        api_access, level2_access, wms_wfs_access, max_downloads, is_active
    FROM gravport.subscription_tiers
    WHERE tier_id IN (8, 9, 10)
) gst
WHERE st.tier_id = gst.new_tier_id;

-- 1e. organizations: tambah kolom yang hilang
ALTER TABLE geoportal.organizations
    ADD COLUMN IF NOT EXISTS org_type   varchar(30) NOT NULL DEFAULT 'data_partner',
    ADD COLUMN IF NOT EXISTS seat_count integer NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS is_active  boolean NOT NULL DEFAULT true;

UPDATE geoportal.organizations go
SET org_type   = gravgo.org_type,
    seat_count = gravgo.seat_count,
    is_active  = gravgo.is_active
FROM gravport.organizations gravgo
WHERE go.org_id = gravgo.organization_id;


-- ════════════════════════════════════════════════════════════
-- BAGIAN 2: CREATE TABLE — tabel baru di geoportal schema
-- ════════════════════════════════════════════════════════════

-- 2a. login_otps (dari auth.login_otps — untuk 2FA)
CREATE TABLE IF NOT EXISTS geoportal.login_otps (
    id         serial PRIMARY KEY,
    acc_id     bigint NOT NULL REFERENCES geoportal.accounts(acc_id) ON DELETE CASCADE,
    otp_code   varchar(8) NOT NULL,
    expires_at timestamptz NOT NULL,
    used       boolean NOT NULL DEFAULT false,
    created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_login_otps_acc_id ON geoportal.login_otps(acc_id);

-- 2b. password_resets (dari auth.password_resets)
CREATE TABLE IF NOT EXISTS geoportal.password_resets (
    id         serial PRIMARY KEY,
    acc_id     bigint NOT NULL REFERENCES geoportal.accounts(acc_id) ON DELETE CASCADE,
    token      varchar(128) NOT NULL UNIQUE,
    expires_at timestamptz NOT NULL,
    used       boolean NOT NULL DEFAULT false,
    created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_password_resets_acc_id ON geoportal.password_resets(acc_id);

-- 2c. data_providers
CREATE TABLE IF NOT EXISTS geoportal.data_providers (
    provider_id      serial PRIMARY KEY,
    provider_name    varchar(256) NOT NULL UNIQUE,
    provider_type    varchar(32)  NOT NULL DEFAULT 'government',
    contact_email    varchar(256),
    contact_person   varchar(128),
    revenue_share_pct numeric(5,2) NOT NULL DEFAULT 75.00,
    bank_account     varchar(64),
    bank_name        varchar(64),
    is_active        boolean NOT NULL DEFAULT true,
    notes            text,
    joined_at        timestamptz NOT NULL DEFAULT now()
);

-- 2d. api_keys
CREATE TABLE IF NOT EXISTS geoportal.api_keys (
    key_id      serial PRIMARY KEY,
    acc_id      bigint NOT NULL REFERENCES geoportal.accounts(acc_id) ON DELETE CASCADE,
    key_name    varchar(100) NOT NULL,
    key_prefix  char(8) NOT NULL,
    key_hash    char(64) NOT NULL UNIQUE,
    scopes      text[] NOT NULL DEFAULT ARRAY['read'],
    created_at  timestamptz NOT NULL DEFAULT now(),
    last_used_at timestamptz,
    revoked_at  timestamptz
);
CREATE INDEX IF NOT EXISTS idx_api_keys_acc_id ON geoportal.api_keys(acc_id);

-- 2e. download_transactions
CREATE TABLE IF NOT EXISTS geoportal.download_transactions (
    transaction_id      bigserial PRIMARY KEY,
    acc_id              bigint REFERENCES geoportal.accounts(acc_id) ON DELETE SET NULL,
    provider_id         integer REFERENCES geoportal.data_providers(provider_id) ON DELETE SET NULL,
    dataset_code        varchar(32) NOT NULL,
    dataset_type        varchar(16) NOT NULL,
    filter_params       jsonb NOT NULL DEFAULT '{}',
    row_count           integer,
    download_size_bytes bigint,
    transaction_amount  numeric(14,2) NOT NULL DEFAULT 0,
    gravport_commission numeric(14,2) NOT NULL DEFAULT 0,
    provider_revenue    numeric(14,2) NOT NULL DEFAULT 0,
    status              varchar(16) NOT NULL DEFAULT 'completed',
    user_agent          varchar(256),
    downloaded_at       timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_dl_tx_acc_id   ON geoportal.download_transactions(acc_id);
CREATE INDEX IF NOT EXISTS idx_dl_tx_provider ON geoportal.download_transactions(provider_id);
CREATE INDEX IF NOT EXISTS idx_dl_tx_at       ON geoportal.download_transactions(downloaded_at);

-- 2f. invoices
CREATE TABLE IF NOT EXISTS geoportal.invoices (
    invoice_id      serial PRIMARY KEY,
    invoice_number  varchar(30) NOT NULL UNIQUE,
    acc_id          bigint NOT NULL REFERENCES geoportal.accounts(acc_id) ON DELETE RESTRICT,
    subs_id         bigint REFERENCES geoportal.subscriptions(subs_id) ON DELETE SET NULL,
    billing_cycle   varchar(10) NOT NULL DEFAULT 'monthly',
    tier_name       varchar(30),
    subtotal        numeric(14,2) NOT NULL,
    vat_pct         numeric(5,2) NOT NULL DEFAULT 11.00,
    vat_amount      numeric(14,2) NOT NULL,
    total_amount    numeric(14,2) NOT NULL,
    issued_at       timestamptz NOT NULL DEFAULT now(),
    due_date        date,
    paid_at         timestamptz,
    status          varchar(20) NOT NULL DEFAULT 'unpaid',
    notes           text,
    created_by      bigint REFERENCES geoportal.accounts(acc_id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_invoices_acc_id ON geoportal.invoices(acc_id);

-- 2g. revenue_shares
CREATE TABLE IF NOT EXISTS geoportal.revenue_shares (
    revenue_id      bigserial PRIMARY KEY,
    provider_id     integer NOT NULL REFERENCES geoportal.data_providers(provider_id),
    period_start    date NOT NULL,
    period_end      date NOT NULL,
    total_downloads integer NOT NULL DEFAULT 0,
    gross_revenue   numeric(18,2) NOT NULL DEFAULT 0,
    provider_share  numeric(18,2) NOT NULL DEFAULT 0,
    platform_share  numeric(18,2) NOT NULL DEFAULT 0,
    payment_status  varchar(16) NOT NULL DEFAULT 'pending',
    paid_at         timestamptz,
    notes           text,
    created_at      timestamptz NOT NULL DEFAULT now(),
    UNIQUE (provider_id, period_start, period_end)
);

-- 2h. pending_registrations
CREATE TABLE IF NOT EXISTS geoportal.pending_registrations (
    pending_id      serial PRIMARY KEY,
    full_name       varchar(120) NOT NULL,
    email           varchar(160) NOT NULL,
    password_hash   text NOT NULL,
    tier_name       varchar(30) NOT NULL,
    billing_cycle   varchar(10) NOT NULL DEFAULT 'monthly',
    status          varchar(20) NOT NULL DEFAULT 'pending_payment',
    rejection_note  text,
    reviewed_by     bigint REFERENCES geoportal.accounts(acc_id) ON DELETE SET NULL,
    expires_at      timestamptz NOT NULL DEFAULT (now() + interval '7 days'),
    created_at      timestamptz NOT NULL DEFAULT now(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    midtrans_order_id varchar(100),
    CHECK (billing_cycle IN ('monthly','annual')),
    CHECK (status IN ('pending_payment','approved','rejected','expired'))
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_pending_reg_active_email
    ON geoportal.pending_registrations(email) WHERE status = 'pending_payment';

-- 2i. pending_organizations
CREATE TABLE IF NOT EXISTS geoportal.pending_organizations (
    pending_id      serial PRIMARY KEY,
    org_name        varchar(200) NOT NULL,
    org_email       varchar(160) NOT NULL,
    contact_name    varchar(120) NOT NULL,
    seat_count      integer NOT NULL DEFAULT 5,
    billing_cycle   varchar(10) NOT NULL DEFAULT 'monthly',
    status          varchar(20) NOT NULL DEFAULT 'pending_payment',
    rejection_note  text,
    reviewed_by     bigint REFERENCES geoportal.accounts(acc_id) ON DELETE SET NULL,
    expires_at      timestamptz NOT NULL DEFAULT (now() + interval '7 days'),
    created_at      timestamptz NOT NULL DEFAULT now(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    midtrans_order_id varchar(100),
    CHECK (billing_cycle IN ('monthly','annual')),
    CHECK (seat_count >= 1 AND seat_count <= 100),
    CHECK (status IN ('pending_payment','approved','rejected','expired'))
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_pending_org_active_email
    ON geoportal.pending_organizations(org_email) WHERE status = 'pending_payment';

-- 2j. organization_members
CREATE TABLE IF NOT EXISTS geoportal.organization_members (
    member_id       serial PRIMARY KEY,
    organization_id bigint NOT NULL REFERENCES geoportal.organizations(org_id) ON DELETE CASCADE,
    acc_id          bigint NOT NULL REFERENCES geoportal.accounts(acc_id) ON DELETE CASCADE,
    is_admin        boolean NOT NULL DEFAULT false,
    joined_at       timestamptz NOT NULL DEFAULT now(),
    UNIQUE (organization_id, acc_id)
);
CREATE INDEX IF NOT EXISTS idx_org_members_acc ON geoportal.organization_members(acc_id);
CREATE INDEX IF NOT EXISTS idx_org_members_org ON geoportal.organization_members(organization_id);

-- 2k. team_invitations
CREATE TABLE IF NOT EXISTS geoportal.team_invitations (
    invite_id       serial PRIMARY KEY,
    org_id          bigint NOT NULL REFERENCES geoportal.organizations(org_id) ON DELETE CASCADE,
    invited_email   varchar(255) NOT NULL,
    token           char(64) NOT NULL UNIQUE,
    invited_by      bigint NOT NULL REFERENCES geoportal.accounts(acc_id) ON DELETE CASCADE,
    expires_at      timestamptz NOT NULL DEFAULT (now() + interval '7 days'),
    accepted_at     timestamptz,
    cancelled_at    timestamptz,
    created_at      timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_team_inv_email ON geoportal.team_invitations(invited_email);
CREATE INDEX IF NOT EXISTS idx_team_inv_org   ON geoportal.team_invitations(org_id);

-- 2l. land_administrative_areas
CREATE TABLE IF NOT EXISTS geoportal.land_administrative_areas (
    adm_id    bigserial PRIMARY KEY,
    adm_name  text NOT NULL,
    adm_level smallint NOT NULL DEFAULT 1,
    adm_code  varchar(10),
    geom      geometry
);
CREATE INDEX IF NOT EXISTS idx_land_adm_geom ON geoportal.land_administrative_areas USING GIST(geom);

-- 2m. point_administrative_areas
CREATE TABLE IF NOT EXISTS geoportal.point_administrative_areas (
    point_id bigint NOT NULL REFERENCES geoportal.point_grav_anom(point_id) ON DELETE CASCADE,
    adm_id   bigint NOT NULL REFERENCES geoportal.land_administrative_areas(adm_id) ON DELETE CASCADE,
    PRIMARY KEY (point_id, adm_id)
);

-- 2n. raster_administrative_areas
CREATE TABLE IF NOT EXISTS geoportal.raster_administrative_areas (
    raster_id bigint NOT NULL REFERENCES geoportal.raster_grav_anom(raster_id) ON DELETE CASCADE,
    adm_id    bigint NOT NULL REFERENCES geoportal.land_administrative_areas(adm_id) ON DELETE CASCADE,
    PRIMARY KEY (raster_id, adm_id)
);

-- 2o. dataset_metadata_xml
CREATE TABLE IF NOT EXISTS geoportal.dataset_metadata_xml (
    metadata_level      text PRIMARY KEY,
    source_path         text NOT NULL,
    file_identifier     text,
    parent_identifier   text,
    hierarchy_level_name text,
    metadata_date       date,
    language_code       text,
    character_set       text,
    title               text,
    abstract            text,
    individual_name     text,
    organisation_name   text,
    position_name       text,
    voice               text,
    delivery_point      text,
    city                text,
    administrative_area text,
    postal_code         text,
    country             text,
    emails_json         jsonb,
    contact_role        text,
    raw_xml             xml NOT NULL,
    imported_at         timestamptz NOT NULL DEFAULT now()
);

-- 2p. staging_gravity_points
CREATE TABLE IF NOT EXISTS geoportal.staging_gravity_points (
    staged_point_id bigserial PRIMARY KEY,
    dataset_id      bigint REFERENCES geoportal.datasets_grav_anom(dataset_id),
    acc_id          bigint NOT NULL REFERENCES geoportal.accounts(acc_id),
    point_value     double precision NOT NULL,
    point_obs_type  varchar(20) NOT NULL DEFAULT 'terrestrial',
    source_file     text,
    geom            geometry(Point,4326) NOT NULL,
    staged_at       timestamptz NOT NULL DEFAULT now(),
    review_status   varchar(20) NOT NULL DEFAULT 'pending',
    reviewed_by     bigint REFERENCES geoportal.accounts(acc_id),
    reviewed_at     timestamptz,
    review_notes    text
);

-- 2q. staging_gravity_rasters
CREATE TABLE IF NOT EXISTS geoportal.staging_gravity_rasters (
    staged_raster_id bigserial PRIMARY KEY,
    dataset_id       bigint REFERENCES geoportal.datasets_grav_anom(dataset_id),
    acc_id           bigint NOT NULL REFERENCES geoportal.accounts(acc_id),
    raster_resolution numeric,
    source_file      text,
    staged_at        timestamptz NOT NULL DEFAULT now(),
    review_status    varchar(20) NOT NULL DEFAULT 'pending',
    reviewed_by      bigint REFERENCES geoportal.accounts(acc_id),
    reviewed_at      timestamptz,
    review_notes     text
);

-- 2r. dataset_user_submissions (MetadataSubmissionService)
CREATE TABLE IF NOT EXISTS geoportal.dataset_user_submissions (
    id                    bigserial PRIMARY KEY,
    metadata_file_identifier text NOT NULL,
    jenis_data            text NOT NULL,
    provinsi              text NOT NULL,
    level_data            text NOT NULL,
    bahasa                text NOT NULL,
    character_set         text NOT NULL,
    hierarchy_level       text NOT NULL,
    metadata_date_stamp   date NOT NULL,
    individual_name       text NOT NULL,
    organisation_name     text NOT NULL,
    position_name         text,
    voice                 text,
    email                 text NOT NULL,
    delivery_point        text,
    city                  text,
    administrative_area   text,
    postal_code           text,
    country               text NOT NULL DEFAULT 'Indonesia',
    abstract              text NOT NULL,
    purpose               text,
    topic_category        text,
    keywords              text[],
    west_bound_lon        numeric(9,6),
    east_bound_lon        numeric(9,6),
    south_bound_lat       numeric(9,6),
    north_bound_lat       numeric(9,6),
    temporal_begin        date,
    temporal_end          date,
    lineage_statement     text,
    projection_crs        text DEFAULT 'WGS 1984',
    acc_id                bigint REFERENCES geoportal.accounts(acc_id),
    stored_files          jsonb DEFAULT '[]',
    submission_key        text UNIQUE,
    submitted_at          timestamptz NOT NULL DEFAULT now()
);


-- ════════════════════════════════════════════════════════════
-- BAGIAN 3: MIGRASI DATA dari gravport.* dan auth.*
-- ════════════════════════════════════════════════════════════

-- 3a. point_grav_anom: isi source_file dan orthometric_height
-- (join kembali ke gravport.anomaly_gravity_point_data via geom matching — lambat)
-- Lebih efisien: truncate + re-insert dengan kolom baru
TRUNCATE geoportal.point_grav_anom RESTART IDENTITY CASCADE;

INSERT INTO geoportal.point_grav_anom
    (dataset_id, point_value, point_obs_type, geom, source_file, orthometric_height)
SELECT
    d.dataset_id,
    ROUND(p.point_value::numeric, 3),
    p.point_obs_type,
    p.geom,
    p.source_file,
    ((p.point_metadata ->> 'orthometric_height'))::double precision
FROM gravport.anomaly_gravity_point_data p
JOIN geoportal.datasets_grav_anom d
    ON d.dataset_anom_type = p.point_anom_type
    AND d.dataset_level    = p.data_level
WHERE p.status = 'active';

-- 3b. login_otps (dari auth.login_otps)
-- user_id dalam auth = acc_id dalam geoportal (sama nilainya karena migrasi dengan explicit id)
INSERT INTO geoportal.login_otps (id, acc_id, otp_code, expires_at, used, created_at)
SELECT id, user_id, otp_code, expires_at, used, created_at
FROM auth.login_otps
WHERE user_id IN (SELECT acc_id FROM geoportal.accounts)
ON CONFLICT (id) DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.login_otps','id'),
    COALESCE((SELECT MAX(id) FROM geoportal.login_otps), 1));

-- 3c. password_resets (dari auth.password_resets)
INSERT INTO geoportal.password_resets (id, acc_id, token, expires_at, used, created_at)
SELECT id, user_id, token, expires_at, used, created_at
FROM auth.password_resets
WHERE user_id IN (SELECT acc_id FROM geoportal.accounts)
ON CONFLICT DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.password_resets','id'),
    COALESCE((SELECT MAX(id) FROM geoportal.password_resets), 1));

-- 3d. data_providers (dari gravport.data_providers)
INSERT INTO geoportal.data_providers
    (provider_id, provider_name, provider_type, contact_email, contact_person,
     revenue_share_pct, bank_account, bank_name, is_active, notes, joined_at)
SELECT provider_id, provider_name, provider_type, contact_email, contact_person,
       revenue_share_pct, bank_account, bank_name, is_active, notes, joined_at
FROM gravport.data_providers
ON CONFLICT (provider_name) DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.data_providers','provider_id'),
    COALESCE((SELECT MAX(provider_id) FROM geoportal.data_providers), 1));

-- 3e. api_keys (dari gravport.api_keys — user_id = acc_id)
INSERT INTO geoportal.api_keys
    (key_id, acc_id, key_name, key_prefix, key_hash, scopes, created_at, last_used_at, revoked_at)
SELECT key_id, user_id, key_name, key_prefix, key_hash, scopes, created_at, last_used_at, revoked_at
FROM gravport.api_keys
WHERE user_id IN (SELECT acc_id FROM geoportal.accounts)
ON CONFLICT DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.api_keys','key_id'),
    COALESCE((SELECT MAX(key_id) FROM geoportal.api_keys), 1));

-- 3f. download_transactions (dari gravport.download_transactions — user_id = acc_id)
INSERT INTO geoportal.download_transactions
    (transaction_id, acc_id, provider_id, dataset_code, dataset_type, filter_params,
     row_count, download_size_bytes, transaction_amount, gravport_commission,
     provider_revenue, status, user_agent, downloaded_at)
SELECT transaction_id,
       CASE WHEN user_id IN (SELECT acc_id FROM geoportal.accounts) THEN user_id ELSE NULL END,
       CASE WHEN provider_id IN (SELECT provider_id FROM geoportal.data_providers) THEN provider_id ELSE NULL END,
       dataset_code, dataset_type, filter_params, row_count, download_size_bytes,
       transaction_amount, gravport_commission, provider_revenue, status, user_agent, downloaded_at
FROM gravport.download_transactions
ON CONFLICT DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.download_transactions','transaction_id'),
    COALESCE((SELECT MAX(transaction_id) FROM geoportal.download_transactions), 1));

-- 3g. invoices (dari gravport.invoices — user_id = acc_id)
INSERT INTO geoportal.invoices
    (invoice_id, invoice_number, acc_id, subs_id, billing_cycle, tier_name,
     subtotal, vat_pct, vat_amount, total_amount, issued_at, due_date,
     paid_at, status, notes, created_by)
SELECT gi.invoice_id, gi.invoice_number,
       CASE WHEN gi.user_id IN (SELECT acc_id FROM geoportal.accounts) THEN gi.user_id ELSE NULL END,
       gs.subs_id,
       gi.billing_cycle, gi.tier_name, gi.subtotal, gi.vat_pct, gi.vat_amount,
       gi.total_amount, gi.issued_at, gi.due_date, gi.paid_at, gi.status, gi.notes,
       CASE WHEN gi.created_by IN (SELECT acc_id FROM geoportal.accounts) THEN gi.created_by ELSE NULL END
FROM gravport.invoices gi
LEFT JOIN geoportal.subscriptions gs ON gs.subs_id = gi.subscription_id
ON CONFLICT (invoice_number) DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.invoices','invoice_id'),
    COALESCE((SELECT MAX(invoice_id) FROM geoportal.invoices), 1));

-- 3h. revenue_shares (dari gravport.revenue_shares)
INSERT INTO geoportal.revenue_shares
    (revenue_id, provider_id, period_start, period_end, total_downloads,
     gross_revenue, provider_share, platform_share, payment_status, paid_at, notes, created_at)
SELECT rs.revenue_id, rs.provider_id, rs.period_start, rs.period_end, rs.total_downloads,
       rs.gross_revenue, rs.provider_share, rs.platform_share, rs.payment_status,
       rs.paid_at, rs.notes, rs.created_at
FROM gravport.revenue_shares rs
WHERE rs.provider_id IN (SELECT provider_id FROM geoportal.data_providers)
ON CONFLICT (provider_id, period_start, period_end) DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.revenue_shares','revenue_id'),
    COALESCE((SELECT MAX(revenue_id) FROM geoportal.revenue_shares), 1));

-- 3i. pending_registrations (dari gravport.pending_registrations)
INSERT INTO geoportal.pending_registrations
    (pending_id, full_name, email, password_hash, tier_name, billing_cycle,
     status, rejection_note, reviewed_by, expires_at, created_at, updated_at, midtrans_order_id)
SELECT pending_id, full_name, email, password_hash, tier_name, billing_cycle,
       status, rejection_note,
       CASE WHEN reviewed_by IN (SELECT acc_id FROM geoportal.accounts) THEN reviewed_by ELSE NULL END,
       expires_at, created_at, updated_at, midtrans_order_id
FROM gravport.pending_registrations
ON CONFLICT DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.pending_registrations','pending_id'),
    COALESCE((SELECT MAX(pending_id) FROM geoportal.pending_registrations), 1));

-- 3j. pending_organizations (dari gravport.pending_organizations)
INSERT INTO geoportal.pending_organizations
    (pending_id, org_name, org_email, contact_name, seat_count, billing_cycle,
     status, rejection_note, reviewed_by, expires_at, created_at, updated_at, midtrans_order_id)
SELECT pending_id, org_name, org_email, contact_name, seat_count, billing_cycle,
       status, rejection_note,
       CASE WHEN reviewed_by IN (SELECT acc_id FROM geoportal.accounts) THEN reviewed_by ELSE NULL END,
       expires_at, created_at, updated_at, midtrans_order_id
FROM gravport.pending_organizations
ON CONFLICT DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.pending_organizations','pending_id'),
    COALESCE((SELECT MAX(pending_id) FROM geoportal.pending_organizations), 1));

-- 3k. organization_members (dari gravport.organization_members)
-- organization_id dalam gravport = org_id dalam geoportal (migrasi explicit id)
INSERT INTO geoportal.organization_members
    (member_id, organization_id, acc_id, is_admin, joined_at)
SELECT member_id, organization_id, user_id, is_admin, joined_at
FROM gravport.organization_members
WHERE organization_id IN (SELECT org_id FROM geoportal.organizations)
  AND user_id IN (SELECT acc_id FROM geoportal.accounts)
ON CONFLICT DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.organization_members','member_id'),
    COALESCE((SELECT MAX(member_id) FROM geoportal.organization_members), 1));

-- Sinkron accounts.org_id dari organization_members
UPDATE geoportal.accounts a
SET org_id = om.organization_id
FROM geoportal.organization_members om
WHERE om.acc_id = a.acc_id
  AND a.org_id IS DISTINCT FROM om.organization_id;

-- 3l. team_invitations (dari gravport.team_invitations)
-- org_id dalam gravport = org_id dalam geoportal (sama)
INSERT INTO geoportal.team_invitations
    (invite_id, org_id, invited_email, token, invited_by, expires_at, accepted_at, cancelled_at, created_at)
SELECT invite_id, org_id, invited_email, token, invited_by, expires_at, accepted_at, cancelled_at, created_at
FROM gravport.team_invitations
WHERE org_id IN (SELECT org_id FROM geoportal.organizations)
  AND invited_by IN (SELECT acc_id FROM geoportal.accounts)
ON CONFLICT DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.team_invitations','invite_id'),
    COALESCE((SELECT MAX(invite_id) FROM geoportal.team_invitations), 1));

-- 3m. land_administrative_areas (dari gravport.land_administrative_areas)
INSERT INTO geoportal.land_administrative_areas
    (adm_id, adm_name, adm_level, adm_code, geom)
OVERRIDING SYSTEM VALUE
SELECT adm_id, adm_name, adm_level, adm_code, geom
FROM gravport.land_administrative_areas
ON CONFLICT DO NOTHING;

SELECT setval(pg_get_serial_sequence('geoportal.land_administrative_areas','adm_id'),
    COALESCE((SELECT MAX(adm_id) FROM geoportal.land_administrative_areas), 1));

-- 3n. point_administrative_areas (dari gravport — point_id sudah match karena re-insert)
-- Perlu rebuild karena point_id baru dari GENERATED ALWAYS
-- Map via geom match
INSERT INTO geoportal.point_administrative_areas (point_id, adm_id)
SELECT gp.point_id, gpa.adm_id
FROM gravport.point_administrative_areas gpa
JOIN gravport.anomaly_gravity_point_data old_p ON old_p.point_id = gpa.point_id
JOIN geoportal.point_grav_anom gp
    ON gp.geom = old_p.geom
    AND gp.point_value = ROUND(old_p.point_value::numeric, 3)
WHERE gpa.adm_id IN (SELECT adm_id FROM geoportal.land_administrative_areas)
ON CONFLICT DO NOTHING;

-- 3o. raster_administrative_areas (dari gravport)
INSERT INTO geoportal.raster_administrative_areas (raster_id, adm_id)
SELECT gr.raster_id, gra.adm_id
FROM gravport.raster_administrative_areas gra
JOIN gravport.raster_tiles old_r ON old_r.tile_id = gra.raster_id
JOIN geoportal.raster_grav_anom gr ON gr.rast = old_r.rast
WHERE gra.adm_id IN (SELECT adm_id FROM geoportal.land_administrative_areas)
ON CONFLICT DO NOTHING;

-- 3p. dataset_metadata_xml (dari gravport.dataset_metadata_xml)
INSERT INTO geoportal.dataset_metadata_xml
SELECT * FROM gravport.dataset_metadata_xml
ON CONFLICT (metadata_level) DO NOTHING;


-- ════════════════════════════════════════════════════════════
-- BAGIAN 4: VIEWS pengganti gravport.* views
-- ════════════════════════════════════════════════════════════

-- faa_l1_points
CREATE OR REPLACE VIEW geoportal.faa_l1_points AS
SELECT
    p.point_id AS id,
    p.point_value AS anomaly_value,
    ST_X(p.geom) AS longitude,
    ST_Y(p.geom) AS latitude,
    p.orthometric_height,
    p.geom,
    p.source_file,
    p.point_obs_type AS survey_mode
FROM geoportal.point_grav_anom p
JOIN geoportal.datasets_grav_anom d ON d.dataset_id = p.dataset_id
WHERE d.dataset_anom_type = 'FAA' AND d.dataset_level = 1;

-- cba_l1_points
CREATE OR REPLACE VIEW geoportal.cba_l1_points AS
SELECT
    p.point_id AS id,
    p.point_value AS anomaly_value,
    ST_X(p.geom) AS longitude,
    ST_Y(p.geom) AS latitude,
    p.orthometric_height,
    p.geom,
    p.source_file,
    p.point_obs_type AS survey_mode
FROM geoportal.point_grav_anom p
JOIN geoportal.datasets_grav_anom d ON d.dataset_id = p.dataset_id
WHERE d.dataset_anom_type = 'CBA' AND d.dataset_level = 1;

-- faa_l2_raster
CREATE OR REPLACE VIEW geoportal.faa_l2_raster AS
SELECT
    r.raster_id AS rid,
    r.rast,
    ST_Envelope(r.rast)::geometry(Polygon,4326) AS grid_geom
FROM geoportal.raster_grav_anom r
JOIN geoportal.datasets_grav_anom d ON d.dataset_id = r.dataset_id
WHERE d.dataset_anom_type = 'FAA' AND d.dataset_level = 2;

-- cba_l2_raster
CREATE OR REPLACE VIEW geoportal.cba_l2_raster AS
SELECT
    r.raster_id AS rid,
    r.rast,
    ST_Envelope(r.rast)::geometry(Polygon,4326) AS grid_geom
FROM geoportal.raster_grav_anom r
JOIN geoportal.datasets_grav_anom d ON d.dataset_id = r.dataset_id
WHERE d.dataset_anom_type = 'CBA' AND d.dataset_level = 2;

-- datasets (pengganti gravport.datasets view)
CREATE OR REPLACE VIEW geoportal.datasets AS
SELECT
    d.dataset_id AS id,
    d.dataset_name AS title,
    'national' AS spatial_scope,
    'Indonesia' AS country_name,
    true AS is_viewable,
    true AS is_downloadable,
    COALESCE(d.metadata_path, '') AS description,
    false AS is_dummy,
    d.created_at,
    d.created_at AS updated_at,
    CASE WHEN d.dataset_level = 2 THEN 'raster' ELSE 'point' END AS data_kind,
    d.dataset_anom_type AS anom_type,
    d.dataset_level AS data_level,
    d.acc_id AS organization_id,
    d.dataset_anom_type AS anomaly_type,
    d.dataset_level AS data_level_num
FROM geoportal.datasets_grav_anom d;

COMMIT;

-- ════════════════════════════════════════════════════════════
-- VERIFIKASI FINAL
-- ════════════════════════════════════════════════════════════
SELECT relname AS tabel, n_live_tup AS baris
FROM pg_stat_user_tables
WHERE schemaname = 'geoportal'
ORDER BY relname;
