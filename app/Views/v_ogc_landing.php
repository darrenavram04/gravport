<?php // Passed: $wmsUrl, $wfsUrl, $cswUrl ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OGC Web Services - GravPort</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Poppins', sans-serif; background: radial-gradient(circle at top right, rgba(167,96,37,.14), transparent 28%), linear-gradient(180deg, #eff4f7 0%, #dfe7ee 100%); min-height: 100vh; color: #142033; }
.wrap { max-width: 860px; margin: 0 auto; padding: 32px 16px 80px; }
.hero { text-align: center; margin-bottom: 40px; }
.eyebrow { font-size: .75rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #a76025; margin-bottom: 8px; }
h1 { font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 800; color: #142033; margin-bottom: 12px; }
.lead { font-size: .95rem; color: #4a6080; line-height: 1.7; max-width: 600px; margin: 0 auto; }
.ogc-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 40px; }
.ogc-card { background: #fff; border: 1px solid rgba(20,32,51,.08); border-radius: 18px; padding: 24px 22px; box-shadow: 0 4px 20px rgba(16,24,40,.07); }
.ogc-icon { font-size: 2rem; color: #a76025; margin-bottom: 12px; }
.ogc-title { font-size: 1rem; font-weight: 700; color: #142033; margin-bottom: 4px; }
.ogc-subtitle { font-size: .78rem; color: #6b7280; margin-bottom: 12px; }
.ogc-desc { font-size: .84rem; color: #4a6080; line-height: 1.6; margin-bottom: 16px; }
.url-block { background: #f3f4f6; border-radius: 8px; padding: 8px 12px; font-family: 'JetBrains Mono', monospace; font-size: .75rem; color: #8b4a17; word-break: break-all; margin-bottom: 10px; }
.btn-ogc { display: inline-flex; align-items: center; gap: 6px; color: #fff; background: #a76025; border-radius: 10px; padding: 8px 16px; font-size: .82rem; font-weight: 700; text-decoration: none; }
.btn-ogc:hover { background: #8b4a17; }
.info-card { background: rgba(167,96,37,.07); border: 1px solid rgba(167,96,37,.2); border-radius: 14px; padding: 18px 22px; margin-bottom: 20px; }
.info-card h3 { font-size: .88rem; font-weight: 700; color: #8b4a17; margin-bottom: 8px; }
.info-card p { font-size: .85rem; color: #4a6080; line-height: 1.65; }
.crs-note { background: rgba(26,108,199,.06); border: 1px solid rgba(26,108,199,.15); border-radius: 12px; padding: 14px 18px; font-size: .85rem; color: #1a4c8b; line-height: 1.6; }
</style>
</head>
<body>
<?php echo view('partials/site_header', ['activePage' => 'ogc']); ?>

<div class="wrap">
  <div class="hero">
    <div class="eyebrow"><i class="bi bi-globe2"></i> Standar OGC</div>
    <h1>OGC Web Services GravPort</h1>
    <p class="lead">
      Akses data gravitasi GravPort langsung dari QGIS, ArcGIS Pro, dan aplikasi GIS lainnya menggunakan protokol standar OGC - WMS, WFS, dan CSW.
    </p>
  </div>

  <div class="crs-note" style="margin-bottom:24px">
    <i class="bi bi-pin-map-fill"></i> <strong>Sistem Koordinat (CRS):</strong>
    Semua data GravPort tersedia dalam <strong>EPSG:4326 (WGS84)</strong> - koordinat geografis lintang/bujur.
    Cakupan area: <strong>Jawa-Bali, Indonesia</strong> (BBox: 105°-116° BT, 5.5°-9° LS).
    Data Jawa membentang di zona UTM 48S dan 49S.
  </div>

  <div class="ogc-grid">

    <div class="ogc-card">
      <div class="ogc-icon"><i class="bi bi-map-fill"></i></div>
      <div class="ogc-title">WMS - Web Map Service</div>
      <div class="ogc-subtitle">OGC WMS 1.3.0</div>
      <div class="ogc-desc">Tampilkan layer anomali gravitasi (FAA & CBA) langsung di QGIS atau ArcGIS Pro sebagai layer peta. Titik dirender sebagai PNG tiles dengan gradasi warna biru→merah sesuai nilai anomali.</div>
      <div class="url-block"><?= esc($wmsUrl) ?></div>
      <div style="margin-bottom:10px;font-size:.78rem;color:#6b7280"><strong>Layer tersedia:</strong> gravport:gravity_faa | gravport:gravity_cba</div>
      <a href="<?= site_url('ogc/wms?SERVICE=WMS&REQUEST=GetCapabilities&VERSION=1.3.0') ?>" target="_blank" class="btn-ogc">
        <i class="bi bi-download"></i> GetCapabilities XML
      </a>
    </div>

    <div class="ogc-card">
      <div class="ogc-icon"><i class="bi bi-diagram-3-fill"></i></div>
      <div class="ogc-title">WFS - Web Feature Service</div>
      <div class="ogc-subtitle">OGC WFS 2.0.0</div>
      <div class="ogc-desc">Download fitur titik gravitasi sebagai GeoJSON atau GML langsung ke QGIS/skrip Python. Filter berdasarkan BBOX, jenis anomali (FAA/CBA), dan jumlah fitur.</div>
      <div class="url-block"><?= esc($wfsUrl) ?></div>
      <div style="margin-bottom:10px;font-size:.78rem;color:#6b7280"><strong>Feature type:</strong> gravport:gravity_points</div>
      <a href="<?= site_url('ogc/wfs?SERVICE=WFS&REQUEST=GetCapabilities&VERSION=2.0.0') ?>" target="_blank" class="btn-ogc">
        <i class="bi bi-download"></i> GetCapabilities XML
      </a>
    </div>

    <div class="ogc-card">
      <div class="ogc-icon"><i class="bi bi-archive-fill"></i></div>
      <div class="ogc-title">CSW - Catalog Service</div>
      <div class="ogc-subtitle">OGC CSW 2.0.2</div>
      <div class="ogc-desc">Query katalog metadata GravPort secara terstandar. Portal Ina-Geoportal dan Satu Peta dapat meng-harvest metadata dataset ini. Format Dublin Core + ISO 19115.</div>
      <div class="url-block"><?= esc($cswUrl) ?></div>
      <div style="margin-bottom:10px;font-size:.78rem;color:#6b7280"><strong>Output:</strong> Dublin Core XML | ISO 19115</div>
      <a href="<?= site_url('ogc/csw?SERVICE=CSW&REQUEST=GetCapabilities&VERSION=2.0.2') ?>" target="_blank" class="btn-ogc">
        <i class="bi bi-download"></i> GetCapabilities XML
      </a>
    </div>

  </div>

  <div class="info-card">
    <h3><i class="bi bi-info-circle-fill"></i> Cara Menambahkan Layer di QGIS</h3>
    <p>
      <strong>WMS:</strong> Layer → Tambah Layer → Tambah Layer WMS/WMTS → URL: <code style="font-family:monospace;background:rgba(167,96,37,.1);padding:1px 6px;border-radius:4px"><?= site_url('ogc/wms') ?></code><br>
      <strong>WFS:</strong> Layer → Tambah Layer → Tambah Layer WFS → URL: <code style="font-family:monospace;background:rgba(167,96,37,.1);padding:1px 6px;border-radius:4px"><?= site_url('ogc/wfs') ?></code><br><br>
      Akses WMS/WFS bersifat publik. Untuk download data via WFS GetFeature tanpa autentikasi, tersedia hingga 5.000 titik per permintaan (BBOX maks. 2°×2°).
      Untuk akses data programatik yang lebih lengkap, gunakan <a href="<?= site_url('api/docs') ?>" style="color:#a76025">REST API GravPort</a>.
    </p>
  </div>

  <div style="text-align:center;margin-top:20px">
    <a href="<?= site_url('api/docs') ?>" style="color:#a76025;font-size:.88rem;font-weight:600;text-decoration:none">
      <i class="bi bi-code-slash"></i> Lihat dokumentasi REST API →
    </a>
  </div>
</div>
</body>
</html>

