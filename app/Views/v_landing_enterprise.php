<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Enterprise</title>
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
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Manrope', sans-serif;
      color: var(--c-text);
      background:
        radial-gradient(circle at top left, rgba(167,96,37,.28), transparent 30%),
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

    /* ── HERO ── */
    .hero {
      max-width: 900px; margin: 0 auto;
      padding: 80px 32px 60px;
      text-align: center;
    }
    .hero-badge {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 8px 18px; border-radius: 999px;
      background: rgba(167,96,37,.15); border: 1px solid rgba(167,96,37,.3);
      font-size: .75rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase;
      color: var(--c-amber); margin-bottom: 28px;
    }
    .hero h1 {
      font-family: 'Fraunces', serif;
      font-size: clamp(38px, 6vw, 72px);
      line-height: 1.04; color: #fff; margin-bottom: 20px;
    }
    .hero h1 span { color: var(--c-amber); }
    .hero p { font-size: 1.05rem; color: var(--c-muted); line-height: 1.8; max-width: 620px; margin: 0 auto 36px; }
    .hero-price {
      display: inline-block;
      font-family: 'Fraunces', serif; font-size: 48px; color: #fff;
      margin-bottom: 8px;
    }
    .hero-price span { font-size: 18px; font-weight: 600; color: var(--c-muted); }
    .hero-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; margin-top: 28px; }

    /* ── STATS BAR ── */
    .stats-bar {
      display: flex; justify-content: center;
      flex-wrap: wrap;
      border-top: 1px solid rgba(255,255,255,.07);
      border-bottom: 1px solid rgba(255,255,255,.07);
      background: rgba(255,255,255,.03);
      padding: 24px 40px;
      margin-bottom: 60px;
    }
    .stat-item { text-align: center; padding: 0 36px; border-right: 1px solid rgba(255,255,255,.08); }
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
      color: var(--c-amber); margin-bottom: 14px;
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
    .feat-card--highlight {
      background: rgba(167,96,37,.08);
      border-color: rgba(167,96,37,.25);
    }
    .feat-icon { font-size: 28px; color: var(--c-amber); margin-bottom: 14px; }
    .feat-card h3 { font-size: 1rem; font-weight: 800; color: #fff; margin-bottom: 10px; }
    .feat-card p { font-size: .85rem; color: var(--c-muted); line-height: 1.7; }
    .feat-badge {
      display: inline-block; font-size: .65rem; font-weight: 800;
      letter-spacing: .12em; text-transform: uppercase;
      padding: 3px 8px; border-radius: 999px;
      background: rgba(167,96,37,.2); color: var(--c-amber);
      margin-bottom: 12px;
    }

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
    .check { color: var(--c-amber); font-weight: 800; }
    .dash  { color: rgba(255,255,255,.25); }
    .table-wrap {
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 20px; overflow: hidden;
    }

    /* ── USE CASES ── */
    .use-cases { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
    .use-card {
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 20px; padding: 28px 24px;
      display: flex; gap: 18px; align-items: flex-start;
    }
    .use-icon {
      width: 48px; height: 48px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      border-radius: 14px;
      background: rgba(167,96,37,.12);
      font-size: 22px; color: var(--c-amber);
    }
    .use-card h3 { font-size: .95rem; font-weight: 800; color: #fff; margin-bottom: 8px; }
    .use-card p  { font-size: .84rem; color: var(--c-muted); line-height: 1.65; }

    /* ── PRICING CARD ── */
    .pricing-shell { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px; align-items: start; }
    .pricing-card {
      background: rgba(167,96,37,.08);
      border: 1px solid rgba(167,96,37,.3);
      border-radius: 24px; padding: 36px 32px;
    }
    .pricing-card h3 { font-family: 'Fraunces', serif; font-size: 30px; color: #fff; margin-bottom: 8px; }
    .price-big { font-family: 'Fraunces', serif; font-size: 52px; color: #fff; line-height: 1; margin-bottom: 6px; }
    .price-big span { font-size: 18px; font-weight: 600; color: var(--c-muted); }
    .price-note { font-size: .82rem; color: var(--c-muted); margin-bottom: 28px; }
    .feature-list { list-style: none; }
    .feature-list li {
      font-size: .875rem; color: var(--c-muted);
      padding: 9px 0; border-bottom: 1px solid rgba(255,255,255,.06);
      display: flex; align-items: center; gap: 10px;
    }
    .feature-list li:last-child { border-bottom: none; }
    .feature-list li::before { content: '✓'; color: var(--c-amber); font-weight: 800; flex-shrink: 0; }
    .pricing-note {
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 20px; padding: 28px 24px;
    }
    .pricing-note h4 { font-size: .95rem; font-weight: 800; color: #fff; margin-bottom: 14px; }
    .pricing-note p  { font-size: .85rem; color: var(--c-muted); line-height: 1.7; margin-bottom: 12px; }
    .pricing-note a  { color: var(--c-amber); text-decoration: none; font-weight: 700; }

    /* ── CTA ── */
    .cta-box {
      background: rgba(167,96,37,.07);
      border: 1px solid rgba(167,96,37,.22);
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
      .features-grid, .use-cases, .pricing-shell { grid-template-columns: 1fr; }
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
    <a class="btn btn-primary" href="mailto:admin@geoportal.id">Hubungi Kami</a>
  </div>
</nav>

<?php if (session()->getFlashdata('error')): ?>
<div style="
  position:fixed; top:90px; left:50%; transform:translateX(-50%);
  z-index:9999; max-width:560px; width:calc(100% - 40px);
  background:#1a0a0a; border:1px solid rgba(239,68,68,0.4);
  border-radius:14px; padding:14px 20px;
  color:#fca5a5; font-size:13px; font-weight:500;
  box-shadow:0 8px 32px rgba(0,0,0,0.5);
  display:flex; align-items:flex-start; gap:10px;">
  <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444;flex-shrink:0;margin-top:2px;"></i>
  <span><?= esc(session()->getFlashdata('error')) ?></span>
</div>
<?php endif; ?>

<!-- HERO -->
<div class="hero">
  <div class="hero-badge"><i class="bi bi-building"></i> Enterprise Plan</div>
  <h1>Data gravitasi tanpa batas untuk <span>profesional.</span></h1>
  <p>
    Unduhan tidak terbatas, akses raster GeoTIFF Level 2, REST API, dan SLA 99.9%
    - untuk perusahaan eksplorasi, konsultan geoteknik, dan institusi riset.
  </p>
  <div>
    <div class="hero-price">Rp 10jt <span>/ bulan</span></div>
  </div>
  <div class="hero-actions">
    <a class="btn btn-primary" href="mailto:admin@geoportal.id">Hubungi Kami</a>
    <a class="btn btn-ghost" href="<?= site_url('landing/free') ?>">Bandingkan dengan Free &rarr;</a>
  </div>
</div>

<!-- STATS BAR -->
<div class="stats-bar">
  <div class="stat-item">
    <div class="stat-val">628K+</div>
    <div class="stat-lbl">Titik Data</div>
  </div>
  <div class="stat-item">
    <div class="stat-val">∞</div>
    <div class="stat-lbl">Unduhan / Bulan</div>
  </div>
  <div class="stat-item">
    <div class="stat-val">4</div>
    <div class="stat-lbl">Tipe Dataset</div>
  </div>
  <div class="stat-item">
    <div class="stat-val">99.9%</div>
    <div class="stat-lbl">Uptime SLA</div>
  </div>
</div>

<!-- FEATURES -->
<section>
  <div class="container">
    <div class="section-tag">Eksklusif Enterprise</div>
    <h2 class="section-title">Fitur yang tidak ada di paket Free</h2>
    <p class="section-sub">Enterprise memberikan akses penuh ke seluruh data dan infrastruktur GravPort, termasuk dataset Level 2 dan API.</p>
    <div class="features-grid">
      <div class="feat-card feat-card--highlight">
        <div class="feat-badge">Eksklusif</div>
        <div class="feat-icon"><i class="bi bi-layers"></i></div>
        <h3>Data Level 2 - Raster GeoTIFF</h3>
        <p>Akses grid FAA dan CBA Level 2 dalam format GeoTIFF resolusi tinggi. Siap diintegrasi ke ArcGIS, QGIS, dan pipeline analisis Anda.</p>
      </div>
      <div class="feat-card feat-card--highlight">
        <div class="feat-badge">Eksklusif</div>
        <div class="feat-icon"><i class="bi bi-code-slash"></i></div>
        <h3>REST API Access</h3>
        <p>Query data langsung via REST API dengan autentikasi token. Cocok untuk integrasi pipeline data, dashboard internal, atau aplikasi geospasial custom.</p>
      </div>
      <div class="feat-card feat-card--highlight">
        <div class="feat-badge">Eksklusif</div>
        <div class="feat-icon"><i class="bi bi-infinity"></i></div>
        <h3>Unduhan Tidak Terbatas</h3>
        <p>Tidak ada kuota bulanan. Unduh seluruh dataset, clip area berapapun ukurannya, kapan pun Anda butuhkan.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-shield-check"></i></div>
        <h3>SLA 99.9% Uptime</h3>
        <p>Jaminan ketersediaan layanan dengan monitoring 24/7. Prioritas pemulihan insiden dan laporan bulanan.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-headset"></i></div>
        <h3>Priority Support</h3>
        <p>Tim GravPort siap membantu kebutuhan teknis dan non-teknis Anda. Respons dalam 1 hari kerja.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-bar-chart-line"></i></div>
        <h3>Semua Fitur Free</h3>
        <p>Termasuk WebMap interaktif, katalog publik, metadata ISO 19115, clip area, dan data Level 1 (FAA & CBA vector).</p>
      </div>
    </div>
  </div>
</section>

<!-- USE CASES -->
<section>
  <div class="container">
    <div class="section-tag">Cocok Untuk</div>
    <h2 class="section-title">Siapa yang menggunakan Enterprise?</h2>
    <p class="section-sub">Enterprise dirancang untuk tim dan organisasi yang butuh data gravitasi secara rutin dan dalam volume besar.</p>
    <div class="use-cases">
      <div class="use-card">
        <div class="use-icon"><i class="bi bi-geo"></i></div>
        <div>
          <h3>Perusahaan Eksplorasi Mineral</h3>
          <p>Interpretasi struktur bawah permukaan menggunakan anomali gravitasi untuk studi kelayakan dan target pengeboran.</p>
        </div>
      </div>
      <div class="use-card">
        <div class="use-icon"><i class="bi bi-building-gear"></i></div>
        <div>
          <h3>Konsultan Geoteknik</h3>
          <p>Data gravitasi untuk analisis stabilitas lereng, identifikasi zona lemah, dan pemetaan variasi densitas batuan.</p>
        </div>
      </div>
      <div class="use-card">
        <div class="use-icon"><i class="bi bi-mortarboard"></i></div>
        <div>
          <h3>Institusi Riset & Universitas</h3>
          <p>Proyek penelitian skala besar yang butuh akses seluruh dataset termasuk raster GeoTIFF untuk analisis gridding dan pemodelan.</p>
        </div>
      </div>
      <div class="use-card">
        <div class="use-icon"><i class="bi bi-pc-display"></i></div>
        <div>
          <h3>Developer & Data Platform</h3>
          <p>Integrasi data gravitasi ke dalam aplikasi, dashboard, atau pipeline geospasial via REST API.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PRICING -->
<section>
  <div class="container">
    <div class="section-tag">Harga</div>
    <h2 class="section-title">Investasi yang sepadan</h2>
    <p class="section-sub">Satu paket, akses penuh. Tidak ada biaya tersembunyi.</p>
    <div class="pricing-shell">
      <div class="pricing-card">
        <h3>Enterprise</h3>
        <div class="price-big">Rp 10jt <span>/ bulan</span></div>
        <p class="price-note">Ditagih bulanan. Hubungi kami untuk harga tahunan dengan diskon.</p>
        <ul class="feature-list">
          <li>Semua fitur Free</li>
          <li>Data Level 2 Raster GeoTIFF</li>
          <li>Unduhan tidak terbatas</li>
          <li>REST API dengan autentikasi token</li>
          <li>SLA 99.9% uptime</li>
          <li>Priority support (respons &lt; 1 hari kerja)</li>
        </ul>
        <a class="btn btn-primary" href="mailto:admin@geoportal.id" style="width:100%;justify-content:center;margin-top:28px">Hubungi Kami untuk Mulai</a>
      </div>
      <div class="pricing-note">
        <h4>Cara Berlangganan</h4>
        <p>Kirim email ke <a href="mailto:admin@geoportal.id">admin@geoportal.id</a> dengan subjek "Enterprise Inquiry" beserta nama organisasi dan kebutuhan Anda.</p>
        <p>Tim GravPort akan menghubungi Anda dalam 1 hari kerja untuk diskusi kebutuhan dan onboarding.</p>
        <h4 style="margin-top:20px">Butuh lebih besar?</h4>
        <p>Untuk instansi pemerintah (BIG, ESDM, BRIN, BMKG), tersedia paket <strong>Government</strong> dengan kontrak MoU tahunan. <a href="mailto:admin@geoportal.id">Hubungi kami</a>.</p>
      </div>
    </div>
  </div>
</section>

<!-- COMPARISON TABLE -->
<section>
  <div class="container">
    <div class="section-tag">Perbandingan Lengkap</div>
    <h2 class="section-title">Free vs Enterprise</h2>
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
          <tr><td class="col-feature">Harga</td><td>Rp 0</td><td>Rp 10jt / bulan</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- CTA -->
<div class="container">
  <div class="cta-box">
    <h2>Siap upgrade ke Enterprise?</h2>
    <p>Hubungi tim GravPort untuk diskusi kebutuhan dan onboarding. Respons dalam 1 hari kerja.</p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
      <a class="btn btn-primary" href="mailto:admin@geoportal.id">Kirim Email ke Tim GravPort</a>
      <a class="btn btn-ghost" href="<?= site_url('landing/free') ?>">Mulai dengan Free Dulu &rarr;</a>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer>
  <span>&copy; <?= date('Y') ?> GravPort &middot; Geodesi ITB &middot;
    <a href="<?= site_url('/') ?>">Kembali ke Beranda</a> &middot;
    <a href="<?= site_url('landing/free') ?>">Paket Free</a>
  </span>
</footer>

</body>
</html>

