<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($dataset['title']) ?> - GravPort Dataset View</title>

    <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=30'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css'); ?>">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

    <style>
        body.dataset-view-page {
            margin: 0;
            font-family: "Poppins", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(167, 96, 37, 0.18), transparent 28%),
                linear-gradient(180deg, #eef3f7 0%, #dfe7ee 100%);
            color: #142033;
        }

        .dataset-shell {
            max-width: 1220px;
            margin: 0 auto;
            padding: calc(var(--landing-header-offset) + 18px) 20px 36px;
        }

        .dataset-grid {
            display: grid;
            grid-template-columns: minmax(0, 360px) minmax(0, 1fr);
            gap: 18px;
        }

        .dataset-panel {
            border-radius: 26px;
            border: 1px solid rgba(20, 32, 51, 0.08);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 20px 50px rgba(16, 24, 40, 0.12);
        }

        .dataset-panel--info {
            padding: 24px;
        }

        .dataset-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(167, 96, 37, 0.1);
            color: #8b4a17;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .dataset-panel h1 {
            margin: 16px 0 10px;
            font-size: 32px;
            line-height: 1.08;
        }

        .dataset-panel p {
            margin: 0;
            line-height: 1.7;
            color: #4b5b70;
        }

        .dataset-meta {
            display: grid;
            gap: 12px;
            margin-top: 22px;
        }

        .dataset-meta__row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 18px;
            background: rgba(8, 17, 32, 0.04);
        }

        .dataset-meta__row span {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6b7a8f;
        }

        .dataset-meta__row strong {
            font-size: 14px;
            color: #142033;
            text-align: right;
        }

        .dataset-status {
            margin-top: 18px;
            padding: 14px 16px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(20, 32, 51, 0.06), rgba(167, 96, 37, 0.08));
            color: #314156;
        }

        .dataset-status strong {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #8b4a17;
        }

        .dataset-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 22px;
        }

        .dataset-btn {
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

        .dataset-btn--primary {
            background: #a76025;
            color: #fff;
        }

        .dataset-btn--secondary {
            background: rgba(8, 17, 32, 0.08);
            color: #142033;
        }

        .dataset-btn--ghost {
            background: rgba(8, 17, 32, 0.06);
            color: #142033;
        }

        .dataset-map-wrap {
            position: relative;
            overflow: hidden;
        }

        #map {
            min-height: 640px;
            border-radius: 26px;
        }

        .dataset-map-note {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 18px;
            z-index: 500;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(20, 32, 51, 0.84);
            color: #f4f7fb;
            box-shadow: 0 14px 34px rgba(16, 24, 40, 0.2);
        }

        .dataset-map-note strong {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #ffd6b5;
        }

        .dataset-map-note span {
            font-size: 14px;
            line-height: 1.5;
        }

        @media (max-width: 960px) {
            .dataset-grid {
                grid-template-columns: 1fr;
            }

            #map {
                min-height: 460px;
            }
        }

        /* Breadcrumb */
        .dataset-breadcrumb {
            padding: calc(var(--landing-header-offset, 80px) + 12px) 20px 0;
            max-width: 1220px;
            margin: 0 auto;
        }
        .dataset-breadcrumb ol {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px 2px;
            font-size: 13px;
            color: rgba(20, 32, 51, 0.55);
        }
        .dataset-breadcrumb li + li::before {
            content: "›";
            margin: 0 6px;
            opacity: 0.5;
        }
        .dataset-breadcrumb a {
            color: rgba(20, 32, 51, 0.65);
            text-decoration: none;
            transition: color 0.15s;
        }
        .dataset-breadcrumb a:hover {
            color: #a76025;
        }
        .dataset-breadcrumb li[aria-current="page"] {
            color: #142033;
            font-weight: 600;
            max-width: 340px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dataset-shell {
            padding-top: 14px !important;
        }
    </style>
</head>
<body class="dataset-view-page gravport-landing">

<?= view('partials/site_header', [
    'activePage' => 'catalog',
    'headerClass' => 'header--solid',
]) ?>

<?php
$fromParam = $_GET['from'] ?? '';
$catalogBase = site_url('catalog');
$backUrl = (strpos($fromParam, $catalogBase) === 0) ? $fromParam : $catalogBase;
?>

<nav class="dataset-breadcrumb" aria-label="Breadcrumb">
    <ol>
        <li><a href="<?= site_url('/') ?>">Home</a></li>
        <li><a href="<?= esc($backUrl) ?>">Data Catalog</a></li>
        <li aria-current="page"><?= esc($dataset['title']) ?></li>
    </ol>
</nav>

<main class="dataset-shell">
    <div class="dataset-grid">
        <aside class="dataset-panel dataset-panel--info">
            <span class="dataset-kicker">Dataset Preview</span>
            <h1><?= esc($dataset['title']) ?></h1>
            <p>
                Preview terhubung langsung ke sumber aktif WebMap.
            </p>

            <div class="dataset-meta">
                <div class="dataset-meta__row">
                    <span>Spatial Scope</span>
                    <strong><?= esc($dataset['spatial_scope'] ?? '-') ?></strong>
                </div>
                <div class="dataset-meta__row">
                    <span>Coverage</span>
                    <strong><?= esc($dataset['province_name'] ?? 'Jawa-Bali') ?></strong>
                </div>
            </div>

            <div class="dataset-actions">
                <a href="<?= site_url('catalog/download/' . (int) $dataset['id']) ?>" class="dataset-btn dataset-btn--primary">
                    <i class="bi bi-download"></i>
                    <?= esc($dataset['download_label'] ?? 'Download Data') ?>
                </a>
                <a href="<?= site_url('catalog/download-metadata/' . (int) $dataset['id']) ?>" class="dataset-btn dataset-btn--secondary">
                    <i class="bi bi-file-earmark-code"></i>
                    <?= esc($dataset['metadata_label'] ?? 'Download Metadata XML') ?>
                </a>
                <a href="<?= esc($backUrl) ?>" class="dataset-btn dataset-btn--ghost">
                    <i class="bi bi-arrow-left"></i>
                    Back to catalog
                </a>
            </div>
        </aside>

        <section class="dataset-panel dataset-map-wrap">
            <div id="map"></div>
        </section>
    </div>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const datasetConfig = {
        type: '<?= esc($dataset['type']) ?>',
        label: '<?= esc($dataset['title'], 'js') ?>',
        geojsonUrl: '<?= site_url('catalog/geojson/' . (int) $dataset['id']) ?>',
    };
    const initialBbox = <?= json_encode($initialBbox) ?>;

    const map = L.map('map', { preferCanvas: true });
    if (initialBbox) {
        map.fitBounds([
            [initialBbox.south, initialBbox.west],
            [initialBbox.north, initialBbox.east],
        ], { padding: [0, 0] });
    } else {
        map.setView([-7.45, 110.15], 6);
    }
    const pointRenderer = L.canvas({ padding: 0.5 });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let activeLayer = null;
    let requestId = 0;
    let refreshTimer = null;
    let skipNextRefresh = false;

    function styleFeature(feature) {
        if (datasetConfig.type === 'raster') {
            return {
                color: '#4c736a',
                weight: 0.9,
                fillColor: colorForRaster(feature.properties.summary_value),
                fillOpacity: 0.42,
                dashArray: '5 4',
            };
        }

        if (feature.properties.is_aggregate) {
            const count = Number(feature.properties.point_count || 1);
            return {
                radius: Math.max(5, Math.min(13, 4 + Math.log10(count + 1) * 4)),
                color: '#fff',
                weight: 1,
                fillColor: colorForNumber(feature.properties.summary_value),
                fillOpacity: 0.88,
            };
        }

        const zoom = map.getZoom();
        return {
            radius: zoom >= 11 ? 3.8 : zoom >= 9 ? 3.2 : 2.7,
            color: '#fff',
            weight: 0.8,
            fillColor: colorForNumber(feature.properties.summary_value),
            fillOpacity: 0.82,
        };
    }

    const SEISMIC_STOPS = [
        [-1.000, [0,   0, 140]],
        [-0.750, [0,  55, 220]],
        [-0.500, [0, 135, 255]],
        [-0.250, [110, 195, 255]],
        [ 0.000, [255, 255, 255]],
        [ 0.250, [255, 220,  90]],
        [ 0.500, [255, 140,   0]],
        [ 0.750, [215,  50,   0]],
        [ 1.000, [140,   0,   0]],
    ];

    function interpolateSeismic(t) {
        t = Math.max(-1, Math.min(1, t));
        for (let i = 0; i < SEISMIC_STOPS.length - 1; i++) {
            const [t0, c0] = SEISMIC_STOPS[i];
            const [t1, c1] = SEISMIC_STOPS[i + 1];
            if (t <= t1) {
                const f = (t - t0) / (t1 - t0);
                return `rgb(${Math.round(c0[0]+f*(c1[0]-c0[0]))},${Math.round(c0[1]+f*(c1[1]-c0[1]))},${Math.round(c0[2]+f*(c1[2]-c0[2]))})`;
            }
        }
        const c = SEISMIC_STOPS.at(-1)[1];
        return `rgb(${c[0]},${c[1]},${c[2]})`;
    }

    function colorForNumber(value) {
        const absMax = 215;
        return interpolateSeismic(Number(value || 0) / absMax);
    }

    function colorForRaster(value) {
        const absMax = 335;
        return interpolateSeismic(Number(value || 0) / absMax);
    }

    async function loadPreview(fit = false) {
        const currentRequestId = ++requestId;
        const bounds = map.getBounds();
        const params = new URLSearchParams({
            bbox: [
                bounds.getWest().toFixed(6),
                bounds.getSouth().toFixed(6),
                bounds.getEast().toFixed(6),
                bounds.getNorth().toFixed(6)
            ].join(','),
            zoom: String(Math.round(map.getZoom())),
        });

        const res = await fetch(`${datasetConfig.geojsonUrl}?${params.toString()}`);
        if (!res.ok) {
            throw new Error(`GeoJSON request failed with status ${res.status}`);
        }

        const text = await res.text();
        let payload;
        try {
            payload = JSON.parse(text);
        } catch {
            throw new Error('Preview dataset mengembalikan respons non-JSON.');
        }

        if (currentRequestId !== requestId) {
            return;
        }

        if (activeLayer) {
            map.removeLayer(activeLayer);
            activeLayer = null;
        }

        activeLayer = L.geoJSON(payload.collection, {
            style: styleFeature,
            pointToLayer: (feature, latlng) => L.circleMarker(latlng, {
                ...styleFeature(feature),
                renderer: pointRenderer,
            }),
            onEachFeature: (feature, layer) => {
                const label = feature.properties.summary_label || 'Value';
                const value = feature.properties.summary_value ?? '-';
                const unit = feature.properties.summary_unit || '';
                const extra = feature.properties.is_aggregate
                    ? `<br>Jumlah titik: <strong>${Number(feature.properties.point_count || 0).toLocaleString('id-ID')}</strong>`
                    : '';
                layer.bindPopup(`<strong>${feature.properties.title || datasetConfig.label}</strong><br>${label}: <strong>${value}</strong> ${unit}${extra}`);
            }
        }).addTo(map);

        const previewStatus = document.getElementById('datasetPreviewStatus');
        const previewNote = document.getElementById('datasetMapNote');

        if (payload.meta?.mode === 'aggregated_points') {
            const grouped = Number(payload.meta?.feature_count ?? payload.collection?.features?.length ?? 0);
            const sourceCount = Number(payload.meta?.source_feature_count ?? 0);
            previewStatus.textContent = sourceCount > 0
                ? `Mode ringan aktif: ${grouped.toLocaleString('id-ID')} sel mewakili ${sourceCount.toLocaleString('id-ID')} titik sumber.`
                : 'Belum ada titik yang masuk ke viewport aktif.';
            previewNote.textContent = 'Tampilan awal dataset titik memakai agregasi viewport agar tetap ringan, lalu detail asli muncul saat area dipersempit.';
        } else if (payload.meta?.mode === 'points') {
            const count = Number(payload.meta?.feature_count ?? payload.collection?.features?.length ?? 0);
            previewStatus.textContent = count > 0
                ? `Detail titik individual aktif pada viewport ini (${count.toLocaleString('id-ID')} titik termuat).`
                : 'Belum ada titik yang masuk ke viewport aktif.';
            previewNote.textContent = 'Semua titik pada area yang sedang terlihat dimuat langsung tanpa klaster.';
        } else {
            previewStatus.textContent = 'Grid raster aktif mengikuti viewport yang sedang terlihat.';
            previewNote.textContent = 'Preview grid hanya memuat area aktif pada viewport agar tetap responsif.';
        }

        if (fit && activeLayer.getLayers().length) {
            const layerBounds = activeLayer.getBounds();
            if (layerBounds.isValid()) {
                skipNextRefresh = true;
                map.fitBounds(layerBounds, { padding: [24, 24] });
            }
        }
    }

    function scheduleRefresh() {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(() => {
            loadPreview(false).catch((error) => {
                console.error(error);
                const previewStatus = document.getElementById('datasetPreviewStatus');
                const previewNote = document.getElementById('datasetMapNote');
                previewStatus.textContent = 'Preview belum berhasil dimuat.';
                previewNote.textContent = error.message || 'Gagal memuat data preview dataset.';
            });
        }, 220);
    }

    map.on('moveend zoomend', () => {
        if (skipNextRefresh) {
            skipNextRefresh = false;
            return;
        }
        scheduleRefresh();
    });

    loadPreview(true).catch((error) => {
        console.error(error);
        const previewStatus = document.getElementById('datasetPreviewStatus');
        const previewNote = document.getElementById('datasetMapNote');
        previewStatus.textContent = 'Preview belum berhasil dimuat.';
        previewNote.textContent = error.message || 'Gagal memuat data preview dataset.';
    });
</script>
</body>
</html>
