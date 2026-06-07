<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Data Providers</title>
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css') ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=31') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
  <style>
    body { background: linear-gradient(180deg,#eff4f7 0%,#dfe7ee 100%); font-family:'Poppins',sans-serif; color:#142033; }
    .hub-shell { max-width:1180px; margin:0 auto; padding:calc(var(--landing-header-offset,112px) + 18px) 20px 40px; }
    .hub-back { display:inline-flex; align-items:center; gap:6px; font-size:.85rem; font-weight:700; color:#a76025; text-decoration:none; margin-bottom:22px; }
    .hub-title { font-size:32px; font-weight:800; margin:0 0 24px; }
    .card-section { background:#fff; border-radius:24px; padding:28px; box-shadow:0 4px 24px rgba(16,24,40,.09); margin-bottom:28px; }
    .card-section h2 { font-size:16px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7a8f; margin:0 0 20px; }
    .table-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; font-size:.875rem; }
    th { background:#f0f4f8; color:#6b7a8f; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; padding:10px 14px; text-align:left; white-space:nowrap; }
    td { padding:12px 14px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
    tr:last-child td { border-bottom:none; }
    .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:.72rem; font-weight:800; }
    .badge-active { background:#C6EFCE; color:#217346; }
    .badge-inactive { background:#f0f4f8; color:#6b7a8f; }
    .badge-govt { background:#D9E6F2; color:#1B3A5C; }
    .btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:999px; font-weight:700; font-size:.82rem; cursor:pointer; text-decoration:none; border:none; }
    .btn-primary { background:#a76025; color:#fff; }
    .btn-ghost { background:rgba(20,32,51,.07); color:#142033; }
    .btn-danger { background:#fce8e8; color:#b91c1c; }
    .btn-sm { padding:5px 12px; font-size:.78rem; }
    .form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; }
    .form-group label { display:block; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7a8f; margin-bottom:5px; }
    .form-group input, .form-group select, .form-group textarea {
      width:100%; padding:10px 14px; border:1px solid #dde3ec; border-radius:14px;
      font-size:.875rem; background:#fafbfc; color:#142033; font-family:inherit;
    }
    .form-group textarea { resize:vertical; min-height:72px; }
    .alert { padding:12px 18px; border-radius:14px; margin-bottom:20px; font-size:.875rem; font-weight:600; }
    .alert-success { background:#C6EFCE; color:#217346; }
    .alert-error   { background:#fce8e8; color:#b91c1c; }
    .revenue-pct { font-weight:800; color:#a76025; }
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(10,15,30,.55); backdrop-filter:blur(4px); z-index:900; align-items:center; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal-box { background:#fff; border-radius:24px; padding:32px; max-width:560px; width:calc(100% - 40px); box-shadow:0 24px 64px rgba(0,0,0,.18); }
    .modal-box h3 { font-size:20px; font-weight:800; margin:0 0 20px; }
  </style>
</head>
<body class="admin-hub-page gravport-landing">
<?= view('partials/site_header', ['activePage'=>'admin','headerClass'=>'header--solid']) ?>

<main class="hub-shell">
  <a class="hub-back" href="<?= site_url('admin') ?>"><i class="bi bi-arrow-left"></i> Admin Hub</a>
  <h1 class="hub-title"><i class="bi bi-building" style="color:#a76025"></i> Data Providers</h1>

  <?php if ($msg = session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= esc($msg) ?></div>
  <?php endif ?>
  <?php if ($msg = session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><i class="bi bi-exclamation-circle"></i> <?= esc($msg) ?></div>
  <?php endif ?>

  <!-- ── Provider List ── -->
  <div class="card-section">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h2 style="margin:0">Daftar Provider (<?= count($providers) ?>)</h2>
      <button class="btn btn-primary" onclick="document.getElementById('modalAdd').classList.add('open')">
        <i class="bi bi-plus-lg"></i> Tambah Provider
      </button>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Nama Provider</th><th>Tipe</th><th>Kontak</th>
            <th>Revenue Share</th><th>Total DL</th><th>Total Revenue</th>
            <th>Status</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($providers as $p): ?>
          <tr>
            <td style="color:#6b7a8f"><?= (int)$p['provider_id'] ?></td>
            <td style="font-weight:700"><?= esc($p['provider_name']) ?></td>
            <td><span class="badge badge-govt"><?= esc(strtoupper($p['provider_type'])) ?></span></td>
            <td style="font-size:.8rem">
              <?= esc($p['contact_email'] ?: '-') ?><br>
              <span style="color:#6b7a8f"><?= esc($p['contact_person'] ?: '') ?></span>
            </td>
            <td class="revenue-pct"><?= number_format((float)$p['revenue_share_pct'],1) ?>%
              <small style="color:#6b7a8f;font-weight:400"> Provider</small></td>
            <td><?= number_format((int)$p['total_downloads']) ?></td>
            <td>Rp <?= number_format((float)$p['total_revenue'],0,',','.') ?></td>
            <td>
              <span class="badge <?= $p['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                <?= $p['is_active'] ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </td>
            <td style="white-space:nowrap">
              <button class="btn btn-ghost btn-sm" onclick='openEdit(<?= json_encode($p) ?>)'>
                <i class="bi bi-pencil"></i>
              </button>
              <form method="post" action="<?= site_url('admin/providers/'.(int)$p['provider_id'].'/toggle') ?>" style="display:inline">
                <?= csrf_field() ?>
                <button class="btn btn-sm <?= $p['is_active'] ? 'btn-danger' : 'btn-ghost' ?>" type="submit">
                  <i class="bi bi-<?= $p['is_active'] ? 'pause' : 'play' ?>-circle"></i>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach ?>
          <?php if (!$providers): ?>
          <tr><td colspan="9" style="text-align:center;color:#6b7a8f;padding:32px">Belum ada provider.</td></tr>
          <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>

  <p style="font-size:.8rem;color:#6b7a8f">
    <i class="bi bi-info-circle"></i>
    Revenue share benchmark: App Store 30%, AWS Marketplace 20%, Spotify 30%.
    Gravport mengambil <strong>25%</strong> (provider mendapat <strong>75%</strong>) - lebih ramah provider dari App Store.
  </p>
</main>

<!-- ── Modal: Add Provider ── -->
<div class="modal-overlay" id="modalAdd">
  <div class="modal-box">
    <h3><i class="bi bi-plus-circle" style="color:#a76025"></i> Tambah Provider Baru</h3>
    <form method="post" action="<?= site_url('admin/providers/store') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-group" style="grid-column:1/-1">
          <label>Nama Provider *</label>
          <input type="text" name="provider_name" required placeholder="BIG - Badan Informasi Geospasial">
        </div>
        <div class="form-group">
          <label>Tipe</label>
          <select name="provider_type">
            <option value="government">Government</option>
            <option value="institution">Institution</option>
            <option value="academic">Academic</option>
            <option value="private">Private</option>
          </select>
        </div>
        <div class="form-group">
          <label>Revenue Share Provider (%)</label>
          <input type="number" name="revenue_share_pct" value="75" min="0" max="100" step="0.01">
        </div>
        <div class="form-group">
          <label>Email Kontak</label>
          <input type="email" name="contact_email" placeholder="info@big.go.id">
        </div>
        <div class="form-group">
          <label>Nama PIC</label>
          <input type="text" name="contact_person" placeholder="Dr. Budi Santoso">
        </div>
        <div class="form-group">
          <label>Nama Bank</label>
          <input type="text" name="bank_name" placeholder="Bank Mandiri">
        </div>
        <div class="form-group">
          <label>No. Rekening</label>
          <input type="text" name="bank_account" placeholder="123-456-7890">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label>Catatan</label>
          <textarea name="notes"></textarea>
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:20px">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('modalAdd').classList.remove('open')">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Modal: Edit Provider ── -->
<div class="modal-overlay" id="modalEdit">
  <div class="modal-box">
    <h3><i class="bi bi-pencil" style="color:#a76025"></i> Edit Provider</h3>
    <form method="post" id="editForm" action="">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-group" style="grid-column:1/-1">
          <label>Nama Provider *</label>
          <input type="text" name="provider_name" id="eProviderName" required>
        </div>
        <div class="form-group">
          <label>Tipe</label>
          <select name="provider_type" id="eProviderType">
            <option value="government">Government</option>
            <option value="institution">Institution</option>
            <option value="academic">Academic</option>
            <option value="private">Private</option>
          </select>
        </div>
        <div class="form-group">
          <label>Revenue Share Provider (%)</label>
          <input type="number" name="revenue_share_pct" id="eRevenuePct" min="0" max="100" step="0.01">
        </div>
        <div class="form-group">
          <label>Email Kontak</label>
          <input type="email" name="contact_email" id="eContactEmail">
        </div>
        <div class="form-group">
          <label>Nama PIC</label>
          <input type="text" name="contact_person" id="eContactPerson">
        </div>
        <div class="form-group">
          <label>Nama Bank</label>
          <input type="text" name="bank_name" id="eBankName">
        </div>
        <div class="form-group">
          <label>No. Rekening</label>
          <input type="text" name="bank_account" id="eBankAccount">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label>Catatan</label>
          <textarea name="notes" id="eNotes"></textarea>
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:20px">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update</button>
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('modalEdit').classList.remove('open')">Batal</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(p) {
  document.getElementById('editForm').action = '<?= site_url('admin/providers/') ?>' + p.provider_id + '/update';
  document.getElementById('eProviderName').value  = p.provider_name  || '';
  document.getElementById('eProviderType').value  = p.provider_type  || 'government';
  document.getElementById('eRevenuePct').value    = p.revenue_share_pct || 75;
  document.getElementById('eContactEmail').value  = p.contact_email  || '';
  document.getElementById('eContactPerson').value = p.contact_person || '';
  document.getElementById('eBankName').value      = p.bank_name      || '';
  document.getElementById('eBankAccount').value   = p.bank_account   || '';
  document.getElementById('eNotes').value         = p.notes          || '';
  document.getElementById('modalEdit').classList.add('open');
}
document.querySelectorAll('.modal-overlay').forEach(function(m) {
  m.addEventListener('click', function(e) { if (e.target === m) m.classList.remove('open'); });
});
</script>
</body>
</html>

