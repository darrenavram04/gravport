document.addEventListener("DOMContentLoaded", () => {
  const cfg = window.WEBMAP_CONFIG;

  const els = {
    anomaly: document.querySelectorAll('input[name="anomaly"]'),
    level: document.querySelectorAll('input[name="level"]'),
    organization: document.querySelectorAll('input[name="organization"]'),
    provinceSelect: document.getElementById("provinceSelect"),
    pointBufferInput: document.getElementById("pointBufferInput"),
    geometryFileInput: document.getElementById("geometryFileInput"),
    drawStatus: document.getElementById("drawStatus"),
    uploadStatus: document.getElementById("uploadStatus"),
    previewBtn: document.getElementById("previewBtn"),
    clearSpatialBtn: document.getElementById("clearSpatialBtn"),
    downloadVectorBtn: document.getElementById("downloadVectorBtn"),
    downloadRasterBtn: document.getElementById("downloadRasterBtn"),
    downloadMetadataBtn: document.getElementById("downloadMetadataBtn"),
    datasetBadge: document.getElementById("datasetBadge"),
    datasetAvailability: document.getElementById("datasetAvailability"),
    datasetTitle: document.getElementById("datasetTitle"),
    datasetNote: document.getElementById("datasetNote"),
    featureCount: document.getElementById("featureCount"),
    filterLabel: document.getElementById("filterLabel"),
    legendTitle: document.getElementById("legendTitle"),
    legendDesc: document.getElementById("legendDesc"),
    legendScale: document.getElementById("legendScale"),
    mapSearchForm: document.getElementById("mapSearchForm"),
    mapSearchInput: document.getElementById("mapSearchInput"),
    drawer: document.getElementById("detailDrawer"),
    drawerTitle: document.getElementById("drawerTitle"),
    drawerSubtitle: document.getElementById("drawerSubtitle"),
    drawerSummary: document.getElementById("drawerSummary"),
    drawerDetails: document.getElementById("drawerDetails"),
    drawerNote: document.getElementById("drawerNote"),
    drawerPrimaryBtn: document.getElementById("drawerPrimaryBtn"),
    drawerSecondaryBtn: document.getElementById("drawerSecondaryBtn"),
    drawerCloseBtn: document.getElementById("drawerCloseBtn"),
    toast: document.getElementById("webmapToast"),
    modeButtons: document.querySelectorAll("[data-mode]"),
    modePanels: document.querySelectorAll("[data-panel]"),
  };

  const state = {
    datasets: new Map(),
    activeDataset: "faa_l1",
    mode: "province",
    map: null,
    baseLayer: null,
    provinceLayer: null,
    dataLayer: null,
    drawGroup: null,
    uploadLayer: null,
    drawnGeometry: null,
    drawnBufferMeters: 1000,
    uploadedGeometry: null,
    selectedFeatureLayer: null,
    selectedFeatureId: null,
    selectedMeta: null,
    previewTimer: null,
    lastRequestId: 0,
    layerAbortController: null,
    skipNextMapReload: false,
    pointRenderer: null,
    rasterRenderer: null,
  };

  initMap();
  bindUI();
  boot();

  async function boot() {
    try {
      const bootstrap = await api(cfg.bootstrapUrl);
      bootstrap.datasets.forEach((item) => state.datasets.set(item.code, item));

      await loadProvinces();
      syncDatasetMeta();

      // Wait for the cinematic loader to dismiss, then load the initial overview.
      // noBounds skips viewport-bounds entirely on the first call so the server
      // aggregates ALL data at the coarse zoom level — guaranteed no OOM.
      // skipNextMapReload suppresses any spurious moveend that fires during the
      // first user interaction before this await resolves.
      await new Promise(resolve => document.addEventListener('wm:mapready', resolve, { once: true }));
      state.skipNextMapReload = true;
      await loadPreview(false, { noBounds: true });
      state.skipNextMapReload = false;
    } catch (error) {
      toast(error.message || "Gagal memuat WebMap.");
    }
  }

  function initMap() {
    state.map = L.map("map", {
      zoomControl: false,
      attributionControl: true,
      preferCanvas: true,
    }).setView([-7.45, 110.15], 6);
    state.map.createPane("provincePane");
    state.map.createPane("dataPane");
    state.map.createPane("uploadPane");
    state.map.getPane("provincePane").style.zIndex = 360;
    state.map.getPane("dataPane").style.zIndex = 390;
    state.map.getPane("uploadPane").style.zIndex = 520;

    state.pointRenderer = L.canvas({ padding: 0.5, pane: "dataPane" });
    state.rasterRenderer = L.svg({ padding: 0.5, pane: "dataPane" });

    L.control.zoom({ position: "topleft" }).addTo(state.map);

    state.baseLayer = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "&copy; OpenStreetMap contributors",
    }).addTo(state.map);

    state.drawGroup = new L.FeatureGroup().addTo(state.map);
    state.uploadLayer = L.geoJSON(null, {
      pane: "uploadPane",
      style: {
        color: "#1a8e7e",
        weight: 2,
        fillColor: "#40c7b6",
        fillOpacity: 0.12,
        dashArray: "6 6",
      },
      pointToLayer: (_, latlng) =>
        L.circleMarker(latlng, {
          pane: "uploadPane",
          radius: 7,
          color: "#1a8e7e",
          fillColor: "#40c7b6",
          fillOpacity: 0.95,
          weight: 2,
        }),
    }).addTo(state.map);

    const drawControl = new L.Control.Draw({
      position: "topleft",
      draw: {
        polygon: true,
        rectangle: true,
        circle: true,
        marker: true,
        polyline: false,
        circlemarker: false,
      },
      edit: {
        featureGroup: state.drawGroup,
        edit: false,
        remove: true,
      },
    });

    state.map.addControl(drawControl);

    state.map.on(L.Draw.Event.CREATED, async (event) => {
      state.drawGroup.clearLayers();
      state.drawGroup.addLayer(event.layer);

      state.uploadLayer.clearLayers();
      state.uploadedGeometry = null;

      const geojson = event.layer.toGeoJSON();
      state.drawnGeometry = geojson.geometry || null;

      if (event.layer instanceof L.Circle) {
        state.drawnBufferMeters = Math.round(event.layer.getRadius());
        els.pointBufferInput.value = state.drawnBufferMeters;
      } else {
        state.drawnBufferMeters = Number(els.pointBufferInput.value || 1000);
      }

      els.drawStatus.textContent = `Boundary aktif: ${state.drawnGeometry?.type || "Unknown"}`;
      setMode("draw");
      await loadPreview(true);
    });

    state.map.on(L.Draw.Event.DELETED, async () => {
      state.drawnGeometry = null;
      els.drawStatus.textContent = "Boundary gambar dihapus.";
      await loadPreview(false);
    });

    state.map.on("moveend zoomend", () => {
      if (state.skipNextMapReload) {
        state.skipNextMapReload = false;
        return;
      }
      schedulePreviewReload();
    });
  }

  function bindUI() {
    [...els.anomaly, ...els.level, ...els.organization].forEach((input) => {
      input.addEventListener("change", async () => {
        syncDatasetMeta();
        await loadPreview(false);
      });
    });

    els.provinceSelect.addEventListener("change", async () => {
      setMode("province");
      refreshProvinceStyle();
      await loadPreview(true);
    });

    els.pointBufferInput.addEventListener("change", async () => {
      state.drawnBufferMeters = Number(els.pointBufferInput.value || 1000);
      if (state.mode === "draw" && state.drawnGeometry?.type === "Point") {
        await loadPreview(false);
      }
    });

    els.geometryFileInput.addEventListener("change", handleGeometryUpload);
    els.previewBtn?.addEventListener("click", () => loadPreview(false));
    els.clearSpatialBtn.addEventListener("click", clearSpatialFilters);
    els.downloadVectorBtn.addEventListener("click", downloadVector);
    els.downloadRasterBtn.addEventListener("click", downloadRaster);
    els.downloadMetadataBtn.addEventListener("click", downloadMetadata);
    els.drawerCloseBtn.addEventListener("click", () => els.drawer.classList.remove("is-open"));

    els.drawerSecondaryBtn.addEventListener("click", () => {
      if (!state.selectedFeatureLayer) return;
      const bounds = state.selectedFeatureLayer.getBounds
        ? state.selectedFeatureLayer.getBounds()
        : null;

      if (bounds && bounds.isValid()) {
        fitMap(bounds, [40, 40]);
      } else if (state.selectedFeatureLayer.getLatLng) {
        state.map.flyTo(state.selectedFeatureLayer.getLatLng(), 12);
      }
    });

    els.drawerPrimaryBtn.addEventListener("click", async () => {
      const dataset = currentDataset();
      if (!dataset) return;

      if (state.selectedMeta?.kind === "aggregate") {
        if (state.selectedFeatureLayer?.getLatLng) {
          state.map.flyTo(state.selectedFeatureLayer.getLatLng(), Math.max(state.map.getZoom(), 10));
        }
        return;
      }

      if (dataset.type === "vector") {
        await downloadVector();
        return;
      }

      if (state.selectedFeatureId) {
        window.open(`${cfg.downloadRasterGridBase}/${state.selectedFeatureId}?dataset=${dataset.code}`, "_blank");
        return;
      }

      await downloadRaster();
    });

    els.modeButtons.forEach((btn) => {
      btn.addEventListener("click", () => setMode(btn.dataset.mode));
    });

    els.mapSearchForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      const query = (els.mapSearchInput.value || "").trim();
      if (!query) return;

      const latLng = query.split(",").map((v) => Number(v.trim()));
      if (latLng.length === 2 && !Number.isNaN(latLng[0]) && !Number.isNaN(latLng[1])) {
        state.map.flyTo([latLng[0], latLng[1]], 12);
        return;
      }

      try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;
        const results = await api(url);
        if (!results.length) {
          toast("Lokasi tidak ditemukan.");
          return;
        }
        state.map.flyTo([Number(results[0].lat), Number(results[0].lon)], 12);
      } catch {
        toast("Pencarian lokasi gagal.");
      }
    });
  }

  function schedulePreviewReload() {
    clearTimeout(state.previewTimer);
    state.previewTimer = setTimeout(() => {
      loadPreview(false).catch((error) => {
        toast(error.message || "Preview layer gagal dimuat.");
      });
    }, 220);
  }

  async function loadProvinces() {
    const collection = await api(cfg.provincesUrl);

    if (state.provinceLayer) {
      state.map.removeLayer(state.provinceLayer);
    }

    state.provinceLayer = L.geoJSON(collection, {
      pane: "provincePane",
      interactive: true,
      style: (feature) => ({
        color: isSelectedProvince(feature) ? "#b35624" : "#8d7e73",
        weight: isSelectedProvince(feature) ? 2.4 : 1,
        fillColor: "#d7c7ba",
        fillOpacity: isSelectedProvince(feature) ? 0.12 : 0.02,
      }),
      onEachFeature: (feature, layer) => {
        const title = feature.properties.title || "Provinsi";
        layer.bindTooltip(title, { sticky: true });

        layer.on("click", async (event) => {
          stopLeafletEvent(event);
          els.provinceSelect.value = feature.properties.feature_id;
          setMode("province");
          refreshProvinceStyle();
          await loadPreview(true);
        });
      },
    }).addTo(state.map);

    const options = collection.features
      .map((feature) => ({
        id: feature.properties.feature_id,
        title: feature.properties.title,
      }))
      .sort((a, b) => a.title.localeCompare(b.title, "id"));

    options.forEach((item) => {
      const option = document.createElement("option");
      option.value = item.id;
      option.textContent = item.title;
      els.provinceSelect.appendChild(option);
    });
  }

  async function loadPreview(fit, opts = {}) {
    const dataset = currentDataset();
    if (!dataset) return;

    const organization = selectedValue("organization");
    if (organization !== "all" && dataset.organization.toLowerCase() !== organization) {
      clearDataLayer();
      renderDatasetMeta(dataset, 0, "Tidak ada source aktif untuk organisasi ini.");
      toast("Dataset untuk organisasi tersebut belum tervalidasi di database aktif.");
      return;
    }

    // Abort any in-flight layer request before sending a new one.
    // Without this, switching provinces rapidly fires simultaneous heavy
    // spatial queries that can exhaust server memory.
    if (state.layerAbortController) {
      state.layerAbortController.abort();
    }
    state.layerAbortController = new AbortController();
    const signal = state.layerAbortController.signal;

    const requestId = ++state.lastRequestId;
    const payload = {
      dataset: dataset.code,
      ...buildSpatialPayload({ forPreview: true, noBounds: Boolean(opts.noBounds) }),
    };

    let response;
    try {
      response = await api(cfg.layerUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
        body: JSON.stringify(payload),
        signal,
      });
    } catch (e) {
      if (e.name === "AbortError") return; // superseded by newer request
      throw e;
    }

    if (requestId !== state.lastRequestId) {
      return;
    }

    renderDatasetMeta(
      response.dataset,
      response.collection.features.length,
      previewNote(response),
      response.meta || {}
    );
    renderLegend(response.dataset, response.meta || {});
    renderData(response.collection, response.dataset, response.meta || {});

    if (fit) {
      fitLayerBounds(state.dataLayer);
    }
  }

  function renderData(collection, dataset, meta) {
    clearDataLayer();
    state.selectedFeatureId = null;
    state.selectedFeatureLayer = null;
    state.selectedMeta = null;

    state.dataLayer = L.geoJSON(collection, {
      pane: "dataPane",
      interactive: true,
      renderer: dataset.type === "raster" ? state.rasterRenderer : undefined,
      bubblingMouseEvents: false,
      style: (feature) => {
        const geomType = feature.geometry?.type;
        if (geomType === "Point" || geomType === "MultiPoint") return {};
        return rasterStyle(dataset, feature);
      },
      pointToLayer: (feature, latlng) =>
        L.circleMarker(latlng, {
          ...pointStyle(dataset, feature.properties, meta),
          pane: "dataPane",
          renderer: state.pointRenderer,
        }),
      onEachFeature: (feature, layer) => {
        layer.on("mouseover", () => {
          state.map.getContainer().style.cursor = "pointer";
        });

        layer.on("mouseout", () => {
          state.map.getContainer().style.cursor = "";
        });

        layer.on("click", async (event) => {
          stopLeafletEvent(event);
          state.selectedFeatureLayer = layer;
          state.selectedFeatureId = feature.properties.is_aggregate ? null : feature.properties.feature_id;

          const extraLine = feature.properties.is_aggregate
            ? `<br>Jumlah titik: <strong>${Number(feature.properties.point_count || 0).toLocaleString("id-ID")}</strong>`
            : "";
          const popup = `
            <strong>${feature.properties.title || dataset.label}</strong><br>
            ${feature.properties.summary_label || "Value"}:
            <strong>${feature.properties.summary_value ?? "-"}</strong>
            ${feature.properties.summary_unit || ""}
            ${extraLine}<br>
            <small>Detail lengkap dibuka di panel kanan.</small>
          `;

          layer.bindPopup(popup).openPopup();
          if (feature.properties.is_aggregate) {
            openAggregateMeta(dataset, feature.properties);
            return;
          }

          await openFeatureMeta(dataset.code, feature.properties.feature_id);
        });
      },
    }).addTo(state.map);

    if (state.dataLayer.bringToFront) {
      state.dataLayer.bringToFront();
    }
  }

  function renderDatasetMeta(dataset, featureCount, forcedNote = null, meta = {}) {
    syncActionButtons(dataset);
    els.datasetBadge.textContent = dataset.type.toUpperCase();
    els.datasetAvailability.textContent = dataset.availability.toUpperCase();
    els.datasetTitle.textContent = dataset.label;
    els.datasetNote.textContent = forcedNote || dataset.note || "";
    els.featureCount.textContent = formatFeatureCount(featureCount, meta);
    els.filterLabel.textContent = currentFilterLabel();
  }

  function renderLegend(dataset, meta) {
    const root = document.documentElement;

    const scaleLabels = "<span>&lt; −50</span><span>−50~0</span><span>0~50</span><span>&gt; 50</span>";
    const unitNote = " Satuan: mGal.";

    if (dataset.type === "vector" && meta.mode === "aggregated_points") {
      els.legendTitle.textContent = "Legenda Ringkasan";
      els.legendDesc.textContent = "Lingkaran mewakili rata-rata anomali gravitasi (mGal) kumpulan titik pada sel viewport." + unitNote;
      els.legendScale.innerHTML = scaleLabels;
      root.style.setProperty(
        "--legend-gradient",
        "linear-gradient(90deg, #d9f8f2 0%, #8fe0d0 33%, #33c0a8 67%, #17836f 100%)"
      );
      return;
    }

    if (dataset.type === "vector") {
      els.legendTitle.textContent = "Legenda Titik";
      els.legendDesc.textContent = "Warna titik menunjukkan nilai anomali gravitasi tiap titik pengukuran." + unitNote;
      els.legendScale.innerHTML = scaleLabels;
      root.style.setProperty(
        "--legend-gradient",
        "linear-gradient(90deg, #d9f8f2 0%, #8fe0d0 33%, #33c0a8 67%, #17836f 100%)"
      );
      return;
    }

    els.legendTitle.textContent = "Legenda Raster/Grid";
    els.legendDesc.textContent = "Warna grid menunjukkan nilai anomali gravitasi rata-rata tiap sel." + unitNote;
    els.legendScale.innerHTML = scaleLabels;
    root.style.setProperty(
      "--legend-gradient",
      "linear-gradient(90deg, #d9f8f2 0%, #8fe0d0 33%, #33c0a8 67%, #17836f 100%)"
    );
  }

  async function openFeatureMeta(datasetCode, featureId) {
    try {
      const payload = await api(`${cfg.featureMetaBase}/${datasetCode}/${featureId}`);
      state.selectedMeta = { kind: "feature", payload };
      els.drawer.classList.add("is-open");
      els.drawerTitle.textContent = payload.feature.title;
      els.drawerSubtitle.textContent = payload.dataset.label;
      els.drawerNote.textContent = payload.feature.note || payload.dataset.note || "";
      els.drawerSummary.innerHTML = renderDetailItems(payload.feature.summary || []);
      els.drawerDetails.innerHTML = renderDetailItems(payload.feature.details || []);
      els.drawerPrimaryBtn.textContent = payload.dataset.type === "vector" ? "Unduh hasil filter" : "Unduh grid ini";
      els.drawerPrimaryBtn.disabled = false;
    } catch (error) {
      toast(error.message || "Gagal memuat detail fitur.");
    }
  }

  function openAggregateMeta(dataset, props) {
    state.selectedMeta = {
      kind: "aggregate",
      payload: { dataset, props },
    };
    els.drawer.classList.add("is-open");
    els.drawerTitle.textContent = props.title || "Ringkasan titik";
    els.drawerSubtitle.textContent = dataset.label;
    els.drawerNote.textContent = "Perbesar area atau gunakan filter yang lebih sempit untuk memunculkan titik asli satu per satu.";
    els.drawerSummary.innerHTML = renderDetailItems([
      { label: props.summary_label || "Mean", value: `${props.summary_value ?? "-"} ${props.summary_unit || ""}` },
      { label: "Jumlah titik", value: Number(props.point_count || 0).toLocaleString("id-ID") },
    ]);
    els.drawerDetails.innerHTML = renderDetailItems([
      { label: "Nilai minimum", value: `${props.min_val ?? "-"} ${props.summary_unit || ""}` },
      { label: "Nilai maksimum", value: `${props.max_val ?? "-"} ${props.summary_unit || ""}` },
      { label: "Ukuran sel", value: `${props.aggregate_cell_size_deg ?? "-"} derajat` },
    ]);
    els.drawerPrimaryBtn.textContent = "Perbesar untuk detail";
    els.drawerPrimaryBtn.disabled = false;
  }

  function renderDetailItems(items) {
    if (!items.length) return "";
    return items
      .map(
        (item) => `
          <div class="wm-detail-item">
            <strong>${escapeHtml(item.label)}</strong>
            <span>${escapeHtml(String(item.value ?? "-"))}</span>
          </div>
        `
      )
      .join("");
  }

  async function downloadVector() {
    const dataset = currentDataset();
    if (!dataset || dataset.type !== "vector") {
      toast("Dataset aktif bukan vector.");
      return;
    }

    const response = await fetch(cfg.downloadVectorUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
      body: JSON.stringify({
        dataset: dataset.code,
        ...buildSpatialPayload({ forPreview: false }),
        force_detail: true,
      }),
    });

    if (response.status === 401) { toast("Login diperlukan untuk mengunduh data."); return; }
    if (response.status === 403) { toast("Akun Anda belum memiliki akses unduh. Silakan upgrade."); return; }
    if (!response.ok) {
      toast(await extractErrorMessage(response, "Unduhan vector gagal."));
      return;
    }

    saveBlob(await response.blob(), filenameFromResponse(response, `${dataset.code}_${Date.now()}.zip`));
  }

  async function downloadRaster() {
    const dataset = currentDataset();
    if (!dataset || dataset.type !== "raster") {
      toast("Dataset aktif bukan raster.");
      return;
    }

    const payload = buildSpatialPayload({ forPreview: false });

    if (payload.province_id) {
      window.open(`${cfg.downloadRasterProvinceBase}/${payload.province_id}?dataset=${dataset.code}`, "_blank");
      return;
    }

    const response = await fetch(cfg.clipRasterUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
      body: JSON.stringify({
        dataset: dataset.code,
        ...payload,
      }),
    });

    if (response.status === 401) { toast("Login diperlukan untuk mengunduh data."); return; }
    if (response.status === 403) { toast("Akun Anda belum memiliki akses unduh raster. Silakan upgrade."); return; }
    if (!response.ok) {
      toast(await extractErrorMessage(response, "Unduhan raster gagal."));
      return;
    }

    saveBlob(await response.blob(), filenameFromResponse(response, `${dataset.code}_${Date.now()}.zip`));
  }

  async function downloadMetadata() {
    const dataset = currentDataset();
    if (!dataset) return;

    const response = await fetch(cfg.downloadMetadataUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
      body: JSON.stringify({
        dataset: dataset.code,
        ...buildSpatialPayload({ forPreview: false }),
      }),
    });

    if (!response.ok) {
      toast(await extractErrorMessage(response, "Unduhan metadata gagal."));
      return;
    }

    saveBlob(await response.blob(), filenameFromResponse(response, `${dataset.code}_metadata_${Date.now()}.xml`));
  }

  async function handleGeometryUpload(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    try {
      const payload = await readGeometryFile(file);
      state.uploadedGeometry = payload.geometry;
      state.drawGroup.clearLayers();
      state.drawnGeometry = null;
      state.uploadLayer.clearLayers();
      state.uploadLayer.addData(payload.preview);
      els.uploadStatus.textContent = `Boundary aktif dari file: ${file.name}`;
      setMode("upload");
      fitLayerBounds(state.uploadLayer);
      await loadPreview(false);
    } catch (error) {
      toast(error.message || "File boundary tidak bisa dibaca.");
    }
  }

  async function readGeometryFile(file) {
    const ext = file.name.split(".").pop().toLowerCase();
    const text = await file.text();

    if (ext === "json" || ext === "geojson") {
      const raw = JSON.parse(text);
      return normalizeGeometry(raw);
    }

    if (ext === "kml") {
      const xml = new DOMParser().parseFromString(text, "text/xml");
      const geojson = window.toGeoJSON.kml(xml);
      return normalizeGeometry(geojson);
    }

    throw new Error("Format file belum didukung. Gunakan GeoJSON atau KML.");
  }

  function normalizeGeometry(input) {
    if (input.type === "Feature") {
      return {
        geometry: input.geometry,
        preview: input,
      };
    }

    if (input.type === "FeatureCollection") {
      const geometries = input.features.map((feature) => feature.geometry).filter(Boolean);
      return {
        geometry: geometries.length === 1
          ? geometries[0]
          : { type: "GeometryCollection", geometries },
        preview: input,
      };
    }

    return {
      geometry: input,
      preview: { type: "Feature", properties: {}, geometry: input },
    };
  }

  function buildSpatialPayload(options = {}) {
    const forPreview = Boolean(options.forPreview);
    const payload = {};
    let explicitFilter = false;

    if (state.mode === "province" && els.provinceSelect.value) {
      payload.province_id = Number(els.provinceSelect.value);
      explicitFilter = true;
    } else if (state.mode === "draw" && state.drawnGeometry) {
      payload.geometry = state.drawnGeometry;
      payload.buffer_meters = state.drawnGeometry.type === "Point"
        ? Number(els.pointBufferInput.value || state.drawnBufferMeters || 1000)
        : state.drawnBufferMeters || 0;
      explicitFilter = true;
    } else if (state.mode === "upload" && state.uploadedGeometry) {
      payload.geometry = state.uploadedGeometry;
      payload.buffer_meters = 0;
      explicitFilter = true;
    }

    // Only add viewport bounds for preview requests (not downloads).
    // Skip bounds when province is selected — the province polygon defines the
    // spatial extent; adding viewport bounds would exclude provinces outside
    // the current viewport and corrupt the spatial filter.
    if (forPreview) {
      if (!options.noBounds && !payload.province_id) {
        payload.bounds = mapBoundsPayload();
      }
      payload.zoom = Math.round(state.map.getZoom());
    }

    return payload;
  }

  function clearSpatialFilters() {
    els.provinceSelect.value = "";
    els.geometryFileInput.value = "";
    state.drawGroup.clearLayers();
    state.uploadLayer.clearLayers();
    state.drawnGeometry = null;
    state.uploadedGeometry = null;
    state.selectedFeatureId = null;
    els.drawStatus.textContent = "Belum ada boundary digambar.";
    els.uploadStatus.textContent = "GeoJSON dan KML didukung pada draft ini.";
    setMode("province");
    refreshProvinceStyle();
    loadPreview(false);
  }

  function setMode(mode) {
    state.mode = mode;
    els.modeButtons.forEach((btn) => {
      btn.classList.toggle("is-active", btn.dataset.mode === mode);
    });
    els.modePanels.forEach((panel) => {
      panel.classList.toggle("is-active", panel.dataset.panel === mode);
    });
    els.filterLabel.textContent = currentFilterLabel();
  }

  function currentFilterLabel() {
    if (state.mode === "province" && els.provinceSelect.value) {
      const text = els.provinceSelect.options[els.provinceSelect.selectedIndex]?.text || "Provinsi";
      return text;
    }
    if (state.mode === "draw" && state.drawnGeometry) {
      return state.drawnGeometry.type;
    }
    if (state.mode === "upload" && state.uploadedGeometry) {
      return state.uploadedGeometry.type || "Upload";
    }
    return "Viewport";
  }

  function syncActionButtons(dataset) {
    const isVector = dataset.type === "vector";
    els.downloadVectorBtn.hidden = !isVector;
    els.downloadRasterBtn.hidden = isVector;
    els.downloadVectorBtn.disabled = !isVector;
    els.downloadRasterBtn.disabled = isVector;
    els.downloadVectorBtn.textContent = "Unduh CSV";
    els.downloadRasterBtn.textContent = "Unduh GeoTIFF";
    els.downloadMetadataBtn.textContent = "Unduh Metadata";
  }

  function pointStyle(dataset, props, meta) {
    const zoom = Number(meta.zoom || state.map?.getZoom?.() || 0);

    if (props.is_aggregate) {
      const count = Number(props.point_count || 1);
      return {
        radius: Math.max(5, Math.min(13, 4 + Math.log10(count + 1) * (zoom >= 8 ? 4 : 3.2))),
        color: "#ffffff",
        weight: 1,
        fillColor: dataset.code === "cba_l1" ? "#17836f" : colorForNumber(props.summary_value),
        fillOpacity: 0.9,
      };
    }

    const radius = zoom >= 11 ? 3.8 : zoom >= 9 ? 3.2 : 2.7;

    return {
      radius,
      color: "#ffffff",
      weight: 0.8,
      fillColor: dataset.code === "cba_l1" ? "#17836f" : colorForNumber(props.summary_value),
      fillOpacity: 0.82,
    };
  }

  function rasterStyle(dataset, feature) {
    return {
      color: dataset.code === "cba_l2" ? "#2f6e62" : "#6e6552",
      weight: 0.9,
      fillColor: colorForRaster(feature.properties.summary_value),
      fillOpacity: 0.42,
      dashArray: "5 4",
    };
  }

  function colorForNumber(value) {
    const v = Number(value ?? 0);
    if (v < -50) return "#d9f8f2";
    if (v < 0)   return "#8fe0d0";
    if (v < 50)  return "#33c0a8";
    return "#17836f";
  }

  function colorForRaster(value) {
    const v = Number(value ?? 0);
    if (v < -50) return "#d9f8f2";
    if (v < 0)   return "#8fe0d0";
    if (v < 50)  return "#33c0a8";
    return "#17836f";
  }

  function clearDataLayer() {
    if (state.dataLayer) {
      state.map.removeLayer(state.dataLayer);
      state.dataLayer = null;
    }
    if (state.map) {
      state.map.getContainer().style.cursor = "";
    }
  }

  function stopLeafletEvent(event) {
    if (event?.originalEvent) {
      L.DomEvent.stopPropagation(event.originalEvent);
      L.DomEvent.preventDefault(event.originalEvent);
    }
  }

  function fitLayerBounds(layer) {
    if (!layer) return;
    const bounds = layer.getBounds ? layer.getBounds() : null;
    if (bounds && bounds.isValid()) {
      fitMap(bounds, [30, 30]);
    }
  }

  function fitMap(bounds, padding) {
    state.skipNextMapReload = true;
    state.map.fitBounds(bounds, { padding });
  }

  function syncDatasetMeta() {
    const dataset = currentDataset();
    if (!dataset) return;
    syncActionButtons(dataset);
    renderDatasetMeta(dataset, 0, null, {});
    renderLegend(dataset, {});
  }

  function currentDataset() {
    const code = `${selectedValue("anomaly")}_${selectedValue("level")}`;
    state.activeDataset = code;
    return state.datasets.get(code) || null;
  }

  function selectedValue(name) {
    return document.querySelector(`input[name="${name}"]:checked`)?.value || "";
  }

  function isSelectedProvince(feature) {
    return String(feature.properties.feature_id) === String(els.provinceSelect.value || "");
  }

  function refreshProvinceStyle() {
    if (!state.provinceLayer) return;
    state.provinceLayer.eachLayer((layer) => {
      const feature = layer.feature;
      layer.setStyle({
        color: isSelectedProvince(feature) ? "#b35624" : "#8d7e73",
        weight: isSelectedProvince(feature) ? 2.4 : 1,
        fillOpacity: isSelectedProvince(feature) ? 0.12 : 0.02,
      });
    });
  }

  async function api(url, options = {}) {
    const response = await fetch(url, options);
    const text = await response.text();
    if (!response.ok) {
      throw new Error(summarizeServerMessage(text, "Request gagal."));
    }

    try {
      return JSON.parse(text);
    } catch {
      throw new Error(summarizeServerMessage(text, "Respons server tidak valid."));
    }
  }

  async function extractErrorMessage(response, fallback) {
    return summarizeServerMessage(await response.text(), fallback);
  }

  function saveBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1500);
  }

  function filenameFromResponse(response, fallback) {
    const disposition = response.headers.get("Content-Disposition") || "";
    const match = disposition.match(/filename=\"?([^\";]+)\"?/i);
    return match?.[1] || fallback;
  }

  function previewNote(response) {
    const baseNote = response.dataset.note || "";
    if (response.meta?.mode === "aggregated_points") {
      const grouped = Number(response.meta?.feature_count ?? 0).toLocaleString("id-ID");
      const source = Number(response.meta?.source_feature_count ?? 0).toLocaleString("id-ID");
      return `${baseNote} ${grouped} sel mewakili ${source} titik pada viewport saat ini.`.trim();
    }
    if (currentFilterLabel() === "Viewport") {
      return `${baseNote} Preview mengikuti viewport aktif.`;
    }
    if (response.meta?.mode === "points") {
      return `${baseNote} Semua titik pada area aktif dimuat apa adanya tanpa klaster.`;
    }
    return baseNote;
  }

  function mapBoundsPayload() {
    const bounds = state.map.getBounds();
    return {
      west: Number(bounds.getWest().toFixed(6)),
      south: Number(bounds.getSouth().toFixed(6)),
      east: Number(bounds.getEast().toFixed(6)),
      north: Number(bounds.getNorth().toFixed(6)),
    };
  }

  function toast(message) {
    els.toast.textContent = message;
    els.toast.classList.add("is-show");
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => {
      els.toast.classList.remove("is-show");
    }, 2800);
  }

  function escapeHtml(value) {
    return String(value)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function formatFeatureCount(featureCount, meta) {
    const displayCount = Number(featureCount || 0);
    if (meta.mode === "aggregated_points") {
      const sourceCount = Number(meta.source_feature_count || 0);
      return sourceCount > 0
        ? `${displayCount.toLocaleString("id-ID")} sel / ${sourceCount.toLocaleString("id-ID")} titik`
        : `${displayCount.toLocaleString("id-ID")} sel`;
    }

    return displayCount.toLocaleString("id-ID");
  }

  function summarizeServerMessage(text, fallback) {
    const plain = String(text || "")
      .replace(/<[^>]+>/g, " ")
      .replace(/\s+/g, " ")
      .trim();

    if (!plain) {
      return fallback;
    }

    if (/Allowed memory size/i.test(plain)) {
      return "Server kehabisan memori saat memproses area yang terlalu luas.";
    }

    if (/Fatal error/i.test(plain)) {
      return plain.replace(/^Fatal error\s*:\s*/i, "");
    }

    return plain.length > 240 ? `${plain.slice(0, 240)}...` : plain;
  }
});
