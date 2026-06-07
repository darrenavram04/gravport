<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Staging Review</title>
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css') ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=31') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
  <style>
    body { background: linear-gradient(180deg,#eff4f7 0%,#dfe7ee 100%); font-family:'Poppins',sans-serif; color:#142033; }
    .hub-shell { max-width:1280px; margin:0 auto; padding:calc(var(--landing-header-offset,112px) + 18px) 20px 60px; }
    .hub-back { display:inline-flex; align-items:center; gap:6px; font-size:.85rem; font-weight:700; color:#a76025; text-decoration:none; margin-bottom:22px; }
    .hub-title { font-size:32px; font-weight:800; margin:0 0 8px; }
    .hub-sub { color:#6b7a8f; font-size:.9rem; margin-bottom:28px; }

    /* KPI row */
    .kpi-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:28px; }
    .kpi-group { background:#fff; border-radius:20px; padding:20px 24px; box-shadow:0 2px 12px rgba(16,24,40,.07); }
    .kpi-group-label { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:#6b7a8f; margin-bottom:12px; }
    .kpi-nums { display:flex; gap:20px; }
    .kpi-num { text-align:center; }
    .kpi-num .val { font-size:26px; font-weight:800; }
    .kpi-num .lbl { font-size:.68rem; color:#6b7a8f; text-transform:uppercase; letter-spacing:.07em; margin-top:2px; }
    .val-pending  { color:#e59a20; }
    .val-approved { color:#217346; }
    .val-rejected { color:#b91c1c; }

    /* Tabs */
    .tabs { display:flex; gap:8px; margin-bottom:20px; }
    .tab {
      padding:9px 22px; border-radius:999px; font-size:.85rem; font-weight:700;
      cursor:pointer; border:none; background:rgba(20,32,51,.07); color:#142033;
      transition:.15s;
    }
    .tab.active { background:#a76025; color:#fff; }

    /* Card */
    .card-section { background:#fff; border-radius:24px; padding:24px; box-shadow:0 4px 24px rgba(16,24,40,.08); margin-bottom:28px; }
    .card-section h2 { font-size:15px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7a8f; margin:0 0 18px; }

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
    .badge-faa { background:#e8f0fe; color:#1a56db; }
    .badge-cba { background:#fce8f3; color:#991b7a; }
    .badge-l1  { background:#e8f5e9; color:#217346; }
    .badge-l2  { background:#fff3e0; color:#a76025; }

    /* Buttons */
    .btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:999px; font-weight:700; font-size:.78rem; cursor:pointer; text-decoration:none; border:none; }
    .btn-approve  { background:#C6EFCE; color:#217346; }
    .btn-reject   { background:#fce8e8; color:#b91c1c; }
    .btn-delete   { background:rgba(20,32,51,.07); color:#6b7a8f; }
    .btn-approve:hover { background:#b0ddb8; }
    .btn-reject:hover  { background:#f5cfcf; }
    .btn-sm { padding:5px 10px; font-size:.74rem; }

    /* Alerts */
    .alert { padding:12px 18px; border-radius:14px; margin-bottom:20px; font-size:.875rem; font-weight:600; }
    .alert-success { background:#C6EFCE; color:#217346; }
    .alert-error   { background:#fce8e8; color:#b91c1c; }
    .alert-info    { background:#e8f0fe; color:#1a56db; }

    /* Empty state */
    .empty { text-align:center; padding:40px; color:#6b7a8f; }
    .empty i { font-size:44px; display:block; margin-bottom:12px; }

    /* Reject modal */
    .modal-overlay {
      position:fixed; inset:0; background:rgba(4,16,29,.55); z-index:200;
      display:none; place-items:center;
    }
    .modal-overlay.open { display:grid; }
    .modal {
      background:#fff; border-radius:24px; padding:32px; width:min(520px,92vw);
      box-shadow:0 24px 80px rgba(4,16,29,.3);
    }
    .modal h3 { font-size:18px; font-weight:800; margin:0 0 8px; }
    .modal p  { font-size:.875rem; color:#6b7a8f; margin:0 0 18px; }
    textarea {
      width:100%; min-height:100px; border-radius:14px; border:1px solid #dde3ec;
      padding:12px 14px; font-family:inherit; font-size:.875rem; resize:vertical;
      background:#fafbfc; outline:none;
    }
    textarea:focus { border-color:#a76025; }
    .modal-actions { display:flex; gap:10px; margin-top:18px; justify-content:flex-end; }
    .btn-cancel { background:rgba(20,32,51,.07); color:#142033; }

    /* Raster notice */
    .raster-notice {
      background:#fff8e7; border:1px solid #ffe0a0; border-radius:14px;
      padding:14px 18px; font-size:.83rem; color:#a76025; margin-bottom:18px;
      display:flex; gap:10px; align-items:flex-start;
    }
    .raster-notice i { flex-shrink:0; margin-top:2px; }

    @media(max-width:720px) { .kpi-row { grid-template-columns:1fr; } }
  </style>
</head>
<body class="admin-hub-page gravport-landing">
<?= view('partials/site_header', ['activePage'=>'admin','headerClass'=>'header--solid']) ?>

<main class="hub-shell">
  <a class="hub-back" href="<?= site_url('admin') ?>"><i class="bi bi-arrow-left"></i> Admin Hub</a>
  <h1 class="hub-title"><i class="bi bi-inbox-fill" style="color:#a76025"></i> Staging Review</h1>
  <p class="hub-sub">Tinjau, setujui, atau tolak data yang diunggah ke staging sebelum dipindahkan ke produksi.</p>

  <?php if ($msg = session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= esc($msg) ?></div>
  <?php endif ?>
  <?php if ($msg = session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><i class="bi bi-exclamation-triangle"></i> <?= esc($msg) ?></div>
  <?php endif ?>

  <!-- ── KPI ── -->
  <div class="kpi-row">
    <div class="kpi-group">
      <div class="kpi-group-label"><i class="bi bi-geo-alt"></i> Data Titik (Point)</div>
      <div class="kpi-nums">
        <div class="kpi-num"><div class="val val-pending"><?= $stats['points_pending'] ?></div><div class="lbl">Pending</div></div>
        <div class="kpi-num"><div class="val val-approved"><?= $stats['points_approved'] ?></div><div class="lbl">Approved</div></div>
        <div class="kpi-num"><div class="val val-rejected"><?= $stats['points_rejected'] ?></div><div class="lbl">Rejected</div></div>
      </div>
    </div>
    <div class="kpi-group">
      <div class="kpi-group-label"><i class="bi bi-layers"></i> Data Raster</div>
      <div class="kpi-nums">
        <div class="kpi-num"><div class="val val-pending"><?= $stats['rasters_pending'] ?></div><div class="lbl">Pending</div></div>
        <div class="kpi-num"><div class="val val-approved"><?= $stats['rasters_approved'] ?></div><div class="lbl">Approved</div></div>
        <div class="kpi-num"><div class="val val-rejected"><?= $stats['rasters_rejected'] ?></div><div class="lbl">Rejected</div></div>
      </div>
    </div>
    <div class="kpi-group">
      <div class="kpi-group-label"><i class="bi bi-info-circle"></i> Panduan Review</div>
      <div style="font-size:.8rem;color:#6b7a8f;line-height:1.7">
        <b>Approve Point</b> → data langsung masuk tabel produksi.<br>
        <b>Approve Raster</b> → status approved; jalankan raster2pgsql untuk load tile.
      </div>
    </div>
  </div>

  <!-- ── TABS ── -->
  <div class="tabs">
    <button class="tab active" onclick="showTab('points', this)">
      <i class="bi bi-geo-alt"></i> Data Titik
      <?php if ($stats['points_pending'] > 0): ?>
        <span style="background:#a76025;color:#fff;padding:1px 7px;border-radius:999px;font-size:.7rem;margin-left:4px"><?= $stats['points_pending'] ?></span>
      <?php endif ?>
    </button>
    <button class="tab" onclick="showTab('rasters', this)">
      <i class="bi bi-layers"></i> Data Raster
      <?php if ($stats['rasters_pending'] > 0): ?>
        <span style="background:#a76025;color:#fff;padding:1px 7px;border-radius:999px;font-size:.7rem;margin-left:4px"><?= $stats['rasters_pending'] ?></span>
      <?php endif ?>
    </button>
  </div>

  <!-- ── POINT TABLE ── -->
  <div id="tab-points">
    <div class="card-section">
      <h2>Staging - Data Titik Gravitasi</h2>
      <p style="font-size:.82rem;color:#6b7a8f;margin:-8px 0 16px">Satu baris = satu file CSV yang diupload. Klik <strong>Lihat Submission</strong> untuk review detail dan approve/tolak.</p>
      <?php if ($points): ?>
      <div style="overflow-x:auto">
        <table>
          <thead>
            <tr>
              <th>Submission</th><th>File CSV</th><th>Tipe</th>
              <th>Mode Obs.</th><th>Jumlah Titik</th>
              <th>Status</th><th>Diunggah</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($points as $p): ?>
            <tr>
              <td style="font-weight:700;color:#6b7a8f;white-space:nowrap">
                <?php if (!empty($p['submission_id'])): ?>
                  #<?= (int)$p['submission_id'] ?>
                <?php else: ?>
                  <span style="color:#b0bec5">—</span>
                <?php endif ?>
              </td>
              <td style="font-size:.8rem;max-width:200px;word-break:break-all"><?= esc($p['source_file']) ?></td>
              <td>
                <span class="badge badge-<?= strtolower(esc($p['point_anom_type'])) ?>">
                  <?= esc($p['point_anom_type']) ?>
                </span>
              </td>
              <td style="font-size:.8rem"><?= esc($p['point_obs_type'] ?? '-') ?></td>
              <td>
                <span style="font-weight:700"><?= number_format((int)($p['point_count'] ?? 0)) ?></span>
                <span style="font-size:.72rem;color:#6b7a8f"> titik</span>
              </td>
              <td>
                <span class="badge badge-<?= esc($p['review_status']) ?>">
                  <?= esc(strtoupper($p['review_status'])) ?>
                </span>
              </td>
              <td style="font-size:.78rem;white-space:nowrap">
                <?= $p['submitted_at'] ? date('d M Y H:i', strtotime($p['submitted_at'])) : '-' ?>
              </td>
              <td>
                <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                  <?php if (!empty($p['submission_id'])): ?>
                    <a href="<?= site_url('admin/metadata-submissions/' . (int)$p['submission_id']) ?>"
                       class="btn btn-sm" style="background:#e8f0fe;color:#1a56db">
                      <i class="bi bi-file-earmark-text"></i> Lihat Submission
                    </a>
                    <a href="<?= site_url('admin/metadata-submissions/' . (int)$p['submission_id'] . '/download/tabular') ?>"
                       class="btn btn-sm" style="background:#f0fdf4;color:#166534">
                      <i class="bi bi-download"></i> CSV
                    </a>
                  <?php else: ?>
                    <span style="font-size:.74rem;color:#b0bec5">Upload langsung</span>
                  <?php endif ?>
                </div>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty">
        <i class="bi bi-inbox"></i>
        Belum ada data titik di staging.
      </div>
      <?php endif ?>
    </div>
  </div>

  <!-- ── RASTER TABLE ── -->
  <div id="tab-rasters" style="display:none">
    <div class="raster-notice">
      <i class="bi bi-info-circle-fill"></i>
      <div>
        <strong>Catatan Raster:</strong>
        Approve raster hanya mengubah status menjadi <em>approved</em>. Tile raster aktual harus dimuat secara terpisah
        menggunakan <code>raster2pgsql</code> dengan path file yang tercantum.
      </div>
    </div>
    <div class="card-section">
      <h2>Staging - Data Raster Gravitasi</h2>
      <?php if ($rasters): ?>
      <div style="overflow-x:auto">
        <table>
          <thead>
            <tr>
              <th>ID</th><th>File Sumber</th><th>Tipe Anomali</th>
              <th>Level</th><th>Path Raster</th>
              <th>Status</th><th>Diunggah</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rasters as $r): ?>
            <tr>
              <td style="font-weight:700;color:#6b7a8f">#<?= $r['id'] ?></td>
              <td style="font-size:.8rem"><?= esc($r['source_file']) ?></td>
              <td>
                <span class="badge badge-<?= strtolower(esc($r['raster_anom_type'])) ?>">
                  <?= esc($r['raster_anom_type']) ?>
                </span>
              </td>
              <td>
                <span class="badge badge-l<?= (int)$r['data_level'] ?>">
                  L<?= (int)$r['data_level'] ?>
                </span>
              </td>
              <td style="font-size:.75rem;color:#6b7a8f;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= esc($r['raster_path'] ?? '-') ?>
              </td>
              <td>
                <span class="badge badge-<?= esc($r['review_status']) ?>">
                  <?= esc(strtoupper($r['review_status'])) ?>
                </span>
                <?php if ($r['review_status'] === 'rejected' && $r['rejection_reason']): ?>
                  <div style="font-size:.72rem;color:#b91c1c;margin-top:3px"><?= esc($r['rejection_reason']) ?></div>
                <?php endif ?>
              </td>
              <td style="font-size:.78rem;white-space:nowrap">
                <?= $r['submitted_at'] ? date('d M Y H:i', strtotime($r['submitted_at'])) : '-' ?>
              </td>
              <td>
                <?php if ($r['review_status'] === 'pending'): ?>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                  <form method="post" action="<?= site_url('dataset/staging/raster/' . (int)$r['id'] . '/approve') ?>"
                        onsubmit="return confirm('Approve raster ini? (Status diubah ke approved; tile harus diload manual)')">
                    <?= csrf_field() ?>
                    <button class="btn btn-approve btn-sm" type="submit">
                      <i class="bi bi-check-lg"></i> Approve
                    </button>
                  </form>
                  <button class="btn btn-reject btn-sm" type="button"
                          onclick="openReject('raster', <?= (int)$r['id'] ?>)">
                    <i class="bi bi-x-lg"></i> Tolak
                  </button>
                </div>
                <?php else: ?>
                  <form method="post" action="<?= site_url('dataset/delete/raster/' . (int)$r['id']) ?>"
                        onsubmit="return confirm('Hapus record staging ini?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-delete btn-sm" type="submit">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                <?php endif ?>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty">
        <i class="bi bi-layers"></i>
        Belum ada data raster di staging.
      </div>
      <?php endif ?>
    </div>
  </div>
</main>

<!-- ── REJECT MODAL ── -->
<div class="modal-overlay" id="reject-overlay" onclick="closeReject(event)">
  <div class="modal" onclick="event.stopPropagation()">
    <h3><i class="bi bi-x-circle" style="color:#b91c1c"></i> Tolak Data Staging</h3>
    <p>Berikan alasan penolakan (opsional). Alasan ini akan tersimpan di record staging.</p>
    <form method="post" id="reject-form">
      <?= csrf_field() ?>
      <textarea name="rejection_reason" placeholder="Contoh: Format data tidak sesuai standar SNI 9217:2024..."></textarea>
      <div class="modal-actions">
        <button type="button" class="btn btn-cancel" onclick="closeReject()">Batal</button>
        <button type="submit" class="btn btn-reject">
          <i class="bi bi-x-lg"></i> Konfirmasi Tolak
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  function showTab(name, btn) {
    document.getElementById('tab-points').style.display  = name === 'points'  ? '' : 'none';
    document.getElementById('tab-rasters').style.display = name === 'rasters' ? '' : 'none';
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
  }

  function openReject(type, id) {
    const url = `<?= site_url('dataset/staging/') ?>${type}/${id}/reject`;
    document.getElementById('reject-form').action = url;
    document.getElementById('reject-overlay').classList.add('open');
    document.querySelector('#reject-form textarea').value = '';
  }

  function closeReject(e) {
    if (!e || e.target === document.getElementById('reject-overlay')) {
      document.getElementById('reject-overlay').classList.remove('open');
    }
  }
</script>
</body>
</html>

