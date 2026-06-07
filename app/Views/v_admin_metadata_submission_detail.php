<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Detail Submission #<?= esc($submission['id']) ?></title>
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css') ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=31') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
  <style>
    body { background:linear-gradient(180deg,#eff4f7 0%,#dfe7ee 100%); font-family:'Poppins',sans-serif; color:#142033; }
    .hub-shell { max-width:960px; margin:0 auto; padding:calc(var(--landing-header-offset,112px) + 18px) 20px 60px; }
    .hub-back { display:inline-flex; align-items:center; gap:6px; font-size:.85rem; font-weight:700; color:#a76025; text-decoration:none; margin-bottom:22px; }
    .hub-title { font-size:28px; font-weight:800; margin:0 0 4px; }
    .hub-sub { color:#6b7a8f; font-size:.88rem; margin-bottom:28px; }
    .card-section { background:#fff; border-radius:24px; padding:28px 32px; box-shadow:0 4px 24px rgba(16,24,40,.08); margin-bottom:20px; }
    .card-section h2 { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:#6b7a8f; margin:0 0 18px; border-bottom:1px solid #f0f4f8; padding-bottom:10px; }
    .field-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px 28px; }
    .field-group { display:flex; flex-direction:column; gap:3px; }
    .field-group.full { grid-column:1/-1; }
    .field-label { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#8898a4; }
    .field-value { font-size:.875rem; color:#142033; word-break:break-word; }
    .field-value.mono { font-family:monospace; font-size:.8rem; color:#3d5a6b; }
    .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:.7rem; font-weight:800; }
    .badge-admin      { background:#e8f0fe; color:#1a56db; }
    .badge-superadmin { background:#fce8f3; color:#991b7a; }
    .badge-user       { background:#e8f5e9; color:#217346; }
    .file-link { color:#a76025; font-size:.82rem; font-weight:600; text-decoration:none; }
    .file-link:hover { text-decoration:underline; }
    .empty-val { color:#b0bec5; font-style:italic; font-size:.83rem; }
    .review-bar { display:flex; align-items:center; gap:12px; background:#fff; border-radius:20px; padding:18px 28px; box-shadow:0 4px 24px rgba(16,24,40,.08); margin-bottom:20px; flex-wrap:wrap; }
    .review-bar .status-badge { font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.1em; padding:4px 14px; border-radius:999px; }
    .status-pending  { background:#fff3cd; color:#856404; }
    .status-approved { background:#d1fae5; color:#065f46; }
    .status-rejected { background:#fee2e2; color:#991b1b; }
    .btn-approve { background:#065f46; color:#fff; border:none; padding:9px 22px; border-radius:12px; font-weight:700; font-size:.85rem; cursor:pointer; }
    .btn-approve:hover { background:#047857; }
    .btn-reject  { background:#fff; color:#991b1b; border:1px solid #fca5a5; padding:9px 22px; border-radius:12px; font-weight:700; font-size:.85rem; cursor:pointer; }
    .btn-reject:hover { background:#fee2e2; }
    .reject-form { display:none; background:#fff8f8; border:1px solid #fca5a5; border-radius:14px; padding:16px 20px; margin-top:10px; width:100%; }
    .reject-form textarea { width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px 12px; font-size:.85rem; resize:vertical; }
  </style>
</head>
<body class="gravport-landing">

<?= view('partials/site_header', ['activePage' => 'admin', 'headerClass' => 'header--solid']) ?>

<main class="hub-shell">
  <a class="hub-back" href="<?= site_url('admin/metadata-submissions') ?>">
    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Submission
  </a>

  <h1 class="hub-title">Detail Submission #<?= esc($submission['id']) ?></h1>
  <p class="hub-sub">
    Dikirim oleh
    <strong><?= esc($submission['individual_name'] ?? '-') ?></strong>
    &mdash;
    <span class="badge badge-<?= esc($submission['submitted_by_role'] ?? 'user') ?>">
      <?= esc(strtoupper($submission['submitted_by_role'] ?? '-')) ?>
    </span>
    &mdash;
    <?= esc(substr((string)($submission['submitted_at'] ?? ''), 0, 16)) ?>
  </p>

  <?php if (session()->getFlashdata('success')): ?>
    <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:12px;padding:12px 18px;margin-bottom:16px;font-size:.875rem;color:#065f46;font-weight:600;">
      <?= esc(session()->getFlashdata('success')) ?>
    </div>
  <?php endif ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:12px;padding:12px 18px;margin-bottom:16px;font-size:.875rem;color:#991b1b;font-weight:600;">
      <?= esc(session()->getFlashdata('error')) ?>
    </div>
  <?php endif ?>

  <!-- Review Bar -->
  <?php $status = $submission['review_status'] ?? 'pending'; ?>
  <div class="review-bar">
    <div style="flex:1;min-width:200px;">
      <div style="font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#6b7a8f;margin-bottom:4px;">Status Review</div>
      <span class="review-bar status-badge status-<?= esc($status) ?>">
        <?= esc(strtoupper($status)) ?>
      </span>
      <?php if (!empty($submission['reviewed_at'])): ?>
        <span style="font-size:.78rem;color:#6b7a8f;margin-left:10px;">
          oleh acc_id <?= esc($submission['reviewed_by'] ?? '-') ?>
          &mdash; <?= esc(substr((string)$submission['reviewed_at'], 0, 16)) ?>
        </span>
      <?php endif ?>
    </div>

    <?php if ($status === 'pending'): ?>
      <form method="post" action="<?= site_url('admin/metadata-submissions/' . (int)$submission['id'] . '/approve') ?>"
            onsubmit="return confirm('Approve metadata ini?')">
        <?= csrf_field() ?>
        <button type="submit" class="btn-approve"><i class="bi bi-check-circle"></i> Approve</button>
      </form>

      <button class="btn-reject" onclick="document.getElementById('rejectForm').style.display='block';this.style.display='none'">
        <i class="bi bi-x-circle"></i> Tolak
      </button>
    <?php elseif ($status === 'approved'): ?>
      <form method="post" action="<?= site_url('admin/metadata-submissions/' . (int)$submission['id'] . '/reject') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="reviewer_notes" value="">
        <button type="submit" class="btn-reject" onclick="return confirm('Batalkan approval?')">Batalkan Approval</button>
      </form>
    <?php endif ?>

    <?php if (!empty($submission['reviewer_notes'])): ?>
      <div style="width:100%;background:#fef2f2;border-radius:10px;padding:10px 14px;font-size:.82rem;color:#991b1b;">
        <strong>Catatan:</strong> <?= esc($submission['reviewer_notes']) ?>
      </div>
    <?php endif ?>
  </div>

  <!-- Reject Form (shown on click) -->
  <div id="rejectForm" class="reject-form">
    <form method="post" action="<?= site_url('admin/metadata-submissions/' . (int)$submission['id'] . '/reject') ?>">
      <?= csrf_field() ?>
      <label style="font-size:.8rem;font-weight:700;color:#991b1b;display:block;margin-bottom:6px;">Alasan Penolakan (opsional)</label>
      <textarea name="reviewer_notes" rows="3" placeholder="Tuliskan alasan penolakan untuk admin..."></textarea>
      <div style="margin-top:10px;display:flex;gap:8px;">
        <button type="submit" class="btn-reject" style="background:#991b1b;color:#fff;">Konfirmasi Tolak</button>
        <button type="button" onclick="document.getElementById('rejectForm').style.display='none';document.querySelector('.btn-reject').style.display=''"
                style="padding:8px 16px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;font-size:.83rem;">Batal</button>
      </div>
    </form>
  </div>

  <!-- Identifikasi Dataset -->
  <div class="card-section">
    <h2><i class="bi bi-tag"></i> Identifikasi Dataset</h2>
    <div class="field-grid">
      <div class="field-group full">
        <span class="field-label">File Identifier</span>
        <span class="field-value mono"><?= esc($submission['metadata_file_identifier'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Jenis Data</span>
        <span class="field-value"><?= esc($submission['jenis_data'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Level Data</span>
        <span class="field-value"><?= esc($submission['level_data'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Provinsi</span>
        <span class="field-value"><?= esc($submission['provinsi'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Bahasa</span>
        <span class="field-value"><?= esc($submission['bahasa'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Character Set</span>
        <span class="field-value"><?= esc($submission['character_set'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Hierarchy Level</span>
        <span class="field-value"><?= esc($submission['hierarchy_level'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Metadata Date Stamp</span>
        <span class="field-value"><?= esc($submission['metadata_date_stamp'] ?? '-') ?></span>
      </div>
    </div>
  </div>

  <!-- Kontak -->
  <div class="card-section">
    <h2><i class="bi bi-person-lines-fill"></i> Informasi Kontak</h2>
    <div class="field-grid">
      <div class="field-group">
        <span class="field-label">Nama Individu</span>
        <span class="field-value"><?= esc($submission['individual_name'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Organisasi</span>
        <span class="field-value"><?= esc($submission['organisation_name'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Jabatan</span>
        <span class="field-value"><?= esc($submission['position_name'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Contact Role</span>
        <span class="field-value"><?= esc($submission['contact_role'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Telepon (Voice)</span>
        <span class="field-value"><?= esc($submission['voice'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Faksimili</span>
        <span class="field-value"><?= esc($submission['facsimilie'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Email</span>
        <span class="field-value"><?= esc($submission['electronic_mail_address'] ?? '-') ?></span>
      </div>
    </div>
  </div>

  <!-- Alamat -->
  <div class="card-section">
    <h2><i class="bi bi-geo-alt"></i> Alamat</h2>
    <div class="field-grid">
      <div class="field-group full">
        <span class="field-label">Delivery Point</span>
        <span class="field-value"><?= esc($submission['delivery_point'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Kota</span>
        <span class="field-value"><?= esc($submission['city'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Provinsi / Area</span>
        <span class="field-value"><?= esc($submission['administrative_area'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Kode Pos</span>
        <span class="field-value"><?= esc($submission['postal_code'] ?? '-') ?></span>
      </div>
      <div class="field-group">
        <span class="field-label">Negara</span>
        <span class="field-value"><?= esc($submission['country'] ?? '-') ?></span>
      </div>
    </div>
  </div>

  <!-- File Upload -->
  <div class="card-section">
    <h2><i class="bi bi-paperclip"></i> File yang Dilampirkan</h2>
    <div style="display:flex;flex-direction:column;gap:12px;">

      <?php
      $fileRows = [
          ['label' => 'Shapefile ZIP', 'key' => 'shapefile_zip_path', 'type' => 'shapefile', 'icon' => 'bi-file-zip'],
          ['label' => 'File Tabular (CSV)',  'key' => 'tabular_file_path',   'type' => 'tabular',   'icon' => 'bi-filetype-csv'],
          ['label' => 'File Raster (TIFF)', 'key' => 'raster_file_path',    'type' => 'raster',    'icon' => 'bi-layers'],
      ];
      foreach ($fileRows as $fr):
          $path = $submission[$fr['key']] ?? '';
      ?>
      <div style="display:flex;align-items:center;gap:14px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:12px 16px;">
        <i class="bi <?= esc($fr['icon']) ?>" style="font-size:1.3rem;color:#6b7a8f;flex-shrink:0"></i>
        <div style="flex:1;min-width:0;">
          <div style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#8898a4;margin-bottom:2px;"><?= esc($fr['label']) ?></div>
          <?php if ($path !== ''): ?>
            <div style="font-family:monospace;font-size:.78rem;color:#3d5a6b;word-break:break-all;"><?= esc(basename($path)) ?></div>
          <?php else: ?>
            <div style="font-style:italic;color:#b0bec5;font-size:.82rem;">Tidak dilampirkan</div>
          <?php endif ?>
        </div>
        <?php if ($path !== ''): ?>
          <a href="<?= site_url('admin/metadata-submissions/' . (int)$submission['id'] . '/download/' . esc($fr['type'])) ?>"
             style="flex-shrink:0;display:inline-flex;align-items:center;gap:5px;background:#a76025;color:#fff;padding:7px 14px;border-radius:10px;font-size:.78rem;font-weight:700;text-decoration:none;">
            <i class="bi bi-download"></i> Download
          </a>
        <?php endif ?>
      </div>
      <?php endforeach; ?>

    </div>
  </div>

</main>
</body>
</html>
