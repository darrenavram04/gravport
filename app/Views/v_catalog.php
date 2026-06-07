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
$anomalies    = is_array($anomalies ?? null) ? $anomalies : [];
$levels       = is_array($levels ?? null) ? $levels : [];
$quota        = $quota ?? null;
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
  <title>GravPort | Data Catalog</title>

  <!-- Keep your existing template assets -->
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css'); ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/fonts.css'); ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=30'); ?>">
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
      padding-top: calc(var(--landing-header-offset, 112px) + 12px);
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
      line-height: 1.18;
      letter-spacing: -0.045em;
      color: #ffffff;
      text-shadow: 0 12px 34px rgba(0,0,0,0.26);
      padding-bottom: 8px;
      overflow: visible;
    }

    .catalog-subtitle{
      max-width: 720px;
      margin: 14px 0 0;
      color: rgba(255,255,255,0.74);
      font-size: 15px;
      line-height: 1.7;
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

    /* Quota indicator */
    .quota-indicator {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 16px;
      padding: 10px 16px;
      border-radius: 14px;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      font-size: 12px;
      color: rgba(255,255,255,0.6);
      flex-wrap: wrap;
    }
    .quota-tier-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }
    .quota-tier-badge.free { background:rgba(74,154,245,0.15); color:#4a9af5; border:1px solid rgba(74,154,245,0.25); }
    .quota-tier-badge.enterprise { background:rgba(240,165,0,0.15); color:#f0a500; border:1px solid rgba(240,165,0,0.3); }
    .quota-tier-badge.lite, .quota-tier-badge.pro, .quota-tier-badge.team { background:rgba(97,212,255,0.12); color:#61d4ff; border:1px solid rgba(97,212,255,0.22); }
    .quota-mini-bar {
      flex: 1;
      min-width: 120px;
      max-width: 180px;
      height: 5px;
      background: rgba(255,255,255,0.1);
      border-radius: 99px;
      overflow: hidden;
    }
    .quota-mini-bar-fill { height: 100%; border-radius: 99px; }

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
      top: calc(var(--landing-header-offset) + 6px);
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
      .filters-panel .filters-form{ display: none; }
      .filters-panel.is-open .filters-form{ display: block; }
      .filters-toggle-btn{
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.14);
        color: rgba(255,255,255,.85); border-radius: 999px;
        padding: 5px 14px; font-size: 12px; font-weight: 700; cursor: pointer;
      }
    }
    @media (min-width: 993px){ .filters-toggle-btn{ display: none; } }

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

    /* ===== Cinematic Loader ===== */
    body.cat-loading { overflow: hidden; }
    .cat-loader {
      position: fixed; inset: 0; z-index: 99999;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      background: #04101d;
      transform: translateY(0);
      transition: transform 0.9s cubic-bezier(0.76,0,0.24,1);
    }
    .cat-loader.is-done { transform: translateY(-100%); }
    .cat-loader__brand {
      display: flex; align-items: center; gap: 14px;
      margin-bottom: 32px;
      opacity: 0; transform: translateY(16px);
      transition: opacity 0.5s, transform 0.5s;
    }
    .cat-loader__brand img { width: 46px; height: 46px; object-fit: contain; filter: drop-shadow(0 0 14px rgba(167,96,37,0.5)); }
    .cat-loader__brand strong { font-family: "Poppins",sans-serif; font-size: 26px; font-weight: 800; color: #fff; }
    .cat-loader__tag {
      font-family: "Poppins",sans-serif; font-size: 10px; font-weight: 800;
      letter-spacing: 0.2em; text-transform: uppercase;
      color: rgba(255,255,255,0.36); margin-bottom: 24px;
      opacity: 0; transition: opacity 0.4s 0.25s;
    }
    .cat-loader__bar { width: 200px; height: 2px; background: rgba(255,255,255,0.1); border-radius: 999px; overflow: hidden; opacity: 0; transition: opacity 0.3s 0.15s; }
    .cat-loader__fill { height: 100%; width: 0%; background: linear-gradient(90deg,#a76025,#ffbf74,#61d4ff); border-radius: 999px; transition: width 1.1s cubic-bezier(0.4,0,0.2,1) 0.1s; }
    .cat-loader.is-visible .cat-loader__brand { opacity: 1; transform: translateY(0); }
    .cat-loader.is-visible .cat-loader__bar  { opacity: 1; }
    .cat-loader.is-visible .cat-loader__tag  { opacity: 1; }
    .cat-loader.is-loading .cat-loader__fill { width: 100%; }

    /* ===== Scroll progress ===== */
    .cat-progress { position: fixed; top: 0; left: 0; height: 3px; width: 0%; background: linear-gradient(90deg,#a76025,#ffbf74,#61d4ff); z-index: 9999; pointer-events: none; transition: width 0.09s linear; }

    /* ===== Title clip-path reveal ===== */
    .catalog-title { clip-path: inset(0 100% 0 0); transition: clip-path 0.9s cubic-bezier(0.16,1,0.3,1) 0.25s; }
    .catalog-title.is-revealed { clip-path: inset(0 0% 0 0); }

    /* ===== Panel slide-in ===== */
    .filters-panel { opacity: 0; transform: translateX(-20px); transition: opacity 0.55s 0.4s, transform 0.6s cubic-bezier(0.16,1,0.3,1) 0.4s; }
    .filters-panel.panel-in { opacity: 1; transform: translateX(0); }
    .results-panel { opacity: 0; transform: translateY(14px); transition: opacity 0.55s 0.5s, transform 0.6s cubic-bezier(0.16,1,0.3,1) 0.5s; }
    .results-panel.panel-in { opacity: 1; transform: translateY(0); }

    /* ===== Card entrance stagger ===== */
    .ds-card {
      opacity: 0;
      transform: translateY(24px);
      transition: opacity 0.5s ease, transform 0.55s cubic-bezier(0.16,1,0.3,1),
                  border-color 0.25s ease, box-shadow 0.3s ease;
    }
    .ds-card.card-in { opacity: 1; transform: translateY(0); }
    .ds-card:hover { border-color: rgba(255,191,116,0.3) !important; box-shadow: 0 20px 60px rgba(0,0,0,0.38) !important; }

    /* ===== Card spotlight hover (pseudo-element) ===== */
    .ds-card { position: relative; }
    .ds-card__inner { position: relative; z-index: 1; }
    .ds-card::before {
      content: '';
      position: absolute; inset: 0;
      border-radius: inherit;
      background: radial-gradient(300px circle at var(--cmx,50%) var(--cmy,50%), rgba(255,191,116,0.13), transparent 65%);
      opacity: 0;
      transition: opacity 0.3s ease;
      pointer-events: none;
      z-index: 0;
    }
    .ds-card:hover::before { opacity: 1; }

    /* ===== Table row hover ===== */
    table.catalog-table tbody tr { transition: background 0.18s; }
    table.catalog-table tbody tr:hover { background: rgba(255,191,116,0.06); }

    .nav-auth{
      display:flex;
      align-items:center;
      gap:12px;
    }
    .nav-login, .nav-logout{
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
    .nav-login:hover, .nav-logout:hover{
      background: rgba(0,0,0,0.22);
    }
    .nav-login--primary{
      background: rgba(255,255,255,0.95);
      color:#69350f;
      border-color: rgba(255,255,255,0.95);
    }
    .nav-login--secondary,
    .nav-logout--secondary{
      background: rgba(7,19,36,0.28);
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

<body class="catalog-page gravport-landing cat-loading">

  <div class="cat-loader" id="catLoader" role="status" aria-label="Loading catalog">
    <div class="cat-loader__brand">
      <img src="<?= base_url('images/gravport_logo_color.png'); ?>" alt="">
      <strong>GravPort</strong>
    </div>
    <p class="cat-loader__tag">Data Catalog</p>
    <div class="cat-loader__bar">
      <div class="cat-loader__fill"></div>
    </div>
  </div>

  <div class="cat-progress" id="catProgress"></div>
  <div class="catalog-bg" aria-hidden="true"></div>

  <div class="page">
    <div id="home">
      <?= view('partials/site_header', [
        'activePage' => 'catalog',
        'headerClass' => 'header--solid',
      ]) ?>

      <main class="catalog-shell">
        <div class="catalog-wrap">

          <!-- Top header -->
          <div class="catalog-top">
            <div class="catalog-title-wrap">
              <h1 class="catalog-title">Data Catalog</h1>
            </div>
          </div>

          <?php if ($quota !== null): ?>
          <?php
            $qTier     = strtolower($quota['tier'] ?? 'none');
            $qUsedB    = (int)($quota['used'] ?? 0);
            $qLimitB   = $quota['limit'];      // null = unlimited
            $qUnlimited = ($qLimitB === null);

            // Format bytes ke MB/GB yang human-readable
            $fmtBytes = static function(int $b): string {
                if ($b >= 1073741824) return round($b / 1073741824, 1) . ' GB';
                if ($b >= 1048576)   return round($b / 1048576, 1) . ' MB';
                if ($b >= 1024)      return round($b / 1024, 1) . ' KB';
                return $b . ' B';
            };

            $qPct = (!$qUnlimited && $qLimitB > 0)
                ? min(100, round($qUsedB / $qLimitB * 100))
                : 0;
            $qBarColor = $qPct >= 90 ? '#ef4444' : ($qPct >= 70 ? '#f0a500' : '#4a9af5');
            $tierLabel = match($qTier) {
                'lite','solo' => 'Lite', 'pro' => 'Pro', 'team' => 'Team',
                'enterprise' => 'Enterprise', 'government' => 'Government',
                default => ucfirst($qTier) ?: 'Lite',
            };
          ?>
          <div class="quota-indicator">
            <span class="quota-tier-badge <?= in_array($qTier, ['enterprise','government'], true) ? 'enterprise' : 'free' ?>">
              <i class="bi bi-person-fill"></i>
              <?= esc($tierLabel) ?>
            </span>
            <?php if (!$qUnlimited): ?>
              <span><?= $fmtBytes($qUsedB) ?> / <?= $fmtBytes((int)$qLimitB) ?> minggu ini</span>
              <div class="quota-mini-bar">
                <div class="quota-mini-bar-fill" style="width:<?= $qPct ?>%; background:<?= $qBarColor ?>;"></div>
              </div>
              <?php if ($qPct >= 100): ?>
                <span style="color:#ef4444; font-weight:600;">Kuota minggu ini habis</span>
              <?php endif; ?>
            <?php else: ?>
              <span><?= $fmtBytes($qUsedB) ?> diunduh &middot; <i class="bi bi-infinity"></i> unlimited</span>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Toolbar -->
          <div class="catalog-toolbar">
            <div class="toolbar-left">
              <span class="pill">Scope: <strong><?= empty($scopes) ? 'All' : esc(implode(', ', array_map('ucfirst', $scopes))) ?></strong></span>
              <span class="pill">Data: <strong><?= empty($anomalies) ? 'All' : esc(strtoupper(implode(', ', $anomalies))) ?></strong></span>
              <span class="pill">Level: <strong><?= empty($levels) ? 'All' : esc(implode(', ', array_map('strtoupper', array_map(static fn ($value) => str_replace('level', 'L', $value), $levels)))) ?></strong></span>
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
                <button type="button" id="btnCards" class="is-active" data-i18n="cat.cards">Cards</button>
                <button type="button" id="btnTable" data-i18n="cat.table">Table</button>
              </div>
            </div>
          </div>

          <div class="catalog-grid">
            <!-- Filters -->
            <aside class="filters-panel">
              <div class="filters-title">
                <span data-i18n="cat.filters">Filters</span>
                <div style="display:flex;gap:8px;align-items:center;">
                  <button type="button" class="filters-toggle-btn" id="filtersToggle" aria-expanded="false">
                    <i class="bi bi-sliders"></i> <span data-i18n="cat.show">Tampilkan</span>
                  </button>
                  <a class="btn-reset" href="<?= esc($baseUrl) ?>" title="Clear all filters"><i class="bi bi-x-circle" style="margin-right:4px;"></i><span data-i18n="cat.reset">Reset</span></a>
                </div>
              </div>

              <form method="get" class="filters-form" id="filtersForm">
                <div class="filters-row">
                  <div>
                    <label for="q" data-i18n="cat.search.lbl">Pencarian</label>
                    <input
                      class="form-control"
                      type="text"
                      name="q"
                      id="q"
                      value="<?= esc_attr($search) ?>"
                      data-i18n-placeholder="cat.search.ph" placeholder="Cari dataset..."
                      autocomplete="off"
                    >
                  </div>

                  <div>
                    <label for="per_page" data-i18n="cat.per_page">Items per page</label>
                    <select name="per_page" id="per_page" class="form-select">
                      <?php foreach ([10, 20, 50] as $n): ?>
                        <option value="<?= $n ?>" <?= q_selected($perPage === (int)$n) ?>><?= $n ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div>
                    <label>Data</label>
                    <div class="check-row">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="anomaly[]" id="anomaly_faa" value="faa" <?= q_checked(in_array('faa', $anomalies, true)) ?>>
                        <label class="form-check-label" for="anomaly_faa">FAA</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="anomaly[]" id="anomaly_cba" value="cba" <?= q_checked(in_array('cba', $anomalies, true)) ?>>
                        <label class="form-check-label" for="anomaly_cba">CBA</label>
                      </div>
                    </div>
                  </div>

                  <div>
                    <label>Level</label>
                    <div class="check-row">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="level[]" id="level_1" value="level1" <?= q_checked(in_array('level1', $levels, true)) ?>>
                        <label class="form-check-label" for="level_1">Level 1</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="level[]" id="level_2" value="level2" <?= q_checked(in_array('level2', $levels, true)) ?>>
                        <label class="form-check-label" for="level_2">Level 2</label>
                      </div>
                    </div>
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

                  <div>
                    <label>Organisasi</label>
                    <div class="check-row flex-col">
                      <?php foreach (($organizations ?? []) as $org): ?>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox"
                            name="organization[]"
                            id="org_<?= esc_attr($org['id']) ?>"
                            value="<?= esc_attr($org['id']) ?>"
                            <?= q_checked(in_array((string)$org['id'], $selectedOrgs ?? [], true)) ?>>
                          <label class="form-check-label" for="org_<?= esc_attr($org['id']) ?>"><?= esc($org['name']) ?></label>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>

                <div class="filters-actions">
                  <button type="submit" class="btn btn-apply">Terapkan Filter</button>
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
                        $anomalyLabel = strtoupper((string) ($d['anomaly_key'] ?? ''));
                        $levelLabel = strtoupper(str_replace('level', 'L', (string) ($d['level_key'] ?? '')));
                        $downloadLabel = ($d['type'] ?? '') === 'raster' ? 'GeoTIFF' : 'CSV';
                      ?>
                      <article class="ds-card">
                        <div class="ds-card__inner">
                          <div class="ds-topline">
                            <span class="ds-chip <?= esc_attr($chipClass) ?>"><?= esc(strtoupper($scope)) ?></span>
                            <div class="ds-meta">
                              <?php if ($anomalyLabel !== '' || $levelLabel !== ''): ?>
                                <span><?= esc(trim($anomalyLabel . ' ' . $levelLabel)) ?></span>
                                <span>&middot;</span>
                              <?php endif; ?>
                              <span><?= esc($d['country_name'] ?? $countryName) ?></span>
                              <?php if ($items !== null && $items !== ''): ?>
                                <span>&middot;</span><span><?= esc((string)$items) ?> items</span>
                              <?php endif; ?>
                            </div>
                          </div>

                          <div class="ds-title"><?= esc($d['title'] ?? '-') ?></div>

                          <div class="ds-actions">
                            <?php if ($isView): ?>
                              <a class="btn-pill btn-pill--primary" href="<?= site_url('catalog/view/' . (int)$d['id']) . '?from=' . urlencode(current_url(true)) ?>" data-i18n="cat.view">View</a>
                            <?php endif; ?>

                            <?php if ($isDown): ?>
                              <a class="btn-pill btn-pill--brown" href="<?= site_url('catalog/download/' . (int)$d['id']) ?>"><span data-i18n="cat.dl">Unduh</span> <?= esc($downloadLabel) ?></a>
                            <?php endif; ?>

                            <?php if ($isView): ?>
                              <a class="btn-pill btn-pill--soft" href="<?= site_url('catalog/download-metadata/' . (int)$d['id']) ?>" data-i18n="cat.metadata.btn">Metadata</a>
                            <?php endif; ?>

                            <?php if (!$isDown && !$isView): ?>
                              <span class="td-muted" data-i18n="cat.no_actions">No actions available</span>
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
                            $downloadLabel = ($d['type'] ?? '') === 'raster' ? 'GeoTIFF' : 'CSV';
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
                                  <a class="btn-pill btn-pill--primary" href="<?= site_url('catalog/view/' . (int)$d['id']) . '?from=' . urlencode(current_url(true)) ?>" data-i18n="cat.view">View</a>
                                <?php endif; ?>

                                <?php if ($isDown): ?>
                                  <a class="btn-pill btn-pill--brown" href="<?= site_url('catalog/download/' . (int)$d['id']) ?>"><span data-i18n="cat.dl">Unduh</span> <?= esc($downloadLabel) ?></a>
                                <?php endif; ?>

                                <?php if ($isView): ?>
                                  <a class="btn-pill btn-pill--soft" href="<?= site_url('catalog/download-metadata/' . (int)$d['id']) ?>" data-i18n="cat.metadata.btn">Metadata</a>
                                <?php endif; ?>

                                <?php if (!$isDown && !$isView): ?>
                                  <span class="td-muted" data-i18n="cat.no_actions">No actions available</span>
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

  <script>
  /* ============================================================
     GravPort Catalog - Creative Layer
  ============================================================ */
  (function () {

    /* --- Cinematic loader --- */
    var loader = document.getElementById('catLoader');
    if (loader) {
      setTimeout(function () {
        loader.classList.add('is-visible');
        setTimeout(function () {
          loader.classList.add('is-loading');
          setTimeout(function () {
            loader.classList.add('is-done');
            document.body.classList.remove('cat-loading');
            /* Trigger page animations after loader gone */
            triggerPageIn();
            setTimeout(function () {
              if (loader.parentNode) loader.parentNode.removeChild(loader);
            }, 1000);
          }, 1550);
        }, 80);
      }, 50);
    } else {
      triggerPageIn();
    }

    function triggerPageIn() {
      /* Title reveal */
      var title = document.querySelector('.catalog-title');
      if (title) setTimeout(function () { title.classList.add('is-revealed'); }, 60);

      /* Panel slide-in */
      var fp = document.querySelector('.filters-panel');
      var rp = document.querySelector('.results-panel');
      if (fp) setTimeout(function () { fp.classList.add('panel-in'); }, 100);
      if (rp) setTimeout(function () { rp.classList.add('panel-in'); }, 160);

      /* Card stagger entrance */
      document.querySelectorAll('.ds-card').forEach(function (card, i) {
        setTimeout(function () { card.classList.add('card-in'); }, 380 + i * 75);
      });
    }

    /* --- Scroll progress bar --- */
    var progressBar = document.getElementById('catProgress');
    if (progressBar) {
      window.addEventListener('scroll', function () {
        var doc = document.documentElement;
        var pct = (window.scrollY / (doc.scrollHeight - window.innerHeight)) * 100;
        progressBar.style.width = Math.min(100, pct) + '%';
      }, { passive: true });
    }

    /* --- Card spotlight hover --- */
    document.querySelectorAll('.ds-card').forEach(function (card) {
      card.addEventListener('mousemove', function (e) {
        var r = card.getBoundingClientRect();
        card.style.setProperty('--cmx', ((e.clientX - r.left) / r.width * 100) + '%');
        card.style.setProperty('--cmy', ((e.clientY - r.top)  / r.height * 100) + '%');
      });
    });

    /* --- Re-animate cards on view toggle --- */
    ['btnCards','btnTable'].forEach(function (id) {
      var btn = document.getElementById(id);
      if (!btn) return;
      btn.addEventListener('click', function () {
        if (id === 'btnCards') {
          document.querySelectorAll('.ds-card').forEach(function (c, i) {
            c.classList.remove('card-in');
            setTimeout(function () { c.classList.add('card-in'); }, 40 + i * 55);
          });
        }
      });
    });

  })();

  // Mobile filter toggle
  (function(){
    const btn   = document.getElementById('filtersToggle');
    const panel = document.querySelector('.filters-panel');
    if (!btn || !panel) return;
    btn.addEventListener('click', function(){
      const open = panel.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open);
      btn.innerHTML = open
        ? '<i class="bi bi-sliders"></i> Sembunyikan'
        : '<i class="bi bi-sliders"></i> Tampilkan';
    });
  })();
  </script>
</body>
</html>

