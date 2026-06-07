<?php
$quota    = $quota  ?? ['tier' => 'none', 'used' => 0, 'limit' => null, 'allowed' => true];
$recent   = $recent ?? [];

$tier     = strtolower($quota['tier'] ?? 'none');
$used     = (int) ($quota['used']  ?? 0);
$limit    = $quota['limit'];
$fullName = (string) (session()->get('full_name') ?? '');
$email    = (string) (session()->get('email')     ?? '');
$role     = auth_current_role();

$isAdminRole = in_array($role, ['admin', 'superadmin'], true);

// Tier display - admin/superadmin get role label, not subscription tier
$tierLabel = match(true) {
    $role === 'superadmin' => 'Superadmin',
    $role === 'admin'      => 'Administrator',
    $tier === 'enterprise' => 'Enterprise',
    $tier === 'government' => 'Government',
    $tier === 'solo'       => 'Lite',
    $tier === 'lite'       => 'Lite',
    $tier === 'pro'        => 'Pro',
    $tier === 'Enterprise'       => 'Enterprise',
    default                => 'Free',
};

$isUnlimited = $isAdminRole || $limit === null;
$pct = (!$isUnlimited && $limit > 0) ? min(100, round($used / $limit * 100)) : 0;

$roleColors = [
    'superadmin' => ['accent' => '#8b4a17', 'glow' => 'rgba(167,96,37,.1)',  'icon' => 'shield-star-fill'],
    'admin'      => ['accent' => '#a76025', 'glow' => 'rgba(167,96,37,.09)', 'icon' => 'shield-check-fill'],
    'user'       => ['accent' => '#1a6cc7', 'glow' => 'rgba(26,108,199,.09)','icon' => 'person-fill'],
    'guest'      => ['accent' => '#4a6080', 'glow' => 'rgba(74,96,128,.08)', 'icon' => 'person'],
];
$rc = $roleColors[$role] ?? $roleColors['user'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Akun Saya</title>
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css') ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/fonts.css') ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=31') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
  <style>
    :root { --accent:#a76025; --accent-lt:#8b4a17; }

    body.account-page {
      background:
        radial-gradient(circle at top right, rgba(167,96,37,.14), transparent 28%),
        linear-gradient(180deg, #eff4f7 0%, #dfe7ee 100%);
      min-height: 100vh;
      font-family: 'Poppins', sans-serif;
      color: #142033;
    }

    .acc-shell {
      max-width: 900px;
      margin: 0 auto;
      padding: calc(var(--landing-header-offset, 112px) + 36px) 24px 72px;
    }

    /* ── Hero ── */
    .acc-hero {
      display: flex;
      align-items: flex-start;
      gap: 24px;
      margin-bottom: 40px;
    }
    .acc-avatar {
      width: 68px; height: 68px;
      border-radius: 50%;
      background: rgba(167,96,37,.1);
      border: 2px solid rgba(167,96,37,.22);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .acc-avatar i { font-size: 28px; color: <?= esc($rc['accent']) ?>; }
    .acc-hero-text { flex: 1; min-width: 0; }
    .acc-eyebrow {
      font-size: 11px; font-weight: 800; letter-spacing: .16em;
      text-transform: uppercase; color: var(--accent);
      margin-bottom: 6px;
    }
    .acc-name {
      font-size: clamp(24px, 4vw, 38px);
      font-weight: 800; line-height: 1.1;
      margin: 0 0 10px; color: #142033;
    }
    .acc-email {
      font-size: 14px; color: #6b7a8f;
      display: flex; align-items: center; gap: 6px;
    }
    .acc-role-pill {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 5px 14px;
      border-radius: 999px;
      font-size: 11px; font-weight: 900; letter-spacing: .12em;
      text-transform: uppercase;
      background: rgba(167,96,37,.1);
      color: <?= esc($rc['accent']) ?>;
      border: 1px solid rgba(167,96,37,.2);
      margin-bottom: 10px;
    }

    /* ── Stat row ── */
    .acc-stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }
    @media (max-width: 600px) { .acc-stats { grid-template-columns: 1fr 1fr; } }
    .acc-stat {
      background: #fff;
      border: 1px solid rgba(20,32,51,.08);
      border-radius: 20px;
      padding: 20px 22px;
      position: relative; overflow: hidden;
      box-shadow: 0 2px 12px rgba(16,24,40,.07);
    }
    .acc-stat::before {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(circle at top right, <?= esc($rc['glow']) ?>, transparent 60%);
      pointer-events: none;
    }
    .acc-stat-label {
      font-size: 10px; font-weight: 800; letter-spacing: .13em;
      text-transform: uppercase; color: #6b7a8f;
      margin-bottom: 8px;
    }
    .acc-stat-val {
      font-size: 26px; font-weight: 800;
      color: <?= esc($rc['accent']) ?>;
      line-height: 1;
    }
    .acc-stat-sub {
      font-size: 11px; color: #6b7a8f; margin-top: 4px;
    }

    /* ── Card ── */
    .acc-card {
      background: #fff;
      border: 1px solid rgba(20,32,51,.08);
      border-radius: 22px;
      padding: 26px 28px;
      margin-bottom: 20px;
      position: relative; overflow: hidden;
      box-shadow: 0 4px 24px rgba(16,24,40,.07);
    }
    .acc-card::before {
      content: '';
      position: absolute; top: 0; right: 0;
      width: 180px; height: 120px;
      background: radial-gradient(circle at top right, <?= esc($rc['glow']) ?>, transparent 65%);
      pointer-events: none;
    }
    .acc-card-head {
      font-size: 10px; font-weight: 800; letter-spacing: .15em;
      text-transform: uppercase; color: #6b7a8f;
      margin-bottom: 18px;
      display: flex; align-items: center; gap: 6px;
    }
    .acc-card-head i { font-size: 13px; color: <?= esc($rc['accent']) ?>; }

    /* Quota bar */
    .quota-row { display: flex; justify-content: space-between; font-size: 13px; color: #6b7a8f; margin-bottom: 8px; }
    .quota-bar { height: 8px; background: rgba(20,32,51,.1); border-radius: 99px; overflow: hidden; }
    .quota-bar-fill { height: 100%; border-radius: 99px; transition: width .4s ease; }

    /* Info rows */
    .info-row { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid rgba(20,32,51,.06); }
    .info-row:last-child { border-bottom: none; padding-bottom: 0; }
    .info-row i { font-size: 15px; color: #6b7a8f; width: 20px; flex-shrink: 0; }
    .info-row-label { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #6b7a8f; min-width: 70px; }
    .info-row-val { font-size: 14px; color: #142033; }

    /* Downloads table */
    .acc-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .acc-table th { color: #6b7a8f; font-size: 10px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; padding: 0 12px 10px 0; text-align: left; border-bottom: 1px solid rgba(20,32,51,.1); }
    .acc-table td { padding: 10px 12px 10px 0; color: #142033; border-bottom: 1px solid rgba(20,32,51,.05); }
    .acc-table tr:last-child td { border-bottom: none; }
    .ds-badge { display: inline-block; padding: 2px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
    .ds-badge.vector { background: rgba(26,108,199,.12); color: #1a6cc7; }
    .ds-badge.raster { background: rgba(167,96,37,.12); color: #8b4a17; }

    /* Upgrade banner - only for free/lite users */
    .upgrade-banner {
      background: linear-gradient(135deg, rgba(167,96,37,.08), rgba(167,96,37,.04));
      border: 1px solid rgba(167,96,37,.2);
      border-radius: 20px;
      padding: 22px 26px;
      display: flex; align-items: center; justify-content: space-between; gap: 16px;
      margin-bottom: 20px; flex-wrap: wrap;
    }
    .upgrade-banner p { margin: 0; font-size: 14px; color: #6b7a8f; }
    .upgrade-banner strong { color: var(--accent); }
    .btn-upgrade {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 10px 22px;
      background: var(--accent); color: #fff;
      border-radius: 999px; font-weight: 700; font-size: 14px;
      text-decoration: none; white-space: nowrap; border: none;
    }
    .btn-upgrade:hover { background: #8f501e; color: #fff; }

    /* Admin access card */
    .admin-access-card {
      background: linear-gradient(135deg, rgba(167,96,37,.07), rgba(167,96,37,.04));
      border: 1px solid rgba(167,96,37,.2);
      border-radius: 20px; padding: 22px 26px;
      margin-bottom: 20px;
      display: flex; align-items: center; gap: 18px;
    }
    .admin-access-icon { font-size: 32px; color: <?= esc($rc['accent']) ?>; flex-shrink: 0; }
    .admin-access-text p { margin: 0; font-size: 13px; color: #6b7a8f; line-height: 1.5; }
    .admin-access-text strong { color: <?= esc($rc['accent']) ?>; }

    .btn-logout {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 10px 22px;
      border: 1px solid rgba(20,32,51,.15);
      border-radius: 999px; font-size: 13px; font-weight: 700;
      color: #6b7a8f; text-decoration: none; margin-top: 8px;
    }
    .btn-logout:hover { border-color: rgba(200,50,50,.4); color: #c43030; }
  </style>
</head>
<body class="account-page gravport-landing">
<?= view('partials/site_header', ['activePage' => 'account', 'headerClass' => 'header--solid']) ?>

<div class="acc-shell">

  <!-- ── Hero ── -->
  <div class="acc-hero">
    <div class="acc-avatar">
      <i class="bi bi-<?= esc($rc['icon']) ?>"></i>
    </div>
    <div class="acc-hero-text">
      <div class="acc-eyebrow">Akun GravPort</div>
      <h1 class="acc-name"><?= esc($fullName ?: 'Pengguna GravPort') ?></h1>
      <div class="acc-role-pill">
        <i class="bi bi-<?= esc($rc['icon']) ?>"></i>
        <?= esc($tierLabel) ?>
      </div>
      <div class="acc-email">
        <i class="bi bi-envelope" style="font-size:12px"></i>
        <?= esc($email ?: '-') ?>
      </div>
    </div>
  </div>

  <!-- ── Stats ── -->
  <div class="acc-stats">
    <div class="acc-stat">
      <div class="acc-stat-label">Unduhan Bulan Ini</div>
      <div class="acc-stat-val"><?= number_format($used) ?></div>
      <div class="acc-stat-sub">
        <?php if ($isUnlimited): ?>
          <i class="bi bi-infinity"></i> Tanpa batas
        <?php else: ?>
          dari <?= number_format((int)$limit) ?> limit
        <?php endif; ?>
      </div>
    </div>
    <div class="acc-stat">
      <div class="acc-stat-label">Total Dataset</div>
      <div class="acc-stat-val"><?= count($recent) ?></div>
      <div class="acc-stat-sub">aktivitas terakhir</div>
    </div>
    <div class="acc-stat">
      <div class="acc-stat-label">Level Akses</div>
      <div class="acc-stat-val" style="font-size:18px;padding-top:4px"><?= esc($tierLabel) ?></div>
      <div class="acc-stat-sub" style="text-transform:capitalize"><?= esc($role) ?></div>
    </div>
  </div>

  <!-- ── Superadmin elevated access notice ── -->
  <?php if ($role === 'superadmin'): ?>
  <div class="admin-access-card">
    <i class="bi bi-shield-star-fill admin-access-icon"></i>
    <div class="admin-access-text">
      <p>
        <strong>Akses Superadmin</strong>
        - Akun ini memiliki hak akses penuh tanpa batasan ke seluruh sistem, data, dan fitur geoportal.
      </p>
      <p style="margin-top:6px">
        <a href="<?= site_url('dataset/manage') ?>" style="color:var(--accent-lt);font-weight:700;font-size:13px;text-decoration:none">
          <i class="bi bi-grid-fill"></i> Buka Admin Hub
        </a>
        <span style="color:rgba(255,255,255,.25);margin:0 8px">|</span>
        <a href="<?= site_url('admin') ?>" style="color:rgba(255,255,255,.45);font-weight:700;font-size:13px;text-decoration:none">
          Panel Admin
        </a>
      </p>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Quota card (users only) ── -->
  <?php if (!$isAdminRole && !$isUnlimited): ?>
  <div class="acc-card">
    <div class="acc-card-head"><i class="bi bi-bar-chart-fill"></i> Kuota Unduhan</div>
    <div class="quota-row">
      <span><?= number_format($used) ?> digunakan bulan ini</span>
      <span>Limit <?= number_format((int)$limit) ?>/bln</span>
    </div>
    <div class="quota-bar">
      <div class="quota-bar-fill"
           style="width:<?= $pct ?>%;
                  background:<?= $pct >= 90 ? '#ef4444' : ($pct >= 70 ? '#f0a500' : '#80bbf5') ?>">
      </div>
    </div>
    <?php if ($pct >= 100): ?>
      <p style="margin:10px 0 0;font-size:12px;color:#ef4444;">
        <i class="bi bi-exclamation-circle"></i> Kuota bulanan habis. Unduhan baru tidak dapat dilakukan.
      </p>
    <?php elseif ($pct >= 80): ?>
      <p style="margin:10px 0 0;font-size:12px;color:#f0a500;">
        <i class="bi bi-exclamation-triangle"></i> Kuota hampir habis (<?= $pct ?>%).
      </p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ── Profile info card ── -->
  <div class="acc-card">
    <div class="acc-card-head"><i class="bi bi-person-lines-fill"></i> Informasi Akun</div>
    <div class="info-row">
      <i class="bi bi-person-fill"></i>
      <span class="info-row-label">Nama</span>
      <span class="info-row-val"><?= esc($fullName ?: '-') ?></span>
    </div>
    <div class="info-row">
      <i class="bi bi-envelope-fill"></i>
      <span class="info-row-label">Email</span>
      <span class="info-row-val"><?= esc($email ?: '-') ?></span>
    </div>
    <div class="info-row">
      <i class="bi bi-shield-fill"></i>
      <span class="info-row-label">Role</span>
      <span class="info-row-val" style="text-transform:capitalize"><?= esc($role) ?></span>
    </div>
    <div class="info-row">
      <i class="bi bi-credit-card-fill"></i>
      <span class="info-row-label">Paket</span>
      <span class="info-row-val"><?= esc($tierLabel) ?></span>
    </div>
  </div>

  <!-- ── Recent downloads ── -->
  <div class="acc-card">
    <div class="acc-card-head"><i class="bi bi-clock-history"></i> Riwayat Unduhan Terbaru</div>
    <?php if (empty($recent)): ?>
      <p style="color:#6b7a8f;font-size:13px;margin:0;">Belum ada unduhan.</p>
    <?php else: ?>
      <div style="overflow-x:auto">
        <table class="acc-table">
          <thead>
            <tr>
              <th>Dataset</th>
              <th>Tipe</th>
              <th>Baris</th>
              <th>Waktu</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $row): ?>
            <tr>
              <td style="font-weight:700"><?= esc(strtoupper($row['dataset_code'] ?? '-')) ?></td>
              <td>
                <span class="ds-badge <?= esc($row['dataset_type'] ?? '') ?>">
                  <?= esc($row['dataset_type'] ?? '-') ?>
                </span>
              </td>
              <td><?= $row['row_count'] ? number_format((int)$row['row_count']) : '-' ?></td>
              <td style="white-space:nowrap; color:#6b7a8f; font-size:12px">
                <?= esc(date('d M Y H:i', strtotime($row['downloaded_at']))) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── Upgrade banner (Lite users only) ── -->
  <?php if (!$isAdminRole && $tier === 'solo'): ?>
  <div class="upgrade-banner">
    <p>
      <strong>Upgrade ke Pro - Rp 349.000/bulan</strong> untuk akses <strong>Level 2 GeoTIFF</strong>,
      unduhan unlimited, dan REST API access.
    </p>
    <a href="<?= site_url('signup') ?>" class="btn-upgrade">
      <i class="bi bi-arrow-up-circle-fill"></i>
      Upgrade Sekarang
    </a>
  </div>
  <?php endif; ?>

  <!-- ══════════════════════════════════════════════════════════════ -->
  <!-- NEW SECTION A: API Key Management (Pro+ only) -->
  <!-- ══════════════════════════════════════════════════════════════ -->
  <?php if ($isPro): ?>
  <div class="account-card" id="api-keys" style="margin-top:20px">
    <div class="card-header-row" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
      <h3 style="margin:0;font-size:1rem;font-weight:700;color:#142033">
        <i class="bi bi-key-fill" style="color:#a76025;margin-right:6px"></i>
        API Keys
      </h3>
      <span style="font-size:.78rem;color:#4a6080;background:rgba(20,32,51,.06);padding:3px 10px;border-radius:99px">Pro+</span>
    </div>

    <?php if (!empty($newApiKey)): ?>
    <div style="background:rgba(62,207,142,.08);border:1px solid rgba(62,207,142,.25);border-radius:12px;padding:14px 16px;margin-bottom:16px">
      <div style="font-size:.8rem;font-weight:700;color:#1a7a4a;margin-bottom:6px">
        <i class="bi bi-check-circle-fill"></i>
        API key baru dibuat - salin sekarang, tidak akan ditampilkan lagi!
      </div>
      <div style="font-family:'JetBrains Mono',monospace;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;font-size:.85rem;color:#065f46;word-break:break-all;letter-spacing:.03em">
        <?= esc($newApiKey) ?>
      </div>
      <button onclick="navigator.clipboard.writeText('<?= esc($newApiKey) ?>');this.textContent='✓ Tersalin!'" style="margin-top:8px;padding:5px 14px;background:#a76025;color:#fff;border:none;border-radius:8px;font-size:.8rem;cursor:pointer">
        Salin Key
      </button>
    </div>
    <?php endif; ?>

    <?php if (!empty($apiKeys)): ?>
    <div style="overflow-x:auto;margin-bottom:16px">
      <table style="width:100%;border-collapse:collapse;font-size:.83rem">
        <thead>
          <tr style="border-bottom:2px solid rgba(20,32,51,.08)">
            <th style="text-align:left;padding:6px 8px;color:#4a6080;font-weight:600">Nama</th>
            <th style="text-align:left;padding:6px 8px;color:#4a6080;font-weight:600">Prefix</th>
            <th style="text-align:left;padding:6px 8px;color:#4a6080;font-weight:600">Scopes</th>
            <th style="text-align:left;padding:6px 8px;color:#4a6080;font-weight:600">Dibuat</th>
            <th style="text-align:left;padding:6px 8px;color:#4a6080;font-weight:600">Terakhir digunakan</th>
            <th style="padding:6px 8px"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($apiKeys as $key): ?>
          <tr style="border-bottom:1px solid rgba(20,32,51,.06)">
            <td style="padding:8px;font-weight:600;color:#142033"><?= esc($key['key_name']) ?></td>
            <td style="padding:8px;font-family:'JetBrains Mono',monospace;color:#6b7280;font-size:.8rem">gp_<?= esc($key['key_prefix']) ?>...</td>
            <td style="padding:8px">
              <?php
              $scopeArr = is_array($key['scopes']) ? $key['scopes'] : explode(',', trim((string)$key['scopes'], '{}'));
              foreach ($scopeArr as $sc):
              ?>
              <span style="background:rgba(167,96,37,.1);color:#8b4a17;padding:2px 8px;border-radius:99px;font-size:.72rem;font-weight:600;margin-right:3px"><?= esc(trim($sc)) ?></span>
              <?php endforeach; ?>
            </td>
            <td style="padding:8px;color:#6b7280;font-size:.8rem"><?= esc(date('d M Y', strtotime($key['created_at']))) ?></td>
            <td style="padding:8px;color:#6b7280;font-size:.8rem"><?= $key['last_used_at'] ? esc(date('d M Y H:i', strtotime($key['last_used_at']))) : '<span style="color:#9ca3af">Belum digunakan</span>' ?></td>
            <td style="padding:8px">
              <form method="POST" action="<?= site_url('account/api-keys/' . $key['key_id'] . '/revoke') ?>" onsubmit="return confirm('Cabut key ini? Tindakan tidak dapat dibatalkan.')">
                <?= csrf_field() ?>
                <button type="submit" style="background:rgba(248,113,113,.1);color:#b91c1c;border:1px solid rgba(248,113,113,.3);padding:3px 10px;border-radius:6px;font-size:.75rem;cursor:pointer">Cabut</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p style="color:#6b7280;font-size:.87rem;margin-bottom:16px">Belum ada API key. Buat key pertama Anda untuk mengakses REST API geoportal.</p>
    <?php endif; ?>

    <!-- Generate new key form -->
    <?php if (count($apiKeys) < 5): ?>
    <details style="border:1px solid rgba(20,32,51,.1);border-radius:10px;padding:12px 16px">
      <summary style="cursor:pointer;font-weight:600;font-size:.87rem;color:#142033;list-style:none">
        <i class="bi bi-plus-circle" style="color:#a76025;margin-right:6px"></i> Buat API Key Baru
      </summary>
      <form method="POST" action="<?= site_url('account/api-keys/generate') ?>" style="margin-top:12px">
        <?= csrf_field() ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
          <div style="flex:1;min-width:180px">
            <label style="font-size:.78rem;color:#4a6080;display:block;margin-bottom:4px">Label key</label>
            <input type="text" name="key_name" placeholder="Contoh: QGIS script, Python ETL" required
              style="width:100%;padding:8px 12px;border:1px solid rgba(20,32,51,.15);border-radius:8px;font-size:.85rem;background:#fff;color:#142033">
          </div>
          <div>
            <label style="font-size:.78rem;color:#4a6080;display:block;margin-bottom:4px">Scopes</label>
            <div style="display:flex;gap:8px;align-items:center">
              <label style="font-size:.82rem;color:#4a6080"><input type="checkbox" name="scopes[]" value="read" checked> read</label>
              <label style="font-size:.82rem;color:#4a6080"><input type="checkbox" name="scopes[]" value="download"> download</label>
            </div>
          </div>
          <button type="submit" style="background:#a76025;color:#fff;border:none;padding:9px 18px;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;white-space:nowrap">
            <i class="bi bi-key"></i> Buat Key
          </button>
        </div>
      </form>
    </details>
    <?php else: ?>
    <p style="font-size:.8rem;color:#9ca3af">Batas 5 API key aktif tercapai. Cabut key lama untuk membuat yang baru.</p>
    <?php endif; ?>

    <div style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(20,32,51,.07)">
      <a href="<?= site_url('api/docs') ?>" style="font-size:.82rem;color:#a76025;text-decoration:none">
        <i class="bi bi-book"></i> Lihat dokumentasi API →
      </a>
    </div>
  </div>
  <?php endif; ?>

  <!-- ══════════════════════════════════════════════════════════════ -->
  <!-- NEW SECTION B: Team Management (Team tier admins) -->
  <!-- ══════════════════════════════════════════════════════════════ -->
  <?php if (!empty($org) && in_array($tier, ['Enterprise','enterprise','government'])): ?>
  <div class="account-card" style="margin-top:20px">
    <div style="display:flex;align-items:center;justify-content:space-between">
      <h3 style="margin:0;font-size:1rem;font-weight:700;color:#142033">
        <i class="bi bi-people-fill" style="color:#a76025;margin-right:6px"></i>
        Manajemen Tim
      </h3>
      <a href="<?= site_url('Enterprise') ?>" style="font-size:.82rem;color:#a76025;text-decoration:none;font-weight:600">
        Kelola Tim <i class="bi bi-arrow-right"></i>
      </a>
    </div>
    <div style="margin-top:12px;display:flex;gap:20px;flex-wrap:wrap">
      <div style="text-align:center">
        <div style="font-size:1.6rem;font-weight:800;color:#142033"><?= esc($org['seat_count'] ?? '-') ?></div>
        <div style="font-size:.75rem;color:#6b7280">Total Seat</div>
      </div>
      <div style="text-align:center">
        <div style="font-size:1.6rem;font-weight:800;color:#a76025">
          <?php
          $memberCount = 0;
          if (!empty($org['organization_id'])) {
            $orgModel = new \App\Models\OrganizationModel();
            $memberCount = count($orgModel->membersOf((int)$org['organization_id']));
          }
          echo $memberCount;
          ?>
        </div>
        <div style="font-size:.75rem;color:#6b7280">Seat Terpakai</div>
      </div>
      <div style="text-align:center">
        <div style="font-size:1.6rem;font-weight:800;color:#1a7a4a"><?= esc(max(0, ($org['seat_count'] ?? 0) - $memberCount)) ?></div>
        <div style="font-size:.75rem;color:#6b7280">Seat Tersisa</div>
      </div>
    </div>
    <p style="margin:10px 0 0;font-size:.8rem;color:#6b7280">Organisasi: <strong><?= esc($org['organization_name'] ?? '') ?></strong></p>
  </div>
  <?php endif; ?>

  <!-- ══════════════════════════════════════════════════════════════ -->
  <!-- NEW SECTION C: Subscription Info & Self-Service Actions -->
  <!-- ══════════════════════════════════════════════════════════════ -->
  <?php if (!empty($subscription)):
    // Map DB columns to display values
    // payment_status: 'S' = active, 'E' = expired/cancelled, 'P' = pending
    $subStatus     = $subscription['payment_status'] ?? 'E';
    $isActiveSub   = $subStatus === 'S';
    $subStatusLabel = match($subStatus) { 'S' => 'Aktif', 'E' => 'Berakhir', 'P' => 'Menunggu', default => ucfirst($subStatus) };
    $subEndAt      = $subscription['end_at'] ?? null;
    $subCycle      = $subscription['payment_cycle'] ?? 'M';
    $subCycleLabel = $subCycle === 'Y' ? 'Tahunan' : 'Bulanan';
  ?>
  <div class="account-card" style="margin-top:20px">
    <h3 style="margin:0 0 12px;font-size:1rem;font-weight:700;color:#142033">
      <i class="bi bi-calendar2-check-fill" style="color:#a76025;margin-right:6px"></i>
      Info Langganan
    </h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;font-size:.85rem">
      <div>
        <div style="color:#6b7280;font-size:.75rem;margin-bottom:2px">Status</div>
        <div style="font-weight:700;color:<?= $isActiveSub ? '#1a7a4a' : '#b91c1c' ?>">
          <?= esc($subStatusLabel) ?>
        </div>
      </div>
      <div>
        <div style="color:#6b7280;font-size:.75rem;margin-bottom:2px">Tier</div>
        <div style="font-weight:700;color:#a76025"><?= esc(ucfirst($subscription['tier_name'] ?? '-')) ?></div>
      </div>
      <div>
        <div style="color:#6b7280;font-size:.75rem;margin-bottom:2px">Berlaku Hingga</div>
        <div style="font-weight:600;color:#142033"><?= $subEndAt ? esc(date('d M Y', strtotime($subEndAt))) : 'Tidak terbatas' ?></div>
      </div>
      <div>
        <div style="color:#6b7280;font-size:.75rem;margin-bottom:2px">Siklus</div>
        <div style="font-weight:600;color:#142033"><?= esc($subCycleLabel) ?></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ══════════════════════════════════════════════════════════════ -->
  <!-- NEW SECTION D: Invoices -->
  <!-- ══════════════════════════════════════════════════════════════ -->
  <?php if (!empty($invoices)): ?>
  <div class="account-card" id="invoices" style="margin-top:20px">
    <h3 style="margin:0 0 12px;font-size:1rem;font-weight:700;color:#142033">
      <i class="bi bi-receipt-cutoff" style="color:#a76025;margin-right:6px"></i>
      Invoice & Kuitansi
    </h3>
    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:.83rem">
        <thead>
          <tr style="border-bottom:2px solid rgba(20,32,51,.08)">
            <th style="text-align:left;padding:6px 8px;color:#4a6080;font-weight:600">No. Invoice</th>
            <th style="text-align:left;padding:6px 8px;color:#4a6080;font-weight:600">Paket</th>
            <th style="text-align:right;padding:6px 8px;color:#4a6080;font-weight:600">Total</th>
            <th style="text-align:left;padding:6px 8px;color:#4a6080;font-weight:600">Tanggal</th>
            <th style="text-align:left;padding:6px 8px;color:#4a6080;font-weight:600">Status</th>
            <th style="padding:6px 8px"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($invoices as $inv):
            $statusColor = match($inv['status']) {
              'paid'      => '#1a7a4a',
              'cancelled' => '#b91c1c',
              default     => '#92400e',
            };
            $statusBg = match($inv['status']) {
              'paid'      => 'rgba(62,207,142,.1)',
              'cancelled' => 'rgba(248,113,113,.1)',
              default     => 'rgba(251,191,36,.1)',
            };
            $statusLabel = match($inv['status']) {
              'paid'      => 'Lunas',
              'cancelled' => 'Dibatalkan',
              default     => 'Belum Lunas',
            };
          ?>
          <tr style="border-bottom:1px solid rgba(20,32,51,.06)">
            <td style="padding:8px;font-family:'JetBrains Mono',monospace;font-size:.8rem;color:#142033;font-weight:600"><?= esc($inv['invoice_number']) ?></td>
            <td style="padding:8px;color:#142033"><?= esc(\App\Models\InvoiceModel::tierLabel($inv['tier_name'] ?? '')) ?> <span style="color:#9ca3af;font-size:.75rem">(<?= esc($inv['billing_cycle']) ?>)</span></td>
            <td style="padding:8px;text-align:right;font-weight:700;color:#142033">Rp <?= number_format((float)$inv['total_amount'], 0, ',', '.') ?></td>
            <td style="padding:8px;color:#6b7280;font-size:.8rem"><?= esc(date('d M Y', strtotime($inv['issued_at']))) ?></td>
            <td style="padding:8px">
              <span style="background:<?= $statusBg ?>;color:<?= $statusColor ?>;padding:2px 10px;border-radius:99px;font-size:.75rem;font-weight:700"><?= $statusLabel ?></span>
            </td>
            <td style="padding:8px">
              <a href="<?= site_url('account/invoice/' . $inv['invoice_id']) ?>" target="_blank"
                style="font-size:.78rem;color:#a76025;text-decoration:none;font-weight:600">
                <i class="bi bi-printer"></i> Cetak
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <a href="<?= site_url('logout') ?>" class="btn-logout">
    <i class="bi bi-box-arrow-right"></i>
    Keluar
  </a>

</div>
</body>
</html>


