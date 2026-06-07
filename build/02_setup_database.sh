#!/bin/bash
# ============================================================
# 02_setup_database.sh — Setup database PostgreSQL 18 untuk GravPort
# Jalankan sebagai: sudo bash 02_setup_database.sh
# ============================================================
set -e

DB_NAME="geoportal"
DB_USER="geoportal_user"
DB_PASS="Gr4vP0rt!Itb@2025"

# Pastikan PostgreSQL 18 berjalan
systemctl start postgresql
sleep 2

echo "=== [1/5] Verifikasi PostgreSQL 18 ==="
PG_VER=$(sudo -u postgres psql -c "SELECT split_part(version(), ' ', 2);" -t 2>/dev/null | xargs | cut -d. -f1)
echo "  PostgreSQL major version: ${PG_VER}"
if [ "${PG_VER}" -lt 18 ]; then
    echo "ERROR: PostgreSQL 18 diperlukan, ditemukan versi ${PG_VER}"
    echo "Jalankan 01_server_setup.sh terlebih dahulu"
    exit 1
fi

echo "=== [2/5] Buat user dan database ==="
sudo -u postgres psql <<EOF
DO \$\$
BEGIN
  IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '${DB_USER}') THEN
    CREATE ROLE ${DB_USER} LOGIN PASSWORD '${DB_PASS}';
  END IF;
END
\$\$;

SELECT 'CREATE DATABASE ${DB_NAME} OWNER ${DB_USER} ENCODING UTF8 TEMPLATE template0'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '${DB_NAME}')\gexec

GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};
ALTER DATABASE ${DB_NAME} OWNER TO ${DB_USER};
EOF

echo "=== [3/5] Install ekstensi PostGIS ==="
sudo -u postgres psql -d "${DB_NAME}" <<EOF
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS postgis_raster;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
EOF

echo "=== [4/5] Buat schema auth dan public + permission ==="
sudo -u postgres psql -d "${DB_NAME}" <<EOF
CREATE SCHEMA IF NOT EXISTS auth;
CREATE SCHEMA IF NOT EXISTS public;
CREATE SCHEMA IF NOT EXISTS testing;

GRANT ALL ON SCHEMA auth TO ${DB_USER};
GRANT ALL ON SCHEMA public TO ${DB_USER};
GRANT ALL ON SCHEMA testing TO ${DB_USER};

GRANT ALL ON ALL TABLES IN SCHEMA auth TO ${DB_USER};
GRANT ALL ON ALL TABLES IN SCHEMA public TO ${DB_USER};

ALTER DEFAULT PRIVILEGES IN SCHEMA auth GRANT ALL ON TABLES TO ${DB_USER};
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO ${DB_USER};
ALTER DEFAULT PRIVILEGES IN SCHEMA testing GRANT ALL ON TABLES TO ${DB_USER};

ALTER DEFAULT PRIVILEGES IN SCHEMA auth GRANT ALL ON SEQUENCES TO ${DB_USER};
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO ${DB_USER};
ALTER DEFAULT PRIVILEGES IN SCHEMA testing GRANT ALL ON SEQUENCES TO ${DB_USER};

ALTER DEFAULT PRIVILEGES GRANT ALL ON FUNCTIONS TO ${DB_USER};
EOF

echo "=== [5/5] Import dump database ==="
DUMP_MAIN="/var/www/geoportal/build/dump_full_with_data.sql"
DUMP_GRAVPORT="/var/www/geoportal/build/dump_gravport.sql"

if [ -f "$DUMP_MAIN" ]; then
    echo "  Import: $DUMP_MAIN ..."
    sudo -u postgres psql -d "${DB_NAME}" -f "$DUMP_MAIN" 2>&1 | tail -5
    echo "  OK: dump utama berhasil di-import"
else
    echo "  WARN: $DUMP_MAIN tidak ditemukan — lewati"
fi

if [ -f "$DUMP_GRAVPORT" ]; then
    echo "  Import: $DUMP_GRAVPORT (data WebMap/gravity) ..."
    sudo -u postgres psql -d "${DB_NAME}" -f "$DUMP_GRAVPORT" 2>&1 | tail -5
    echo "  OK: dump gravport berhasil di-import"
else
    echo "  WARN: $DUMP_GRAVPORT tidak ditemukan — lewati (WebMap mungkin tidak tersedia)"
fi

# Berikan akses ke user setelah import
sudo -u postgres psql -d "${DB_NAME}" <<EOF
GRANT ALL ON ALL TABLES IN SCHEMA auth TO ${DB_USER};
GRANT ALL ON ALL TABLES IN SCHEMA public TO ${DB_USER};
GRANT ALL ON ALL SEQUENCES IN SCHEMA auth TO ${DB_USER};
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO ${DB_USER};
EOF

echo ""
echo "=== DATABASE SETUP SELESAI ==="
echo "Database:  ${DB_NAME}"
echo "User:      ${DB_USER}"
echo "Password:  ${DB_PASS}"
echo ""
echo "Langkah selanjutnya: jalankan 03_deploy_app.sh"
