#!/usr/bin/env python3
"""
deploy.py — Deploy GravPort ke server 167.205.88.206 via SSH/SFTP
Jalankan: python build/deploy.py

Persyaratan:
  pip install paramiko
"""

import os
import sys
import time
import paramiko
from pathlib import Path

# ── Konfigurasi ──────────────────────────────────────────────
SERVER   = '167.205.88.206'
SSH_USER = 'client_24_1'
SSH_PASS = '7DoNtForGeT#9'
SSH_PORT = 22

LOCAL_ROOT  = Path(__file__).parent.parent  # c:\xampp\htdocs\geoportal
REMOTE_ROOT = '/var/www/geoportal'

# Direktori/file yang DILEWATI saat upload
SKIP_DIRS = {
    'vendor',
    '.git',
    '.claude',
    '.codex_tmp',
    '__pycache__',
    'node_modules',
    'services/auth-api/node_modules',
    'writable/cache',
    'writable/session',
    'writable/tmp',
    'build/logs',
    'build/.phpunit.cache',
}

SKIP_FILES = {
    '.env',
    'services/auth-api/.env',
}

# ── Helper ────────────────────────────────────────────────────
def connect():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    print(f"  Menghubungkan ke {SERVER}:{SSH_PORT} sebagai {SSH_USER}...")
    ssh.connect(SERVER, port=SSH_PORT, username=SSH_USER, password=SSH_PASS, timeout=30)
    print("  Terhubung!\n")
    return ssh

def run(ssh, cmd, ignore_error=False, timeout=600):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout, get_pty=True)
    out_lines = []
    for line in stdout:
        line = line.rstrip('\n')
        out_lines.append(line)
        print(f"    {line}")
    exit_code = stdout.channel.recv_exit_status()
    out = '\n'.join(out_lines)
    if exit_code != 0 and not ignore_error:
        print(f"  [WARN] exit={exit_code}")
    return out, "", exit_code

def should_skip(rel_path: str) -> bool:
    rel_posix = rel_path.replace('\\', '/')
    # Cek prefix direktori
    for skip in SKIP_DIRS:
        if rel_posix == skip or rel_posix.startswith(skip + '/'):
            return True
    # Cek nama file exact
    for skip in SKIP_FILES:
        if rel_posix == skip:
            return True
    return False

def sftp_mkdir_p(sftp, remote_dir):
    parts = remote_dir.split('/')
    path = ''
    for part in parts:
        if not part:
            path = '/'
            continue
        path = f"{path}/{part}" if path != '/' else f"/{part}"
        try:
            sftp.stat(path)
        except IOError:
            try:
                sftp.mkdir(path)
            except IOError:
                pass

def upload_dir(sftp, local_dir: Path, progress_cb=None):
    total = 0
    for item in sorted(local_dir.rglob('*')):
        rel = item.relative_to(local_dir)
        rel_str = str(rel).replace('\\', '/')
        if should_skip(rel_str):
            continue
        remote_path = f"{REMOTE_ROOT}/{rel_str}"
        if item.is_dir():
            sftp_mkdir_p(sftp, remote_path)
        elif item.is_file():
            try:
                sftp.put(str(item), remote_path)
                total += 1
                if progress_cb:
                    progress_cb(total, rel_str)
            except Exception as e:
                print(f"  [SKIP] {rel_str}: {e}")
    return total

# ── Langkah-langkah deployment ───────────────────────────────
def step_check_server(ssh):
    print("── [STEP 1] Cek kondisi server ──────────────────────")
    run(ssh, "uname -a", ignore_error=True)
    run(ssh, "whoami", ignore_error=True)
    run(ssh, "df -h / | tail -1", ignore_error=True)
    run(ssh, "free -h | grep Mem", ignore_error=True)

def step_clean_old_app(ssh):
    print("\n── [STEP 2] Hapus instalasi lama & siapkan direktori ──")
    print("  Menghapus file lama di /var/www/geoportal/ ...")
    # Hapus konten lama tapi pertahankan direktori utama
    run(ssh, f"rm -rf {REMOTE_ROOT}", ignore_error=True)
    run(ssh, f"mkdir -p {REMOTE_ROOT}", ignore_error=True)
    run(ssh, f"mkdir -p {REMOTE_ROOT}/writable/{{cache,logs,session,tmp}}", ignore_error=True)
    run(ssh, f"mkdir -p /var/log/gravport", ignore_error=True)
    print("  Direktori bersih dan siap.")

def step_upload_files(ssh):
    print("\n── [STEP 3] Upload file aplikasi ─────────────────────")
    sftp = ssh.open_sftp()
    count = [0]

    def progress(n, path):
        count[0] = n
        if n % 100 == 0:
            print(f"  ... {n} file ter-upload ({path})")

    total = upload_dir(sftp, LOCAL_ROOT, progress)
    sftp.close()
    print(f"  Total: {total} file ter-upload")

def step_upload_configs(ssh):
    print("\n── [STEP 4] Upload konfigurasi & database production ─")
    sftp = ssh.open_sftp()
    build_dir = LOCAL_ROOT / 'build'

    uploads = [
        (build_dir / 'prod.env',              f"{REMOTE_ROOT}/.env"),
        (build_dir / 'auth-api.prod.env',     f"{REMOTE_ROOT}/services/auth-api/.env"),
        (build_dir / 'dump_full_with_data.sql', f"{REMOTE_ROOT}/build/dump_full_with_data.sql"),
        (build_dir / 'dump_gravport.sql',     f"{REMOTE_ROOT}/build/dump_gravport.sql"),
    ]
    for local, remote in uploads:
        if local.exists():
            print(f"  Upload: {local.name} ({local.stat().st_size // 1024} KB)...")
            sftp.put(str(local), remote)
            print(f"  OK: {local.name} → {remote}")
        else:
            print(f"  SKIP (tidak ada): {local.name}")
    sftp.close()

def step_run_server_setup(ssh):
    print("\n── [STEP 5] Install dependencies server ──────────────")
    cmds = [
        f"chmod +x {REMOTE_ROOT}/build/01_server_setup.sh",
        f"chmod +x {REMOTE_ROOT}/build/02_setup_database.sh",
        f"chmod +x {REMOTE_ROOT}/build/03_deploy_app.sh",
    ]
    for cmd in cmds:
        run(ssh, cmd, ignore_error=True)

    print("\n  Menjalankan 01_server_setup.sh (install PG 18, Apache, PHP, Node)...")
    print("  (Ini mungkin memakan 5-10 menit pertama kali)")
    run(ssh, f"sudo bash {REMOTE_ROOT}/build/01_server_setup.sh", timeout=900)

def step_setup_database(ssh):
    print("\n── [STEP 6] Setup database PostgreSQL 18 ─────────────")
    run(ssh, f"sudo bash {REMOTE_ROOT}/build/02_setup_database.sh", timeout=600)

def step_deploy_app(ssh):
    print("\n── [STEP 7] Deploy aplikasi ───────────────────────────")
    run(ssh, f"sudo bash {REMOTE_ROOT}/build/03_deploy_app.sh", timeout=600)

def step_verify(ssh):
    print("\n── [STEP 8] Verifikasi deployment ─────────────────────")
    checks = [
        ("Apache",    "systemctl is-active apache2"),
        ("PHP",       "php -v | head -1"),
        ("PG versi",  "sudo -u postgres psql -c 'SELECT version();' -t | head -1"),
        ("DB test",   "sudo -u postgres psql -d geoportal -c 'SELECT count(*) FROM information_schema.tables;' -t"),
        ("Auth API",  "curl -sf http://127.0.0.1:4010/health"),
        ("HTTP test", "curl -sf -o /dev/null -w '%{http_code}' http://127.0.0.1/"),
        ("PM2",       "pm2 status"),
    ]
    print()
    for label, cmd in checks:
        out, _, code = run(ssh, cmd, ignore_error=True)
        status = "OK" if (code == 0 and out.strip()) else "WARN"
        print(f"  [{status}] {label}")

# ── Main ──────────────────────────────────────────────────────
def main():
    print("=" * 60)
    print("  GravPort Deployment Script — PostgreSQL 18")
    print(f"  Target: {SERVER}")
    print(f"  Source: {LOCAL_ROOT}")
    print("=" * 60 + "\n")

    try:
        ssh = connect()
    except Exception as e:
        print(f"\nERROR: Tidak bisa terhubung ke server: {e}")
        print("\nKemungkinan penyebab:")
        print("  1. SSH port 22 diblokir firewall")
        print("  2. Layanan SSH belum aktif di server")
        print("  3. Kredensial salah")
        sys.exit(1)

    try:
        step_check_server(ssh)
        step_clean_old_app(ssh)
        step_upload_files(ssh)
        step_upload_configs(ssh)
        step_run_server_setup(ssh)
        step_setup_database(ssh)
        step_deploy_app(ssh)
        step_verify(ssh)
    finally:
        ssh.close()

    print("\n" + "=" * 60)
    print("  DEPLOYMENT SELESAI!")
    print(f"  Akses: http://{SERVER}/")
    print("=" * 60)

if __name__ == '__main__':
    main()
