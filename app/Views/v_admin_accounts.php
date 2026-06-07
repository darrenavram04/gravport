<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Kelola Akun</title>
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css') ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=31') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
  <style>
    body { background:linear-gradient(180deg,#eff4f7 0%,#dfe7ee 100%); font-family:'Poppins',sans-serif; color:#142033; }
    .hub-shell { max-width:1280px; margin:0 auto; padding:calc(var(--landing-header-offset,112px) + 18px) 20px 60px; }
    .hub-back  { display:inline-flex; align-items:center; gap:6px; font-size:.85rem; font-weight:700; color:#a76025; text-decoration:none; margin-bottom:22px; }
    .hub-title { font-size:32px; font-weight:800; margin:0 0 6px; }
    .hub-sub   { color:#6b7a8f; font-size:.9rem; margin-bottom:28px; }
    .card-section { background:#fff; border-radius:24px; padding:24px; box-shadow:0 4px 24px rgba(16,24,40,.08); margin-bottom:24px; }
    .card-section h2 { font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7a8f; margin:0 0 18px; }
    table { width:100%; border-collapse:collapse; font-size:.84rem; }
    th { background:#f0f4f8; color:#6b7a8f; font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; padding:10px 12px; text-align:left; white-space:nowrap; }
    td { padding:10px 12px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
    tr:last-child td { border-bottom:none; }
    tr:hover td { background:#fafbfc; }
    .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:.7rem; font-weight:800; }
    .badge-superadmin { background:#fce8f3; color:#991b7a; }
    .badge-admin  { background:#e8f0fe; color:#1a56db; }
    .badge-user   { background:#e8f5e9; color:#217346; }
    .badge-active   { background:#C6EFCE; color:#217346; }
    .badge-inactive { background:#fce8e8; color:#b91c1c; }
    .btn { display:inline-flex; align-items:center; gap:4px; padding:5px 12px; border-radius:999px; font-size:.74rem; font-weight:700; cursor:pointer; border:none; text-decoration:none; }
    .btn-sm { padding:4px 10px; font-size:.72rem; }
    .alert { padding:12px 18px; border-radius:14px; margin-bottom:20px; font-size:.875rem; font-weight:600; }
    .alert-success { background:#C6EFCE; color:#217346; }
    .alert-error   { background:#fce8e8; color:#b91c1c; }
    /* Create form */
    .create-form { display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:12px; align-items:end; }
    .create-form label { display:block; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#6b7a8f; margin-bottom:4px; }
    .create-form input, .create-form select { width:100%; padding:9px 12px; border-radius:12px; border:1px solid rgba(20,32,51,.12); font-size:.875rem; }
    .btn-create { background:#a76025; color:#fff; min-height:40px; white-space:nowrap; padding:0 18px; border-radius:12px; font-size:.875rem; }
    .search-bar { margin-bottom:14px; }
    .search-bar input { padding:8px 14px; border-radius:12px; border:1px solid rgba(20,32,51,.12); font-size:.875rem; width:300px; }
    @media(max-width:860px) { .create-form { grid-template-columns:1fr; } }
  </style>
</head>
<body class="gravport-landing">

<?= view('partials/site_header', ['activePage' => 'admin', 'headerClass' => 'header--solid']) ?>

<main class="hub-shell">
  <a class="hub-back" href="<?= site_url('dataset/manage') ?>">
    <i class="bi bi-arrow-left"></i> Admin Hub
  </a>

  <h1 class="hub-title">Kelola Akun</h1>
  <p class="hub-sub">Buat akun admin/user baru, ubah role, atau nonaktifkan akun.</p>

  <?php if ($msg = session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc($msg) ?></div>
  <?php endif ?>
  <?php if ($msg = session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><?= esc($msg) ?></div>
  <?php endif ?>

  <!-- ── Buat Akun Baru ───────────────────────────────────────────── -->
  <div class="card-section">
    <h2><i class="bi bi-person-plus"></i> Buat Akun Baru</h2>
    <form method="post" action="<?= site_url('admin/accounts/create') ?>">
      <?= csrf_field() ?>
      <div class="create-form">
        <div>
          <label>Nama Lengkap *</label>
          <input type="text" name="acc_name" required placeholder="Nama">
        </div>
        <div>
          <label>Email *</label>
          <input type="email" name="acc_email" required placeholder="email@domain.com">
        </div>
        <div>
          <label>Password * (min 8 karakter)</label>
          <input type="password" name="acc_password" required minlength="8" placeholder="••••••••">
        </div>
        <div>
          <label>Role *</label>
          <select name="role">
            <option value="user">User</option>
            <option value="admin" selected>Admin</option>
            <option value="superadmin">Superadmin</option>
          </select>
        </div>
        <div>
          <button type="submit" class="btn btn-create">
            <i class="bi bi-plus-circle"></i> Buat Akun
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- ── Daftar Akun ──────────────────────────────────────────────── -->
  <div class="card-section">
    <h2><i class="bi bi-people"></i> Semua Akun (<?= count($accounts) ?>)</h2>

    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="Cari nama, email, role…" oninput="filterTable()">
    </div>

    <div style="overflow-x:auto">
      <table id="accountTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Email</th>
            <th>Role</th>
            <th>Langganan</th>
            <th>Berakhir</th>
            <th>Akun</th>
            <th>Downloads</th>
            <th>Dibuat</th>
            <th>Ubah Role</th>
            <th>Toggle Akun</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($accounts as $acc): ?>
          <?php
            $role      = $acc['role'] ?? 'user';
            $isActive  = ($acc['is_active'] === true || $acc['is_active'] === 't' || $acc['is_active'] === 1);
            $tier      = $acc['active_tier'] ?? null;
            $subEndAt  = $acc['sub_end_at']  ?? null;
          ?>
          <tr>
            <td style="color:#6b7a8f;font-size:.78rem"><?= (int)$acc['acc_id'] ?></td>
            <td style="font-weight:600"><?= esc($acc['acc_name']) ?></td>
            <td style="font-size:.82rem"><?= esc($acc['acc_email']) ?></td>
            <td><span class="badge badge-<?= esc($role) ?>"><?= esc(strtoupper($role)) ?></span></td>
            <td>
              <?php if ($tier): ?>
                <span class="badge badge-active"><?= esc(strtoupper($tier)) ?></span>
              <?php else: ?>
                <span class="badge" style="background:#f0f4f8;color:#6b7a8f">Tidak Ada</span>
              <?php endif ?>
            </td>
            <td style="font-size:.78rem;white-space:nowrap;color:<?= $subEndAt ? '#142033' : '#6b7a8f' ?>">
              <?= $subEndAt ? esc(substr((string)$subEndAt, 0, 10)) : '—' ?>
            </td>
            <td>
              <span class="badge badge-<?= $isActive ? 'active' : 'inactive' ?>">
                <?= $isActive ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </td>
            <td style="text-align:center"><?= number_format((int)($acc['download_count'] ?? 0)) ?></td>
            <td style="font-size:.78rem;white-space:nowrap"><?= esc(substr((string)($acc['created_at'] ?? ''), 0, 10)) ?></td>
            <td>
              <form method="post" action="<?= site_url('admin/accounts/' . (int)$acc['acc_id'] . '/set-role') ?>" style="display:inline-flex;gap:4px;align-items:center">
                <?= csrf_field() ?>
                <select name="role" style="padding:3px 6px;border-radius:8px;border:1px solid rgba(20,32,51,.15);font-size:.74rem">
                  <option value="user"       <?= $role === 'user'       ? 'selected' : '' ?>>User</option>
                  <option value="admin"      <?= $role === 'admin'      ? 'selected' : '' ?>>Admin</option>
                  <option value="superadmin" <?= $role === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                </select>
                <button type="submit" class="btn btn-sm" style="background:rgba(20,32,51,.08);color:#142033">Simpan</button>
              </form>
            </td>
            <td>
              <form method="post" action="<?= site_url('admin/accounts/' . (int)$acc['acc_id'] . '/toggle-active') ?>" style="display:inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm"
                  style="background:<?= $isActive ? '#fce8e8' : '#C6EFCE' ?>;color:<?= $isActive ? '#b91c1c' : '#217346' ?>">
                  <?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script>
function filterTable() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('#accountTable tbody tr').forEach(function(tr) {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
</body>
</html>
