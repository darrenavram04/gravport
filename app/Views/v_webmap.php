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
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css'); ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/webmap.css'); ?>">
</head>
<body>

<?= view('partials/site_header', [
  'activePage' => 'webmap',
]) ?>

<main class="wm-page">
  <aside class="wm-sidebar">
    <div class="wm-card wm-card--hero">
      <p class="wm-eyebrow">Pengunduhan Data</p>
      <h1>Gravimetri Interaktif</h1>
      <p class="wm-copy">
        Mulai dari tampilan netral, lalu zoom ke area yang dibutuhkan untuk memuat detail titik atau grid secara ringan.
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
          <span class="wm-stat-label">Filter aktif</span>
          <strong id="filterLabel">Semua</strong>
        </div>
      </div>
    </div>

    <div class="wm-actions">
      <button id="previewBtn" class="wm-primary-btn" type="button">Preview Layer</button>
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
<script src="<?= base_url('site/js/webmap.js'); ?>"></script>

</body>
</html>
