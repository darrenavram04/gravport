#!/usr/bin/env python3
"""
deploy.py — Upload GravPort ke server 167.205.88.206 via SCP + SSH
Jalankan: python deploy.py

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

# Direktori/file yang DILEWATI saat upload (terlalu besar atau tidak perlu)
SKIP_DIRS = {
    'vendor',            # akan di-install ulang via composer di server
    '.git',
    '.claude',
    '.codex_tmp',
    '__pycache__',
    'services/auth-api/node_modules',  # akan di-install ulang via npm
    'writable/cache',
    'writable/session',
    'writable/tmp',
}

SKIP_FILES = {
    '.env',                        # akan di-upload terpisah
    'services/auth-api/.env',      # akan di-upload terpisah
    'build/check_server.py',
    'build/deploy.py',
}

# ── Helper ────────────────────────────────────────────────────
def connect():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    print(f"  Menghubungkan ke {SERVER}:{SSH_PORT} sebagai {SSH_USER}...")
    ssh.connect(SERVER, port=SSH_PORT, username=SSH_USER, password=SSH_PASS, timeout=30)
    print("  Terhubung!\n")
    return ssh

def run(ssh, cmd, ignore_error=False):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=300)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    exit_code = stdout.channel.recv_exit_status()
    if exit_code != 0 and not ignore_error:
        print(f"  [WARN] exit={exit_code}: {err[:200]}")
    return out, err, exit_code

def should_skip(rel_path: str) -> bool:
    rel_posix = rel_path.replace('\\', '/')
    for skip in SKIP_DIRS:
        if rel_posix == skip or rel_posix.startswith(skip + '/'):
            return True
    return rel_posix in SKIP_FILES

def upload_dir(sftp, local_dir: Path, remote_dir: str, progress_cb=None):
    total = 0
    for item in local_dir.rglob('*'):
        rel = item.relative_to(local_dir.parent)
        rel_str = str(rel).replace('\\', '/')
        if should_skip(rel_str):
            continue
        remote_path = f"{REMOTE_ROOT}/{rel_str}"
        if item.is_dir():
            try:
                sftp.mkdir(remote_path)
            except IOError:
                pass  # sudah ada
        elif item.is_file():
            try:
                sftp.put(str(item), remote_path)
                total += 1
                if progress_cb:
                    progress_cb(total, str(rel))
            except Exception as e:
                print(f"  [SKIP] {rel_str}: {e}")
    return total

# ── Langkah-langkah deployment ───────────────────────────────
def step_check_server(ssh):
    print("── [STEP 1] Cek server ──────────────────────────────")
    out, _, _ = run(ssh, "uname -a && whoami && df -h / | tail -1")
    print(f"  {out[:300]}")

def step_prepare_remote(ssh):
    print("\n── [STEP 2] Siapkan direktori di server ──────────────")
    commands = [
        f"mkdir -p {REMOTE_ROOT}",
        f"mkdir -p {REMOTE_ROOT}/writable/{{cache,logs,session,tmp}}",
        f"mkdir -p {REMOTE_ROOT}/services/auth-api",
        f"mkdir -p /var/log/gravport",
    ]
    for cmd in commands:
        run(ssh, cmd)
    print("  Direktori siap.")

def step_upload_files(ssh):
    print("\n── [STEP 3] Upload file aplikasi ─────────────────────")
    sftp = ssh.open_sftp()
    count = [0]

    def progress(n, path):
        count[0] = n
        if n % 50 == 0:
            print(f"  ... {n} file ter-upload ({path})")

    total = upload_dir(sftp, LOCAL_ROOT, REMOTE_ROOT, progress)
    sftp.close()
    print(f"  Total: {total} file ter-upload")

def step_upload_configs(ssh):
    print("\n── [STEP 4] Upload konfigurasi production ────────────")
    sftp = ssh.open_sftp()
    build_dir = LOCAL_ROOT / 'build'

    uploads = [
        (build_dir / 'prod.env',         f"{REMOTE_ROOT}/.env"),
        (build_dir / 'auth-api.prod.env', f"{REMOTE_ROOT}/services/auth-api/.env"),
        (build_dir / 'dump_full_with_data.sql', f"{REMOTE_ROOT}/build/dump_full_with_data.sql"),
    ]
    for local, remote in uploads:
        if local.exists():
            sftp.put(str(local), remote)
            print(f"  OK: {local.name} → {remote}")
        else:
            print(f"  SKIP (tidak ada): {local}")
    sftp.close()

def step_run_server_setup(ssh):
    print("\n── [STEP 5] Jalankan server setup scripts ─────────────")
    scripts = [
        f"chmod +x {REMOTE_ROOT}/build/01_server_setup.sh",
        f"chmod +x {REMOTE_ROOT}/build/02_setup_database.sh",
        f"chmod +x {REMOTE_ROOT}/build/03_deploy_app.sh",
        f"sudo bash {REMOTE_ROOT}/build/01_server_setup.sh 2>&1 | tail -20",
        f"sudo bash {REMOTE_ROOT}/build/02_setup_database.sh 2>&1 | tail -20",
        f"sudo bash {REMOTE_ROOT}/build/03_deploy_app.sh 2>&1 | tail -30",
    ]
    for cmd in scripts:
        print(f"\n  Menjalankan: {cmd[:60]}...")
        out, err, code = run(ssh, cmd, ignore_error=True)
        if out:
            for line in out.split('\n')[-10:]:
                print(f"    {line}")

def step_verify(ssh):
    print("\n── [STEP 6] Verifikasi deployment ─────────────────────")
    checks = [
        ("Apache", "systemctl is-active apache2"),
        ("PHP",    "php -v | head -1"),
        ("PG",     "sudo -u postgres psql -c '\\l' | grep geoportal"),
        ("Auth API", "curl -s http://127.0.0.1:4010/health"),
        ("URL test", f"curl -s -o /dev/null -w '%{{http_code}}' http://127.0.0.1/"),
    ]
    for label, cmd in checks:
        out, _, code = run(ssh, cmd, ignore_error=True)
        status = "OK" if out else "WARN"
        print(f"  [{status}] {label}: {out[:80] if out else 'tidak ada output'}")

# ── Main ──────────────────────────────────────────────────────
def main():
    print("=" * 60)
    print("  GravPort Deployment Script")
    print(f"  Target: {SERVER}")
    print("=" * 60 + "\n")

    try:
        ssh = connect()
    except Exception as e:
        print(f"\nERROR: Tidak bisa terhubung ke server: {e}")
        print("\nKemungkinan penyebab:")
        print("  1. SSH port 22 diblokir firewall")
        print("  2. Layanan SSH belum aktif di server")
        print("  3. Kredensial salah")
        print("\nSolusi: Lihat DEPLOYMENT_GUIDE.md untuk panduan manual")
        sys.exit(1)

    try:
        step_check_server(ssh)
        step_prepare_remote(ssh)
        step_upload_files(ssh)
        step_upload_configs(ssh)
        step_run_server_setup(ssh)
        step_verify(ssh)
    finally:
        ssh.close()

    print("\n" + "=" * 60)
    print("  DEPLOYMENT SELESAI!")
    print(f"  Akses: http://{SERVER}/")
    print("=" * 60)

if __name__ == '__main__':
    main()
