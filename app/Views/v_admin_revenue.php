<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Revenue Dashboard</title>
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
    .kpi-row { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:28px; }
    .kpi { background:#fff; border-radius:20px; padding:22px; box-shadow:0 2px 12px rgba(16,24,40,.08); }
    .kpi-val { font-size:28px; font-weight:800; color:#a76025; }
    .kpi-label { font-size:.75rem; color:#6b7a8f; text-transform:uppercase; letter-spacing:.08em; margin-top:4px; }
    table { width:100%; border-collapse:collapse; font-size:.875rem; }
    th { background:#f0f4f8; color:#6b7a8f; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; padding:10px 14px; text-align:left; }
    td { padding:11px 14px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
    tr:last-child td { border-bottom:none; }
    .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:.72rem; font-weight:800; }
    .badge-pending  { background:#FFF8E7; color:#a76025; }
    .badge-paid     { background:#C6EFCE; color:#217346; }
    .badge-disputed { background:#fce8e8; color:#b91c1c; }
    .btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:999px; font-weight:700; font-size:.82rem; cursor:pointer; text-decoration:none; border:none; }
    .btn-primary { background:#a76025; color:#fff; }
    .btn-ghost { background:rgba(20,32,51,.07); color:#142033; }
    .btn-success { background:#C6EFCE; color:#217346; }
    .btn-sm { padding:5px 12px; font-size:.78rem; }
    .month-nav { display:flex; align-items:center; gap:12px; margin-bottom:24px; }
    .month-nav form { display:flex; align-items:center; gap:8px; }
    .month-nav select, .month-nav input[type=number] {
      padding:8px 12px; border:1px solid #dde3ec; border-radius:12px;
      font-family:inherit; font-size:.875rem; background:#fafbfc;
    }
    .alert { padding:12px 18px; border-radius:14px; margin-bottom:20px; font-size:.875rem; font-weight:600; }
    .alert-success { background:#C6EFCE; color:#217346; }
    .alert-error   { background:#fce8e8; color:#b91c1c; }
    .revenue-bar { height:8px; border-radius:999px; background:#f0f4f8; overflow:hidden; margin-top:6px; }
    .revenue-bar-fill { height:100%; border-radius:999px; background:linear-gradient(90deg,#a76025,#ffbf74); }
    .provider-share { font-weight:800; color:#217346; }
    .platform-share { color:#a76025; }
    @media(max-width:720px) { .kpi-row { grid-template-columns:1fr 1fr; } }
  </style>
</head>
<body class="admin-hub-page gravport-landing">
<?= view('partials/site_header', ['activePage'=>'admin','headerClass'=>'header--solid']) ?>

<main class="hub-shell">
  <a class="hub-back" href="<?= site_url('admin') ?>"><i class="bi bi-arrow-left"></i> Admin Hub</a>
  <h1 class="hub-title"><i class="bi bi-graph-up-arrow" style="color:#a76025"></i> Revenue Dashboard</h1>

  <?php if ($msg = session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= esc($msg) ?></div>
  <?php endif ?>

  <!-- ── KPI Row ── -->
  <div class="kpi-row">
    <div class="kpi">
      <div class="kpi-val"><?= number_format((int)$stats['total_downloads_this_month']) ?></div>
      <div class="kpi-label">Download Bulan Ini</div>
    </div>
    <div class="kpi">
      <div class="kpi-val"><?= number_format((int)$stats['total_downloads_all_time']) ?></div>
      <div class="kpi-label">Total Download (All Time)</div>
    </div>
    <div class="kpi">
      <div class="kpi-val" style="font-size:18px">
        Rp <?= number_format((float)$stats['provider_revenue_this_month'],0,',','.') ?>
      </div>
      <div class="kpi-label">Revenue Provider Bulan Ini (75%)</div>
    </div>
    <div class="kpi">
      <div class="kpi-val" style="font-size:18px">
        Rp <?= number_format((float)$stats['platform_revenue_this_month'],0,',','.') ?>
      </div>
      <div class="kpi-label">Revenue Gravport Bulan Ini (25%)</div>
    </div>
  </div>

  <!-- ── Month Selector + Generate ── -->
  <div class="month-nav">
    <form method="get" action="<?= site_url('admin/revenue') ?>">
      <select name="month">
        <?php for ($m = 1; $m <= 12; $m++): ?>
        <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>>
          <?= date('F', mktime(0,0,0,$m,1)) ?>
        </option>
        <?php endfor ?>
      </select>
      <input type="number" name="year" value="<?= $year ?>" min="2024" max="2030" style="width:90px">
      <button type="submit" class="btn btn-ghost"><i class="bi bi-search"></i> Tampilkan</button>
    </form>

    <form method="post" action="<?= site_url('admin/revenue/generate') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="year"  value="<?= $year ?>">
      <input type="hidden" name="month" value="<?= $month ?>">
      <button type="submit" class="btn btn-primary"
              onclick="return confirm('Generate revenue share untuk <?= date('F Y', mktime(0,0,0,$month,1,$year)) ?>?')">
        <i class="bi bi-calculator"></i> Generate Revenue Share
      </button>
    </form>
  </div>

  <!-- ── Revenue Share Table ── -->
  <div class="card-section">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <h2 style="margin:0">
        Revenue Share - <?= date('F Y', mktime(0,0,0,$month,1,$year)) ?>
      </h2>
      <span style="font-size:.8rem;color:#6b7a8f">Pembagian: 75% Provider / 25% Gravport</span>
    </div>

    <?php if ($rows): ?>
    <?php
    $maxShare = max(array_map(fn($r) => (float)$r['provider_share'], $rows));
    $maxShare = $maxShare ?: 1;
    ?>
    <div style="overflow-x:auto">
      <table>
        <thead>
          <tr>
            <th>Provider</th><th>Downloads</th>
            <th>Gross Revenue</th><th>Provider (75%)</th><th>Gravport (25%)</th>
            <th>Status</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <div style="font-weight:700"><?= esc($r['provider_name']) ?></div>
              <div style="font-size:.75rem;color:#6b7a8f"><?= esc($r['contact_email'] ?? '') ?></div>
              <?php if ($r['bank_name']): ?>
              <div style="font-size:.75rem;color:#6b7a8f">
                <?= esc($r['bank_name']) ?> | <?= esc($r['bank_account'] ?? '') ?>
              </div>
              <?php endif ?>
            </td>
            <td style="font-weight:700;color:#a76025"><?= number_format((int)$r['total_downloads']) ?></td>
            <td>Rp <?= number_format((float)$r['gross_revenue'],0,',','.') ?></td>
            <td>
              <div class="provider-share">Rp <?= number_format((float)$r['provider_share'],0,',','.') ?></div>
              <div class="revenue-bar">
                <div class="revenue-bar-fill" style="width:<?= round((float)$r['provider_share']/$maxShare*100,1) ?>%"></div>
              </div>
            </td>
            <td class="platform-share">Rp <?= number_format((float)$r['platform_share'],0,',','.') ?></td>
            <td>
              <span class="badge badge-<?= esc($r['payment_status']) ?>">
                <?= esc(strtoupper($r['payment_status'])) ?>
              </span>
            </td>
            <td>
              <?php if ($r['payment_status'] === 'pending'): ?>
              <form method="post" action="<?= site_url('admin/revenue/'.(int)$r['revenue_id'].'/paid') ?>" style="display:inline">
                <?= csrf_field() ?>
                <button class="btn btn-success btn-sm" type="submit">
                  <i class="bi bi-check-circle"></i> Lunas
                </button>
              </form>
              <?php else: ?>
              <span style="color:#6b7a8f;font-size:.8rem">Lunas <?= $r['paid_at'] ? date('d M Y',strtotime($r['paid_at'])) : '' ?></span>
              <?php endif ?>
            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:48px;color:#6b7a8f">
      <i class="bi bi-inbox" style="font-size:48px;display:block;margin-bottom:12px"></i>
      Belum ada data revenue share untuk periode ini.<br>
      <small>Klik <strong>Generate Revenue Share</strong> untuk menghitung dari log download.</small>
    </div>
    <?php endif ?>
  </div>

  <!-- ── Dataset Breakdown ── -->
  <?php if (!empty($stats['dataset_breakdown'])): ?>
  <div class="card-section">
    <h2>Dataset Paling Banyak Diunduh (Bulan Ini)</h2>
    <table>
      <thead><tr><th>Dataset</th><th>Tipe</th><th>Jumlah Download</th></tr></thead>
      <tbody>
        <?php foreach ($stats['dataset_breakdown'] as $b): ?>
        <tr>
          <td style="font-weight:700"><?= esc(strtoupper($b['dataset_code'])) ?></td>
          <td><?= esc($b['dataset_type']) ?></td>
          <td><?= number_format((int)$b['cnt']) ?></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <?php endif ?>

  <!-- ── Recent Download Log ── -->
  <div class="card-section">
    <h2>Log Download Terbaru (50 terakhir)</h2>
    <div style="overflow-x:auto">
      <table>
        <thead>
          <tr><th>Waktu</th><th>User</th><th>Dataset</th><th>Provider</th><th>Baris</th><th>Commission</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $tx): ?>
          <tr>
            <td style="font-size:.8rem;white-space:nowrap">
              <?= date('d M H:i', strtotime($tx['downloaded_at'])) ?>
            </td>
            <td><?= ($tx['acc_id'] ?? $tx['user_id'] ?? null) ? '#'.(int)($tx['acc_id'] ?? $tx['user_id']) : '<span style="color:#6b7a8f">guest</span>' ?></td>
            <td style="font-weight:700"><?= esc(strtoupper($tx['dataset_code'])) ?></td>
            <td style="font-size:.8rem"><?= esc($tx['provider_name'] ?? '-') ?></td>
            <td><?= $tx['row_count'] !== null ? number_format((int)$tx['row_count']) : '-' ?></td>
            <td style="color:#a76025">Rp <?= number_format((float)$tx['gravport_commission'],0,',','.') ?></td>
            <td><span class="badge badge-<?= $tx['status'] === 'completed' ? 'paid' : 'pending' ?>"><?= esc($tx['status']) ?></span></td>
          </tr>
          <?php endforeach ?>
          <?php if (!$recent): ?>
          <tr><td colspan="7" style="text-align:center;color:#6b7a8f;padding:32px">Belum ada aktivitas download.</td></tr>
          <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>

