# Panduan Deployment GravPort ke Server 167.205.88.206

## Situasi Saat Ini

Server 167.205.88.206 **bisa di-ping** dari jaringan ITB tapi **SSH port 22 terblokir**.
Ini kemungkinan karena firewall server belum dikonfigurasi untuk menerima koneksi.

---

## OPSI A — Deploy Otomatis (jika SSH bisa diakses)

Jika port 22 sudah terbuka, cukup jalankan:

```
python build\deploy.py
```

Script ini akan:
1. Upload semua file via SCP
2. Install Apache, PHP, PostgreSQL, Node.js di server
3. Import database
4. Start semua service
5. Verifikasi deployment

---

## OPSI B — Deploy Manual (langkah demi langkah)

### Langkah 1: Buka akses SSH ke server

**Jika Anda punya akses fisik ke server atau console:**
```bash
# Di server, buka SSH dari IP kampus
ufw allow from 167.205.88.0/24 to any port 22
# ATAU
iptables -I INPUT -p tcp --dport 22 -j ACCEPT
```

**Jika perlu hubungi IT ITB:**
Minta agar port 22 dibuka untuk IP Anda (10.32.102.203) ke server 167.205.88.206.

### Langkah 2: SSH ke server (dari terminal Windows/Mac/Linux)

Buka **PowerShell** atau **Command Prompt**, ketik:

```
ssh client_24_1@167.205.88.206
```

Masukkan password: `7DoNtForGeT#9`

### Langkah 3: Upload file ke server

Dari PowerShell di komputer Anda (bukan di server), jalankan:

```powershell
# Upload seluruh folder geoportal (kecuali vendor dan node_modules)
scp -r C:\xampp\htdocs\geoportal client_24_1@167.205.88.206:/var/www/
```

Untuk file besar, gunakan rsync (jika tersedia):
```bash
rsync -avz --progress \
  --exclude='vendor/' \
  --exclude='.git/' \
  --exclude='services/auth-api/node_modules/' \
  --exclude='writable/cache/' \
  --exclude='writable/session/' \
  C:/xampp/htdocs/geoportal/ \
  client_24_1@167.205.88.206:/var/www/geoportal/
```

### Langkah 4: Setup server (jalankan di server via SSH)

```bash
# Masuk ke server
ssh client_24_1@167.205.88.206

# Jalankan scripts berurutan
sudo bash /var/www/geoportal/build/01_server_setup.sh
sudo bash /var/www/geoportal/build/02_setup_database.sh
sudo bash /var/www/geoportal/build/03_deploy_app.sh
```

---

## File yang Sudah Disiapkan

| File | Keterangan |
|------|------------|
| `build/prod.env` | .env production untuk app PHP |
| `build/auth-api.prod.env` | .env production untuk auth-api Node.js |
| `build/dump_full_with_data.sql` | Export database PostgreSQL (20KB) |
| `build/01_server_setup.sh` | Install Apache, PHP, PostgreSQL, Node.js |
| `build/02_setup_database.sh` | Setup database `geoportal` + import data |
| `build/03_deploy_app.sh` | Deploy app + start services |
| `build/geoportal.conf` | Apache virtual host config |
| `build/ecosystem.config.js` | PM2 config untuk auth-api |
| `build/deploy.py` | Script deploy otomatis via SSH |

---

## Konfigurasi Production

**Database di server:**
- Nama: `geoportal`
- User: `geoportal_user`
- Password: `Gr4vP0rt!Itb@2025`
- Port: 5432 (default PostgreSQL Linux)

**URL aplikasi:** `http://167.205.88.206/`

**Auth API:** `http://127.0.0.1:4010` (hanya localhost, tidak expose ke publik)

---

## Verifikasi Setelah Deploy

```bash
# Di server
systemctl status apache2          # Apache harus "active"
systemctl status postgresql        # PostgreSQL harus "active"
pm2 status                         # gravport-auth-api harus "online"
curl http://127.0.0.1/             # Harus return HTML
curl http://127.0.0.1:4010/health  # Harus return {"status":"ok"}
```

---

## Troubleshooting

### PHP tidak bisa connect ke PostgreSQL
```bash
# Cek ekstensi PHP
php -m | grep pgsql
# Install jika belum ada
sudo apt-get install -y php8.2-pgsql
sudo systemctl restart apache2
```

### Auth API tidak bisa start
```bash
pm2 logs gravport-auth-api
# Cek .env sudah benar
cat /var/www/geoportal/services/auth-api/.env
```

### File besar (model 3D, site) tidak ter-upload
```bash
# Upload ulang hanya file besar
scp -r C:\xampp\htdocs\geoportal\public\model client_24_1@167.205.88.206:/var/www/geoportal/public/
scp -r C:\xampp\htdocs\geoportal\public\site client_24_1@167.205.88.206:/var/www/geoportal/public/
```

### Permission denied
```bash
sudo chown -R www-data:www-data /var/www/geoportal
sudo chmod -R 775 /var/www/geoportal/writable
```
