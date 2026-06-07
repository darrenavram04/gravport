-- ============================================================
-- GravPort — Migration: API Keys, Invoices, Team Invitations
-- Run: psql -U postgres -p 5433 -d gravport -f migrate_api_invoice.sql
-- ============================================================

SET search_path TO gravport, public;

-- ── 1. API KEYS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS gravport.api_keys (
    key_id        SERIAL       PRIMARY KEY,
    user_id       INT          NOT NULL,           -- app-enforced ref to auth.users.id
    key_name      VARCHAR(100) NOT NULL,           -- user-defined label (e.g. "QGIS script")
    key_prefix    CHAR(8)      NOT NULL,           -- first 8 chars of plain key, shown in UI list
    key_hash      CHAR(64)     NOT NULL UNIQUE,    -- SHA-256(plain_key), used for lookup
    scopes        TEXT[]       NOT NULL DEFAULT ARRAY['read'],  -- 'read', 'download'
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    last_used_at  TIMESTAMPTZ,
    revoked_at    TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS idx_api_keys_user   ON gravport.api_keys(user_id);
CREATE INDEX IF NOT EXISTS idx_api_keys_hash   ON gravport.api_keys(key_hash);
CREATE INDEX IF NOT EXISTS idx_api_keys_active ON gravport.api_keys(user_id) WHERE revoked_at IS NULL;

-- ── 2. INVOICES ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS gravport.invoices (
    invoice_id      SERIAL        PRIMARY KEY,
    invoice_number  VARCHAR(30)   NOT NULL UNIQUE,  -- e.g. INV-2026-0001
    user_id         INT           NOT NULL,          -- app-enforced ref to auth.users.id
    subscription_id INT,                             -- ref to gravport.subscriptions
    billing_cycle   VARCHAR(10)   NOT NULL DEFAULT 'monthly',  -- monthly / annual
    tier_name       VARCHAR(30),                     -- solo, pro, team, …
    subtotal        NUMERIC(14,2) NOT NULL,
    vat_pct         NUMERIC(5,2)  NOT NULL DEFAULT 11.00,  -- PPN 11%
    vat_amount      NUMERIC(14,2) NOT NULL,
    total_amount    NUMERIC(14,2) NOT NULL,
    issued_at       TIMESTAMPTZ   NOT NULL DEFAULT now(),
    due_date        DATE,
    paid_at         TIMESTAMPTZ,
    status          VARCHAR(20)   NOT NULL DEFAULT 'unpaid',  -- unpaid / paid / cancelled
    notes           TEXT,
    created_by      INT           -- superadmin user_id who generated this invoice
);
CREATE INDEX IF NOT EXISTS idx_invoices_user   ON gravport.invoices(user_id);
CREATE INDEX IF NOT EXISTS idx_invoices_status ON gravport.invoices(status);
CREATE INDEX IF NOT EXISTS idx_invoices_number ON gravport.invoices(invoice_number);

-- ── 3. TEAM INVITATIONS ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS gravport.team_invitations (
    invite_id      SERIAL        PRIMARY KEY,
    org_id         INT           NOT NULL,           -- ref to gravport.organizations
    invited_email  VARCHAR(255)  NOT NULL,
    token          CHAR(64)      NOT NULL UNIQUE,    -- random hex token for accept link
    invited_by     INT           NOT NULL,           -- user_id of org admin who invited
    expires_at     TIMESTAMPTZ   NOT NULL DEFAULT (now() + INTERVAL '7 days'),
    accepted_at    TIMESTAMPTZ,
    cancelled_at   TIMESTAMPTZ,
    created_at     TIMESTAMPTZ   NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_team_inv_org   ON gravport.team_invitations(org_id);
CREATE INDEX IF NOT EXISTS idx_team_inv_email ON gravport.team_invitations(invited_email);
CREATE INDEX IF NOT EXISTS idx_team_inv_token ON gravport.team_invitations(token);

-- ── 4. UPDATE SUBSCRIPTION TIER PRICES ───────────────────────
-- Pro: 299,000 → 349,000 monthly / 2,990,000 → 3,490,000 annual
UPDATE gravport.subscription_tiers
SET    monthly_fee = 349000,
       annual_fee  = 3490000
WHERE  tier_name   = 'pro';

-- Verify update
SELECT tier_name, monthly_fee, annual_fee
FROM   gravport.subscription_tiers
ORDER  BY tier_id;
