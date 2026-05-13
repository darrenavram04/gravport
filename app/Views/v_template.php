<!DOCTYPE html>
<?php
$versionedAsset = static function (string $relativePath): string {
    $absolutePath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : date('YmdHis');

    return base_url($relativePath) . '?v=' . rawurlencode($version);
};

$teamImageUrls = [
    1 => $versionedAsset('assets/img/team/team-1.jpg'),
    2 => $versionedAsset('assets/img/team/team-2.jpg'),
    3 => $versionedAsset('assets/img/team/team-3.jpg'),
    4 => $versionedAsset('assets/img/team/team-4.jpg'),
];
$geoidSourceUrl = 'https://icgem.gfz-potsdam.de/tom_longtime';
$geoidReferenceUrl = 'https://earth-info.nga.mil/index.php?dir=wgs84&action=wgs84';
?>
<html class="wide wow-animation" lang="en">
  <head>
    <title>GravPort | Geoportal Jawa-Bali</title>
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <link rel="icon" href="<?= base_url('images/itb.png'); ?>" type="image/x-icon">
    <meta name="theme-color" content="#a76025">
    <!-- Stylesheets-->
    <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Poppins:400,500,600%7CTeko:300,400,500%7CMaven+Pro:500">
    <link rel="stylesheet" href="<?= base_url('site/')?>css/bootstrap.css">
    <link rel="stylesheet" href="<?= base_url('site/')?>css/fonts.css">
    <link rel="stylesheet" href="<?= esc($versionedAsset('site/css/style.css')); ?>">
    <script type="module" src="<?= esc($versionedAsset('site/js/model-viewer.min.js')); ?>"></script>
    <!-- Vendor CSS Files -->

    <link href="<?= base_url('assets/')?>vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= base_url('assets/')?>vendor/aos/aos.css" rel="stylesheet">
    <link href="<?= base_url('assets/')?>vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/')?>vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <style>.ie-panel{display: none;background: #212121;padding: 10px 0;box-shadow: 3px 3px 5px 0 rgba(0,0,0,.3);clear: both;text-align:center;position: relative;z-index: 1;} html.ie-10 .ie-panel, html.lt-ie-10 .ie-panel {display: block;}</style>
    <style>
    .nav-pills .nav-link.active {
      background-color: #a76025 !important;
      color: #fff !important;
    }

    .nav-auth{
      display:flex;
      align-items:center;
      gap:12px;
    }
    .nav-login{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-height:42px;
      color:#fff;
      text-decoration:none;
      font-weight:700;
      padding:0 16px;
      border-radius:999px;
      border:1px solid rgba(255,255,255,0.25);
      background: rgba(0,0,0,0.12);
      line-height:1;
    }
    .nav-logout{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      color:rgba(255,255,255,0.78);
      text-decoration:none;
      font-weight:700;
      padding:0 6px;
      border-radius:999px;
      border:none;
      background:transparent;
      line-height:1;
    }
    .nav-login:hover{ background: rgba(0,0,0,0.22); }
    .nav-logout:hover{ color:#fff; }
    .nav-login--primary{
      background: rgba(255,255,255,0.95);
      color:#69350f;
      border-color: rgba(255,255,255,0.95);
    }
    .nav-login--secondary{
      background: rgba(7,19,36,0.28);
    }
    .nav-logout--secondary{
      background: transparent;
      border: none;
    }
    .nav-role{
      color: rgba(255,255,255,0.9);
      font-weight:800;
      letter-spacing:.06em;
      font-size:12px;
    }

    /* ===== GravPort Footer ===== */
    .gravport-footer {
      background: #050c18;
      font-family: "Poppins", sans-serif;
      color: rgba(255,255,255,0.72);
    }
    .gravport-footer__separator {
      height: 2px;
      background: linear-gradient(90deg, #a76025 0%, #ffbf74 40%, #61d4ff 100%);
      opacity: 0.72;
    }
    .gravport-footer__body {
      display: grid;
      grid-template-columns: 1.5fr 1fr 1fr 1.2fr;
      gap: 40px;
      max-width: 1200px;
      margin: 0 auto;
      padding: 52px 28px 40px;
    }
    @media (max-width: 900px) {
      .gravport-footer__body { grid-template-columns: 1fr 1fr; gap: 28px; }
    }
    @media (max-width: 560px) {
      .gravport-footer__body { grid-template-columns: 1fr; }
    }
    .gravport-footer__logo {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }
    .gravport-footer__logo img {
      width: 36px;
      height: 36px;
      object-fit: contain;
    }
    .gravport-footer__logo strong {
      font-size: 17px;
      font-weight: 800;
      color: #fff;
      letter-spacing: 0.04em;
    }
    .gravport-footer__pitch {
      font-size: 13px;
      line-height: 1.75;
      color: rgba(255,255,255,0.52);
      margin: 0;
      max-width: 280px;
    }
    .gravport-footer__heading {
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: rgba(255,255,255,0.38);
      margin: 0 0 16px;
    }
    .gravport-footer__links {
      list-style: none;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .gravport-footer__links a {
      font-size: 14px;
      color: rgba(255,255,255,0.65);
      text-decoration: none;
      transition: color 0.16s;
    }
    .gravport-footer__links a:hover {
      color: #ffbf74;
    }
    .gravport-footer__inst-text {
      font-size: 13px;
      line-height: 1.7;
      color: rgba(255,255,255,0.48);
      margin: 0 0 18px;
    }
    .gravport-footer__social {
      display: flex;
      gap: 12px;
    }
    .gravport-footer__social a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(255,255,255,0.05);
      color: rgba(255,255,255,0.65);
      font-size: 15px;
      text-decoration: none;
      transition: background 0.18s, color 0.18s, border-color 0.18s;
    }
    .gravport-footer__social a:hover {
      background: rgba(167,96,37,0.22);
      color: #ffbf74;
      border-color: rgba(167,96,37,0.4);
    }
    .gravport-footer__bottom {
      border-top: 1px solid rgba(255,255,255,0.06);
      text-align: center;
      padding: 18px 28px;
      font-size: 12px;
      color: rgba(255,255,255,0.32);
      letter-spacing: 0.04em;
    }

    /* ===== Cinematic Loader ===== */
    body.gp-loading { overflow: hidden; }
    .gp-loader {
      position: fixed;
      inset: 0;
      z-index: 99999;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: #04101d;
      transform: translateY(0);
      transition: transform 0.9s cubic-bezier(0.76, 0, 0.24, 1);
    }
    .gp-loader.is-done { transform: translateY(-100%); }
    .gp-loader__brand {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 36px;
      opacity: 0;
      transform: translateY(18px);
      transition: opacity 0.55s ease, transform 0.55s ease;
    }
    .gp-loader__brand img {
      width: 52px; height: 52px; object-fit: contain;
      filter: drop-shadow(0 0 16px rgba(167,96,37,0.5));
    }
    .gp-loader__brand strong {
      font-family: "Poppins", sans-serif;
      font-size: 30px; font-weight: 800;
      color: #fff; letter-spacing: 0.04em;
    }
    .gp-loader.is-visible .gp-loader__brand {
      opacity: 1; transform: translateY(0);
    }
    .gp-loader__bar {
      width: 220px; height: 2px;
      background: rgba(255,255,255,0.1);
      border-radius: 999px; overflow: hidden;
      opacity: 0;
      transition: opacity 0.4s 0.2s;
    }
    .gp-loader.is-visible .gp-loader__bar { opacity: 1; }
    .gp-loader__fill {
      height: 100%; width: 0%;
      background: linear-gradient(90deg, #a76025, #ffbf74 50%, #61d4ff);
      border-radius: 999px;
      transition: width 1.1s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
    }
    .gp-loader.is-loading .gp-loader__fill { width: 100%; }
    .gp-loader__sub {
      margin-top: 18px;
      font-size: 10px; font-weight: 800;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: rgba(255,255,255,0.3);
      opacity: 0;
      transition: opacity 0.5s 0.35s;
    }
    .gp-loader.is-visible .gp-loader__sub { opacity: 1; }

    /* ===== Marquee ticker ===== */
    .gp-marquee {
      position: relative; z-index: 3;
      overflow: hidden; white-space: nowrap;
      border-top: 1px solid rgba(255,255,255,0.07);
      border-bottom: 1px solid rgba(255,255,255,0.07);
      background: rgba(4,8,20,0.95);
      padding: 13px 0;
      cursor: default;
    }
    .gp-marquee__track {
      display: inline-block;
      animation: gp-marquee 34s linear infinite;
      will-change: transform;
    }
    .gp-marquee:hover .gp-marquee__track { animation-play-state: paused; }
    .gp-marquee__item {
      display: inline-block;
      font-size: 10px; font-weight: 800;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: rgba(255,255,255,0.48);
      padding: 0 22px;
      transition: color 0.2s;
    }
    .gp-marquee__item:hover { color: #ffbf74; }
    .gp-marquee__sep {
      display: inline-block;
      color: #a76025; font-size: 8px;
      vertical-align: middle;
    }
    @keyframes gp-marquee {
      from { transform: translateX(0); }
      to   { transform: translateX(-50%); }
    }

    /* ===== Magnetic button ===== */
    .btn-magnetic {
      transition: transform 0.35s cubic-bezier(0.23,1,0.32,1) !important;
    }

    /* ===== Scroll progress bar ===== */
    .gp-scroll-progress {
      position: fixed;
      top: 0;
      left: 0;
      width: 0%;
      height: 3px;
      background: linear-gradient(90deg, #a76025 0%, #ffbf74 50%, #61d4ff 100%);
      z-index: 9999;
      pointer-events: none;
      transition: width 0.08s linear;
    }

    /* ===== Word-reveal animation ===== */
    .reveal-heading { overflow: visible; }
    .reveal-word {
      display: inline-block;
      overflow: hidden;
      vertical-align: bottom;
      line-height: inherit;
    }
    .reveal-word__inner {
      display: inline-block;
      transform: translateY(108%);
      opacity: 0;
      transition: transform 0.72s cubic-bezier(0.16,1,0.3,1), opacity 0.48s ease;
    }
    .reveal-heading.is-revealed .reveal-word__inner {
      transform: translateY(0);
      opacity: 1;
    }

    /* ===== Ambient particle canvas ===== */
    #heroParticles {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 1;
      opacity: 0.55;
    }

    /* ===== Contact section dark override ===== */
    .section-shell--contact {
      background: #070b14 !important;
      color: rgba(255,255,255,0.88) !important;
    }
    .section-shell--contact .title-classic-title h3,
    .section-shell--contact .title-classic-text p,
    .section-shell--contact .contact-heading {
      color: rgba(255,255,255,0.88) !important;
    }

    /* ===== Contact form visual upgrade ===== */
    .section-shell--contact .form-input {
      border: 1px solid rgba(255,255,255,0.14) !important;
      border-radius: 14px !important;
      background: rgba(255,255,255,0.05) !important;
      color: #fff !important;
      padding: 14px 16px !important;
      font-size: 14px !important;
      transition: border-color 0.18s, box-shadow 0.18s !important;
      box-shadow: none !important;
    }
    .section-shell--contact .form-input:focus {
      outline: none !important;
      border-color: rgba(97,212,255,0.4) !important;
      background: rgba(255,255,255,0.07) !important;
      box-shadow: 0 0 0 4px rgba(97,212,255,0.1) !important;
    }
    .section-shell--contact .form-input::placeholder {
      color: rgba(255,255,255,0.32) !important;
    }
    .section-shell--contact .form-label {
      position: static !important;
      transform: none !important;
      display: block !important;
      margin-bottom: 8px !important;
      font-size: 12px !important;
      font-weight: 700 !important;
      letter-spacing: 0.08em !important;
      text-transform: uppercase !important;
      color: rgba(255,255,255,0.6) !important;
      pointer-events: none !important;
      top: auto !important;
      left: auto !important;
    }
    .section-shell--contact .form-wrap {
      margin-bottom: 20px !important;
    }
    .section-shell--contact textarea.form-input {
      min-height: 120px !important;
      resize: vertical !important;
    }

  </style>
  </head>
  <body class="gravport-landing gp-loading">
    <div class="gp-loader" id="gpLoader" role="status" aria-label="Loading GravPort">
      <div class="gp-loader__brand">
        <img src="<?= base_url('images/itb.png'); ?>" alt="GravPort logo">
        <strong>GravPort</strong>
      </div>
      <div class="gp-loader__bar">
        <div class="gp-loader__fill" id="gpLoaderFill"></div>
      </div>
      <p class="gp-loader__sub">Jawa-Bali Geoportal</p>
    </div>
    <!-- ===== Global Story Background (shared video) ===== -->
    <div class="story-bg" aria-hidden="true">
      <video class="story-bg__video" autoplay muted loop playsinline>
        <source src="<?= base_url('videos/bg-1.mp4'); ?>" type="video/mp4">
      </video>
      <div class="story-bg__veil"></div>
    </div>
    <!-- ===== /Global Story Background ===== -->

    <?= view('partials/site_header', [
      'activePage' => 'home',
      'shareButtonId' => 'siteShareBtn',
    ]) ?>

    <div class="page">
      <div id="home" class="home-scope">

        <!-- HERO -->
        <section class="hero story-scene" id="sceneHome">
          <canvas id="heroParticles" aria-hidden="true"></canvas>
          <div class="scene-sticky">
            <div class="hero-overlay"></div>
            <!-- NOTE: reveal blur layer is handled by CSS on .hero-media::after (mask spotlight) -->
          </div>
          <div class="hero-spot-glow"></div>
          <div class="hero-spot-veil"></div>
          <!-- BLUR TEXT LAYER (default, outside spotlight) -->
          <div class="hero-inner hero-inner--blur" aria-hidden="true">
            <div class="hero-content">
              <div class="hero-eyebrow">JAWA-BALI GEOPORTAL</div>

              <h1 class="hero-heading">
                <span class="hero-static">We Are</span>
                <span class="hero-dynamic" id="heroDynamic_blur">Geospatialists.</span>
              </h1>

              <p class="hero-subtitle">
                Geoportal FAA dan CBA level 1-2 untuk preview, filter area, dan unduh cepat.
              </p>

            </div>
          </div>

          <!-- SHARP TEXT LAYER (revealed only by spotlight) -->
          <div class="hero-inner hero-inner--sharp">
            <div class="hero-content">
              <div class="hero-eyebrow">JAWA-BALI GEOPORTAL</div>

              <h1 class="hero-heading">
                <span class="hero-static">We Are</span>
                <span class="hero-dynamic" id="heroDynamic">Geospatialists.</span>
              </h1>

              <p class="hero-subtitle">
                Geoportal FAA dan CBA level 1-2 untuk preview, filter area, dan unduh cepat.
              </p>

            </div>
          </div>
        </section>

        <!-- Home-only custom cursor + trailing blobs -->
        <div class="home-cursor" id="homeCursor" aria-hidden="true"></div>
        <div class="home-cursor-shapes" id="homeShapes" aria-hidden="true">
          <div class="home-shape home-shape-1"></div>
          <div class="home-shape home-shape-2"></div>
          <div class="home-shape home-shape-3"></div>
        </div>

      </div><!-- /#home -->
    </div><!-- /.page -->

    <div class="gp-marquee" aria-hidden="true">
      <div class="gp-marquee__track">
        <span class="gp-marquee__item">FAA Level 1</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">CBA Level 1</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">FAA Level 2</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">CBA Level 2</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">Jawa-Bali Geoportal</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">Geodesi ITB</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">Anomali Gayaberat</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">Free Air Anomaly</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">Complete Bouguer Anomaly</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">GravPort</span><span class="gp-marquee__sep">✦</span>
        <!-- duplicate for seamless loop -->
        <span class="gp-marquee__item">FAA Level 1</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">CBA Level 1</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">FAA Level 2</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">CBA Level 2</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">Jawa-Bali Geoportal</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">Geodesi ITB</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">Anomali Gayaberat</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">Free Air Anomaly</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">Complete Bouguer Anomaly</span><span class="gp-marquee__sep">✦</span>
        <span class="gp-marquee__item">GravPort</span><span class="gp-marquee__sep">✦</span>
      </div>
    </div>

      <!-- ================== DATASET HERO ================== -->
      <section id="dataset" class="dataset-hero story-scene section-shell section-shell--dataset">
        <div class="scene-sticky">

          <!-- Overlay content -->
          <div class="dataset-hero__overlay dataset-constellation" id="datasetOrbit">
            <div class="dataset-constellation__intro">
              <div class="dataset-constellation__copy">
                <span class="dataset-constellation__kicker"></span>
                <h2 class="dataset-hero__title">Data Catalog</h2>
                <p class="dataset-hero__subtitle">
                  FAA dan CBA level 1-2 siap dipreview dan diunduh.
                </p>
              </div>
              <a href="<?= site_url('catalog'); ?>" class="dataset-hero__catalog-link dataset-constellation__catalog-link btn-magnetic">
                Open Catalog
              </a>
            </div>

            <div class="dataset-constellation__stage" aria-label="Dataset constellation showcase">
              <div class="dataset-constellation__visual">
                <div class="dataset-constellation__visual-shell">
                  <div class="dataset-constellation__aura dataset-constellation__aura--warm" aria-hidden="true"></div>
                  <div class="dataset-constellation__aura dataset-constellation__aura--cool" aria-hidden="true"></div>
                  <div class="dataset-constellation__grid" aria-hidden="true"></div>
                  <div class="dataset-constellation__axis dataset-constellation__axis--x" aria-hidden="true"></div>
                  <div class="dataset-constellation__axis dataset-constellation__axis--y" aria-hidden="true"></div>
                  <div class="dataset-constellation__masthead" aria-hidden="true">DATASET EDITION</div>

                  <article class="dataset-constellation__feature-card" id="dsFeatureCard">
                    <div class="dataset-constellation__feature-halo" aria-hidden="true"></div>
                    <div class="dataset-constellation__feature-media">
                      <img id="dsHeroImage" src="<?= base_url('images/grav-1.png'); ?>" alt="Dataset preview">
                    </div>
                    <div class="dataset-constellation__feature-body">
                      <div class="dataset-constellation__feature-meta">
                        <span class="dataset-constellation__feature-tag" id="dsTag">FAA L1</span>
                        <span class="dataset-constellation__feature-mode" id="dsFeatureMode">SCATTER</span>
                      </div>
                      <div class="dataset-constellation__feature-overline" id="dsMeta">Level 1 | FAA National | Scatter</div>
                      <h4 id="dsFeatureTitle">Scatter FAA Level 1 - Indonesia</h4>
                      <p id="dsFeatureSubtitle">Free Air Anomaly</p>
                      <p class="dataset-constellation__feature-desc" id="dsDesc">
                        Klik Open Catalog untuk akses lebih lanjut.
                      </p>
                      <div class="panel-specs dataset-constellation__feature-specs" id="dsSpecs">
                        <span class="spec-chip">Coverage: Indonesia</span>
                        <span class="spec-chip">Format: Shapefile & CSV</span>
                        <span class="spec-chip">Catalog: FAA Level 1</span>
                      </div>
                      <div class="panel-actions dataset-constellation__feature-actions">
                        <a class="btn-pill btn-pill--primary" id="dsView" href="#" role="button">Open Catalog</a>
                        <a class="btn-pill btn-pill--ghost" id="dsDownload" href="#" role="button">Preview</a>
                      </div>
                      <div class="panel-progress dataset-constellation__feature-progress">
                        <span class="panel-index" id="dsIndex">01 / 04</span>
                        <div class="panel-dots dataset-constellation__steps" id="dsDots" aria-hidden="true"></div>
                      </div>
                    </div>
                  </article>

                  <div class="dataset-constellation__nodes" id="orbitItems" role="list">
                    <!-- nodes injected by JS -->
                  </div>
                </div>
              </div>
            </div>

          </div> <!-- /.dataset-hero__overlay -->
      </section>
      <!-- ================== END DATASET HERO ================== -->


      <!-- About Section -->
      <section id="About" class="about section section-shell section-shell--light">
        <div class="container about-shell" data-aos="fade-up">
            <div class="section-heading section-heading--split section-heading--about">
              <div class="about-heading-card">
                <span class="section-kicker">Gravity Geoportal Indonesia</span>
                <h2 class="section-title">Data anomali gayaberat nasional dalam satu platform</h2>
              </div>
              <div class="about-heading-side">
                <p class="section-lead">
                  FAA dan CBA level 1-2 untuk preview, analisis, dan unduh data.
                </p>
                <div class="about-metrics" aria-label="Geoportal highlights">
                  <article class="about-metric">
                    <span class="about-metric__label">Coverage</span>
                    <strong><span class="js-counter" data-target="9" data-suffix=" Provinsi">0 Provinsi</span></strong>
                    <p>Jawa dan Bali — data gayaberat dari 9 provinsi dalam satu platform.</p>
                  </article>
                  <article class="about-metric">
                    <span class="about-metric__label">Dataset</span>
                    <strong><span class="js-counter" data-target="4" data-suffix=" Tipe Data">0 Tipe Data</span></strong>
                    <p>FAA L1, CBA L1, FAA L2, CBA L2 — scatter dan grid siap diunduh.</p>
                  </article>
                  <article class="about-metric">
                    <span class="about-metric__label">Platform</span>
                    <strong><span class="js-counter" data-target="3" data-suffix=" Fitur Utama">0 Fitur</span></strong>
                    <p>Catalog, WebMap, dan Metadata — satu alur dari preview hingga unduh.</p>
                  </article>
                </div>
              </div>
            </div>

          <div class="row align-items-center g-4">
            <div class="col-lg-6">
              <div class="media-panel">
                <img src="<?= base_url('images/pengukuran.jpg'); ?>" class="img-fluid rounded shadow" alt="Gravity survey activity">
              </div>
            </div>

            <div class="col-lg-6">
              <div class="content-panel about-panel">
                <h3 class="about-title">Overview</h3>

                <ul class="nav nav-pills about-tabs mb-3" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="pill" href="#tab1">Free Air Anomaly</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#tab2">Complete Bouguer Anomaly</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#tab3">Integrasi</a>
                  </li>
                </ul>

                <hr class="about-divider">

                <div class="tab-content mt-3">
                  <div class="tab-pane fade show active" id="tab1">
                    <p class="text-muted fst-italic text-justify">FAA dipakai untuk membaca pola awal medan gayaberat.</p>
                    <p class="text-justify"><strong>Cocok untuk screening regional dan identifikasi area prospek.</strong></p>
                    <p class="text-justify">Di GravPort, FAA bisa langsung dipreview lewat katalog dan WebMap.</p>
                  </div>

                  <div class="tab-pane fade" id="tab2">
                    <p class="text-muted fst-italic text-justify">CBA lebih siap untuk interpretasi geologi dan struktur bawah permukaan.</p>
                    <p class="text-justify"><strong>Level 1 dan level 2 menghubungkan data titik ke grid raster yang lebih detail.</strong></p>
                    <p class="text-justify">Alurnya tetap ringkas: preview, pilih area, lalu unduh.</p>
                  </div>

                  <div class="tab-pane fade" id="tab3">
                    <p class="text-justify"><strong>GravPort menghubungkan katalog, preview peta, dan unduhan dalam satu alur.</strong></p>
                    <p class="text-justify">User bisa cek data lebih dulu sebelum memotong area yang dibutuhkan.</p>
                    <p class="text-justify"><strong>Hasilnya lebih cepat, lebih jelas, dan lebih siap dipakai.</strong></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Tools Section-->
      <section class="section section-sm section-first bg-default text-center section-shell section-shell--tools" id="tools">
        <div class="container">
          <div class="section-heading section-heading--center" data-aos="fade-up">
            <h2 class="section-title">Toolkit</h2>
          </div>

          <div class="row row-30 justify-content-center align-items-center tool-grid">
            <div class="col-md-7 col-lg-5 col-xl-6 text-lg-left wow fadeInUp">
              <div class="tool-visual">
                <div class="tool-visual__frame">
                  <span class="tool-visual__badge">3D Geoid</span>
                  <div class="tool-geoid-viewer" style="position:relative;overflow:hidden;border-radius:inherit;">
                    <iframe
                      id="sketchfabGeoid"
                      title="EGM 2008 with Blue Marble Texture"
                      src="https://sketchfab.com/models/c731817a2c6548d89afb5af36b82e1a2/embed?autostart=1&preload=1&autospin=1&ui_watermark=0&ui_controls=0&ui_infos=0&ui_stop=0&ui_inspector=0&ui_annotations=0&ui_settings=0&ui_vr=0&ui_ar=0&ui_help=0&ui_fullscreen=0"
                      frameborder="0"
                      allow="autoplay; fullscreen; xr-spatial-tracking"
                      style="width:100%;height:100%;display:block;border:0;"
                      loading="lazy">
                    </iframe>
                  </div>
                  <div class="tool-visual__frame-glow" aria-hidden="true"></div>
                </div>
                <p class="tool-visual__caption">
                  Model EGM2008 interaktif — drag untuk memutar. Data via <a href="<?= esc($geoidReferenceUrl) ?>" target="_blank" rel="noopener">WGS84/EGM2008</a>.
                </p>
              </div>
            </div>

            <div class="col-lg-7 col-xl-6">
              <div class="row row-30">
                <div class="col-sm-6 wow fadeInRight">
                  <article class="box-icon-modern box-icon-modern-custom tool-card">
                    <div class="tool-card__icon">
                      <img src="<?= base_url('images/civil3d.jpg'); ?>" alt="Civil 3D">
                    </div>
                    <div class="tool-card__body">
                      <h5 class="box-icon-modern-title">Civil 3D</h5>
                      <div class="box-icon-modern-decor"></div>
                      <p class="box-icon-modern-text">Pemodelan terrain dan visualisasi permukaan geospasial dari data titik pengukuran gravitasi</p>
                    </div>
                  </article>
                </div>
                <div class="col-sm-6 wow fadeInRight" data-wow-delay=".1s">
                  <article class="box-icon-modern box-icon-modern-2 tool-card">
                    <div class="tool-card__icon">
                      <img src="<?= base_url('images/arcgispro.jpeg'); ?>" alt="ArcGIS Pro">
                    </div>
                    <div class="tool-card__body">
                      <h5 class="box-icon-modern-title">ArcGIS Pro</h5>
                      <div class="box-icon-modern-decor"></div>
                      <p class="box-icon-modern-text">Analisis spasial dan pemetaan vektor FAA dan CBA level 1-2 skala regional</p>
                    </div>
                  </article>
                </div>
                <div class="col-sm-6 wow fadeInRight" data-wow-delay=".2s">
                  <article class="box-icon-modern box-icon-modern-2 tool-card">
                    <div class="tool-card__icon">
                      <img src="<?= base_url('images/agisoft.jpg'); ?>" alt="Agisoft Metashape">
                    </div>
                    <div class="tool-card__body">
                      <h5 class="box-icon-modern-title">Agisoft Metashape</h5>
                      <div class="box-icon-modern-decor"></div>
                      <p class="box-icon-modern-text">Rekonstruksi dan georeferensi model permukaan dari data citra udara gravitimetri</p>
                    </div>
                  </article>
                </div>
                <div class="col-sm-6 wow fadeInRight" data-wow-delay=".3s">
                  <article class="box-icon-modern box-icon-modern-2 tool-card">
                    <div class="tool-card__icon">
                      <img src="<?= base_url('images/redtoolbox.png'); ?>" alt="RedToolBox">
                    </div>
                    <div class="tool-card__body">
                      <h5 class="box-icon-modern-title">RedToolBox</h5>
                      <div class="box-icon-modern-decor"></div>
                      <p class="box-icon-modern-text">Pre-processing dan quality control data pengukuran gravitasi airborne PPK</p>
                    </div>
                  </article>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Meet The Team-->
      <section id="team" class="team section section-shell section-shell--team">

        <div class="container">
          <div class="section-heading section-heading--center" data-aos="fade-up">
            <span class="section-kicker">Core Team</span>
            <h2 class="section-title">Meet Our Team</h2>
            <p class="section-lead">
              Tim pengolahan data, visualisasi, dan pengembangan GravPort.
            </p>
          </div>
          <div class="row gy-4">
            <div class="col-lg-3 col-md-6 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="100">
              <div class="team-member">
                <div class="member-img">
                  <img src="<?= esc($teamImageUrls[1]) ?>" class="img-fluid" alt="Darren Avram">
                  <div class="social">
                    <a href="https://www.instagram.com/dpandasig?igsh=MWV2eDFlenNnbm9wMQ=="><i class="bi bi-instagram"></i></a>
                    <a href="https://www.linkedin.com/in/darrenavram/"><i class="bi bi-linkedin"></i></a>
                  </div>
                </div>
                <div class="member-info">
                  <h4>Darren Avram P.S.</h4>
                  <span>15122021</span>
                </div>
              </div>
            </div><!-- End Team Member -->
  
            <div class="col-lg-3 col-md-6 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="200">
              <div class="team-member">
                <div class="member-img">
                  <img src="<?= esc($teamImageUrls[2]) ?>" class="img-fluid" alt="Alena Cansery">
                  <div class="social">
                    <a href="https://www.instagram.com/alenacsry"><i class="bi bi-instagram"></i></a>
                    <a href="https://www.linkedin.com/in/alena-cansery-b02a34246?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=ios_app"><i class="bi bi-linkedin"></i></a>
                  </div>
                </div>
                <div class="member-info">
                  <h4>Haifa Bilqis</h4>
                  <span>15122063</span>
                </div>
              </div>
            </div><!-- End Team Member -->
  
            <div class="col-lg-3 col-md-6 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="300">
              <div class="team-member">
                <div class="member-img">
                  <img src="<?= esc($teamImageUrls[3]) ?>" class="img-fluid" alt="Mitzu Lintang Saputra">
                  <div class="social">
                    <a href="https://www.instagram.com/mitzl_?igsh=MWRxOW9sajU0b2t1dw=="><i class="bi bi-instagram"></i></a>
                    <a href="https://www.linkedin.com/in/mitzu-lintang-saputra-812ba6327?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app"><i class="bi bi-linkedin"></i></a>
                  </div>
                </div>
                <div class="member-info">
                  <h4>Alfian Rahma</h4>
                  <span>15122094</span>
                </div>
              </div>
            </div><!-- End Team Member -->
  
            <div class="col-lg-3 col-md-6 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="400">
              <div class="team-member">
                <div class="member-img">
                  <img src="<?= esc($teamImageUrls[4]) ?>" class="img-fluid" alt="M Rayhan Syahrir">
                  <div class="social">
                    <a href=""><i class="bi bi-instagram"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                  </div>
                </div>
                <div class="member-info">
                  <h4>Ahmad Hakim</h4>
                  <span>15122118</span>
                </div>
              </div>
            </div><!-- End Team Member -->
  
          </div>
  
        </div>
  
      </section><!-- /Team Section -->

      <!-- Contact Form-->
      <section class="section section-sm section-last bg-default text-left section-shell section-shell--contact" id="contacts">
        <div class="container contact-shell">
          <article class="title-classic contact-heading">
            <div class="title-classic-title">
              <h3>Get in touch</h3>
            </div>
            <div class="title-classic-text">
              <p>Kalau Anda ingin berdiskusi soal dataset, visualisasi, atau alur WebMap, kirim pesan melalui form ini dan tim GravPort akan menindaklanjutinya.</p>
            </div>
          </article>
          <form class="rd-form rd-form-variant-2 rd-mailform contact-form-shell" data-form-output="form-output-global" data-form-type="contact" method="post" action="<?= base_url('site/bat/rd-mailform.php'); ?>">
            <div class="row row-14 gutters-14">
              <div class="col-md-4">
                <div class="form-wrap">
                  <input class="form-input" id="contact-your-name-2" type="text" name="name" data-constraints="@Required">
                  <label class="form-label" for="contact-your-name-2">Your Name</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-wrap">
                  <input class="form-input" id="contact-email-2" type="email" name="email" data-constraints="@Email @Required">
                  <label class="form-label" for="contact-email-2">E-mail</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-wrap">
                  <input class="form-input" id="contact-phone-2" type="text" name="phone" data-constraints="@Numeric">
                  <label class="form-label" for="contact-phone-2">Phone</label>
                </div>
              </div>
              <div class="col-12">
                <div class="form-wrap">
                  <label class="form-label" for="contact-message-2">Message</label>
                  <textarea class="form-input textarea-lg" id="contact-message-2" name="message" data-constraints="@Required"></textarea>
                </div>
              </div>
            </div>
            <button class="button button-primary button-pipaluk btn-magnetic" type="submit">Send Message</button>
          </form>
        </div>
      </section>

      <!-- Page Footer -->
      <footer class="gravport-footer">
        <div class="gravport-footer__separator" aria-hidden="true"></div>
        <div class="gravport-footer__body">
          <div class="gravport-footer__col gravport-footer__col--brand">
            <div class="gravport-footer__logo">
              <img src="<?= base_url('images/itb.png'); ?>" alt="Logo ITB">
              <strong>GravPort</strong>
            </div>
            <p class="gravport-footer__pitch">
              Geoportal anomali gayaberat nasional — FAA dan CBA level 1–2 dalam satu platform terpadu untuk preview, filter area, dan unduh cepat.
            </p>
          </div>

          <div class="gravport-footer__col">
            <h4 class="gravport-footer__heading">Dataset</h4>
            <ul class="gravport-footer__links">
              <li><a href="<?= site_url('catalog'); ?>">Data Catalog</a></li>
              <li><a href="<?= site_url('webmap'); ?>">WebMap</a></li>
              <li><a href="<?= site_url('metadata'); ?>">Metadata Form</a></li>
            </ul>
          </div>

          <div class="gravport-footer__col">
            <h4 class="gravport-footer__heading">Explore</h4>
            <ul class="gravport-footer__links">
              <li><a href="<?= site_url('/'); ?>#About">About</a></li>
              <li><a href="<?= site_url('/'); ?>#tools">Tools</a></li>
              <li><a href="<?= site_url('/'); ?>#team">Team</a></li>
              <li><a href="<?= site_url('/'); ?>#contacts">Contact</a></li>
            </ul>
          </div>

          <div class="gravport-footer__col gravport-footer__col--institution">
            <h4 class="gravport-footer__heading">Institut Teknologi Bandung</h4>
            <p class="gravport-footer__inst-text">Program Studi Teknik Geodesi dan Geomatika<br>Jalan Ganesha 10, Bandung 40132</p>
            <div class="gravport-footer__social">
              <a href="https://www.instagram.com/dpandasig" aria-label="Instagram" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>
              <a href="https://www.linkedin.com/in/darrenavram/" aria-label="LinkedIn" target="_blank" rel="noopener"><i class="bi bi-linkedin"></i></a>
            </div>
          </div>
        </div>

        <div class="gravport-footer__bottom">
          <span>&copy; <span class="copyright-year"></span> GravPort &middot; Geodesi ITB &middot; Jawa-Bali Gravity Data Platform</span>
        </div>
      </footer>

      <div class="modal fade" id="modalCta" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h4>Contact Us</h4>
              <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
              <form class="rd-form rd-form-variant-2 rd-mailform" data-form-output="form-output-global" data-form-type="contact-modal" method="post" action="<?= base_url('site/bat/rd-mailform.php'); ?>">
                <div class="row row-14 gutters-14">
                  <div class="col-12">
                    <div class="form-wrap">
                      <input class="form-input" id="contact-modal-name" type="text" name="name" data-constraints="@Required">
                      <label class="form-label" for="contact-modal-name">Your Name</label>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="form-wrap">
                      <input class="form-input" id="contact-modal-email" type="email" name="email" data-constraints="@Email @Required">
                      <label class="form-label" for="contact-modal-email">E-mail</label>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="form-wrap">
                      <input class="form-input" id="contact-modal-phone" type="text" name="phone" data-constraints="@Numeric">
                      <label class="form-label" for="contact-modal-phone">Phone</label>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="form-wrap">
                      <label class="form-label" for="contact-modal-message">Message</label>
                      <textarea class="form-input textarea-lg" id="contact-modal-message" name="message" data-constraints="@Required"></textarea>
                    </div>
                  </div>
                </div>
                <button class="button button-primary button-pipaluk" type="submit">Send Message</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="site-toast" id="siteToast" role="status" aria-live="polite"></div>

    <!-- Global Mailform Output-->
    <div class="snackbars" id="form-output-global"></div>
    <!-- Javascript-->
    <script src="<?= base_url('site/')?>js/core.min.js"></script>
    <script src="<?= base_url('site/')?>js/script.js"></script>
    <script src="<?= base_url('assets/')?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('assets/')?>vendor/php-email-form/validate.js"></script>
    <script src="<?= base_url('assets/')?>vendor/aos/aos.js"></script>
    <script src="<?= base_url('assets/')?>vendor/glightbox/js/glightbox.min.js"></script>
    <script src="<?= base_url('assets/')?>vendor/swiper/swiper-bundle.min.js"></script>
    <script src="<?= base_url('assets/')?>vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="<?= base_url('assets/')?>vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="<?= base_url('site/')?>js/main_2.js?v=5"></script>
    <script>
      if (window.AOS) {
        AOS.init({
          duration: 900,
          once: true,
          offset: 80
        });
      }
    </script>



    <script>
    (function () {
      const header = document.getElementById('siteHeader');
      const hero = document.getElementById('sceneHome');
      if (!header || !hero) return;

      function onScroll() {
        const heroBottom = hero.getBoundingClientRect().bottom;
        const trigger = Math.max(header.offsetHeight + 28, window.innerHeight * 0.72);

        if (heroBottom <= trigger) {
          header.classList.add('header--solid');
        } else {
          header.classList.remove('header--solid');
        }
      }

      window.addEventListener('scroll', onScroll, { passive: true });
      window.addEventListener('resize', onScroll);
      onScroll();
    })();
    </script>

    <script>
    (function () {
      // works whether: #sceneHome itself is the hero OR it contains .hero
      const hero = document.querySelector('#sceneHome.hero, #sceneHome .hero, #sceneHome');
      if (!hero) return;

      function setSpot(e){
        const r = hero.getBoundingClientRect();
        const x = ((e.clientX - r.left) / r.width) * 100;
        const y = ((e.clientY - r.top) / r.height) * 100;

        hero.style.setProperty('--spot-x', x + '%');
        hero.style.setProperty('--spot-y', y + '%');
        hero.style.setProperty('--spot-size', '280px');
      }

      hero.addEventListener('mouseenter', (e) => {
        hero.classList.add('spotlight-active');
        document.body.classList.add('hero-spotlight-active');
        setSpot(e);
      });

      hero.addEventListener('mousemove', setSpot, { passive: true });

      hero.addEventListener('mouseleave', () => {
        hero.classList.remove('spotlight-active');
        document.body.classList.remove('hero-spotlight-active');
      });
    })();
    </script>

    <script>
    (function () {
      const sharp = document.getElementById('heroDynamic');
      const blur  = document.getElementById('heroDynamic_blur');
      if (!sharp || !blur) return;

      blur.textContent = sharp.textContent;

      const obs = new MutationObserver(() => {
        blur.textContent = sharp.textContent;
      });

      obs.observe(sharp, { childList: true, characterData: true, subtree: true });
    })();
    </script>

    <script>
    (function(){
      const root = document.getElementById('datasetOrbit');
      if (!root) return;

      const railEl = document.getElementById('orbitItems');
      const heroImage = document.getElementById('dsHeroImage');
      const featureTitle = document.getElementById('dsFeatureTitle');
      const featureSubtitle = document.getElementById('dsFeatureSubtitle');
      const featureMode = document.getElementById('dsFeatureMode');

      if (!railEl || !heroImage) return;

      const dsTitle = featureTitle;
      const dsMeta  = document.getElementById('dsMeta');
      const dsDesc  = document.getElementById('dsDesc');
      const dsSpecs = document.getElementById('dsSpecs');
      const dsView  = document.getElementById('dsView');
      const dsDownload = document.getElementById('dsDownload');
      const dsIndex = document.getElementById('dsIndex');
      const dsDots  = document.getElementById('dsDots');
      const dsTag = document.getElementById('dsTag');
      const slots = ['northwest', 'northeast', 'southwest', 'southeast'];

      const items = [
        {
          id: "faa_l1",
          tag: "FAA L1",
          meta: "Level 1 | FAA National | Scatter",
          title: "Scatter FAA Level 1 - Indonesia",
          desc: "Dataset scatter FAA Level 1 siap untuk analisis regional & integrasi ke model gravitasi.",
          specs: ["Coverage: Indonesia", "Format: Shapefile & CSV", "Catalog: FAA Level 1"],
          img: "<?= base_url('images/grav-1.png'); ?>",
          catalogUrl: "<?= site_url('catalog') . '?q=' . rawurlencode('Scatter FAA Level 1'); ?>",
          previewUrl: "<?= site_url('catalog/view/1'); ?>",
          primaryLabel: "Open Catalog",
          secondaryLabel: "Preview",
          mode: "SCATTER",
          accent: "#ffb24d",
          accentSoft: "rgba(255, 178, 77, 0.24)"
        },
        {
          id: "cba_l1",
          tag: "CBA L1",
          meta: "Level 1 | CBA National | Scatter",
          title: "Scatter CBA Level 1 - Indonesia",
          desc: "Dataset scatter CBA Level 1 siap untuk analisis regional & integrasi ke model gravitasi.",
          specs: ["Coverage: Indonesia", "Format: Shapefile & CSV", "Catalog: CBA Level 1"],
          img: "<?= base_url('images/grav-2.png'); ?>",
          catalogUrl: "<?= site_url('catalog') . '?q=' . rawurlencode('Scatter CBA Level 1'); ?>",
          previewUrl: "<?= site_url('catalog/view/2'); ?>",
          primaryLabel: "Open Catalog",
          secondaryLabel: "Preview",
          mode: "SCATTER",
          accent: "#ff8c5b",
          accentSoft: "rgba(255, 140, 91, 0.24)"
        },
        {
          id: "faa_l2",
          tag: "FAA L2",
          meta: "Level 2 | FAA National | Grid",
          title: "Grid FAA Level 2 - Indonesia",
          desc: "Dataset raster grid FAA Level 2 siap dipakai untuk analisis spasial dan integrasi model gravitasi.",
          specs: ["Coverage: Indonesia", "Format: GeoTIFF", "Catalog: FAA Level 2"],
          img: "<?= base_url('images/grav-3.png'); ?>",
          catalogUrl: "<?= site_url('catalog') . '?q=' . rawurlencode('Grid FAA Level 2'); ?>",
          previewUrl: "<?= site_url('catalog/view/3'); ?>",
          primaryLabel: "Open Catalog",
          secondaryLabel: "Preview",
          mode: "GRID",
          accent: "#54beff",
          accentSoft: "rgba(84, 190, 255, 0.24)"
        },
        {
          id: "cba_l2",
          tag: "CBA L2",
          meta: "Level 2 | CBA National | Grid",
          title: "Grid CBA Level 2 - Indonesia",
          desc: "Dataset raster grid CBA Level 2 (Complete Bouguer Anomaly) untuk analisis geospasial lanjut.",
          specs: ["Coverage: Indonesia", "Format: GeoTIFF", "Catalog: CBA Level 2"],
          img: "<?= base_url('images/grav-4.png'); ?>",
          catalogUrl: "<?= site_url('catalog') . '?q=' . rawurlencode('Grid CBA Level 2'); ?>",
          previewUrl: "<?= site_url('catalog/view/4'); ?>",
          primaryLabel: "Open Catalog",
          secondaryLabel: "Preview",
          mode: "GRID",
          accent: "#7ad8cf",
          accentSoft: "rgba(122, 216, 207, 0.24)"
        }
      ];

      function escapeHtml(s){
        return String(s).replace(/[&<>"']/g, (c) => ({
          '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
      }

      let activeIndex = 0;
      let autoplayTimer = null;
      let switchingTimer = null;

      function renderDots(active){
        if (!dsDots) return;

        dsDots.innerHTML = "";
        items.forEach((_, i) => {
          const dot = document.createElement('span');
          dot.className = "dot" + (i === active ? " is-active" : "");
          dot.setAttribute('aria-hidden', 'true');
          dsDots.appendChild(dot);
        });
      }

      const cards = items.map((it, i) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `dataset-constellation__node dataset-constellation__node--${slots[i] || slots[0]}`;
        button.dataset.index = String(i);
        button.style.setProperty('--node-accent', it.accent);
        button.setAttribute('role', 'listitem');
        button.innerHTML = `
          <span class="dataset-constellation__node-card">
            <span class="dataset-constellation__node-media">
              <img src="${it.img}" alt="${escapeHtml(it.title)}">
            </span>
            <span class="dataset-constellation__node-body">
              <small>${escapeHtml(it.tag)}</small>
              <strong>${escapeHtml(it.title)}</strong>
              <em>${escapeHtml(it.meta)}</em>
            </span>
          </span>
        `;

        button.addEventListener('click', () => {
          if (activeIndex === i && it.catalogUrl) {
            window.location.href = it.catalogUrl;
            return;
          }

          applyItem(i, true);
        });

        railEl.appendChild(button);
        return button;
      });

      function applyItem(i, animate = false){
        const it = items[i];
        if (!it) return;

        activeIndex = i;
        root.dataset.active = it.id;
        root.style.setProperty('--dataset-accent', it.accent);
        root.style.setProperty('--dataset-accent-soft', it.accentSoft);

        if (animate) {
          root.classList.add('is-switching');
          clearTimeout(switchingTimer);
          switchingTimer = setTimeout(() => {
            root.classList.remove('is-switching');
          }, 780);
        }

        if (dsTitle) dsTitle.textContent = it.title;
        if (dsMeta) dsMeta.textContent = it.meta;
        if (dsDesc) dsDesc.textContent = it.desc;
        if (featureTitle) featureTitle.textContent = it.title;
        if (featureSubtitle) featureSubtitle.textContent = it.mode === 'GRID'
          ? 'Raster spread siap ditelusuri'
          : 'Vector spread siap ditelusuri';
        if (dsTag) dsTag.textContent = it.tag;
        if (featureMode) featureMode.textContent = it.mode;
        if (heroImage) {
          heroImage.src = it.img;
          heroImage.alt = it.title;
        }

        if (dsSpecs){
          dsSpecs.innerHTML = "";
          it.specs.forEach(s => {
            const chip = document.createElement('span');
            chip.className = "spec-chip";
            chip.textContent = s;
            dsSpecs.appendChild(chip);
          });
        }

        if (dsView){
          dsView.href = it.catalogUrl;
          dsView.textContent = it.primaryLabel || "Open Catalog";
        }
        if (dsDownload){
          dsDownload.href = it.previewUrl || it.catalogUrl;
          dsDownload.textContent = it.secondaryLabel || "Preview";
        }

        if (dsIndex){
          const left = String(i + 1).padStart(2, '0');
          const total = String(items.length).padStart(2, '0');
          dsIndex.textContent = `${left} / ${total}`;
        }

        renderDots(i);
        cards.forEach((card, idx) => {
          card.classList.toggle('is-active', idx === i);
        });
      }

      function restartAutoplay(){
        if (autoplayTimer) clearInterval(autoplayTimer);
        autoplayTimer = setInterval(() => {
          const next = (activeIndex + 1) % items.length;
          applyItem(next, true);
        }, 5600);
      }

      function stopAutoplay(){
        if (autoplayTimer) clearInterval(autoplayTimer);
      }

      root.addEventListener('mouseenter', stopAutoplay);
      root.addEventListener('mouseleave', restartAutoplay);

      applyItem(0, false);
      restartAutoplay();
    })();
    </script>

    <script>
    /* ---- Cinematic Loader ---- */
    (function () {
      var loader = document.getElementById('gpLoader');
      if (!loader) return;

      setTimeout(function () {
        loader.classList.add('is-visible');
        setTimeout(function () {
          loader.classList.add('is-loading');
          setTimeout(function () {
            loader.classList.add('is-done');
            document.body.classList.remove('gp-loading');
            setTimeout(function () {
              if (loader.parentNode) loader.parentNode.removeChild(loader);
            }, 1000);
          }, 1600);
        }, 80);
      }, 60);
    })();
    </script>

    <script>
    /* ============================================================
       GravPort Creative Layer
       I: Scroll progress bar
       H: Scroll-spy nav
       J: Count-up metrics
       K: Hero ambient particles
       +: Section heading word-reveal
       M: Magnetic buttons
    ============================================================ */
    (function () {

      /* ---- I: Scroll progress bar ---- */
      var progressBar = document.createElement('div');
      progressBar.className = 'gp-scroll-progress';
      document.body.appendChild(progressBar);

      function updateProgress() {
        var scrollTop = window.scrollY || document.documentElement.scrollTop;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
        var pct = docHeight > 0 ? Math.min(100, (scrollTop / docHeight) * 100) : 0;
        progressBar.style.width = pct + '%';
      }
      window.addEventListener('scroll', updateProgress, { passive: true });
      updateProgress();


      /* ---- H: Scroll-spy nav ---- */
      var spySections = ['home', 'dataset', 'About', 'tools', 'team', 'contacts'];
      var navLinks = document.querySelectorAll('.site-nav__link[data-nav-section]');

      if (navLinks.length && 'IntersectionObserver' in window) {
        var activeSection = null;

        function setActiveSpy(id) {
          if (activeSection === id) return;
          activeSection = id;
          navLinks.forEach(function (link) {
            var sec = link.getAttribute('data-nav-section');
            if (sec === id) {
              link.classList.add('active', 'is-active');
              link.setAttribute('aria-current', 'page');
            } else {
              link.classList.remove('active', 'is-active');
              link.removeAttribute('aria-current');
            }
          });
        }

        var spyObserver = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              setActiveSpy(entry.target.id);
            }
          });
        }, { threshold: 0.35, rootMargin: '-10% 0px -45% 0px' });

        spySections.forEach(function (id) {
          var el = document.getElementById(id);
          if (el) spyObserver.observe(el);
        });
      }


      /* ---- J: Count-up metrics ---- */
      function animateCounter(el) {
        var target = parseInt(el.getAttribute('data-target'), 10) || 0;
        var suffix = el.getAttribute('data-suffix') || '';
        var start = 0;
        var duration = 1400;
        var startTime = null;

        function easeOutQuart(t) { return 1 - Math.pow(1 - t, 4); }

        function step(ts) {
          if (!startTime) startTime = ts;
          var progress = Math.min((ts - startTime) / duration, 1);
          var value = Math.round(easeOutQuart(progress) * target);
          el.textContent = value + suffix;
          if (progress < 1) requestAnimationFrame(step);
        }

        requestAnimationFrame(step);
      }

      if ('IntersectionObserver' in window) {
        var counterObserver = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting && !entry.target.dataset.counted) {
              entry.target.dataset.counted = '1';
              animateCounter(entry.target);
              counterObserver.unobserve(entry.target);
            }
          });
        }, { threshold: 0.6 });

        document.querySelectorAll('.js-counter').forEach(function (el) {
          counterObserver.observe(el);
        });
      }


      /* ---- Word-reveal for section headings ---- */
      function wrapWords(el) {
        var text = el.textContent;
        var words = text.split(/(\s+)/);
        el.innerHTML = '';
        el.classList.add('reveal-heading');
        words.forEach(function (word, i) {
          if (/^\s+$/.test(word)) {
            el.appendChild(document.createTextNode(word));
          } else {
            var outer = document.createElement('span');
            outer.className = 'reveal-word';
            var inner = document.createElement('span');
            inner.className = 'reveal-word__inner';
            inner.style.transitionDelay = (i * 0.045) + 's';
            inner.textContent = word;
            outer.appendChild(inner);
            el.appendChild(outer);
          }
        });
      }

      var revealHeadings = document.querySelectorAll('.section-title, .dataset-hero__title');
      revealHeadings.forEach(wrapWords);

      if ('IntersectionObserver' in window) {
        var revealObserver = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-revealed');
              revealObserver.unobserve(entry.target);
            }
          });
        }, { threshold: 0.2 });

        revealHeadings.forEach(function (el) {
          revealObserver.observe(el);
        });
      }


      /* ---- K: Hero ambient particles ---- */
      (function () {
        var canvas = document.getElementById('heroParticles');
        if (!canvas) return;

        var ctx = canvas.getContext('2d');
        var W, H, particles;
        var mouseX = -999, mouseY = -999;
        var raf;

        function resize() {
          var hero = document.getElementById('sceneHome');
          W = canvas.width  = hero ? hero.offsetWidth  : window.innerWidth;
          H = canvas.height = hero ? hero.offsetHeight : window.innerHeight;
        }

        function mkParticle() {
          return {
            x: Math.random() * W,
            y: Math.random() * H,
            r: 1 + Math.random() * 1.8,
            vx: (Math.random() - 0.5) * 0.22,
            vy: (Math.random() - 0.5) * 0.18,
            a: 0.08 + Math.random() * 0.28,
          };
        }

        function init() {
          resize();
          particles = Array.from({ length: 28 }, mkParticle);
        }

        function draw() {
          ctx.clearRect(0, 0, W, H);
          particles.forEach(function (p) {
            var dx = p.x - mouseX;
            var dy = p.y - mouseY;
            var dist = Math.sqrt(dx * dx + dy * dy);
            var glow = dist < 160 ? 1 - dist / 160 : 0;
            var alpha = p.a + glow * 0.55;

            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r + glow * 1.8, 0, Math.PI * 2);
            ctx.fillStyle = glow > 0.1
              ? 'rgba(97, 212, 255, ' + alpha + ')'
              : 'rgba(255, 255, 255, ' + alpha + ')';
            ctx.fill();

            p.x += p.vx;
            p.y += p.vy;
            if (p.x < -10) p.x = W + 10;
            if (p.x > W + 10) p.x = -10;
            if (p.y < -10) p.y = H + 10;
            if (p.y > H + 10) p.y = -10;
          });

          raf = requestAnimationFrame(draw);
        }

        var hero = document.getElementById('sceneHome');
        if (hero) {
          hero.addEventListener('mousemove', function (e) {
            var r = hero.getBoundingClientRect();
            mouseX = e.clientX - r.left;
            mouseY = e.clientY - r.top;
          }, { passive: true });
          hero.addEventListener('mouseleave', function () {
            mouseX = -999; mouseY = -999;
          });
        }

        window.addEventListener('resize', function () {
          resize();
          particles = Array.from({ length: 28 }, mkParticle);
        });

        init();
        draw();
      })();


      /* ---- M: Magnetic buttons ---- */
      document.querySelectorAll('.btn-magnetic').forEach(function (btn) {
        btn.addEventListener('mousemove', function (e) {
          var r = btn.getBoundingClientRect();
          var dx = (e.clientX - (r.left + r.width  / 2)) * 0.30;
          var dy = (e.clientY - (r.top  + r.height / 2)) * 0.30;
          btn.style.transform = 'translate(' + dx + 'px, ' + dy + 'px)';
        });
        btn.addEventListener('mouseleave', function () {
          btn.style.transform = '';
        });
      });

    })();
    </script>

  </body>
</html>
