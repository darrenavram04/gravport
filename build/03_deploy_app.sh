#!/bin/bash
# ============================================================
# 03_deploy_app.sh — Deploy aplikasi GravPort ke server
# Jalankan sebagai: sudo bash 03_deploy_app.sh
# Asumsi: file sudah ada di /var/www/geoportal/
# ============================================================
set -e

APP_DIR="/var/www/geoportal"
DB_PASS="Gr4vP0rt!Itb@2025"   # Harus sama dengan 02_setup_database.sh

echo "=== [1/7] Set permission direktori ==="
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 755 {} \;
find "$APP_DIR" -type f -exec chmod 644 {} \;
chmod -R 775 "$APP_DIR/writable"
chmod -R 775 "$APP_DIR/public"

echo "=== [2/7] Salin file .env production ==="
cp "$APP_DIR/build/prod.env" "$APP_DIR/.env"

# Update password di .env
sed -i "s/GANTI_DENGAN_PASSWORD_KUAT/${DB_PASS}/g" "$APP_DIR/.env"

# Update .env auth-api
cp "$APP_DIR/build/auth-api.prod.env" "$APP_DIR/services/auth-api/.env"
sed -i "s/GANTI_DENGAN_PASSWORD_KUAT/${DB_PASS}/g" "$APP_DIR/services/auth-api/.env"

echo "=== [3/7] Install PHP dependencies (Composer) ==="
cd "$APP_DIR"
composer install --no-dev --optimize-autoloader --no-interaction

echo "=== [4/7] Generate encryption key ==="
php spark key:generate --force

echo "=== [5/7] Setup Apache virtual host ==="
cp "$APP_DIR/build/geoportal.conf" /etc/apache2/sites-available/geoportal.conf
a2dissite 000-default.conf 2>/dev/null || true
a2ensite geoportal.conf
a2enmod rewrite
systemctl reload apache2

echo "=== [6/7] Setup auth-api Node.js service ==="
cd "$APP_DIR/services/auth-api"
npm install --production

mkdir -p /var/log/gravport
chown -R www-data:www-data /var/log/gravport

# Salin ecosystem config
cp "$APP_DIR/build/ecosystem.config.js" "$APP_DIR/services/auth-api/ecosystem.config.js"

# Start dengan PM2
pm2 delete gravport-auth-api 2>/dev/null || true
pm2 start "$APP_DIR/services/auth-api/ecosystem.config.js"
pm2 save

echo "=== [7/7] Verifikasi ==="
echo "--- Apache status ---"
systemctl is-active apache2

echo "--- PHP test ---"
php -r "echo 'PHP OK: ' . phpversion() . PHP_EOL;"

echo "--- Database test ---"
PGPASSWORD="${DB_PASS}" psql -h 127.0.0.1 -U geoportal_user -d geoportal -c "SELECT 'DB OK' as status;" 2>/dev/null || echo "WARN: DB test gagal"

echo "--- Auth API test ---"
sleep 2
curl -s http://127.0.0.1:4010/health | head -1 || echo "WARN: Auth API belum respond (tunggu beberapa detik)"

echo "--- PM2 status ---"
pm2 status

echo ""
echo "============================================"
echo "DEPLOYMENT SELESAI!"
echo "Geoportal dapat diakses di: http://167.205.88.206/"
echo "============================================"
