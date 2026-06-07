#!/bin/bash
# ============================================================
# 01_server_setup.sh — Setup server Ubuntu 24.04 untuk GravPort
# Jalankan sebagai: sudo bash 01_server_setup.sh
# Server: 167.205.88.206
# ============================================================
set -e

echo "=== [1/8] Update sistem ==="
apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get upgrade -y

echo "=== [2/8] Install tools dasar ==="
apt-get install -y curl wget gnupg2 lsb-release unzip git ca-certificates

echo "=== [3/8] Install Apache2 + PHP 8.3 ==="
DEBIAN_FRONTEND=noninteractive apt-get install -y \
    apache2 \
    php8.3 \
    php8.3-cli \
    php8.3-common \
    php8.3-pgsql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-intl \
    php8.3-gd \
    libapache2-mod-php8.3 2>/dev/null || \
DEBIAN_FRONTEND=noninteractive apt-get install -y \
    apache2 \
    php8.2 php8.2-cli php8.2-common php8.2-pgsql \
    php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip \
    php8.2-intl php8.2-gd libapache2-mod-php8.2

echo "=== [4/8] Install Composer ==="
if ! command -v composer &>/dev/null; then
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    rm composer-setup.php
    mv composer.phar /usr/local/bin/composer
fi
composer --version

echo "=== [5/8] Install PostgreSQL 18 (PGDG repository) ==="
# Hapus PostgreSQL lama jika ada
if dpkg -l | grep -q postgresql-16; then
    echo "  Menghapus PostgreSQL 16 lama..."
    systemctl stop postgresql 2>/dev/null || true
    apt-get remove -y --purge postgresql-16 postgresql-client-16 postgresql-common 2>/dev/null || true
    apt-get autoremove -y 2>/dev/null || true
fi
if dpkg -l | grep -q postgresql-14; then
    systemctl stop postgresql 2>/dev/null || true
    apt-get remove -y --purge postgresql-14 postgresql-client-14 postgresql-common 2>/dev/null || true
fi

# Tambah PGDG repository
install -d /usr/share/postgresql-common/pgdg
curl -fsSL "https://www.postgresql.org/media/keys/ACCC4CF8.asc" \
    -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc

sh -c 'echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" \
    > /etc/apt/sources.list.d/pgdg.list'

apt-get update

# Install PostgreSQL 18 + PostGIS 3
DEBIAN_FRONTEND=noninteractive apt-get install -y postgresql-18 postgresql-client-18 postgresql-18-postgis-3

systemctl enable postgresql
systemctl start postgresql

echo "  PostgreSQL version: $(sudo -u postgres psql -c 'SELECT version();' -t | head -1 | xargs)"

echo "=== [6/8] Install Node.js 22 ==="
if ! command -v node &>/dev/null || [ "$(node -e 'process.stdout.write(process.version.split(".")[0].replace("v",""))')" -lt 20 ]; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y nodejs
fi
echo "  Node: $(node -v), npm: $(npm -v)"

echo "=== [7/8] Install PM2 (process manager untuk Node.js) ==="
npm install -g pm2
pm2 startup systemd -u root --hp /root 2>/dev/null || true
systemctl enable pm2-root 2>/dev/null || true

echo "=== [8/8] Aktifkan Apache modules ==="
a2enmod rewrite
a2enmod headers
systemctl enable apache2
systemctl start apache2
systemctl reload apache2

echo ""
echo "=== SETUP SELESAI ==="
echo "Apache: $(apache2 -v 2>&1 | head -1)"
echo "PHP:    $(php -v | head -1)"
echo "PG:     $(sudo -u postgres psql -c 'SELECT version();' -t | head -1 | xargs)"
echo "Node:   $(node -v)"
echo "PM2:    $(pm2 -v)"
echo ""
echo "Langkah selanjutnya: jalankan 02_setup_database.sh"
