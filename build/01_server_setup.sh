#!/bin/bash
# ============================================================
# 01_server_setup.sh — Setup server Ubuntu/Debian untuk GravPort
# Jalankan sebagai: sudo bash 01_server_setup.sh
# Server: 167.205.88.206
# ============================================================
set -e

echo "=== [1/7] Update sistem ==="
apt-get update -y
apt-get upgrade -y

echo "=== [2/7] Install Apache2 + PHP 8.2 ==="
apt-get install -y \
    apache2 \
    php8.2 \
    php8.2-cli \
    php8.2-common \
    php8.2-pgsql \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-zip \
    php8.2-intl \
    php8.2-gd \
    libapache2-mod-php8.2 \
    unzip \
    curl \
    git \
    2>&1 || {
    # Fallback: coba PHP 8.1 jika 8.2 tidak ada
    apt-get install -y \
        php8.1 php8.1-cli php8.1-common php8.1-pgsql \
        php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip \
        php8.1-intl php8.1-gd libapache2-mod-php8.1
}

echo "=== [3/7] Install Composer ==="
if ! command -v composer &>/dev/null; then
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        echo "ERROR: Composer installer corrupt"
        rm composer-setup.php
        exit 1
    fi
    php composer-setup.php --quiet
    rm composer-setup.php
    mv composer.phar /usr/local/bin/composer
fi
composer --version

echo "=== [4/7] Install PostgreSQL ==="
apt-get install -y postgresql postgresql-contrib
systemctl enable postgresql
systemctl start postgresql

# Install PostGIS
apt-get install -y postgresql-16-postgis-3 2>/dev/null || \
apt-get install -y postgresql-15-postgis-3 2>/dev/null || \
apt-get install -y postgresql-14-postgis-3 2>/dev/null || \
echo "WARN: PostGIS tidak ditemukan — install manual nanti"

echo "=== [5/7] Install Node.js 20 ==="
if ! command -v node &>/dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
fi
node -v
npm -v

echo "=== [6/7] Install PM2 (process manager untuk Node.js) ==="
npm install -g pm2
pm2 startup systemd -u root --hp /root
systemctl enable pm2-root

echo "=== [7/7] Aktifkan Apache modules ==="
a2enmod rewrite
a2enmod headers
systemctl restart apache2

echo ""
echo "=== SETUP SELESAI ==="
echo "Apache: $(apache2 -v 2>&1 | head -1)"
echo "PHP:    $(php -v | head -1)"
echo "PG:     $(psql --version)"
echo "Node:   $(node -v)"
echo "PM2:    $(pm2 -v)"
echo ""
echo "Langkah selanjutnya: jalankan 02_setup_database.sh"
