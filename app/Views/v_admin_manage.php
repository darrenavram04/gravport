<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Admin Hub</title>

  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css'); ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=26'); ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css'); ?>">

  <style>
    body.admin-hub-page {
      margin: 0;
      font-family: "Poppins", sans-serif;
      background:
        radial-gradient(circle at top right, rgba(167, 96, 37, 0.18), transparent 26%),
        linear-gradient(180deg, #eff4f7 0%, #dfe7ee 100%);
      color: #142033;
    }

    .admin-hub-page .site-header {
      background: rgba(167, 96, 37, 0.94);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.16);
    }

    .admin-shell {
      max-width: 1180px;
      margin: 0 auto;
      padding: 120px 20px 38px;
    }

    .admin-title {
      margin: 0 0 18px;
      font-size: 40px;
      line-height: 1.04;
    }

    .admin-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
    }

    .admin-card {
      padding: 22px;
      border-radius: 28px;
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(20, 32, 51, 0.08);
      box-shadow: 0 20px 48px rgba(16, 24, 40, 0.12);
    }

    .admin-card__top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 16px;
    }

    .admin-card i {
      font-size: 26px;
      color: #a76025;
    }

    .admin-badge {
      display: inline-flex;
      align-items: center;
      min-height: 32px;
      padding: 0 12px;
      border-radius: 999px;
      background: rgba(167, 96, 37, 0.1);
      color: #8b4a17;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }

    .admin-card h2 {
      margin: 0 0 16px;
      font-size: 22px;
    }

    .admin-link,
    .admin-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-height: 44px;
      padding: 0 18px;
      border-radius: 999px;
      text-decoration: none;
      font-weight: 700;
      border: 0;
    }

    .admin-link {
      background: #a76025;
      color: #fff;
    }

    .admin-link--ghost {
      background: rgba(20, 32, 51, 0.06);
      color: #142033;
    }

    .admin-upload {
      display: grid;
      gap: 12px;
    }

    .admin-upload label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #6b7a8f;
    }

    .admin-upload input {
      width: 100%;
      min-height: 48px;
      padding: 10px 12px;
      border-radius: 18px;
      border: 1px solid rgba(20, 32, 51, 0.12);
      background: rgba(8, 17, 32, 0.03);
      color: #142033;
    }

    .admin-button {
      width: 100%;
      background: rgba(20, 32, 51, 0.08);
      color: #6b7a8f;
      cursor: not-allowed;
    }

    @media (max-width: 960px) {
      .admin-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="admin-hub-page">

<?= view('partials/site_header', [
    'activePage' => '',
]) ?>

<main class="admin-shell">
  <h1 class="admin-title">Admin Hub</h1>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <?php
    $packageSummary = $packageSummary ?? null;
    $importReport = $importReport ?? null;
    $faaLevel2Report = $importReport['level2']['faa'] ?? [];
    $cbaLevel2Report = $importReport['level2']['cba'] ?? [];
    $faaLevel2GridStep = isset($faaLevel2Report['grid_step_deg']) ? rtrim(rtrim(number_format((float) $faaLevel2Report['grid_step_deg'], 3, '.', ''), '0'), '.') : null;
    $cbaLevel2GridStep = isset($cbaLevel2Report['grid_step_deg']) ? rtrim(rtrim(number_format((float) $cbaLevel2Report['grid_step_deg'], 3, '.', ''), '0'), '.') : null;
  ?>

  <section class="admin-grid">
    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-journal-richtext"></i>
        <span class="admin-badge">Metadata</span>
      </div>
      <h2>Metadata Workspace</h2>
      <a class="admin-link" href="<?= site_url('metadata') ?>">
        <span>Buka Metadata</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </article>

    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-cloud-arrow-up"></i>
        <span class="admin-badge">Level 1</span>
      </div>
      <h2>Import Package</h2>
      <?php if ($packageSummary): ?>
        <div class="admin-upload">
          <label>Paket Aktif</label>
          <input type="text" value="<?= esc($packageSummary['package']) ?>" readonly>
          <label>FAA CSV</label>
          <input type="text" value="<?= esc(count($packageSummary['level1']['faa_csv'])) ?> file" readonly>
          <label>CBA CSV</label>
          <input type="text" value="<?= esc(count($packageSummary['level1']['cba_csv'])) ?> file" readonly>
          <label>Metadata Level 1</label>
          <input type="text" value="<?= esc($packageSummary['level1']['metadata']) ?>" readonly>
          <form method="post" action="<?= site_url('dataset/upload') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="package" value="<?= esc($packageSummary['package']) ?>">
            <button class="admin-link" type="submit">
              <span>Import ke PostgreSQL</span>
              <i class="bi bi-arrow-up-right-circle"></i>
            </button>
          </form>
        </div>
      <?php else: ?>
        <p>Tidak ada folder import yang terdeteksi di <code>writable/imports</code>.</p>
      <?php endif; ?>
    </article>

    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-cloud-arrow-up"></i>
        <span class="admin-badge">Level 2</span>
      </div>
      <h2>Raster Ready</h2>
      <?php if ($packageSummary): ?>
        <div class="admin-upload">
          <label>FAA TIFF</label>
          <input type="text" value="<?= esc($packageSummary['level2']['faa_tif']) ?>" readonly>
          <label>CBA TIFF</label>
          <input type="text" value="<?= esc($packageSummary['level2']['cba_tif']) ?>" readonly>
          <label>Metadata Level 2</label>
          <input type="text" value="<?= esc($packageSummary['level2']['metadata']) ?>" readonly>
          <a class="admin-link admin-link--ghost" href="<?= site_url('webmap') ?>">
            <span>Buka WebMap</span>
            <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      <?php else: ?>
        <p>Belum ada TIFF level 2 yang siap diimpor.</p>
      <?php endif; ?>
    </article>

    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-file-earmark-pdf"></i>
        <span class="admin-badge">Guidebook</span>
      </div>
      <h2>View PDF</h2>
      <a class="admin-link admin-link--ghost" href="<?= base_url('guidebooks/metadata-guidebook-dummy.pdf') ?>" target="_blank" rel="noopener">
        <span>Buka Guidebook</span>
        <i class="bi bi-box-arrow-up-right"></i>
      </a>
    </article>

    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-collection"></i>
        <span class="admin-badge">Catalog</span>
      </div>
      <h2>Dataset Catalog</h2>
      <a class="admin-link admin-link--ghost" href="<?= site_url('catalog') ?>">
        <span>Buka Catalog</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </article>

    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-map"></i>
        <span class="admin-badge">WebMap</span>
      </div>
      <h2>WebMap</h2>
      <a class="admin-link admin-link--ghost" href="<?= site_url('webmap') ?>">
        <span>Buka WebMap</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </article>

    <?php if ($importReport): ?>
      <article class="admin-card">
        <div class="admin-card__top">
          <i class="bi bi-check2-square"></i>
          <span class="admin-badge">Hasil Import</span>
        </div>
        <h2>Ringkasan</h2>
        <div class="admin-upload">
          <label>Paket</label>
          <input type="text" value="<?= esc($importReport['package']) ?>" readonly>
          <label>FAA Level 1</label>
          <input type="text" value="<?= esc(($importReport['level1']['faa']['rows'] ?? 0) . ' titik, skip ' . ($importReport['level1']['faa']['skipped_rows'] ?? 0)) ?>" readonly>
          <label>CBA Level 1</label>
          <input type="text" value="<?= esc(($importReport['level1']['cba']['rows'] ?? 0) . ' titik, skip ' . ($importReport['level1']['cba']['skipped_rows'] ?? 0)) ?>" readonly>
          <label>FAA Level 2</label>
          <input type="text" value="<?= esc(($faaLevel2Report['rows'] ?? 0) . ' grid @ ' . ($faaLevel2GridStep ?: '0.125') . '°') ?>" readonly>
          <label>CBA Level 2</label>
          <input type="text" value="<?= esc(($cbaLevel2Report['rows'] ?? 0) . ' grid @ ' . ($cbaLevel2GridStep ?: '0.125') . '°') ?>" readonly>
          <label>Metadata</label>
          <input type="text" value="<?= esc(($importReport['metadata']['level1']['file_identifier'] ?? '-') . ' / ' . ($importReport['metadata']['level2']['file_identifier'] ?? '-')) ?>" readonly>
        </div>
      </article>
    <?php endif; ?>
  </section>
</main>

</body>
</html>
