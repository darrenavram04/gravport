<?php // Passed: $baseUrl (string) ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>REST API Documentation - GravPort</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Poppins', sans-serif;
  background: radial-gradient(circle at top right, rgba(167,96,37,.14), transparent 28%),
              linear-gradient(180deg, #eff4f7 0%, #dfe7ee 100%);
  min-height: 100vh;
  color: #142033;
}
.wrap { max-width: 900px; margin: 0 auto; padding: 32px 16px 80px; }

/* Hero */
.hero { text-align: center; margin-bottom: 48px; }
.eyebrow { font-size: .75rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #a76025; margin-bottom: 8px; }
h1 { font-size: clamp(1.6rem, 3vw, 2.4rem); font-weight: 800; color: #142033; margin-bottom: 12px; }
.lead { font-size: .95rem; color: #4a6080; line-height: 1.7; max-width: 640px; margin: 0 auto; }

/* Sidebar nav */
.doc-layout { display: grid; grid-template-columns: 200px 1fr; gap: 32px; align-items: start; }
@media (max-width: 700px) { .doc-layout { grid-template-columns: 1fr; } }
.toc { position: sticky; top: 100px; }
.toc-title { font-size: .7rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #6b7280; margin-bottom: 10px; }
.toc a { display: block; font-size: .82rem; color: #4a6080; padding: 4px 0; text-decoration: none; border-left: 2px solid transparent; padding-left: 8px; }
.toc a:hover { color: #a76025; border-left-color: #a76025; }
@media (max-width: 700px) { .toc { position: static; } }

/* Section */
.doc-section { margin-bottom: 48px; }
.section-title { font-size: 1.15rem; font-weight: 800; color: #142033; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid rgba(20,32,51,.08); display: flex; align-items: center; gap: 8px; }
.section-title i { color: #a76025; }

/* Info cards */
.info-card { background: #fff; border: 1px solid rgba(20,32,51,.08); border-radius: 14px; padding: 18px 22px; margin-bottom: 16px; box-shadow: 0 2px 12px rgba(16,24,40,.05); }
.info-card h4 { font-size: .88rem; font-weight: 700; color: #142033; margin-bottom: 8px; }
.info-card p { font-size: .85rem; color: #4a6080; line-height: 1.65; }

/* Base URL box */
.base-url-box { background: #142033; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; }
.base-url-box .label { font-size: .7rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.4); margin-bottom: 6px; }
.base-url-box .url { font-family: 'JetBrains Mono', monospace; font-size: .9rem; color: #ffbf74; word-break: break-all; }

/* Endpoint cards */
.endpoint { background: #fff; border: 1px solid rgba(20,32,51,.08); border-radius: 16px; padding: 20px 24px; margin-bottom: 14px; box-shadow: 0 2px 12px rgba(16,24,40,.05); }
.endpoint-line { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; }
.method { display: inline-flex; align-items: center; height: 24px; padding: 0 10px; border-radius: 6px; font-size: .72rem; font-weight: 800; letter-spacing: .06em; font-family: 'JetBrains Mono', monospace; }
.method-get  { background: rgba(26,122,74,.12); color: #1a7a4a; }
.method-post { background: rgba(167,96,37,.12); color: #8b4a17; }
.endpoint-path { font-family: 'JetBrains Mono', monospace; font-size: .85rem; color: #142033; font-weight: 600; }
.auth-badge { font-size: .7rem; font-weight: 700; padding: 2px 8px; border-radius: 99px; background: rgba(167,96,37,.1); color: #8b4a17; margin-left: auto; white-space: nowrap; }
.auth-badge.pub { background: rgba(26,122,74,.1); color: #1a7a4a; }
.endpoint-desc { font-size: .85rem; color: #4a6080; line-height: 1.6; margin-bottom: 12px; }
.params-table { width: 100%; border-collapse: collapse; font-size: .8rem; margin-bottom: 10px; }
.params-table th { text-align: left; padding: 5px 8px; color: #6b7280; font-weight: 600; border-bottom: 1px solid rgba(20,32,51,.08); }
.params-table td { padding: 6px 8px; border-bottom: 1px solid rgba(20,32,51,.04); color: #142033; }
.params-table tr:last-child td { border-bottom: none; }
.param-name { font-family: 'JetBrains Mono', monospace; color: #a76025; font-weight: 600; }
.param-opt { font-size: .68rem; background: rgba(20,32,51,.07); padding: 1px 5px; border-radius: 4px; color: #6b7280; vertical-align: middle; }
.param-req { font-size: .68rem; background: rgba(167,96,37,.12); padding: 1px 5px; border-radius: 4px; color: #8b4a17; vertical-align: middle; }

/* Code block */
.code-block { background: #0f1924; border-radius: 10px; padding: 14px 18px; font-family: 'JetBrains Mono', monospace; font-size: .78rem; color: #c9d8e8; overflow-x: auto; margin-top: 10px; line-height: 1.65; }
.code-block .comment { color: #4a6a8a; }
.code-block .key     { color: #79b8ff; }
.code-block .string  { color: #9ecbff; }
.code-block .number  { color: #f8c555; }

/* Tier access table */
.tier-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.tier-table th { text-align: left; padding: 8px 12px; color: #6b7280; font-weight: 700; border-bottom: 2px solid rgba(20,32,51,.08); font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; }
.tier-table td { padding: 10px 12px; border-bottom: 1px solid rgba(20,32,51,.05); color: #142033; }
.tier-table tr:last-child td { border-bottom: none; }
.tick { color: #1a7a4a; font-size: 1rem; }
.cross { color: #b91c1c; font-size: .9rem; }

/* Alert boxes */
.alert { border-radius: 12px; padding: 14px 18px; font-size: .85rem; line-height: 1.65; margin-bottom: 16px; }
.alert-info  { background: rgba(26,108,199,.06); border: 1px solid rgba(26,108,199,.18); color: #1a4c8b; }
.alert-warn  { background: rgba(251,191,36,.08); border: 1px solid rgba(251,191,36,.25); color: #92400e; }
.alert-tip   { background: rgba(26,122,74,.07); border: 1px solid rgba(26,122,74,.2); color: #155836; }

/* Language tabs */
.code-tabs { display: flex; gap: 6px; margin-bottom: 0; }
.code-tab { padding: 5px 12px; border-radius: 8px 8px 0 0; font-size: .75rem; font-weight: 700; cursor: pointer; border: 1px solid transparent; border-bottom: none; background: rgba(20,32,51,.06); color: #6b7280; }
.code-tab.active { background: #0f1924; color: #c9d8e8; }
.code-panel { display: none; }
.code-panel.active { display: block; }

/* CRS box */
.crs-box { background: rgba(26,108,199,.06); border: 1px solid rgba(26,108,199,.18); border-radius: 12px; padding: 14px 18px; font-size: .84rem; color: #1a4c8b; line-height: 1.6; margin-bottom: 20px; }
</style>
</head>
<body>
<?php echo view('partials/site_header', ['activePage' => 'api']); ?>

<div class="wrap">

  <div class="hero">
    <div class="eyebrow"><i class="bi bi-code-slash"></i> REST API</div>
    <h1>GravPort REST API v1</h1>
    <p class="lead">
      Akses data gravitasi GravPort secara programatik - query titik, unduh GeoJSON, cek kuota, dan integrasikan ke workflow Python, R, atau aplikasi kustom Anda.
    </p>
  </div>

  <div class="doc-layout">

    <!-- TOC -->
    <nav class="toc">
      <div class="toc-title">Konten</div>
      <a href="#overview">Overview</a>
      <a href="#auth">Autentikasi</a>
      <a href="#crs">CRS & Koordinat</a>
      <a href="#endpoints">Endpoints</a>
      <a href="#health">Health Check</a>
      <a href="#datasets">Datasets</a>
      <a href="#points">Points (GeoJSON)</a>
      <a href="#catalog">Catalog</a>
      <a href="#user">User / Quota</a>
      <a href="#errors">Error Codes</a>
      <a href="#examples">Contoh Kode</a>
      <a href="#tier-access">Tier Access</a>
    </nav>

    <!-- Main docs -->
    <div>

      <!-- Overview -->
      <section class="doc-section" id="overview">
        <div class="section-title"><i class="bi bi-info-circle-fill"></i> Overview</div>

        <div class="base-url-box">
          <div class="label">Base URL</div>
          <div class="url"><?= site_url('api/v1') ?></div>
        </div>

        <div class="info-card">
          <h4>Format & Protokol</h4>
          <p>
            Semua response menggunakan format <strong>JSON</strong> dengan wrapper konsisten:<br>
            <code style="font-family:monospace;font-size:.85em">{"status":"ok","data":{...}}</code> untuk sukses,<br>
            <code style="font-family:monospace;font-size:.85em">{"status":"error","message":"..."}</code> untuk error.<br><br>
            GeoJSON endpoints tambahan menggunakan struktur GeoJSON standar RFC 7946.
            Semua request dan response menggunakan encoding <strong>UTF-8</strong>.
          </p>
        </div>

        <div class="info-card">
          <h4>Rate Limiting</h4>
          <p>
            Untuk endpoint /points (GeoJSON): maksimum BBOX <strong>1° × 1°</strong>, maksimum <strong>10.000 titik</strong> per request.<br>
            Untuk endpoint lain: tidak ada rate limit eksplisit saat ini. Gunakan secara wajar.
          </p>
        </div>
      </section>

      <!-- Auth -->
      <section class="doc-section" id="auth">
        <div class="section-title"><i class="bi bi-key-fill"></i> Autentikasi</div>

        <div class="alert alert-info">
          <strong>API Key diperlukan</strong> untuk semua endpoint kecuali <code style="font-family:monospace">/api/v1/health</code>.
          Buat API key di <a href="<?= site_url('account') ?>#api-keys" style="color:#1a4c8b"><strong>halaman Akun → API Keys</strong></a>.
        </div>

        <div class="info-card">
          <h4>Header Autentikasi</h4>
          <p style="margin-bottom:10px">Sertakan API key di setiap request via header <code style="font-family:monospace">Authorization</code>:</p>
          <div class="code-block">Authorization: Bearer gp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</div>
        </div>

        <div class="info-card">
          <h4>Format API Key</h4>
          <p>API key memiliki prefix <strong><code style="font-family:monospace">gp_</code></strong> diikuti 32 karakter hex acak (total 35 karakter).
          Key hanya ditampilkan <strong>sekali</strong> saat dibuat - simpan di tempat aman seperti environment variable.
          Jika hilang, cabut key lama dan buat baru di halaman Akun.</p>
        </div>

        <div class="info-card">
          <h4>Scope API Key</h4>
          <p>
            <strong>read</strong> - Akses datasets, catalog, quota, health.<br>
            <strong>download</strong> - Akses endpoint /points (download GeoJSON). Memerlukan tier Pro atau lebih tinggi.
          </p>
        </div>
      </section>

      <!-- CRS -->
      <section class="doc-section" id="crs">
        <div class="section-title"><i class="bi bi-pin-map-fill"></i> CRS & Koordinat</div>

        <div class="crs-box">
          <strong>Sistem Koordinat Referensi (CRS):</strong> Semua data GravPort menggunakan <strong>EPSG:4326 (WGS84)</strong> - koordinat geografis lintang/bujur desimal.<br><br>
          Semua GeoJSON response menyertakan deklarasi CRS:<br>
          <code style="font-family:monospace;font-size:.82em">"crs": {"type":"name","properties":{"name":"EPSG:4326"}}</code><br><br>
          Header HTTP tambahan: <code style="font-family:monospace">X-GravPort-CRS: EPSG:4326</code><br><br>
          Cakupan area: <strong>Jawa-Bali, Indonesia</strong> (BBox: 105°-116° BT, -9°--5.5° LS / 5.5°-9° LS).<br>
          Data Jawa membentang di zona UTM 48S dan 49S - proyeksi UTM tersedia via konversi di sisi client.
        </div>
      </section>

      <!-- Endpoints -->
      <section class="doc-section" id="endpoints">
        <div class="section-title"><i class="bi bi-diagram-3-fill"></i> Daftar Endpoint</div>

        <!-- Health -->
        <div id="health">
          <div class="endpoint">
            <div class="endpoint-line">
              <span class="method method-get">GET</span>
              <span class="endpoint-path">/api/v1/health</span>
              <span class="auth-badge pub">Publik</span>
            </div>
            <div class="endpoint-desc">Status API dan informasi layanan. Tidak memerlukan API key. Cocok untuk health check monitoring.</div>
            <div class="code-block">{
  <span class="key">"status"</span>: <span class="string">"ok"</span>,
  <span class="key">"service"</span>: <span class="string">"GravPort REST API"</span>,
  <span class="key">"version"</span>: <span class="string">"1.0"</span>,
  <span class="key">"crs"</span>: <span class="string">"EPSG:4326"</span>,
  <span class="key">"coverage"</span>: <span class="string">"Jawa-Bali, Indonesia"</span>,
  <span class="key">"ts"</span>: <span class="string">"2026-05-28T08:00:00+07:00"</span>
}</div>
          </div>
        </div>

        <!-- Datasets -->
        <div id="datasets">
          <div class="endpoint">
            <div class="endpoint-line">
              <span class="method method-get">GET</span>
              <span class="endpoint-path">/api/v1/datasets</span>
              <span class="auth-badge">API Key</span>
            </div>
            <div class="endpoint-desc">Daftar semua dataset gravitasi yang tersedia. Field <code style="font-family:monospace">accessible</code> menunjukkan apakah tier pengguna dapat mengakses dataset tersebut.</div>
            <div class="code-block">{
  <span class="key">"status"</span>: <span class="string">"ok"</span>,
  <span class="key">"data"</span>: [
    {
      <span class="key">"dataset_id"</span>: <span class="number">1</span>,
      <span class="key">"dataset_code"</span>: <span class="string">"JAVA-FAA-L1"</span>,
      <span class="key">"dataset_name"</span>: <span class="string">"Jawa FAA Level 1"</span>,
      <span class="key">"anom_type"</span>: <span class="string">"FAA"</span>,
      <span class="key">"data_level"</span>: <span class="number">1</span>,
      <span class="key">"point_count"</span>: <span class="number">628000</span>,
      <span class="key">"accessible"</span>: <span class="string">true</span>,
      <span class="key">"points_endpoint"</span>: <span class="string">"/api/v1/datasets/JAVA-FAA-L1/points"</span>
    }
  ]
}</div>
          </div>

          <div class="endpoint">
            <div class="endpoint-line">
              <span class="method method-get">GET</span>
              <span class="endpoint-path">/api/v1/datasets/{code}</span>
              <span class="auth-badge">API Key</span>
            </div>
            <div class="endpoint-desc">Detail satu dataset berdasarkan <code style="font-family:monospace">dataset_code</code>. Termasuk metadata XML dan info CRS.</div>
          </div>
        </div>

        <!-- Points -->
        <div id="points">
          <div class="endpoint">
            <div class="endpoint-line">
              <span class="method method-get">GET</span>
              <span class="endpoint-path">/api/v1/datasets/{code}/points</span>
              <span class="auth-badge">API Key + download</span>
            </div>
            <div class="endpoint-desc">
              Download titik gravitasi sebagai <strong>GeoJSON FeatureCollection</strong> dalam area BBOX tertentu.
              Memerlukan scope <code style="font-family:monospace">download</code> dan tier <strong>Pro atau lebih tinggi</strong> untuk level 2.
            </div>
            <table class="params-table">
              <thead><tr><th>Parameter</th><th>Tipe</th><th>Keterangan</th></tr></thead>
              <tbody>
                <tr>
                  <td><span class="param-name">bbox</span> <span class="param-req">wajib</span></td>
                  <td>string</td>
                  <td>Format: <code style="font-family:monospace">minLon,minLat,maxLon,maxLat</code> - maksimum 1° × 1°</td>
                </tr>
                <tr>
                  <td><span class="param-name">anom_type</span> <span class="param-opt">opsional</span></td>
                  <td>string</td>
                  <td><code style="font-family:monospace">FAA</code> (default) atau <code style="font-family:monospace">CBA</code></td>
                </tr>
                <tr>
                  <td><span class="param-name">level</span> <span class="param-opt">opsional</span></td>
                  <td>integer</td>
                  <td><code style="font-family:monospace">1</code> (default) atau <code style="font-family:monospace">2</code> (Pro+ only)</td>
                </tr>
                <tr>
                  <td><span class="param-name">limit</span> <span class="param-opt">opsional</span></td>
                  <td>integer</td>
                  <td>Maksimum titik dikembalikan (default: 10000, maks: 10000)</td>
                </tr>
              </tbody>
            </table>
            <div class="code-block">{
  <span class="key">"type"</span>: <span class="string">"FeatureCollection"</span>,
  <span class="key">"crs"</span>: {<span class="key">"type"</span>: <span class="string">"name"</span>, <span class="key">"properties"</span>: {<span class="key">"name"</span>: <span class="string">"EPSG:4326"</span>}},
  <span class="key">"bbox"</span>: [<span class="number">106.7</span>, <span class="number">-6.3</span>, <span class="number">107.2</span>, <span class="number">-5.9</span>],
  <span class="key">"features"</span>: [
    {
      <span class="key">"type"</span>: <span class="string">"Feature"</span>,
      <span class="key">"geometry"</span>: {<span class="key">"type"</span>: <span class="string">"Point"</span>, <span class="key">"coordinates"</span>: [<span class="number">106.832</span>, <span class="number">-6.175</span>]},
      <span class="key">"properties"</span>: {
        <span class="key">"point_id"</span>: <span class="number">12345</span>,
        <span class="key">"point_value"</span>: <span class="number">-23.7</span>,
        <span class="key">"point_anom_type"</span>: <span class="string">"FAA"</span>,
        <span class="key">"data_level"</span>: <span class="number">1</span>,
        <span class="key">"point_obs_type"</span>: <span class="string">"terrestrial"</span>
      }
    }
  ],
  <span class="key">"total_features"</span>: <span class="number">4218</span>
}</div>
          </div>
        </div>

        <!-- Catalog -->
        <div id="catalog">
          <div class="endpoint">
            <div class="endpoint-line">
              <span class="method method-get">GET</span>
              <span class="endpoint-path">/api/v1/catalog</span>
              <span class="auth-badge">API Key</span>
            </div>
            <div class="endpoint-desc">Katalog dataset dengan filter. Sama seperti /datasets namun mendukung pencarian teks dan filter parameter.</div>
            <table class="params-table">
              <thead><tr><th>Parameter</th><th>Keterangan</th></tr></thead>
              <tbody>
                <tr><td><span class="param-name">q</span> <span class="param-opt">opsional</span></td><td>Cari berdasarkan nama atau deskripsi dataset</td></tr>
                <tr><td><span class="param-name">level</span> <span class="param-opt">opsional</span></td><td>Filter: <code style="font-family:monospace">1</code> atau <code style="font-family:monospace">2</code></td></tr>
                <tr><td><span class="param-name">anom_type</span> <span class="param-opt">opsional</span></td><td>Filter: <code style="font-family:monospace">FAA</code> atau <code style="font-family:monospace">CBA</code></td></tr>
                <tr><td><span class="param-name">scope</span> <span class="param-opt">opsional</span></td><td>Filter: <code style="font-family:monospace">public</code> atau <code style="font-family:monospace">premium</code></td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- User -->
        <div id="user">
          <div class="endpoint">
            <div class="endpoint-line">
              <span class="method method-get">GET</span>
              <span class="endpoint-path">/api/v1/user/quota</span>
              <span class="auth-badge">API Key</span>
            </div>
            <div class="endpoint-desc">Informasi kuota dan tier pengguna saat ini (API key holder).</div>
            <div class="code-block">{
  <span class="key">"status"</span>: <span class="string">"ok"</span>,
  <span class="key">"data"</span>: {
    <span class="key">"tier"</span>: <span class="string">"pro"</span>,
    <span class="key">"quota_used"</span>: <span class="number">42</span>,
    <span class="key">"quota_total"</span>: <span class="string">"unlimited"</span>,
    <span class="key">"can_access_level2"</span>: <span class="string">true</span>
  }
}</div>
          </div>

          <div class="endpoint">
            <div class="endpoint-line">
              <span class="method method-get">GET</span>
              <span class="endpoint-path">/api/v1/user/downloads</span>
              <span class="auth-badge">API Key</span>
            </div>
            <div class="endpoint-desc">Riwayat download terbaru (maksimum 100 record). Diurutkan terbaru dahulu.</div>
          </div>
        </div>
      </section>

      <!-- Errors -->
      <section class="doc-section" id="errors">
        <div class="section-title"><i class="bi bi-exclamation-triangle-fill"></i> Kode Error</div>
        <div class="info-card">
          <table class="params-table" style="margin-bottom:0">
            <thead><tr><th>HTTP Status</th><th>Kode</th><th>Penyebab</th></tr></thead>
            <tbody>
              <tr><td><strong>200</strong></td><td>ok</td><td>Request berhasil</td></tr>
              <tr><td><strong>400</strong></td><td>bad_request</td><td>Parameter tidak valid (mis. bbox format salah atau melebihi 1°×1°)</td></tr>
              <tr><td><strong>401</strong></td><td>unauthorized</td><td>API key tidak ada, tidak valid, atau sudah dicabut</td></tr>
              <tr><td><strong>403</strong></td><td>forbidden</td><td>Tier pengguna tidak mendukung akses ini (mis. level 2 butuh Pro)</td></tr>
              <tr><td><strong>404</strong></td><td>not_found</td><td>Dataset code tidak ditemukan</td></tr>
              <tr><td><strong>429</strong></td><td>rate_limited</td><td>BBOX terlalu besar atau request terlalu banyak</td></tr>
              <tr><td><strong>500</strong></td><td>server_error</td><td>Error internal server</td></tr>
            </tbody>
          </table>
        </div>
        <div class="code-block">{
  <span class="key">"status"</span>: <span class="string">"error"</span>,
  <span class="key">"code"</span>: <span class="string">"forbidden"</span>,
  <span class="key">"message"</span>: <span class="string">"Level 2 data requires Pro tier or above."</span>
}</div>
      </section>

      <!-- Examples -->
      <section class="doc-section" id="examples">
        <div class="section-title"><i class="bi bi-terminal-fill"></i> Contoh Kode</div>

        <div class="info-card">
          <h4><i class="bi bi-terminal" style="color:#a76025"></i> cURL</h4>
          <div class="code-block"><span class="comment"># Health check (tanpa API key)</span>
curl <?= site_url('api/v1/health') ?>


<span class="comment"># Daftar datasets</span>
curl -H <span class="string">"Authorization: Bearer gp_your_key_here"</span> \
     <?= site_url('api/v1/datasets') ?>


<span class="comment"># Download titik FAA dalam BBOX Jakarta</span>
curl -H <span class="string">"Authorization: Bearer gp_your_key_here"</span> \
     "<?= site_url('api/v1/datasets/JAVA-FAA-L1/points') ?>?bbox=106.7,-6.3,107.2,-5.9&anom_type=FAA" \
     -o jakarta_faa.geojson</div>
        </div>

        <div class="info-card">
          <h4><i class="bi bi-filetype-py" style="color:#a76025"></i> Python (requests)</h4>
          <div class="code-block"><span class="key">import</span> requests

API_KEY = <span class="string">"gp_your_key_here"</span>
BASE_URL = <span class="string">"<?= site_url('api/v1') ?>"</span>
HEADERS  = {<span class="string">"Authorization"</span>: f<span class="string">"Bearer {API_KEY}"</span>}

<span class="comment"># Cek quota</span>
r = requests.get(f<span class="string">"{BASE_URL}/user/quota"</span>, headers=HEADERS)
print(r.json())

<span class="comment"># Download GeoJSON titik gravitasi FAA di sekitar Bandung</span>
params = {
    <span class="string">"bbox"</span>: <span class="string">"107.4,-7.1,107.8,-6.8"</span>,
    <span class="string">"anom_type"</span>: <span class="string">"FAA"</span>,
    <span class="string">"level"</span>: <span class="number">1</span>,
}
r = requests.get(f<span class="string">"{BASE_URL}/datasets/JAVA-FAA-L1/points"</span>, headers=HEADERS, params=params)
geojson = r.json()
print(f<span class="string">"Diperoleh {geojson['total_features']} titik"</span>)

<span class="comment"># Simpan ke file</span>
<span class="key">import</span> json
<span class="key">with</span> open(<span class="string">"bandung_faa.geojson"</span>, <span class="string">"w"</span>) <span class="key">as</span> f:
    json.dump(geojson, f, indent=2)</div>
        </div>

        <div class="info-card">
          <h4><i class="bi bi-map" style="color:#a76025"></i> QGIS - Add WFS Layer via REST API</h4>
          <p style="margin-bottom:10px">Untuk akses GIS langsung, gunakan endpoint OGC WFS:</p>
          <div class="code-block"><span class="comment"># Di QGIS: Layer → Add Layer → Add WFS Layer</span>
URL: <?= site_url('ogc/wfs') ?>

<span class="comment"># Atau gunakan OGC WMS untuk tampilan:</span>
URL: <?= site_url('ogc/wms') ?>

<span class="comment"># Lihat halaman OGC untuk detail lebih lanjut</span></div>
          <p style="margin-top:10px;font-size:.82rem;color:#6b7280">
            Untuk akses programatik yang lebih fleksibel, gunakan endpoint REST API /points di atas - mendukung filter BBOX, anom_type, dan level secara langsung.
          </p>
        </div>

        <div class="info-card">
          <h4><i class="bi bi-bar-chart-fill" style="color:#a76025"></i> Python - Visualisasi dengan GeoPandas</h4>
          <div class="code-block"><span class="key">import</span> requests
<span class="key">import</span> geopandas <span class="key">as</span> gpd
<span class="key">import</span> matplotlib.pyplot <span class="key">as</span> plt

API_KEY = <span class="string">"gp_your_key_here"</span>
headers = {<span class="string">"Authorization"</span>: f<span class="string">"Bearer {API_KEY}"</span>}

<span class="comment"># Ambil data FAA di Jawa Tengah</span>
r = requests.get(
    <span class="string">"<?= site_url('api/v1/datasets/JAVA-FAA-L1/points') ?>"</span>,
    headers=headers,
    params={<span class="string">"bbox"</span>: <span class="string">"108.0,-7.8,111.0,-6.0"</span>, <span class="string">"limit"</span>: <span class="number">5000</span>}
)

<span class="comment"># Buat GeoDataFrame langsung dari GeoJSON</span>
gdf = gpd.GeoDataFrame.from_features(r.json()[<span class="string">"features"</span>], crs=<span class="string">"EPSG:4326"</span>)

<span class="comment"># Plot anomali</span>
fig, ax = plt.subplots(figsize=(<span class="number">10</span>, <span class="number">8</span>))
gdf.plot(ax=ax, column=<span class="string">"point_value"</span>, cmap=<span class="string">"RdBu_r"</span>,
         markersize=<span class="number">2</span>, legend=<span class="string">True</span>)
ax.set_title(<span class="string">"Anomali Gravitasi FAA - Jawa Tengah"</span>)
plt.savefig(<span class="string">"jawa_tengah_faa.png"</span>, dpi=<span class="number">150</span>)
plt.show()</div>
        </div>
      </section>

      <!-- Tier Access -->
      <section class="doc-section" id="tier-access">
        <div class="section-title"><i class="bi bi-shield-check"></i> Tier Access Matrix</div>
        <div class="info-card">
          <table class="tier-table">
            <thead>
              <tr>
                <th>Endpoint</th>
                <th>Lite (Solo)</th>
                <th>Pro</th>
                <th>Team</th>
                <th>Enterprise</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>/api/v1/health</td>
                <td><span class="tick">✓</span> Publik</td>
                <td><span class="tick">✓</span></td>
                <td><span class="tick">✓</span></td>
                <td><span class="tick">✓</span></td>
              </tr>
              <tr>
                <td>/api/v1/datasets</td>
                <td><span class="cross">✗</span></td>
                <td><span class="tick">✓</span></td>
                <td><span class="tick">✓</span></td>
                <td><span class="tick">✓</span></td>
              </tr>
              <tr>
                <td>/points Level 1</td>
                <td><span class="cross">✗</span></td>
                <td><span class="tick">✓</span></td>
                <td><span class="tick">✓</span></td>
                <td><span class="tick">✓</span></td>
              </tr>
              <tr>
                <td>/points Level 2</td>
                <td><span class="cross">✗</span></td>
                <td><span class="tick">✓</span></td>
                <td><span class="tick">✓</span></td>
                <td><span class="tick">✓</span></td>
              </tr>
              <tr>
                <td>/user/quota</td>
                <td><span class="cross">✗</span></td>
                <td><span class="tick">✓</span></td>
                <td><span class="tick">✓</span></td>
                <td><span class="tick">✓</span></td>
              </tr>
              <tr>
                <td>Jumlah API Key</td>
                <td>-</td>
                <td>Maks 5</td>
                <td>Maks 5 / anggota</td>
                <td>Unlimited</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="alert alert-warn">
          <strong>Catatan:</strong> API key hanya tersedia untuk pengguna tier <strong>Pro atau lebih tinggi</strong>.
          Pengguna Lite tidak mendapatkan akses ke REST API.
          <a href="<?= site_url('account/upgrade') ?>" style="color:#92400e;font-weight:700">Upgrade sekarang →</a>
        </div>

        <div style="text-align:center;margin-top:28px;display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
          <a href="<?= site_url('account') ?>#api-keys" style="color:#a76025;font-size:.88rem;font-weight:700;text-decoration:none">
            <i class="bi bi-key-fill"></i> Buat API Key →
          </a>
          <a href="<?= site_url('ogc') ?>" style="color:#4a6080;font-size:.88rem;font-weight:600;text-decoration:none">
            <i class="bi bi-globe2"></i> OGC Services (WMS/WFS/CSW) →
          </a>
        </div>
      </section>

    </div>
  </div>
</div>

</body>
</html>

