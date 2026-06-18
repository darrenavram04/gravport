<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Status Submission Saya</title>
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css') ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=31') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
  <style>
    body { background: linear-gradient(180deg,#eff4f7 0%,#dfe7ee 100%); font-family:'Poppins',sans-serif; color:#142033; }
    .hub-shell { max-width:1280px; margin:0 auto; padding:calc(var(--landing-header-offset,112px) + 18px) 20px 60px; }
    .hub-back { display:inline-flex; align-items:center; gap:6px; font-size:.85rem; font-weight:700; color:#a76025; text-decoration:none; margin-bottom:22px; }
    .hub-title { font-size:32px; font-weight:800; margin:0 0 6px; }
    .hub-sub   { color:#6b7a8f; font-size:.9rem; margin-bottom:28px; }

    /* Tabs */
    .tabs { display:flex; gap:8px; margin-bottom:20px; }
    .tab  { padding:9px 22px; border-radius:999px; font-size:.85rem; font-weight:700;
            cursor:pointer; border:none; background:rgba(20,32,51,.07); color:#142033; transition:.15s; }
    .tab.active { background:#a76025; color:#fff; }

    /* Card */
    .card-section { background:#fff; border-radius:24px; padding:24px; box-shadow:0 4px 24px rgba(16,24,40,.08); margin-bottom:28px; }
    .card-section h2 { font-size:14px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7a8f; margin:0 0 18px; }

    /* Table */
    table { width:100%; border-collapse:collapse; font-size:.85rem; }
    th { background:#f0f4f8; color:#6b7a8f; font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; padding:10px 12px; text-align:left; white-space:nowrap; }
    td { padding:11px 12px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
    tr:last-child td { border-bottom:none; }
    tr:hover td { background:#fafbfc; }

    /* Badges */
    .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:.7rem; font-weight:800; }
    .badge-pending  { background:#FFF8E7; color:#a76025; }
    .badge-approved { background:#C6EFCE; color:#217346; }
    .badge-rejected { background:#fce8e8; color:#b91c1c; }
    .badge-point    { background:#e8f0fe; color:#1a56db; }
    .badge-raster   { background:#f3e8ff; color:#7e22ce; }
    .badge-metadata { background:#fff3e0; color:#a76025; }

    /* Alerts */
    .alert { padding:12px 18px; border-radius:14px; margin-bottom:20px; font-size:.875rem; font-weight:600; }
    .alert-success { background:#C6EFCE; color:#217346; }
    .alert-error   { background:#fce8e8; color:#b91c1c; }

    /* Empty */
    .empty { text-align:center; padding:40px; color:#6b7a8f; }
    .empty i { font-size:44px; display:block; margin-bottom:12px; }

    /* Notes */
    .note-cell { max-width:220px; font-size:.78rem; color:#6b7a8f; white-space:normal; }
  </style>
</head>
<body class="gravport-landing">

<?= view('partials/site_header', ['activePage' => 'admin', 'headerClass' => 'header--solid']) ?>

<main class="hub-shell">
  <a class="hub-back" href="<?= site_url('dataset/manage') ?>">
    <i class="bi bi-arrow-left"></i> Kembali ke Admin Hub
  </a>

  <h1 class="hub-title">Status Submission Saya</h1>
  <p class="hub-sub">Pantau status persetujuan data titik gravity, raster, dan metadata yang telah Anda kirimkan ke superadmin.</p>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab active" onclick="showTab('tab-points',this)">
      <i class="bi bi-geo-alt"></i> Titik Gravity
      <?php $pendingPts = count(array_filter($pointSubmissions ?? [], fn($r) => $r['review_status'] === 'pending')); ?>
      <?php if ($pendingPts > 0): ?><span style="margin-left:6px;background:#a76025;color:#fff;border-radius:999px;padding:1px 7px;font-size:.7rem"><?= $pendingPts ?></span><?php endif; ?>
    </button>
    <button class="tab" onclick="showTab('tab-rasters',this)">
      <i class="bi bi-grid-3x3"></i> Raster
      <?php $pendingRas = count(array_filter($rasterSubmissions ?? [], fn($r) => $r['review_status'] === 'pending')); ?>
      <?php if ($pendingRas > 0): ?><span style="margin-left:6px;background:#a76025;color:#fff;border-radius:999px;padding:1px 7px;font-size:.7rem"><?= $pendingRas ?></span><?php endif; ?>
    </button>
    <button class="tab" onclick="showTab('tab-meta',this)">
      <i class="bi bi-file-earmark-text"></i> Metadata
    </button>
  </div>

  <!-- ── TITIK GRAVITY ────────────────────────────────────────────── -->
  <div id="tab-points">
    <div class="card-section">
      <h2>Submission Titik Gravity</h2>
      <?php if (empty($pointSubmissions)): ?>
        <div class="empty"><i class="bi bi-inbox"></i>Belum ada submission titik.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Tipe</th>
              <th>Level</th>
              <th>Jumlah Titik</th>
              <th>File Sumber</th>
              <th>Status</th>
              <th>Catatan Reviewer</th>
              <th>Dikirim</th>
              <th>Ditinjau</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pointSubmissions as $row): ?>
            <tr>
              <td><?= esc($row['id']) ?></td>
              <td><span class="badge badge-point">FAA/CBA</span></td>
              <td>L<?= esc($row['data_level'] ?? 1) ?></td>
              <td><?= number_format((int)($row['point_count'] ?? 0)) ?> titik</td>
              <td style="font-size:.78rem"><?= esc($row['source_file'] ?? '-') ?></td>
              <td>
                <?php $st = $row['review_status']; ?>
                <span class="badge badge-<?= esc($st) ?>">
                  <?= $st === 'pending' ? 'Menunggu' : ($st === 'approved' ? 'Disetujui' : 'Ditolak') ?>
                </span>
              </td>
              <td class="note-cell"><?= esc($row['catatan_reviewer'] ?? '-') ?></td>
              <td style="white-space:nowrap;font-size:.78rem"><?= esc(substr((string)($row['submitted_at'] ?? ''), 0, 16)) ?></td>
              <td style="white-space:nowrap;font-size:.78rem"><?= esc(substr((string)($row['reviewed_at'] ?? '-'), 0, 16)) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── RASTER ────────────────────────────────────────────────────── -->
  <div id="tab-rasters" style="display:none">
    <div class="card-section">
      <h2>Submission Raster</h2>
      <?php if (empty($rasterSubmissions)): ?>
        <div class="empty"><i class="bi bi-inbox"></i>Belum ada submission raster.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Tipe</th>
              <th>Level</th>
              <th>File Sumber</th>
              <th>Status</th>
              <th>Catatan Reviewer</th>
              <th>Dikirim</th>
              <th>Ditinjau</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rasterSubmissions as $row): ?>
            <tr>
              <td><?= esc($row['id']) ?></td>
              <td><span class="badge badge-raster">Raster</span></td>
              <td>L<?= esc($row['data_level'] ?? 2) ?></td>
              <td style="font-size:.78rem"><?= esc($row['source_file'] ?? '-') ?></td>
              <td>
                <?php $st = $row['review_status']; ?>
                <span class="badge badge-<?= esc($st) ?>">
                  <?= $st === 'pending' ? 'Menunggu' : ($st === 'approved' ? 'Disetujui' : 'Ditolak') ?>
                </span>
              </td>
              <td class="note-cell"><?= esc($row['catatan_reviewer'] ?? '-') ?></td>
              <td style="white-space:nowrap;font-size:.78rem"><?= esc(substr((string)($row['submitted_at'] ?? ''), 0, 16)) ?></td>
              <td style="white-space:nowrap;font-size:.78rem"><?= esc(substr((string)($row['reviewed_at'] ?? '-'), 0, 16)) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── METADATA ──────────────────────────────────────────────────── -->
  <div id="tab-meta" style="display:none">
    <div class="card-section">
      <h2>Submission Metadata</h2>
      <p style="font-size:.82rem;color:#6b7a8f;margin:-8px 0 16px">Metadata yang telah Anda kirim melalui form metadata. Ditinjau langsung oleh superadmin.</p>
      <?php if (empty($metaSubmissions)): ?>
        <div class="empty"><i class="bi bi-inbox"></i>Belum ada submission metadata.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Identifier</th>
              <th>Jenis Data</th>
              <th>Provinsi</th>
              <th>Level</th>
              <th>Role Pengirim</th>
              <th>Dikirim</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($metaSubmissions as $row): ?>
            <tr>
              <td><?= esc($row['id']) ?></td>
              <td style="font-size:.78rem;max-width:200px;word-break:break-all"><?= esc($row['metadata_file_identifier'] ?? '-') ?></td>
              <td><?= esc($row['jenis_data'] ?? '-') ?></td>
              <td><?= esc($row['provinsi'] ?? '-') ?></td>
              <td><?= esc($row['level_data'] ?? '-') ?></td>
              <td><span class="badge badge-metadata"><?= esc($row['submitted_by_role'] ?? '-') ?></span></td>
              <td style="white-space:nowrap;font-size:.78rem"><?= esc(substr((string)($row['submitted_at'] ?? ''), 0, 16)) ?></td>
              <td>
                <?php $st = $row['review_status'] ?? 'pending'; ?>
                <span class="badge badge-<?= esc($st) ?>">
                  <?= $st === 'pending' ? 'Menunggu' : ($st === 'approved' ? 'Disetujui' : 'Ditolak') ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
function showTab(id, btn) {
  document.querySelectorAll('[id^="tab-"]').forEach(function(el){ el.style.display='none'; });
  document.querySelectorAll('.tab').forEach(function(el){ el.classList.remove('active'); });
  var el = document.getElementById(id);
  if (el) el.style.display = '';
  if (btn) btn.classList.add('active');
}
</script>

</body>
</html>
