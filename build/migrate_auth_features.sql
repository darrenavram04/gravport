-- Migration: tambah tabel untuk forgot password dan 2FA OTP
-- Jalankan sekali terhadap database MockUp, schema auth.

-- Tabel token reset password
CREATE TABLE IF NOT EXISTS auth.password_resets (
    id         SERIAL PRIMARY KEY,
    user_id    INTEGER NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    token      VARCHAR(128) NOT NULL UNIQUE,
    expires_at TIMESTAMPTZ NOT NULL,
    used       BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_password_resets_token   ON auth.password_resets (token);
CREATE INDEX IF NOT EXISTS idx_password_resets_user_id ON auth.password_resets (user_id);

-- Tabel OTP untuk 2FA login
CREATE TABLE IF NOT EXISTS auth.login_otps (
    id         SERIAL PRIMARY KEY,
    user_id    INTEGER NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    otp_code   VARCHAR(8) NOT NULL,
    expires_at TIMESTAMPTZ NOT NULL,
    used       BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_login_otps_user_id ON auth.login_otps (user_id);
