<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | WebMap</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=30'); ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css'); ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/webmap.css?v=3'); ?>">

  <style>
    /* ===== WebMap Cinematic Loader ===== */
    body.wm-loading { overflow: hidden; }
    .wm-loader {
      position: fixed; inset: 0; z-index: 99999;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      background: #04101d;
      transform: translateY(0);
      transition: transform 0.95s cubic-bezier(0.76,0,0.24,1);
    }
    .wm-loader.is-done { transform: translateY(-100%); }
    .wm-loader__brand {
      display: flex; align-items: center; gap: 14px;
      margin-bottom: 12px;
      opacity: 0; transform: translateY(16px);
      transition: opacity 0.5s, transform 0.5s;
    }
    .wm-loader__brand img { width: 46px; height: 46px; object-fit: contain; filter: drop-shadow(0 0 16px rgba(97,212,255,0.4)); }
    .wm-loader__brand strong { font-family: "Manrope",sans-serif; font-size: 26px; font-weight: 800; color: #fff; }
    .wm-loader__tag {
      font-family: "Manrope",sans-serif; font-size: 10px; font-weight: 800;
      letter-spacing: 0.22em; text-transform: uppercase;
      color: rgba(97,212,255,0.55); margin-bottom: 28px;
      opacity: 0; transition: opacity 0.4s 0.2s;
    }
    .wm-loader__bar { width: 200px; height: 2px; background: rgba(255,255,255,0.1); border-radius: 999px; overflow: hidden; opacity: 0; transition: opacity 0.3s 0.15s; }
    .wm-loader__fill { height: 100%; width: 0%; background: linear-gradient(90deg,#61d4ff,#a76025,#ffbf74); border-radius: 999px; transition: width 1.2s cubic-bezier(0.4,0,0.2,1) 0.1s; }
    .wm-loader__status { font-family: "Manrope",sans-serif; font-size: 11px; color: rgba(255,255,255,0.3); margin-top: 14px; letter-spacing: 0.08em; opacity: 0; transition: opacity 0.4s 0.4s; }
    .wm-loader.is-visible .wm-loader__brand { opacity: 1; transform: translateY(0); }
    .wm-loader.is-visible .wm-loader__bar,
    .wm-loader.is-visible .wm-loader__tag,
    .wm-loader.is-visible .wm-loader__status { opacity: 1; }
    .wm-loader.is-loading .wm-loader__fill { width: 100%; }

    /* ===== Sidebar card entrance ===== */
    .wm-card {
      opacity: 0;
      transform: translateX(-18px);
      transition: opacity 0.5s ease, transform 0.55s cubic-bezier(0.16,1,0.3,1);
    }
    .wm-card.card-in { opacity: 1; transform: translateX(0); }

    /* ===== Map stage reveal ===== */
    .wm-stage {
      clip-path: inset(0 0 0 100%);
      transition: clip-path 1s cubic-bezier(0.16,1,0.3,1) 0.55s;
    }
    .wm-stage.stage-in { clip-path: inset(0 0 0 0); }

    /* ===== Stat box pulse on update ===== */
    .wm-stat-box strong {
      display: inline-block;
      transition: transform 0.22s cubic-bezier(0.34,1.56,0.64,1), color 0.22s;
    }
    .wm-stat-box strong.val-bump {
      transform: scale(1.18);
      color: #ffbf74;
    }

    /* ===== Button shimmer ===== */
    .wm-primary-btn, .wm-secondary-btn {
      position: relative; overflow: hidden;
    }
    .wm-primary-btn::after, .wm-secondary-btn::after {
      content: '';
      position: absolute; top: 0; left: -100%;
      width: 60%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
      transform: skewX(-20deg);
      transition: left 0s;
    }
    .wm-primary-btn:hover::after, .wm-secondary-btn:hover::after {
      left: 160%;
      transition: left 0.55s ease;
    }

    /* ===== Scroll progress ===== */
    .wm-progress { position: fixed; top: 0; left: 0; height: 3px; width: 0%; background: linear-gradient(90deg,#61d4ff,#a76025); z-index: 9999; pointer-events: none; }
  </style>
</head>
<body class="gravport-landing wm-loading">

<div class="wm-loader" id="wmLoader" role="status" aria-label="Loading WebMap">
  <div class="wm-loader__brand">
    <img src="<?= base_url('images/itb.png'); ?>" alt="">
    <strong>GravPort</strong>
  </div>
  <p class="wm-loader__tag">WebMap</p>
  <div class="wm-loader__bar">
    <div class="wm-loader__fill"></div>
  </div>
  <p class="wm-loader__status" id="wmLoaderStatus">Initializing map layers&hellip;</p>
</div>

<div class="wm-progress" id="wmProgress"></div>

<?= view('partials/site_header', [
  'activePage' => 'webmap',
  'headerClass' => 'header--solid',
]) ?>

<main class="wm-page">
  <aside class="wm-sidebar">
    <div class="wm-card wm-card--hero">
      <p class="wm-eyebrow">WebMap</p>
      <h1>WebMap</h1>
      <p class="wm-copy">
        Pilih data, tentukan area, lalu unduh hasilnya.
      </p>
    </div>

    <div class="wm-card">
      <p class="wm-section-title">Data</p>
      <div class="wm-radio-grid">
        <label class="wm-radio-pill">
          <input type="radio" name="anomaly" value="faa" checked>
          <span>Free Air Anomaly</span>
        </label>
        <label class="wm-radio-pill">
          <input type="radio" name="anomaly" value="cba">
          <span>Complete Bouguer Anomaly</span>
        </label>
      </div>
    </div>

    <div class="wm-card">
      <p class="wm-section-title">Level</p>
      <div class="wm-inline-grid">
        <label class="wm-radio-pill">
          <input type="radio" name="level" value="l1" checked>
          <span>Level 1</span>
        </label>
        <label class="wm-radio-pill">
          <input type="radio" name="level" value="l2">
          <span>Level 2</span>
        </label>
      </div>
    </div>

    <div class="wm-card">
      <p class="wm-section-title">Organizations</p>
      <div class="wm-inline-grid">
        <label class="wm-radio-pill">
          <input type="radio" name="organization" value="all" checked>
          <span>All</span>
        </label>
        <label class="wm-radio-pill">
          <input type="radio" name="organization" value="itb">
          <span>ITB</span>
        </label>
        <label class="wm-radio-pill">
          <input type="radio" name="organization" value="big">
          <span>BIG</span>
        </label>
      </div>
    </div>

    <div class="wm-card">
      <div class="wm-card-head wm-card-head--filter">
        <p class="wm-section-title">Spatial Filter</p>
        <button id="clearSpatialBtn" class="wm-ghost-btn" type="button">Reset</button>
      </div>

      <div class="wm-mode-grid">
        <button class="wm-mode-btn is-active" type="button" data-mode="province">Provinsi</button>
        <button class="wm-mode-btn" type="button" data-mode="draw">Draw</button>
        <button class="wm-mode-btn" type="button" data-mode="upload">Upload</button>
      </div>

      <div class="wm-mode-panel is-active" data-panel="province">
        <label class="wm-field-label" for="provinceSelect">AOI Provinsi</label>
        <select id="provinceSelect" class="wm-select">
          <option value="">Semua provinsi</option>
        </select>
      </div>

      <div class="wm-mode-panel" data-panel="draw">
        <p class="wm-helper">
          Gunakan toolbar Leaflet di peta untuk membuat polygon, rectangle, circle, atau point.
        </p>
        <label class="wm-field-label" for="pointBufferInput">Buffer point/circle (meter)</label>
        <input id="pointBufferInput" class="wm-input" type="number" min="0" value="1000">
        <p id="drawStatus" class="wm-status-line">Belum ada boundary digambar.</p>
      </div>

      <div class="wm-mode-panel" data-panel="upload">
        <label class="wm-field-label" for="geometryFileInput">Boundary file</label>
        <input id="geometryFileInput" class="wm-input" type="file" accept=".json,.geojson,.kml">
        <p id="uploadStatus" class="wm-status-line">GeoJSON dan KML didukung pada draft ini.</p>
      </div>
    </div>

    <div class="wm-card">
      <p class="wm-section-title">Dataset Aktif</p>
      <div class="wm-dataset-meta">
        <span id="datasetBadge" class="wm-badge">Loading</span>
        <span id="datasetAvailability" class="wm-badge wm-badge--soft">...</span>
      </div>
      <h3 id="datasetTitle" class="wm-dataset-title">Memuat dataset...</h3>
      <p id="datasetNote" class="wm-copy wm-copy--small"></p>

      <div class="wm-stats">
        <div class="wm-stat-box">
          <span class="wm-stat-label">Fitur termuat</span>
          <strong id="featureCount">0</strong>
        </div>
        <div class="wm-stat-box">
          <span class="wm-stat-label">Area aktif</span>
          <strong id="filterLabel">Viewport</strong>
        </div>
      </div>
    </div>

    <div class="wm-actions">
      <button id="downloadVectorBtn" class="wm-secondary-btn" type="button">Unduh Vector</button>
      <button id="downloadRasterBtn" class="wm-secondary-btn" type="button">Unduh Raster</button>
      <button id="downloadMetadataBtn" class="wm-secondary-btn" type="button">Unduh Metadata</button>
    </div>
  </aside>

  <section class="wm-stage">
    <div class="wm-stage-toolbar">
      <form id="mapSearchForm" class="wm-search-form">
        <input id="mapSearchInput" type="text" placeholder="Cari lokasi atau ketik: -6.9, 107.6">
        <button type="submit">Cari</button>
      </form>
    </div>

    <div id="map"></div>

    <div class="wm-legend">
      <strong id="legendTitle">Legenda</strong>
      <div class="wm-legend-bar"></div>
      <div id="legendScale" class="wm-legend-scale"></div>
      <p id="legendDesc">Preview layer aktif</p>
    </div>

    <aside id="detailDrawer" class="wm-drawer">
      <div class="wm-drawer-head">
        <div>
          <p class="wm-eyebrow wm-eyebrow--light">Metadata</p>
          <h3 id="drawerTitle">Pilih fitur di peta</h3>
          <p id="drawerSubtitle" class="wm-drawer-subtitle">Ringkasan dan detail akan muncul di sini.</p>
        </div>
        <button id="drawerCloseBtn" class="wm-drawer-close" type="button">&times;</button>
      </div>

      <div id="drawerSummary" class="wm-detail-list"></div>
      <div id="drawerDetails" class="wm-detail-list wm-detail-list--details"></div>
      <p id="drawerNote" class="wm-copy wm-copy--small"></p>

      <div class="wm-drawer-actions">
        <button id="drawerPrimaryBtn" class="wm-primary-btn" type="button">Aksi</button>
        <button id="drawerSecondaryBtn" class="wm-secondary-btn" type="button">Zoom ke fitur</button>
      </div>
    </aside>
  </section>
</main>

<div id="webmapToast" class="wm-toast"></div>

<script>
  window.WEBMAP_CONFIG = {
    bootstrapUrl: "<?= site_url('webmap/bootstrap') ?>",
    provincesUrl: "<?= site_url('webmap/provinces') ?>",
    layerUrl: "<?= site_url('webmap/layer') ?>",
    featureMetaBase: "<?= site_url('webmap/feature-meta') ?>",
    downloadVectorUrl: "<?= site_url('webmap/download/vector') ?>",
    downloadMetadataUrl: "<?= site_url('webmap/download/metadata') ?>",
    clipRasterUrl: "<?= site_url('webmap/clip/raster') ?>",
    downloadRasterGridBase: "<?= site_url('webmap/download/raster/grid') ?>",
    downloadRasterProvinceBase: "<?= site_url('webmap/download/raster/province') ?>"
  };
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tmcw/togeojson@5.8.1/dist/togeojson.umd.js"></script>
<script src="<?= base_url('site/js/webmap.js?v=2'); ?>"></script>

<script>
/* ============================================================
   GravPort WebMap — Creative Layer
============================================================ */
(function () {

  /* --- Cinematic loader --- */
  var loader  = document.getElementById('wmLoader');
  var statusEl = document.getElementById('wmLoaderStatus');
  var stage   = document.querySelector('.wm-stage');

  var statusMessages = [
    'Initializing map layers…',
    'Loading gravity dataset…',
    'Preparing WebMap…',
  ];
  var msgIdx = 0;

  if (loader) {
    /* Cycle status messages */
    var msgTimer = setInterval(function () {
      msgIdx = (msgIdx + 1) % statusMessages.length;
      if (statusEl) statusEl.textContent = statusMessages[msgIdx];
    }, 900);

    setTimeout(function () {
      loader.classList.add('is-visible');
      setTimeout(function () {
        loader.classList.add('is-loading');

        /* Dismiss after bar fills */
        setTimeout(function () {
          clearInterval(msgTimer);
          loader.classList.add('is-done');
          document.body.classList.remove('wm-loading');
          triggerPageIn();
          setTimeout(function () {
            if (loader.parentNode) loader.parentNode.removeChild(loader);
          }, 1100);
        }, 1700);
      }, 80);
    }, 50);
  } else {
    triggerPageIn();
  }

  function triggerPageIn() {
    /* Sidebar cards stagger entrance */
    document.querySelectorAll('.wm-card').forEach(function (card, i) {
      setTimeout(function () { card.classList.add('card-in'); }, i * 80);
    });

    /* Map stage reveal */
    if (stage) setTimeout(function () { stage.classList.add('stage-in'); }, 120);
  }

  /* --- Stat box pulse on DOM mutation --- */
  function patchStatEl(el) {
    if (!el) return;
    var prev = el.textContent;
    var obs = new MutationObserver(function () {
      if (el.textContent !== prev) {
        prev = el.textContent;
        el.classList.remove('val-bump');
        void el.offsetWidth; /* reflow */
        el.classList.add('val-bump');
        setTimeout(function () { el.classList.remove('val-bump'); }, 320);
      }
    });
    obs.observe(el, { characterData: true, childList: true, subtree: true });
  }
  patchStatEl(document.getElementById('featureCount'));
  patchStatEl(document.getElementById('filterLabel'));

  /* --- Scroll progress bar --- */
  var bar = document.getElementById('wmProgress');
  if (bar) {
    window.addEventListener('scroll', function () {
      var doc = document.documentElement;
      var pct = doc.scrollHeight > window.innerHeight
        ? (window.scrollY / (doc.scrollHeight - window.innerHeight)) * 100
        : 100;
      bar.style.width = Math.min(100, pct) + '%';
    }, { passive: true });
  }

})();
</script>

</body>
</html>
