<!DOCTYPE html>
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
    <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=26'); ?>">
    <!-- Vendor CSS Files -->

    <link href="<?= base_url('assets/')?>vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= base_url('assets/')?>vendor/aos/aos.css" rel="stylesheet">
    <link href="<?= base_url('assets/')?>vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/')?>vendor/swiper/swiper-bundle.min.css" rel="stylesheet">


    <script type="module" src="https://unpkg.com/@google/model-viewer@latest/dist/model-viewer.min.js"></script>
    <style>.ie-panel{display: none;background: #212121;padding: 10px 0;box-shadow: 3px 3px 5px 0 rgba(0,0,0,.3);clear: both;text-align:center;position: relative;z-index: 1;} html.ie-10 .ie-panel, html.lt-ie-10 .ie-panel {display: block;}</style>
    <style>
    .nav-pills .nav-link.active {
      background-color: #09007b !important;
      color: white !important;
    }

    .nav-auth{
      display:flex;
      align-items:center;
      gap:12px;
    }
    .nav-login, .nav-logout{
      color:#fff;
      text-decoration:none;
      font-weight:700;
      padding:8px 12px;
      border-radius:999px;
      border:1px solid rgba(255,255,255,0.25);
      background: rgba(0,0,0,0.12);
    }
    .nav-login:hover, .nav-logout:hover{
      background: rgba(0,0,0,0.22);
    }
    .nav-role{
      color: rgba(255,255,255,0.9);
      font-weight:800;
      letter-spacing:.06em;
      font-size:12px;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid rgba(255,255,255,0.18);
      background: rgba(255,255,255,0.08);
    }

  </style>
  </head>
  <body class="gravport-landing">
    <div class="preloader">
      <div class="preloader-body">
        <div class="cssload-container"><span></span><span></span><span></span><span></span>
        </div>
      </div>
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
                Geoportal data anomali gayaberat level 1 dan level 2 untuk eksplorasi cepat, preview interaktif, dan alur unduh yang lebih rapi.
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
                Geoportal data anomali gayaberat level 1 dan level 2 untuk eksplorasi cepat, preview interaktif, dan alur unduh yang lebih rapi.
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


      <!-- ================== DATASET HERO ================== -->
      <section id="dataset" class="dataset-hero story-scene section-shell section-shell--dataset">
        <div class="scene-sticky">

          <!-- Overlay content -->
          <div class="dataset-hero__overlay dataset-constellation" id="datasetOrbit">
            <div class="dataset-constellation__intro">
              <div class="dataset-constellation__copy">
                <span class="dataset-constellation__kicker">Editorial Dataset Deck</span>
                <h2 class="dataset-hero__title">Datasets</h2>
                <p class="dataset-hero__subtitle">
                  Data Free Air Anomaly (FAA) dan Complete Bouguer Anomaly (CBA) dalam level 1 & 2 siap diunduh untuk analisis geospasial.
                </p>
              </div>
              <a href="<?= site_url('catalog'); ?>" class="dataset-hero__catalog-link dataset-constellation__catalog-link">
                Browse full catalog
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
                      <p id="dsFeatureSubtitle">Vector spread siap ditelusuri</p>
                      <p class="dataset-constellation__feature-desc" id="dsDesc">
                        Dataset scatter FAA Level 1 siap untuk analisis regional & integrasi ke model gravitasi.
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
          <div class="section-heading section-heading--split">
            <div>
              <span class="section-kicker">Overview</span>
              <h2 class="section-title">Gravity anomaly data with a cleaner story flow</h2>
            </div>
            <p class="section-lead">
              GravPort merangkum FAA dan CBA level 1-2 ke dalam alur yang lebih jelas: pahami konteksnya, telusuri dataset yang tersedia, lalu masuk ke eksplorasi interaktif dan unduhan yang lebih terarah.
            </p>
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
                    <p class="text-muted fst-italic text-justify">Free Air Anomaly membantu membaca perubahan medan gayaberat setelah koreksi dasar dilakukan terhadap posisi pengamatan.</p>
                    <p class="text-justify"><strong>Layer FAA cocok dipakai untuk interpretasi awal, peninjauan pola regional, dan identifikasi area yang layak didalami lebih lanjut.</strong></p>
                    <p class="text-justify">Pada GravPort, dataset FAA disiapkan dalam format yang lebih mudah ditinjau lewat katalog maupun WebMap sehingga perpindahan dari preview ke unduhan terasa lebih mulus.</p>
                  </div>

                  <div class="tab-pane fade" id="tab2">
                    <p class="text-muted fst-italic text-justify">Complete Bouguer Anomaly menambahkan koreksi massa dan topografi sehingga pembacaan anomali menjadi lebih siap untuk analisis geologi dan struktur bawah permukaan.</p>
                    <p class="text-justify"><strong>Dataset CBA level 1 dan level 2 memberi jembatan dari inspeksi cepat berbasis titik ke analisis grid raster yang lebih detail.</strong></p>
                    <p class="text-justify">Dengan tampilan yang lebih tertata, user bisa memahami konteks data terlebih dahulu sebelum masuk ke proses preview, pemilihan area, dan pengunduhan hasil.</p>
                  </div>

                  <div class="tab-pane fade" id="tab3">
                    <p class="text-justify"><strong>GravPort dirancang sebagai alur kerja yang terhubung: pahami konsep, buka katalog, masuk ke WebMap, lalu potong data berdasarkan kebutuhan area studi.</strong></p>
                    <p class="text-justify">Pendekatan ini membuat halaman depan tidak hanya menjadi etalase, tetapi juga pintu masuk yang jelas menuju proses analisis spasial yang lebih interaktif.</p>
                    <p class="text-justify"><strong>Hasil akhirnya adalah pengalaman yang lebih ringkas, lebih terarah, dan lebih siap dipakai untuk eksplorasi data gravimetri.</strong></p>
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
            <span class="section-kicker">Toolchain</span>
            <h2 class="section-title">Toolkit yang lebih rapi untuk akuisisi sampai analisis</h2>
            <p class="section-lead">
              Visual 3D, pengolahan fotogrametri, dan GIS disusun sebagai ekosistem kerja yang saling melengkapi, bukan potongan workflow yang berdiri sendiri.
            </p>
          </div>

          <div class="row row-30 justify-content-center align-items-center tool-grid">
            <div class="col-md-7 col-lg-5 col-xl-6 text-lg-left wow fadeInUp">
              <div class="tool-visual">
                <div class="sketchfab-embed-wrapper">
                  <iframe
                    title="Earth Geoid"
                    frameborder="0"
                    allowfullscreen
                    mozallowfullscreen="true"
                    webkitallowfullscreen="true"
                    allow="autoplay; fullscreen; xr-spatial-tracking"
                    xr-spatial-tracking
                    execution-while-out-of-viewport
                    execution-while-not-rendered
                    web-share
                    src="https://sketchfab.com/models/43020d93da284e199ff5424195287c77/embed?autostart=1&transparent=1&ui_hint=0"
                    style="width:100%; height:100%; border:0;">
                  </iframe>
                </div>
                <p class="tool-visual__caption">
                  Earth Geoid by
                  <a href="https://sketchfab.com/COMET_Team?utm_medium=embed&utm_campaign=share-popup&utm_content=43020d93da284e199ff5424195287c77" target="_blank" rel="nofollow">The COMET Program</a>
                  on
                  <a href="https://sketchfab.com/3d-models/earth-geoid-43020d93da284e199ff5424195287c77?utm_medium=embed&utm_campaign=share-popup&utm_content=43020d93da284e199ff5424195287c77" target="_blank" rel="nofollow">Sketchfab</a>
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
                      <p class="box-icon-modern-text">Pengolahan data .dwg borepile, pilecap, dan kolom</p>
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
                      <p class="box-icon-modern-text">Pengolahan data vektor borepile, pilecap, dan kolom</p>
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
                      <p class="box-icon-modern-text">Post processing data fotogrametri RTK dan PPK</p>
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
                      <p class="box-icon-modern-text">Pre Processing data fotogrametri PPK</p>
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
              Tim ini menyatukan pengolahan data, visualisasi, dan pengembangan web agar alur GravPort terasa lebih utuh dari halaman utama sampai WebMap.
            </p>
          </div>
          <div class="row gy-4">
            <div class="col-lg-3 col-md-6 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="100">
              <div class="team-member">
                <div class="member-img">
                  <img src="<?= base_url('assets/img/team/team-1.jpg'); ?>" class="img-fluid" alt="Darren Avram">
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
                  <img src="<?= base_url('assets/img/team/team-2.jpg'); ?>" class="img-fluid" alt="Alena Cansery">
                  <div class="social">
                    <a href="https://www.instagram.com/alenacsry"><i class="bi bi-instagram"></i></a>
                    <a href="https://www.linkedin.com/in/alena-cansery-b02a34246?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=ios_app"><i class="bi bi-linkedin"></i></a>
                  </div>
                </div>
                <div class="member-info">
                  <h4>Alena Cansery</h4>
                  <span>15122023</span>
                </div>
              </div>
            </div><!-- End Team Member -->
  
            <div class="col-lg-3 col-md-6 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="300">
              <div class="team-member">
                <div class="member-img">
                  <img src="<?= base_url('assets/img/team/team-3.jpg'); ?>" class="img-fluid" alt="Mitzu Lintang Saputra">
                  <div class="social">
                    <a href="https://www.instagram.com/mitzl_?igsh=MWRxOW9sajU0b2t1dw=="><i class="bi bi-instagram"></i></a>
                    <a href="https://www.linkedin.com/in/mitzu-lintang-saputra-812ba6327?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app"><i class="bi bi-linkedin"></i></a>
                  </div>
                </div>
                <div class="member-info">
                  <h4>Mitzu Lintang S</h4>
                  <span>15122039</span>
                </div>
              </div>
            </div><!-- End Team Member -->
  
            <div class="col-lg-3 col-md-6 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="400">
              <div class="team-member">
                <div class="member-img">
                  <img src="<?= base_url('assets/img/team/team-4.jpg'); ?>" class="img-fluid" alt="M Rayhan Syahrir">
                  <div class="social">
                    <a href=""><i class="bi bi-instagram"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                  </div>
                </div>
                <div class="member-info">
                  <h4>M Rayhan Syahrir</h4>
                  <span>15122041</span>
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
            <button class="button button-primary button-pipaluk" type="submit">Send Message</button>
          </form>
        </div>
      </section>

      <!-- Page Footer-->
      <footer class="section section-fluid footer-minimal context-dark">
        <div class="bg-gray-15">
          <div class="container-fluid">
            <div class="footer-minimal-inset oh">
              <ul class="footer-list-category-2">
                <li><a href="#">Activity</a></li>
                <li><a href="#">Contact</a></li>
                <li><a href="#">Help</a></li>
              </ul>
            </div>
            <div class="footer-minimal-bottom-panel text-sm-left">
              <div class="row row-10 align-items-md-center">
                <div class="col-sm-6 col-md-4 text-sm-right text-md-center">
                </div>
                <div class="col-sm-6 col-md-4 order-sm-first">
                  <!-- Rights-->
                  <p class="rights"><span>&copy;&nbsp;</span><span class="copyright-year"></span> <span>Waskita-internship | All rights reserved.</span>
                  </p>
              </div>
            </div>
          </div>
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


  </body>
</html>
