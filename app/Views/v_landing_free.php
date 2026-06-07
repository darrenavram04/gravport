<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Akses Gratis</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
  <style>
    :root {
      --c-bg:    #04101d;
      --c-navy:  #0b1b34;
      --c-amber: #ffbf74;
      --c-cyan:  #61d4ff;
      --c-text:  rgba(245,248,255,.96);
      --c-muted: rgba(219,226,242,.72);
      --c-green: #4ade80;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Manrope', sans-serif;
      color: var(--c-text);
      background:
        radial-gradient(circle at top left, rgba(34,197,94,.18), transparent 30%),
        radial-gradient(circle at 80% 10%, rgba(97,212,255,.14), transparent 26%),
        linear-gradient(135deg, #04101d 0%, #091427 50%, #0b1b34 100%);
      min-height: 100vh;
    }

    /* ── NAV ── */
    nav {
      position: sticky; top: 0; z-index: 100;
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 40px;
      background: rgba(4,16,29,.82);
      backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(255,255,255,.07);
    }
    .nav-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
    .nav-brand img { width: 36px; height: 36px; object-fit: contain; }
    .nav-brand strong { font-size: 16px; letter-spacing: .1em; text-transform: uppercase; color: #fff; }
    .nav-actions { display: flex; gap: 10px; align-items: center; }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 999px; font-weight: 700; font-size: .875rem; text-decoration: none; border: none; cursor: pointer; }
    .btn-ghost { border: 1px solid rgba(255,255,255,.18); color: rgba(255,255,255,.82); background: transparent; }
    .btn-ghost:hover { background: rgba(255,255,255,.07); color: #fff; }
    .btn-primary { background: linear-gradient(135deg,#fff4e7,var(--c-amber),#ffd095); color: #08111f; }
    .btn-primary:hover { opacity: .9; }
    .btn-green { background: rgba(34,197,94,.15); color: var(--c-green); border: 1px solid rgba(34,197,94,.3); }
    .btn-green:hover { background: rgba(34,197,94,.25); }

    /* ── HERO ── */
    .hero {
      max-width: 900px; margin: 0 auto;
      padding: 80px 32px 60px;
      text-align: center;
    }
    .hero-badge {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 8px 18px; border-radius: 999px;
      background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.25);
      font-size: .75rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase;
      color: var(--c-green); margin-bottom: 28px;
    }
    .hero h1 {
      font-family: 'Fraunces', serif;
      font-size: clamp(38px, 6vw, 72px);
      line-height: 1.04;
      color: #fff;
      margin-bottom: 20px;
    }
    .hero h1 span { color: var(--c-green); }
    .hero p {
      font-size: 1.05rem; color: var(--c-muted); line-height: 1.8; max-width: 620px; margin: 0 auto 36px;
    }
    .hero-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

    /* ── STATS BAR ── */
    .stats-bar {
      display: flex; justify-content: center; gap: 0;
      flex-wrap: wrap;
      border-top: 1px solid rgba(255,255,255,.07);
      border-bottom: 1px solid rgba(255,255,255,.07);
      background: rgba(255,255,255,.03);
      padding: 24px 40px;
      margin-bottom: 60px;
    }
    .stat-item {
      text-align: center; padding: 0 36px;
      border-right: 1px solid rgba(255,255,255,.08);
    }
    .stat-item:last-child { border-right: none; }
    .stat-val { font-family: 'Fraunces', serif; font-size: 32px; color: #fff; }
    .stat-lbl { font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--c-muted); margin-top: 4px; }

    /* ── CONTAINER ── */
    .container { max-width: 1100px; margin: 0 auto; padding: 0 32px; }

    /* ── SECTION ── */
    section { padding: 64px 0; }
    .section-tag {
      display: inline-block; font-size: .7rem; font-weight: 800;
      letter-spacing: .18em; text-transform: uppercase;
      color: var(--c-green); margin-bottom: 14px;
    }
    .section-title { font-family: 'Fraunces', serif; font-size: clamp(28px, 4vw, 44px); color: #fff; margin-bottom: 12px; }
    .section-sub { color: var(--c-muted); font-size: .95rem; line-height: 1.75; max-width: 580px; margin-bottom: 40px; }

    /* ── FEATURES GRID ── */
    .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
    .feat-card {
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.09);
      border-radius: 20px; padding: 28px 24px;
    }
    .feat-icon { font-size: 28px; color: var(--c-green); margin-bottom: 14px; }
    .feat-card h3 { font-size: 1rem; font-weight: 800; color: #fff; margin-bottom: 10px; }
    .feat-card p { font-size: .85rem; color: var(--c-muted); line-height: 1.7; }

    /* ── ACCESS TABLE ── */
    .access-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    .access-table th {
      text-align: left; padding: 12px 16px;
      background: rgba(255,255,255,.04);
      font-size: .7rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--c-muted);
    }
    .access-table td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.06); color: var(--c-muted); }
    .access-table tr:last-child td { border-bottom: none; }
    .access-table .col-feature { color: var(--c-text); font-weight: 600; }
    .check { color: var(--c-green); font-weight: 800; }
    .dash  { color: rgba(255,255,255,.25); }
    .table-wrap {
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 20px; overflow: hidden;
    }

    /* ── CTA ── */
    .cta-box {
      background: rgba(34,197,94,.06);
      border: 1px solid rgba(34,197,94,.2);
      border-radius: 28px; padding: 52px 40px;
      text-align: center; margin: 64px 0;
    }
    .cta-box h2 { font-family: 'Fraunces', serif; font-size: clamp(28px, 4vw, 44px); color: #fff; margin-bottom: 16px; }
    .cta-box p { color: var(--c-muted); line-height: 1.75; max-width: 500px; margin: 0 auto 32px; }

    /* ── FOOTER ── */
    footer {
      border-top: 1px solid rgba(255,255,255,.07);
      padding: 28px 40px; text-align: center;
      font-size: .8rem; color: rgba(255,255,255,.3);
    }
    footer a { color: var(--c-amber); text-decoration: none; }

    @media (max-width: 860px) {
      nav { padding: 14px 20px; }
      .features-grid { grid-template-columns: 1fr; }
      .stats-bar { gap: 20px; }
      .stat-item { border-right: none; padding: 10px 20px; }
    }
  </style>
</head>
<body>

<!-- NAV -->
<nav>
  <a class="nav-brand" href="<?= site_url('/') ?>">
    <img src="<?= base_url('images/gravport_logo_color.png') ?>" alt="GravPort">
    <strong>GravPort</strong>
  </a>
  <div class="nav-actions">
    <a class="btn btn-ghost" href="<?= site_url('landing') ?>">Kembali</a>
    <a class="btn btn-primary" href="<?= site_url('signup') ?>">Daftar Gratis</a>
  </div>
</nav>

<!-- HERO -->
<div class="hero">
  <div class="hero-badge"><i class="bi bi-shield-check"></i> Gratis Selamanya</div>
  <h1>Mulai eksplorasi data gravitasi <span>tanpa biaya.</span></h1>
  <p>
    Akun Free GravPort memberikan akses penuh ke data Level 1 (FAA & CBA), WebMap interaktif,
    dan unduhan hingga 500 file per bulan - tanpa kartu kredit.
  </p>
  <div class="hero-actions">
    <a class="btn btn-primary" href="<?= site_url('signup') ?>">Daftar Sekarang - Gratis</a>
    <a class="btn btn-ghost" href="<?= site_url('catalog') ?>">Lihat Katalog Data</a>
  </div>
</div>

<!-- STATS BAR -->
<div class="stats-bar">
  <div class="stat-item">
    <div class="stat-val">628K+</div>
    <div class="stat-lbl">Titik Data</div>
  </div>
  <div class="stat-item">
    <div class="stat-val">500</div>
    <div class="stat-lbl">Unduhan / Bulan</div>
  </div>
  <div class="stat-item">
    <div class="stat-val">2</div>
    <div class="stat-lbl">Tipe Dataset</div>
  </div>
  <div class="stat-item">
    <div class="stat-val">9</div>
    <div class="stat-lbl">Provinsi</div>
  </div>
</div>

<!-- FEATURES -->
<section>
  <div class="container">
    <div class="section-tag">Yang Anda Dapatkan</div>
    <h2 class="section-title">Fitur lengkap untuk riset dan eksplorasi</h2>
    <p class="section-sub">Akun gratis bukan akun terbatas - ini adalah akses penuh ke seluruh data publik geoportal.</p>
    <div class="features-grid">
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-map"></i></div>
        <h3>WebMap Interaktif</h3>
        <p>Visualisasi distribusi titik gravitasi secara spasial. Filter by provinsi, tipe anomali, dan mode observasi.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-download"></i></div>
        <h3>Download Data Level 1</h3>
        <p>Unduh FAA Level 1 dan CBA Level 1 dalam format Shapefile dan CSV. Hingga 500 file per bulan.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-search"></i></div>
        <h3>Katalog Publik</h3>
        <p>Telusuri metadata lengkap semua dataset - termasuk dataset Level 2 yang bisa Anda upgrade nanti.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-file-earmark-text"></i></div>
        <h3>Metadata ISO 19115</h3>
        <p>Setiap dataset dilengkapi metadata standar ISO 19115. Unduh sebagai XML atau lihat online.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-scissors"></i></div>
        <h3>Clip by Area</h3>
        <p>Potong data sesuai area studi Anda langsung dari WebMap - tanpa perlu mengunduh seluruh dataset.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-person-check"></i></div>
        <h3>Tanpa Kartu Kredit</h3>
        <p>Daftar hanya butuh email aktif. Tidak ada uji coba berbatas waktu - akun Free aktif selamanya.</p>
      </div>
    </div>
  </div>
</section>

<!-- ACCESS TABLE -->
<section>
  <div class="container">
    <div class="section-tag">Perbandingan Paket</div>
    <h2 class="section-title">Free vs Enterprise</h2>
    <p class="section-sub">Mulai dengan Free, upgrade ke Enterprise jika Anda butuh raster GeoTIFF dan akses API tanpa batas.</p>
    <div class="table-wrap">
      <table class="access-table">
        <thead>
          <tr>
            <th>Fitur</th>
            <th>Free</th>
            <th>Enterprise</th>
          </tr>
        </thead>
        <tbody>
          <tr><td class="col-feature">WebMap Interaktif</td><td class="check">✓</td><td class="check">✓</td></tr>
          <tr><td class="col-feature">Katalog Dataset Publik</td><td class="check">✓</td><td class="check">✓</td></tr>
          <tr><td class="col-feature">Data Level 1 (FAA & CBA vector)</td><td class="check">✓</td><td class="check">✓</td></tr>
          <tr><td class="col-feature">Metadata ISO 19115</td><td class="check">✓</td><td class="check">✓</td></tr>
          <tr><td class="col-feature">Clip by area (WebMap)</td><td class="check">✓</td><td class="check">✓</td></tr>
          <tr><td class="col-feature">Kuota unduhan</td><td>500 file/bulan</td><td>Tidak terbatas</td></tr>
          <tr><td class="col-feature">Data Level 2 (Raster GeoTIFF)</td><td class="dash">-</td><td class="check">✓</td></tr>
          <tr><td class="col-feature">REST API Akses</td><td class="dash">-</td><td class="check">✓</td></tr>
          <tr><td class="col-feature">Priority Support & SLA 99.9%</td><td class="dash">-</td><td class="check">✓</td></tr>
          <tr><td class="col-feature">Harga</td><td class="check">Rp 0 / bulan</td><td>Rp 10jt / bulan</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- CTA -->
<div class="container">
  <div class="cta-box">
    <h2>Siap mulai sekarang?</h2>
    <p>Buat akun gratis dalam 60 detik. Tidak perlu kartu kredit - langsung akses 628K+ titik data gravitasi.</p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
      <a class="btn btn-primary" href="<?= site_url('signup') ?>">Daftar Gratis Sekarang</a>
      <a class="btn btn-ghost" href="<?= site_url('landing/enterprise') ?>">Bandingkan dengan Enterprise &rarr;</a>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer>
  <span>&copy; <?= date('Y') ?> GravPort &middot; Geodesi ITB &middot;
    <a href="<?= site_url('/') ?>">Kembali ke Beranda</a> &middot;
    <a href="<?= site_url('landing/enterprise') ?>">Paket Enterprise</a>
  </span>
</footer>

</body>
</html>

