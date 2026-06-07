<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Admin Hub</title>

  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css'); ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=31'); ?>">
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

    .admin-shell {
      max-width: 1180px;
      margin: 0 auto;
      padding: calc(var(--landing-header-offset) + 18px) 20px 38px;
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

    /* ── ADM LOADER ── */
    body.adm-loading { overflow: hidden; }
    .adm-loader {
      position: fixed; inset: 0; z-index: 9999;
      display: flex; align-items: center; justify-content: center;
      background: #0a0f1e;
      transition: transform 0.9s cubic-bezier(.76,0,.24,1);
    }
    .adm-loader.is-done { transform: translateY(-100%); pointer-events: none; }
    .adm-loader__grid {
      position: absolute; inset: 0; opacity: 0.03;
      background-image: linear-gradient(rgba(167,96,37,1) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(167,96,37,1) 1px, transparent 1px);
      background-size: 48px 48px;
    }
    .adm-loader__content {
      position: relative; text-align: center; color: #fff;
      opacity: 0; transform: translateY(18px);
      transition: opacity 0.4s, transform 0.4s;
    }
    .adm-loader.is-visible .adm-loader__content { opacity: 1; transform: none; }
    .adm-loader__icon {
      width: 64px; height: 64px; border-radius: 20px; margin: 0 auto 20px;
      background: rgba(167,96,37,0.12); border: 1px solid rgba(167,96,37,0.28);
      display: flex; align-items: center; justify-content: center;
      font-size: 26px; color: #a76025;
    }
    .adm-loader__name { font-size: 28px; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 6px; }
    .adm-loader__status {
      font-size: 11px; color: rgba(255,255,255,0.42);
      letter-spacing: 0.12em; text-transform: uppercase;
      margin-bottom: 22px; height: 16px;
      transition: opacity 0.2s;
    }
    .adm-loader__bar {
      width: 180px; height: 2px; border-radius: 999px;
      background: rgba(255,255,255,0.08); overflow: hidden; margin: 0 auto;
    }
    .adm-loader__bar-fill {
      height: 100%; width: 0%; border-radius: 999px;
      background: linear-gradient(90deg, #a76025, #ffbf74);
      transition: width 0.08s linear;
    }

    /* ── SCROLL PROGRESS ── */
    .adm-progress {
      position: fixed; top: 0; left: 0; z-index: 9990;
      height: 3px; width: 0%;
      background: linear-gradient(90deg, #a76025, #ffbf74);
      transition: width 0.1s linear;
      pointer-events: none;
    }

    /* ── PAGE-IN ANIMATION STATE ── */
    body.adm-loading .admin-title,
    body.adm-loading .admin-card { opacity: 0; transform: translateY(28px); }
    .admin-title { transition: opacity 0.7s ease, transform 0.7s ease; }
    .admin-card  { transition: opacity 0.5s ease, transform 0.5s ease, box-shadow 0.25s ease; }

    /* ── CARD HOVER LIFT ── */
    .admin-card:hover {
      transform: translateY(-5px) !important;
      box-shadow: 0 32px 64px rgba(16,24,40,0.18) !important;
    }
    .admin-card { position: relative; overflow: hidden; }

    /* ── LINK SHIMMER ── */
    .admin-link { position: relative; overflow: hidden; }
    .admin-link::after {
      content: ''; position: absolute; top: 0; left: -100%;
      width: 60%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
      transition: left 0.5s ease;
    }
    .admin-link:hover::after { left: 160%; }
  </style>
</head>
<body class="admin-hub-page gravport-landing adm-loading">

<!-- Cinematic Loader -->
<div class="adm-loader" id="admLoader">
  <div class="adm-loader__grid"></div>
  <div class="adm-loader__content">
    <div class="adm-loader__icon"><i class="bi bi-grid-3x3-gap-fill"></i></div>
    <div class="adm-loader__name">Admin Hub</div>
    <div class="adm-loader__status" id="admStatus">Verifying session…</div>
    <div class="adm-loader__bar"><div class="adm-loader__bar-fill" id="admBarFill"></div></div>
  </div>
</div>
<div class="adm-progress" id="admProgress"></div>

<?= view('partials/site_header', [
    'activePage' => 'admin',
    'headerClass' => 'header--solid',
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
      <a class="admin-link admin-link--ghost" href="<?= base_url('guidebooks/guidebook-metadata.pdf') ?>" target="_blank" rel="noopener">
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

    <?php
      $currentRole = auth_current_role();
      $isSuperAdmin = $currentRole === 'superadmin';
    ?>

    <?php if ($isSuperAdmin): ?>
    <!-- ── SUPERADMIN-ONLY CARDS ─────────────────────────────────── -->

    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-people"></i>
        <span class="admin-badge" style="display:none"></span>
      </div>
      <h2>Pendaftaran Tertunda</h2>
      <p style="font-size:.85rem;color:#6b7a8f;margin:0 0 14px">Setujui atau tolak akun individual &amp; tim yang menunggu aktivasi.</p>
      <a class="admin-link" href="<?= site_url('admin/pending') ?>">
        <span>Kelola Pendaftaran</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </article>

    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-credit-card-2-front"></i>
        <span class="admin-badge" style="display:none"></span>
      </div>
      <h2>Manajemen Langganan</h2>
      <p style="font-size:.85rem;color:#6b7a8f;margin:0 0 14px">Tetapkan atau batalkan langganan pengguna, lihat distribusi tier.</p>
      <a class="admin-link" href="<?= site_url('admin/subscriptions') ?>">
        <span>Kelola Langganan</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </article>

    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-layers-half"></i>
        <span class="admin-badge" style="display:none"></span>
      </div>
      <h2>Data Staging / Temp</h2>
      <p style="font-size:.85rem;color:#6b7a8f;margin:0 0 14px">Tinjau titik &amp; raster gravity yang dikirim admin, approve atau tolak.</p>
      <a class="admin-link" href="<?= site_url('dataset/staging') ?>">
        <span>Tinjau Staging</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </article>

    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-file-earmark-text"></i>
        <span class="admin-badge" style="display:none"></span>
      </div>
      <h2>Submission Metadata</h2>
      <p style="font-size:.85rem;color:#6b7a8f;margin:0 0 14px">Lihat semua metadata yang dikirimkan pengguna ke sistem.</p>
      <a class="admin-link admin-link--ghost" href="<?= site_url('admin/metadata-submissions') ?>">
        <span>Lihat Submissions</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </article>

    <!-- Upload XML Metadata -->
    <article class="admin-card" id="card-upload-xml">
      <div class="admin-card__top">
        <i class="bi bi-file-earmark-code"></i>
        <span class="admin-badge" style="display:none"></span>
      </div>
      <h2>Upload Metadata XML</h2>
      <p style="font-size:.85rem;color:#6b7a8f;margin:0 0 14px">Upload file <code>.xml</code> CatMD ke katalog produksi (dataset_metadata_xml).</p>

      <?php if (session()->getFlashdata('xml_success')): ?>
        <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:.8rem;color:#065f46;font-weight:600;">
          <?= esc(session()->getFlashdata('xml_success')) ?>
        </div>
      <?php endif ?>
      <?php if (session()->getFlashdata('xml_error')): ?>
        <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:.8rem;color:#991b1b;font-weight:600;">
          <?= esc(session()->getFlashdata('xml_error')) ?>
        </div>
      <?php endif ?>

      <form method="post" action="<?= site_url('admin/upload-metadata-xml') ?>" enctype="multipart/form-data"
            style="display:flex;flex-direction:column;gap:10px;">
        <?= csrf_field() ?>
        <div>
          <label style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#6b7a8f;display:block;margin-bottom:4px;">Dataset</label>
          <select name="dataset_code" required
                  style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:7px 10px;font-size:.84rem;color:#142033;">
            <option value="">-- Pilih Dataset --</option>
            <option value="faa_l1">Free Air Anomaly Level 1</option>
            <option value="cba_l1">Complete Bouguer Anomaly Level 1</option>
            <option value="faa_l2">Free Air Anomaly Level 2</option>
            <option value="cba_l2">Complete Bouguer Anomaly Level 2</option>
          </select>
        </div>
        <div>
          <label style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#6b7a8f;display:block;margin-bottom:4px;">File XML</label>
          <input type="file" name="metadata_xml" accept=".xml" required
                 style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:7px 10px;font-size:.84rem;color:#142033;">
        </div>
        <button type="submit"
                style="width:100%;display:flex;align-items:center;justify-content:center;gap:6px;background:#a76025;color:#fff;border:none;padding:9px 0;border-radius:10px;font-weight:700;font-size:.85rem;cursor:pointer;margin-top:2px;">
          <i class="bi bi-cloud-upload"></i> Upload ke Katalog
        </button>
      </form>
    </article>

    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-building"></i>
        <span class="admin-badge" style="display:none"></span>
      </div>
      <h2>Data Providers</h2>
      <p style="font-size:.85rem;color:#6b7a8f;margin:0 0 14px">Tambah &amp; kelola provider data dan pengaturan revenue share.</p>
      <a class="admin-link admin-link--ghost" href="<?= site_url('admin/providers') ?>">
        <span>Kelola Providers</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </article>

    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-bar-chart-line"></i>
        <span class="admin-badge" style="display:none"></span>
      </div>
      <h2>Revenue Share</h2>
      <p style="font-size:.85rem;color:#6b7a8f;margin:0 0 14px">Generate laporan revenue bulanan dan tandai pembayaran provider.</p>
      <a class="admin-link admin-link--ghost" href="<?= site_url('admin/revenue') ?>">
        <span>Lihat Revenue</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </article>

    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-person-gear"></i>
        <span class="admin-badge" style="display:none"></span>
      </div>
      <h2>Kelola Akun</h2>
      <p style="font-size:.85rem;color:#6b7a8f;margin:0 0 14px">Buat akun admin/user baru, ubah role, atau nonaktifkan akun.</p>
      <a class="admin-link" href="<?= site_url('admin/accounts') ?>">
        <span>Kelola Akun</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </article>

    <?php else: ?>
    <!-- ── ADMIN (non-superadmin) CARDS ──────────────────────────── -->

    <article class="admin-card">
      <div class="admin-card__top">
        <i class="bi bi-clock-history"></i>
        <span class="admin-badge">Admin</span>
      </div>
      <h2>Status Submission Saya</h2>
      <p style="font-size:.85rem;color:#6b7a8f;margin:0 0 14px">Pantau status persetujuan data titik, raster, dan metadata yang telah Anda kirimkan ke superadmin.</p>
      <a class="admin-link" href="<?= site_url('dataset/my-submissions') ?>">
        <span>Lihat Status</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </article>

    <?php endif; ?>
  </section>
</main>

<script>
(function () {
  var loader   = document.getElementById('admLoader');
  var statusEl = document.getElementById('admStatus');
  var barFill  = document.getElementById('admBarFill');
  var msgs     = ['Verifying session…', 'Loading workspace…', 'Preparing datasets…', 'Ready.'];
  var msgIdx   = 0;
  var barPct   = 0;

  function setMsg(idx) {
    if (!statusEl) return;
    statusEl.style.opacity = '0';
    setTimeout(function () {
      statusEl.textContent = msgs[idx] || msgs[msgs.length - 1];
      statusEl.style.opacity = '1';
    }, 150);
  }

  function advanceBar(target, cb) {
    var iv = setInterval(function () {
      barPct = Math.min(barPct + 3, target);
      if (barFill) barFill.style.width = barPct + '%';
      if (barPct >= target) { clearInterval(iv); if (cb) cb(); }
    }, 18);
  }

  function triggerPageIn() {
    var title = document.querySelector('.admin-title');
    if (title) { title.style.opacity = '1'; title.style.transform = 'none'; }
    document.querySelectorAll('.admin-card').forEach(function (el, i) {
      setTimeout(function () {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
      }, i * 75 + 60);
    });
  }

  if (loader) {
    setTimeout(function () {
      loader.classList.add('is-visible');
      advanceBar(55, function () {
        setMsg(1);
        setTimeout(function () {
          advanceBar(82, function () {
            setMsg(2);
            setTimeout(function () {
              advanceBar(100, function () {
                setMsg(3);
                setTimeout(function () {
                  loader.classList.add('is-done');
                  document.body.classList.remove('adm-loading');
                  triggerPageIn();
                }, 320);
              });
            }, 220);
          });
        }, 380);
      });
    }, 60);
  } else {
    triggerPageIn();
  }

  /* Scroll progress */
  var prog = document.getElementById('admProgress');
  if (prog) {
    window.addEventListener('scroll', function () {
      var s = document.documentElement.scrollTop;
      var h = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      prog.style.width = (h > 0 ? (s / h * 100) : 0) + '%';
    }, { passive: true });
  }

  /* Card spotlight hover */
  document.querySelectorAll('.admin-card').forEach(function (card) {
    var glow = document.createElement('div');
    glow.style.cssText = [
      'position:absolute;inset:0;pointer-events:none;border-radius:inherit;',
      'opacity:0;transition:opacity 0.3s;',
      'background:radial-gradient(circle 220px at var(--cmx,50%) var(--cmy,50%),',
      'rgba(167,96,37,0.13),transparent 70%)'
    ].join('');
    card.appendChild(glow);
    card.addEventListener('mousemove', function (e) {
      var r = card.getBoundingClientRect();
      card.style.setProperty('--cmx', (e.clientX - r.left) + 'px');
      card.style.setProperty('--cmy', (e.clientY - r.top) + 'px');
      glow.style.opacity = '1';
    });
    card.addEventListener('mouseleave', function () { glow.style.opacity = '0'; });
  });
})();
</script>
</body>
</html>
