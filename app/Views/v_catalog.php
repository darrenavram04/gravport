<?php
// ----------------------------
// v_catalog.php (Concept A: Command Center)
// Requires variables from controller:
// $datasets, $total, $perPage, $page, $totalPages,
// $search, $downloadable, $viewable, $scopes,
// $countryCode, $countryName
// ----------------------------

$search       = $search ?? '';
$downloadable = (bool)($downloadable ?? false);
$viewable     = (bool)($viewable ?? false);
$scopes       = is_array($scopes ?? null) ? $scopes : [];
$countryCode  = $countryCode ?? 'ID';
$countryName  = $countryName ?? 'Indonesia';
$datasets     = is_array($datasets ?? null) ? $datasets : [];
$total        = (int)($total ?? 0);
$perPage      = (int)($perPage ?? 20);
$page         = (int)($page ?? 1);
$totalPages   = (int)($totalPages ?? 1);

// Build base url + query helper
$baseUrl = current_url();
$query   = $_GET ?? [];

function q_checked($cond) {
  return $cond ? 'checked' : '';
}
function q_selected($cond) {
  return $cond ? 'selected' : '';
}
function esc_attr($v) {
  return esc($v, 'attr');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Data Catalog</title>

  <!-- Keep your existing template assets -->
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css'); ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/fonts.css'); ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=2'); ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css'); ?>">
  <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Poppins:400,500,600%7CTeko:300,400,500%7CMaven+Pro:500">

  <style>
    /* =============================
       Concept A: Command Center
       Only scoped to .catalog-page
       ============================= */
    body.catalog-page{
      font-family: "Poppins", sans-serif;
      background: #0b1020;
      color: rgba(255,255,255,0.92);
      overflow-x: hidden;
    }

    /* Page container sits below header */
    .catalog-shell{
      position: relative;
      min-height: 100vh;
      padding-top: 100px; /* header height buffer */
    }

    /* Background: scientific + enterprise (subtle) */
    .catalog-bg{
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
      background:
        radial-gradient(900px 520px at 20% 15%, rgba(165,106,42,0.26), rgba(0,0,0,0) 60%),
        radial-gradient(900px 520px at 78% 40%, rgba(0,95,254,0.22), rgba(0,0,0,0) 62%),
        radial-gradient(700px 420px at 45% 92%, rgba(255,229,227,0.10), rgba(0,0,0,0) 60%),
        linear-gradient(180deg, #070a14 0%, #0b1020 45%, #090c1a 100%);
    }
    .catalog-bg::after{
      content:"";
      position:absolute;
      inset:0;
      background-image:
        radial-gradient(rgba(255,255,255,0.07) 1px, transparent 1px);
      background-size: 20px 20px;
      opacity: 0.12;
      mix-blend-mode: soft-light;
    }

    /* Header should look like v_template (brown bar). */
    /* If your style.css already defines .site-header, this just stabilizes it. */
    .site-header{
      position: fixed !important;
      top: 0; left: 0; right: 0;
      z-index: 9999;
      background: rgba(165,106,42,0.96) !important;
      backdrop-filter: saturate(1.05) blur(8px);
      -webkit-backdrop-filter: saturate(1.05) blur(8px);
      border-bottom: 1px solid rgba(255,255,255,0.14);
    }

    /* Main layout */
    .catalog-wrap{
      position: relative;
      z-index: 2;
      max-width: 1220px;
      margin: 0 auto;
      padding: 28px 18px 60px;
    }

    /* Top bar */
    .catalog-top{
      display: flex;
      align-items: flex-end;
      justify-content: flex-start;
      margin-bottom: 22px;
    }
    @media (max-width: 992px){
      .catalog-top{ align-items: flex-start; }
    }

    .catalog-title-wrap{
      position: relative;
      padding: 4px 0 14px;
    }

    .catalog-title{
      margin: 0;
      font-weight: 800;
      font-size: clamp(2.5rem, 5vw, 4.4rem);
      line-height: .96;
      letter-spacing: -0.045em;
      color: #ffffff;
      text-shadow: 0 12px 34px rgba(0,0,0,0.26);
    }

    .catalog-title-wrap::after{
      content: "";
      display: block;
      width: 92px;
      height: 4px;
      margin-top: 14px;
      border-radius: 999px;
      background: linear-gradient(90deg, #ffd29a 0%, #f59e53 100%);
      box-shadow: 0 10px 22px rgba(245, 158, 83, 0.36);
    }

    /* Toolbar */
    .catalog-toolbar{
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin: 14px 0 18px;
      padding: 10px 12px;
      border-radius: 16px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.10);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }
    .toolbar-left, .toolbar-right{
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .pill{
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 10px;
      border-radius: 999px;
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.12);
      color: rgba(255,255,255,0.86);
      font-size: 12px;
      user-select: none;
    }

    .view-toggle{
      display: inline-flex;
      border-radius: 999px;
      overflow: hidden;
      border: 1px solid rgba(255,255,255,0.14);
    }
    .view-toggle button{
      appearance: none;
      border: 0;
      background: rgba(255,255,255,0.06);
      color: rgba(255,255,255,0.82);
      padding: 8px 12px;
      font-size: 12px;
      cursor: pointer;
    }
    .view-toggle button.is-active{
      background: rgba(0,95,254,0.28);
      color: rgba(255,255,255,0.96);
    }

    /* Layout: filters + results */
    .catalog-grid{
      display: grid;
      grid-template-columns: 340px 1fr;
      gap: 16px;
      align-items: start;
    }
    @media (max-width: 992px){
      .catalog-grid{ grid-template-columns: 1fr; }
    }

    /* Filters panel */
    .filters-panel{
      position: sticky;
      top: 110px;
      border-radius: 18px;
      padding: 14px;
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(255,255,255,0.12);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      box-shadow: 0 18px 46px rgba(0,0,0,0.28);
    }
    @media (max-width: 992px){
      .filters-panel{ position: relative; top: 0; }
    }

    .filters-title{
      margin: 0 0 10px;
      font-size: 14px;
      letter-spacing: .10em;
      text-transform: uppercase;
      opacity: .85;
      display:flex;
      align-items:center;
      justify-content: space-between;
      gap: 10px;
    }

    .filters-panel label{
      font-size: 12px;
      opacity: .85;
      margin-bottom: 6px;
      font-weight: 600;
    }
    .filters-panel .form-control,
    .filters-panel .form-select{
      background: rgba(255,255,255,0.10);
      border: 1px solid rgba(255,255,255,0.14);
      color: rgba(255,255,255,0.92);
      border-radius: 14px;
    }
    .filters-panel .form-control::placeholder{
      color: rgba(255,255,255,0.55);
    }
    .filters-panel .form-control:focus,
    .filters-panel .form-select:focus{
      box-shadow: 0 0 0 0.18rem rgba(0,95,254,0.22);
      border-color: rgba(0,95,254,0.45);
    }

    .filters-row{
      display: grid;
      grid-template-columns: 1fr;
      gap: 12px;
      margin-bottom: 12px;
    }

    .check-row{
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      padding: 10px 10px;
      background: rgba(0,0,0,0.15);
      border: 1px solid rgba(255,255,255,0.10);
      border-radius: 14px;
    }
    .check-row .form-check{
      display: inline-flex;
      align-items: center;
      margin: 0;
      gap: 8px;
    }
    .check-row .form-check-input{
      margin: 0;
    }
    .check-row .form-check-label{
      margin: 0;
      font-size: 12px;
      opacity: .9;
    }

    .filters-actions{
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }
    .btn-apply{
      flex: 1;
      border-radius: 999px !important;
      border: 1px solid rgba(255,255,255,0.14) !important;
      background: rgba(165,106,42,0.95) !important;
      color: #fff !important;
      font-weight: 700;
      padding: 10px 12px !important;
    }
    .btn-apply:hover{
      background: rgba(142,85,32,0.98) !important;
    }
    .btn-reset{
      border-radius: 999px !important;
      border: 1px solid rgba(255,255,255,0.14) !important;
      background: rgba(255,255,255,0.06) !important;
      color: rgba(255,255,255,0.90) !important;
      font-weight: 600;
      padding: 10px 12px !important;
      text-decoration: none;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-width: 92px;
    }
    .btn-reset:hover{
      background: rgba(255,255,255,0.10) !important;
    }

    /* Results panel */
    .results-panel{
      border-radius: 18px;
      padding: 14px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.10);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      box-shadow: 0 18px 46px rgba(0,0,0,0.28);
    }

    /* Cards view */
    .cards-grid{
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    @media (max-width: 1200px){
      .cards-grid{ grid-template-columns: 1fr; }
    }

    .ds-card{
      position: relative;
      border-radius: 18px;
      overflow: hidden;
      border: 1px solid rgba(255,255,255,0.12);
      background:
        radial-gradient(520px 220px at 18% 18%, rgba(0,95,254,0.18), rgba(0,0,0,0) 60%),
        radial-gradient(520px 220px at 84% 30%, rgba(165,106,42,0.18), rgba(0,0,0,0) 62%),
        rgba(255,255,255,0.06);
      box-shadow: 0 16px 46px rgba(0,0,0,0.28);
    }
    .ds-card__inner{
      padding: 14px 14px 12px;
    }

    .ds-topline{
      display:flex;
      align-items:center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 8px;
    }
    .ds-chip{
      display:inline-flex;
      align-items:center;
      gap: 8px;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .06em;
      text-transform: uppercase;
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(0,0,0,0.18);
      color: rgba(255,255,255,0.92);
    }
    .ds-chip--national{ background: rgba(0,95,254,0.16); border-color: rgba(0,95,254,0.26); }
    .ds-chip--regional{ background: rgba(255,204,87,0.14); border-color: rgba(255,204,87,0.22); }
    .ds-chip--local{    background: rgba(88,214,141,0.12); border-color: rgba(88,214,141,0.20); }

    .ds-meta{
      font-size: 12px;
      color: rgba(255,255,255,0.72);
      display:flex;
      align-items:center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .ds-title{
      margin: 8px 0 10px;
      font-size: 16px;
      line-height: 1.25;
      font-weight: 750;
      color: rgba(255,255,255,0.94);
    }

    .ds-actions{
      display:flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 12px;
    }
    .btn-pill{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap: 8px;
      padding: 9px 12px;
      border-radius: 999px;
      font-size: 12px;
      text-decoration: none;
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(255,255,255,0.06);
      color: rgba(255,255,255,0.92);
      font-weight: 700;
    }
    .btn-pill:hover{
      background: rgba(255,255,255,0.10);
      color: rgba(255,255,255,0.96);
    }
    .btn-pill--primary{
      background: rgba(0,95,254,0.26);
      border-color: rgba(0,95,254,0.35);
    }
    .btn-pill--primary:hover{
      background: rgba(0,95,254,0.34);
    }
    .btn-pill--brown{
      background: rgba(165,106,42,0.34);
      border-color: rgba(165,106,42,0.38);
    }
    .btn-pill--brown:hover{
      background: rgba(165,106,42,0.44);
    }
    .btn-pill--soft{
      background: rgba(255,255,255,0.08);
      border-color: rgba(255,255,255,0.18);
    }
    .btn-pill--soft:hover{
      background: rgba(255,255,255,0.14);
    }

    /* Table view */
    .table-wrap{
      width: 100%;
      overflow: auto;
      border-radius: 16px;
      border: 1px solid rgba(255,255,255,0.10);
      background: rgba(0,0,0,0.14);
    }
    table.catalog-table{
      width: 100%;
      border-collapse: collapse;
      min-width: 820px;
    }
    table.catalog-table thead th{
      position: sticky;
      top: 0;
      background: rgba(255,255,255,0.06);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      color: rgba(255,255,255,0.82);
      font-size: 12px;
      letter-spacing: .10em;
      text-transform: uppercase;
      padding: 12px 12px;
      border-bottom: 1px solid rgba(255,255,255,0.10);
      white-space: nowrap;
    }
    table.catalog-table td{
      padding: 12px 12px;
      border-bottom: 1px solid rgba(255,255,255,0.08);
      color: rgba(255,255,255,0.88);
      font-size: 13px;
      vertical-align: middle;
    }
    .td-muted{ color: rgba(255,255,255,0.66); font-size: 12px; }

    .badge-scope{
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: .06em;
      text-transform: uppercase;
      border: 1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.06);
      color: rgba(255,255,255,0.92);
      white-space: nowrap;
    }

    /* Pagination */
    .pagination-simple{
      margin-top: 14px;
      display:flex;
      align-items:center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
      color: rgba(255,255,255,0.70);
      font-size: 12px;
    }
    .pagination-simple a{
      text-decoration: none;
      padding: 8px 12px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(255,255,255,0.06);
      color: rgba(255,255,255,0.90);
      font-weight: 700;
    }
    .pagination-simple a:hover{
      background: rgba(255,255,255,0.10);
    }

    /* Utility */
    .hidden{ display:none !important; }

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

<body class="catalog-page">
  <div class="catalog-bg" aria-hidden="true"></div>

  <div class="page">
    <div id="home">
      <?= view('partials/site_header', [
        'activePage' => 'catalog',
      ]) ?>

      <main class="catalog-shell">
        <div class="catalog-wrap">

          <!-- Top header -->
          <div class="catalog-top">
            <div class="catalog-title-wrap">
              <h1 class="catalog-title">Data Catalog</h1>
            </div>
          </div>

          <!-- Toolbar -->
          <div class="catalog-toolbar">
            <div class="toolbar-left">
              <span class="pill">Scope filters: <strong><?= empty($scopes) ? 'All' : esc(implode(', ', array_map('ucfirst', $scopes))) ?></strong></span>
              <span class="pill">Properties:
                <strong>
                  <?= ($downloadable || $viewable) ? '' : 'All' ?>
                  <?= $downloadable ? 'Downloadable ' : '' ?>
                  <?= $viewable ? 'Viewable' : '' ?>
                </strong>
              </span>
            </div>

            <div class="toolbar-right">
              <div class="view-toggle" role="group" aria-label="View toggle">
                <button type="button" id="btnCards" class="is-active">Cards</button>
                <button type="button" id="btnTable">Table</button>
              </div>
            </div>
          </div>

          <div class="catalog-grid">
            <!-- Filters -->
            <aside class="filters-panel">
              <div class="filters-title">
                <span>Filters</span>
                <a class="btn-reset" href="<?= esc($baseUrl) ?>">Reset</a>
              </div>

              <form method="get" class="filters-form" id="filtersForm">
                <div class="filters-row">
                  <div>
                    <label for="q">Search</label>
                    <input
                      class="form-control"
                      type="text"
                      name="q"
                      id="q"
                      value="<?= esc_attr($search) ?>"
                      placeholder="Search dataset title..."
                      autocomplete="off"
                    >
                  </div>

                  <div>
                    <label for="per_page">Items per page</label>
                    <select name="per_page" id="per_page" class="form-select">
                      <?php foreach ([10, 20, 50] as $n): ?>
                        <option value="<?= $n ?>" <?= q_selected($perPage === (int)$n) ?>><?= $n ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div>
                    <label>Properties</label>
                    <div class="check-row">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="downloadable" id="downloadable" value="1" <?= q_checked($downloadable) ?>>
                        <label class="form-check-label" for="downloadable">Downloadable</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="viewable" id="viewable" value="1" <?= q_checked($viewable) ?>>
                        <label class="form-check-label" for="viewable">Viewable</label>
                      </div>
                    </div>
                  </div>

                  <div>
                    <label>Spatial scope</label>
                    <?php
                      $scopeOptions = [
                        'national' => 'National',
                        'regional' => 'Regional',
                      ];
                    ?>
                    <div class="check-row">
                      <?php foreach ($scopeOptions as $key => $label): ?>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="scope[]" id="scope_<?= esc_attr($key) ?>" value="<?= esc_attr($key) ?>" <?= q_checked(in_array($key, $scopes, true)) ?>>
                          <label class="form-check-label" for="scope_<?= esc_attr($key) ?>"><?= esc($label) ?></label>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>

                <!-- Keep page reset on filter apply -->
                <input type="hidden" name="page" value="1">

                <div class="filters-actions">
                  <button type="submit" class="btn btn-apply">Apply</button>
                  <button type="button" class="btn btn-reset" id="btnQuickApply">Quick apply</button>
                </div>
              </form>
            </aside>

            <!-- Results -->
            <section class="results-panel">

              <!-- Cards View -->
              <div id="viewCards">
                <?php if (empty($datasets)): ?>
                  <div class="td-muted">No datasets found for current filter.</div>
                <?php else: ?>
                  <div class="cards-grid">
                    <?php foreach ($datasets as $d): ?>
                      <?php
                        $scope = $d['spatial_scope'] ?? 'national';
                        $chipClass = 'ds-chip--national';
                        if ($scope === 'regional') $chipClass = 'ds-chip--regional';
                        if ($scope === 'local')    $chipClass = 'ds-chip--local';

                        $isView = !empty($d['is_viewable']);
                        $isDown = !empty($d['is_downloadable']);
                        $items  = $d['items_count'] ?? null;
                        $backend = $d['backend_type'] ?? null;
                        $schema  = $d['data_schema'] ?? null;
                        $table   = $d['data_table'] ?? null;
                      ?>
                      <article class="ds-card">
                        <div class="ds-card__inner">
                          <div class="ds-topline">
                            <span class="ds-chip <?= esc_attr($chipClass) ?>"><?= esc(strtoupper($scope)) ?></span>
                            <div class="ds-meta">
                              <span><?= esc($d['country_name'] ?? $countryName) ?></span>
                              <?php if ($items !== null && $items !== ''): ?>
                                <span>&middot;</span><span><?= esc((string)$items) ?> items</span>
                              <?php endif; ?>
                            </div>
                          </div>

                          <div class="ds-title"><?= esc($d['title'] ?? '-') ?></div>

                          <div class="ds-meta">
                            <?php if ($backend): ?>
                              <span>Backend: <strong><?= esc($backend) ?></strong></span>
                            <?php endif; ?>
                            <?php if ($schema && $table): ?>
                              <span>&middot;</span><span><strong><?= esc($schema) ?>.<?= esc($table) ?></strong></span>
                            <?php endif; ?>
                          </div>

                          <div class="ds-actions">
                            <?php if ($isView): ?>
                              <a class="btn-pill btn-pill--primary" href="<?= site_url('catalog/view/' . (int)$d['id']) ?>">View</a>
                            <?php endif; ?>

                            <?php if ($isDown): ?>
                              <a class="btn-pill btn-pill--brown" href="<?= site_url('catalog/download/' . (int)$d['id']) ?>">Download</a>
                            <?php endif; ?>

                            <?php if ($isView): ?>
                              <a class="btn-pill btn-pill--soft" href="<?= site_url('catalog/download-metadata/' . (int)$d['id']) ?>">Metadata</a>
                            <?php endif; ?>

                            <?php if (!$isDown && !$isView): ?>
                              <span class="td-muted">No actions available</span>
                            <?php endif; ?>
                          </div>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Table View -->
              <div id="viewTable" class="hidden">
                <div class="table-wrap">
                  <table class="catalog-table">
                    <thead>
                      <tr>
                        <th style="width: 44%;">Title</th>
                        <th style="width: 14%;">Scope</th>
                        <th style="width: 18%;">Country/City</th>
                        <th style="width: 24%;">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($datasets)): ?>
                        <tr>
                          <td colspan="4" class="td-muted">No datasets found for current filter.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($datasets as $d): ?>
                          <?php
                            $scope = $d['spatial_scope'] ?? 'national';
                            $isView = !empty($d['is_viewable']);
                            $isDown = !empty($d['is_downloadable']);
                          ?>
                          <tr>
                            <td><?= esc($d['title'] ?? '-') ?></td>
                            <td>
                              <span class="badge-scope"><?= esc(strtoupper($scope)) ?></span>
                            </td>
                            <td class="td-muted"><?= esc($d['country_name'] ?? $countryName) ?></td>
                            <td>
                              <div class="ds-actions" style="margin:0;">
                                <?php if ($isView): ?>
                                  <a class="btn-pill btn-pill--primary" href="<?= site_url('catalog/view/' . (int)$d['id']) ?>">View</a>
                                <?php endif; ?>

                                <?php if ($isDown): ?>
                                  <a class="btn-pill btn-pill--brown" href="<?= site_url('catalog/download/' . (int)$d['id']) ?>">Download</a>
                                <?php endif; ?>

                                <?php if ($isView): ?>
                                  <a class="btn-pill btn-pill--soft" href="<?= site_url('catalog/download-metadata/' . (int)$d['id']) ?>">Metadata</a>
                                <?php endif; ?>

                                <?php if (!$isDown && !$isView): ?>
                                  <span class="td-muted">No actions</span>
                                <?php endif; ?>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Pagination -->
              <div class="pagination-simple">
                <div>
                  Total: <strong><?= number_format($total) ?></strong> datasets
                  &nbsp;|&nbsp; Page <strong><?= number_format($page) ?></strong> of <strong><?= number_format(max(1, $totalPages)) ?></strong>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                  <?php
                    $qPrev = $query;
                    $qNext = $query;
                  ?>
                  <?php if ($page > 1): ?>
                    <?php $qPrev['page'] = $page - 1; ?>
                    <a href="<?= esc($baseUrl . '?' . http_build_query($qPrev)) ?>">&laquo; Previous</a>
                  <?php endif; ?>

                  <?php if ($page < $totalPages): ?>
                    <?php $qNext['page'] = $page + 1; ?>
                    <a href="<?= esc($baseUrl . '?' . http_build_query($qNext)) ?>">Next &raquo;</a>
                  <?php endif; ?>
                </div>
              </div>

            </section>
          </div>

        </div>
      </main>

    </div>
  </div>

  <!-- Keep your template JS -->
  <script src="<?= base_url('site/js/core.min.js'); ?>"></script>
  <script src="<?= base_url('site/js/script.js'); ?>"></script>

  <script>
    // View toggle (Cards/Table)
    (function(){
      const btnCards = document.getElementById('btnCards');
      const btnTable = document.getElementById('btnTable');
      const viewCards = document.getElementById('viewCards');
      const viewTable = document.getElementById('viewTable');

      function setMode(mode){
        const isCards = (mode === 'cards');
        viewCards.classList.toggle('hidden', !isCards);
        viewTable.classList.toggle('hidden', isCards);
        btnCards.classList.toggle('is-active', isCards);
        btnTable.classList.toggle('is-active', !isCards);
        localStorage.setItem('catalog_view_mode', mode);
      }

      btnCards?.addEventListener('click', () => setMode('cards'));
      btnTable?.addEventListener('click', () => setMode('table'));

      const saved = localStorage.getItem('catalog_view_mode');
      if (saved === 'table') setMode('table');
    })();

    // Quick apply button: submits the form (useful on mobile)
    (function(){
      const btn = document.getElementById('btnQuickApply');
      const form = document.getElementById('filtersForm');
      btn?.addEventListener('click', () => form?.submit());
    })();
  </script>
</body>
</html>
