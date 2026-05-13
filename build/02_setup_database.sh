#!/bin/bash
# ============================================================
# 02_setup_database.sh — Setup database PostgreSQL untuk GravPort
# Jalankan sebagai: sudo bash 02_setup_database.sh
# ============================================================
set -e

DB_NAME="geoportal"
DB_USER="geoportal_user"
DB_PASS="Gr4vP0rt!Itb@2025"   # Ganti jika perlu

echo "=== [1/4] Buat user dan database ==="
sudo -u postgres psql <<EOF
-- Buat user jika belum ada
DO \$\$
BEGIN
  IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '${DB_USER}') THEN
    CREATE ROLE ${DB_USER} LOGIN PASSWORD '${DB_PASS}';
  END IF;
END
\$\$;

-- Buat database
SELECT 'CREATE DATABASE ${DB_NAME} OWNER ${DB_USER}'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '${DB_NAME}')\gexec

-- Berikan hak akses
GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};
EOF

echo "=== [2/4] Install ekstensi PostGIS ==="
sudo -u postgres psql -d "${DB_NAME}" <<EOF
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
EOF

echo "=== [3/4] Buat schema auth dan public ==="
sudo -u postgres psql -d "${DB_NAME}" <<EOF
CREATE SCHEMA IF NOT EXISTS auth;
CREATE SCHEMA IF NOT EXISTS public;
GRANT ALL ON SCHEMA auth TO ${DB_USER};
GRANT ALL ON SCHEMA public TO ${DB_USER};
GRANT ALL ON ALL TABLES IN SCHEMA auth TO ${DB_USER};
GRANT ALL ON ALL TABLES IN SCHEMA public TO ${DB_USER};
ALTER DEFAULT PRIVILEGES IN SCHEMA auth GRANT ALL ON TABLES TO ${DB_USER};
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO ${DB_USER};
ALTER DEFAULT PRIVILEGES IN SCHEMA auth GRANT ALL ON SEQUENCES TO ${DB_USER};
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO ${DB_USER};
EOF

echo "=== [4/4] Import dump database ==="
DUMP_FILE="/var/www/geoportal/build/dump_full_with_data.sql"
if [ -f "$DUMP_FILE" ]; then
    sudo -u postgres psql -d "${DB_NAME}" -f "$DUMP_FILE"
    echo "Database berhasil di-import dari: $DUMP_FILE"
else
    echo "WARN: File dump tidak ditemukan di $DUMP_FILE"
    echo "Upload file dump_full_with_data.sql ke server terlebih dahulu"
fi

echo ""
echo "=== DATABASE SETUP SELESAI ==="
echo "Database:  ${DB_NAME}"
echo "User:      ${DB_USER}"
echo "Password:  ${DB_PASS}"
echo ""
echo "UPDATE password di:"
echo "  - /var/www/geoportal/.env"
echo "  - /var/www/geoportal/services/auth-api/.env"
echo ""
echo "Langkah selanjutnya: jalankan 03_deploy_app.sh"
