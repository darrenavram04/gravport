<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Subscriptions</title>
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css') ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=31') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
  <style>
    body { background:linear-gradient(180deg,#eff4f7 0%,#dfe7ee 100%); font-family:'Poppins',sans-serif; color:#142033; }
    .hub-shell { max-width:1180px; margin:0 auto; padding:calc(var(--landing-header-offset,112px) + 18px) 20px 40px; }
    .hub-back { display:inline-flex; align-items:center; gap:6px; font-size:.85rem; font-weight:700; color:#a76025; text-decoration:none; margin-bottom:22px; }
    .hub-title { font-size:32px; font-weight:800; margin:0 0 24px; }
    .card-section { background:#fff; border-radius:24px; padding:28px; box-shadow:0 4px 24px rgba(16,24,40,.09); margin-bottom:28px; }
    .card-section h2 { font-size:16px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7a8f; margin:0 0 20px; }
    .tier-cards { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:28px; }
    .tier-card { background:#fff; border-radius:20px; padding:22px; box-shadow:0 2px 12px rgba(16,24,40,.08); border:2px solid transparent; }
    .tier-card.free { border-color:#dde3ec; }
    .tier-card.enterprise { border-color:#a76025; }
    .tier-card.government { border-color:#1B3A5C; }
    .tier-name { font-size:18px; font-weight:800; margin:0 0 4px; text-transform:capitalize; }
    .tier-price { font-size:13px; color:#6b7a8f; margin-bottom:12px; }
    .tier-count { font-size:32px; font-weight:800; color:#a76025; }
    .tier-label { font-size:.75rem; color:#6b7a8f; text-transform:uppercase; letter-spacing:.08em; }
    table { width:100%; border-collapse:collapse; font-size:.875rem; }
    th { background:#f0f4f8; color:#6b7a8f; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; padding:10px 14px; text-align:left; }
    td { padding:11px 14px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
    tr:last-child td { border-bottom:none; }
    .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:.72rem; font-weight:800; }
    .badge-free       { background:#f0f4f8; color:#6b7a8f; }
    .badge-lite       { background:#e8f5e9; color:#2e7d32; }
    .badge-pro        { background:#e8f0fe; color:#1a56db; }
    .badge-enterprise { background:rgba(167,96,37,.12); color:#8b4a17; }
    .badge-government { background:#D9E6F2; color:#1B3A5C; }
    .badge-approved   { background:#C6EFCE; color:#217346; }
    .badge-active     { background:#C6EFCE; color:#217346; }
    .badge-rejected   { background:#fce8e8; color:#b91c1c; }
    .badge-cancelled  { background:#fce8e8; color:#b91c1c; }
    .badge-pending    { background:#FFF8E7; color:#a76025; }
    .badge-expired    { background:#FFF8E7; color:#a76025; }
    .btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:999px; font-weight:700; font-size:.82rem; cursor:pointer; text-decoration:none; border:none; }
    .btn-primary { background:#a76025; color:#fff; }
    .btn-ghost { background:rgba(20,32,51,.07); color:#142033; }
    .btn-danger { background:#fce8e8; color:#b91c1c; }
    .btn-sm { padding:5px 12px; font-size:.78rem; }
    .form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; }
    .form-group label { display:block; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7a8f; margin-bottom:5px; }
    .form-group input, .form-group select, .form-group textarea {
      width:100%; padding:10px 14px; border:1px solid #dde3ec; border-radius:14px;
      font-size:.875rem; background:#fafbfc; color:#142033; font-family:inherit;
    }
    .alert { padding:12px 18px; border-radius:14px; margin-bottom:20px; font-size:.875rem; font-weight:600; }
    .alert-success { background:#C6EFCE; color:#217346; }
    .alert-error   { background:#fce8e8; color:#b91c1c; }
    @media(max-width:720px) { .tier-cards { grid-template-columns:1fr; } }
  </style>
</head>
<body class="admin-hub-page gravport-landing">
<?= view('partials/site_header', ['activePage'=>'admin','headerClass'=>'header--solid']) ?>

<main class="hub-shell">
  <a class="hub-back" href="<?= site_url('admin') ?>"><i class="bi bi-arrow-left"></i> Admin Hub</a>
  <h1 class="hub-title"><i class="bi bi-credit-card" style="color:#a76025"></i> Manajemen Langganan</h1>

  <?php if ($msg = session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= esc($msg) ?></div>
  <?php endif ?>
  <?php if ($msg = session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><i class="bi bi-exclamation-circle"></i> <?= esc($msg) ?></div>
  <?php endif ?>

  <!-- ── Tier Distribution Cards ── -->
  <div class="tier-cards">
    <?php
    $tierColors = ['free'=>'free','enterprise'=>'enterprise','government'=>'government'];
    $tierFees   = ['free'=>'Rp 0/bln','enterprise'=>'Rp 10 Jt/bln','government'=>'Custom MoU'];
    foreach ($distribution as $d):
      $name = $d['tier_name'];
    ?>
    <div class="tier-card <?= esc($name) ?>">
      <div class="tier-name"><?= esc(ucfirst($name)) ?></div>
      <div class="tier-price"><?= $tierFees[$name] ?? '' ?></div>
      <div class="tier-count"><?= (int)$d['user_count'] ?></div>
      <div class="tier-label">Pengguna Aktif</div>
    </div>
    <?php endforeach ?>
  </div>

  <!-- ── Assign Subscription ── -->
  <div class="card-section">
    <h2><i class="bi bi-person-plus"></i> Tetapkan / Perpanjang Langganan</h2>
    <form method="post" action="<?= site_url('admin/subscriptions/assign') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-group">
          <label>User ID *</label>
          <input type="number" name="user_id" min="1" required placeholder="ID dari tabel users">
        </div>
        <div class="form-group">
          <label>Tier *</label>
          <select name="tier_id" required>
            <?php foreach ($tiers as $t): ?>
            <option value="<?= (int)$t['tier_id'] ?>">
              <?= esc(ucfirst($t['tier_name'])) ?>
              - Rp <?= number_format((float)($t['price_monthly'] ?? $t['monthly_fee'] ?? 0),0,',','.') ?>/bln
            </option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="form-group">
          <label>Berlaku Sampai *</label>
          <input type="date" name="end_date" required
                 min="<?= date('Y-m-d') ?>"
                 value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
        </div>
        <div class="form-group">
          <label>Metode Pembayaran</label>
          <select name="payment_method">
            <option value="">- Pilih -</option>
            <option>Transfer Bank</option>
            <option>Virtual Account</option>
            <option>MoU / Purchase Order</option>
            <option>Gratis / Internal</option>
          </select>
        </div>
        <div class="form-group">
          <label>No. Referensi / PO</label>
          <input type="text" name="payment_ref" placeholder="PO-2026-001">
        </div>
        <div class="form-group">
          <label>Catatan</label>
          <input type="text" name="notes" placeholder="Kontrak Enterprise Q1 2026">
        </div>
      </div>
      <div style="margin-top:18px">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg"></i> Tetapkan Langganan
        </button>
        <small style="margin-left:12px;color:#6b7a8f">
          Langganan aktif sebelumnya akan otomatis dibatalkan.
        </small>
      </div>
    </form>
  </div>

  <!-- ── Subscription List ── -->
  <div class="card-section">
    <h2>Histori Langganan (<?= count($subscriptions) ?>)</h2>
    <div style="overflow-x:auto">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Pengguna</th><th>Tier</th><th>Mulai</th><th>Berakhir</th>
            <th>Siklus</th><th>Status</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($subscriptions as $s):
            $ps = $s['payment_status'] ?? 'E';
            $isActive = $ps === 'S';
            $psLabel = match($ps) { 'S' => 'AKTIF', 'E' => 'BERAKHIR', 'P' => 'PENDING', default => strtoupper($ps) };
            $psBadge = match($ps) { 'S' => 'approved', 'E' => 'rejected', 'P' => 'pending', default => 'pending' };
          ?>
          <tr>
            <td style="color:#6b7a8f;font-size:.78rem"><?= (int)($s['subs_id'] ?? 0) ?></td>
            <td>
              <div style="font-weight:700;font-size:.85rem"><?= esc($s['full_name'] ?? '#'.(int)($s['acc_id'] ?? 0)) ?></div>
              <div style="font-size:.75rem;color:#6b7a8f"><?= esc($s['email'] ?? '') ?></div>
            </td>
            <td>
              <span class="badge badge-<?= esc($s['tier_name'] ?? '') ?>">
                <?= esc(strtoupper($s['tier_name'] ?? '-')) ?>
              </span>
            </td>
            <td style="font-size:.82rem"><?= esc($s['start_date'] ?? '-') ?></td>
            <td style="font-size:.82rem"><?= esc($s['end_at'] ?? 'Tidak terbatas') ?></td>
            <td style="font-size:.8rem"><?= ($s['payment_cycle'] ?? 'M') === 'Y' ? 'Tahunan' : 'Bulanan' ?></td>
            <td>
              <span class="badge badge-<?= $psBadge ?>">
                <?= $psLabel ?>
              </span>
            </td>
            <td>
              <?php if ($isActive): ?>
              <form method="post" action="<?= site_url('admin/subscriptions/'.(int)($s['subs_id'] ?? 0).'/cancel') ?>" style="display:inline"
                    onsubmit="return confirm('Batalkan langganan ini?')">
                <?= csrf_field() ?>
                <button class="btn btn-danger btn-sm" type="submit">
                  <i class="bi bi-x-circle"></i> Batalkan
                </button>
              </form>
              <?php else: ?>
              <span style="color:#6b7a8f;font-size:.8rem">-</span>
              <?php endif ?>
            </td>
          </tr>
          <?php endforeach ?>
          <?php if (!$subscriptions): ?>
          <tr><td colspan="8" style="text-align:center;color:#6b7a8f;padding:32px">Belum ada data langganan.</td></tr>
          <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── Tier Rules ── -->
  <div class="card-section" style="background:#FFF8E7;border:1px solid #E8A020">
    <h2 style="color:#a76025"><i class="bi bi-info-circle"></i> Aturan Tier</h2>
    <table>
      <thead><tr><th>Tier</th><th>Harga/Bulan</th><th>Maks Download/Bulan</th><th>REST API</th><th>WMS/WFS</th></tr></thead>
      <tbody>
        <?php foreach ($tiers as $t): ?>
        <tr>
          <?php
            // PostgreSQL returns booleans as 't'/'f' strings — treat explicitly
            $pgBool = fn($v): bool => $v === true || $v === 't' || $v === '1' || $v === 1;
            $quotaByte = $t['download_quota_byte'] ?? null;
            $quotaLabel = ($quotaByte !== null && $quotaByte !== '')
                ? '<strong>' . round((int)$quotaByte / 1073741824, 1) . ' GB/minggu</strong>'
                : '<strong>Tidak Terbatas</strong>';
          ?>
          <td><span class="badge badge-<?= esc(strtolower($t['tier_name'])) ?>"><?= esc(strtoupper($t['tier_name'])) ?></span></td>
          <td>Rp <?= number_format((float)($t['price_monthly'] ?? 0),0,',','.') ?></td>
          <td><?= $quotaLabel ?></td>
          <td><?= $pgBool($t['api_access'])     ? '<span style="color:#217346;font-weight:700">✓</span>' : '<span style="color:#b91c1c">✗</span>' ?></td>
          <td><?= $pgBool($t['wms_wfs_access']) ? '<span style="color:#217346;font-weight:700">✓</span>' : '<span style="color:#b91c1c">✗</span>' ?></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>

