<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Submission Metadata</title>
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css') ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=31') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
  <style>
    body { background:linear-gradient(180deg,#eff4f7 0%,#dfe7ee 100%); font-family:'Poppins',sans-serif; color:#142033; }
    .hub-shell { max-width:1280px; margin:0 auto; padding:calc(var(--landing-header-offset,112px) + 18px) 20px 60px; }
    .hub-back  { display:inline-flex; align-items:center; gap:6px; font-size:.85rem; font-weight:700; color:#a76025; text-decoration:none; margin-bottom:22px; }
    .hub-title { font-size:32px; font-weight:800; margin:0 0 6px; }
    .hub-sub   { color:#6b7a8f; font-size:.9rem; margin-bottom:28px; }
    .card-section { background:#fff; border-radius:24px; padding:24px; box-shadow:0 4px 24px rgba(16,24,40,.08); }
    .card-section h2 { font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7a8f; margin:0 0 18px; }
    table { width:100%; border-collapse:collapse; font-size:.84rem; }
    th { background:#f0f4f8; color:#6b7a8f; font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; padding:10px 12px; text-align:left; white-space:nowrap; }
    td { padding:11px 12px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
    tr:last-child td { border-bottom:none; }
    tr:hover td { background:#fafbfc; }
    .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:.7rem; font-weight:800; }
    .badge-admin     { background:#e8f0fe; color:#1a56db; }
    .badge-superadmin{ background:#fce8f3; color:#991b7a; }
    .badge-user      { background:#e8f5e9; color:#217346; }
    .empty { text-align:center; padding:40px; color:#6b7a8f; }
    .empty i { font-size:44px; display:block; margin-bottom:12px; }
    .search-bar { margin-bottom:18px; }
    .search-bar input { padding:9px 14px; border-radius:14px; border:1px solid rgba(20,32,51,.12); font-size:.875rem; width:320px; }
  </style>
</head>
<body class="gravport-landing">

<?= view('partials/site_header', ['activePage' => 'admin', 'headerClass' => 'header--solid']) ?>

<main class="hub-shell">
  <a class="hub-back" href="<?= site_url('dataset/manage') ?>">
    <i class="bi bi-arrow-left"></i> Admin Hub
  </a>

  <h1 class="hub-title">Submission Metadata</h1>
  <p class="hub-sub">Semua metadata yang dikirimkan pengguna dan admin ke sistem (200 terbaru).</p>

  <div class="card-section">
    <h2><i class="bi bi-file-earmark-text"></i> Daftar Submission</h2>

    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="Cari identifier, provinsi, nama organisasi…" oninput="filterTable()">
    </div>

    <?php if (empty($submissions)): ?>
      <div class="empty"><i class="bi bi-inbox"></i>Belum ada submission metadata.</div>
    <?php else: ?>
      <div style="overflow-x:auto">
        <table id="submissionTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Identifier</th>
              <th>Jenis</th>
              <th>Provinsi</th>
              <th>Level</th>
              <th>Organisasi</th>
              <th>Nama</th>
              <th>Role</th>
              <th>Dikirim</th>
              <th>Detail</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($submissions as $row): ?>
            <tr>
              <td style="color:#6b7a8f;font-size:.78rem"><?= esc($row['id']) ?></td>
              <td style="font-size:.78rem;max-width:200px;word-break:break-all"><?= esc($row['metadata_file_identifier'] ?? '-') ?></td>
              <td><?= esc($row['jenis_data'] ?? '-') ?></td>
              <td><?= esc($row['provinsi'] ?? '-') ?></td>
              <td><?= esc($row['level_data'] ?? '-') ?></td>
              <td style="font-size:.82rem"><?= esc($row['organisation_name'] ?? '-') ?></td>
              <td style="font-size:.82rem"><?= esc($row['individual_name'] ?? '-') ?></td>
              <td>
                <span class="badge badge-<?= esc($row['submitted_by_role'] ?? 'user') ?>">
                  <?= esc(strtoupper($row['submitted_by_role'] ?? '-')) ?>
                </span>
              </td>
              <td style="white-space:nowrap;font-size:.78rem"><?= esc(substr((string)($row['submitted_at'] ?? ''), 0, 16)) ?></td>
              <td>
                <a href="<?= site_url('admin/metadata-submissions/' . (int)$row['id']) ?>"
                   style="color:#a76025;font-size:.78rem;font-weight:700;text-decoration:none">
                  <i class="bi bi-eye"></i> Lihat
                </a>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
      <p style="font-size:.78rem;color:#6b7a8f;margin-top:12px">
        Total: <strong><?= count($submissions) ?></strong> submission ditampilkan.
      </p>
    <?php endif ?>
  </div>
</main>

<script>
function filterTable() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('#submissionTable tbody tr').forEach(function(tr) {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
</body>
</html>
