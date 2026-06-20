<?php $isRenewing = auth_is_logged_in() && !auth_is_guest(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Daftar</title>
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css'); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,700;0,800;1,700;1,800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* ── Design tokens ─────────────────────────────────────────────────────── */
    :root {
      --bronze:   #a76025;
      --amber:    #ffbf74;
      --amber-d:  #e8a24a;
      --cyan:     #61d4ff;
      --ink:      #060d1a;
      --navy:     #091526;
      --surface:  rgba(10,20,38,.82);
      --glass:    rgba(255,255,255,.055);
      --border:   rgba(255,255,255,.10);
      --border-h: rgba(255,191,116,.38);
      --text:     rgba(240,246,255,.96);
      --muted:    rgba(190,208,235,.65);
      --danger-bg: rgba(255,100,100,.14);
      --danger-bd: rgba(255,100,100,.26);
      --radius-xl: 28px;
      --radius-l:  18px;
      --radius-m:  13px;
      --shadow-card: 0 32px 90px rgba(2,8,22,.55), 0 2px 0 rgba(255,255,255,.04) inset;
    }

    /* ── Reset ─────────────────────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── Body / background ─────────────────────────────────────────────────── */
    body {
      min-height: 100vh;
      font-family: "Manrope", sans-serif;
      color: var(--text);
      background: var(--ink);
      overflow-x: hidden;
    }

    /* Animated mesh gradient */
    .gp-bg {
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background:
        radial-gradient(ellipse 72% 56% at 18%  12%, rgba(167,96,37,.22)   0%, transparent 68%),
        radial-gradient(ellipse 58% 48% at 82%   8%, rgba(20,40,160,.20)   0%, transparent 62%),
        radial-gradient(ellipse 48% 52% at 50% 100%, rgba(97,212,255,.07)  0%, transparent 60%),
        linear-gradient(168deg, #070d1f 0%, #040b18 55%, #060e22 100%);
      animation: bgDrift 18s ease-in-out infinite alternate;
    }
    @keyframes bgDrift {
      0%   { filter: hue-rotate(0deg) brightness(1); }
      50%  { filter: hue-rotate(6deg) brightness(1.04); }
      100% { filter: hue-rotate(-4deg) brightness(.98); }
    }

    /* Subtle grid overlay */
    .gp-grid {
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background-image:
        linear-gradient(rgba(255,255,255,.018) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.018) 1px, transparent 1px);
      background-size: 44px 44px;
    }

    /* ── Shell / layout ────────────────────────────────────────────────────── */
    .auth-shell {
      position: relative; z-index: 1;
      min-height: 100vh; display: grid; place-items: center;
      padding: 32px 18px;
    }
    .auth-frame {
      width: min(1160px, 100%);
      display: grid;
      grid-template-columns: minmax(0,1.1fr) minmax(340px,0.9fr);
      gap: 20px;
      align-items: stretch;
    }
    @media(max-width:880px) {
      .auth-frame { grid-template-columns: 1fr; }
      .auth-story { display: none; }
    }

    /* ── Shared card shell ─────────────────────────────────────────────────── */
    .auth-story, .auth-card {
      position: relative;
      border-radius: var(--radius-xl);
      border: 1px solid var(--border);
      box-shadow: var(--shadow-card);
      overflow: hidden;
    }

    /* ── LEFT — story panel ─────────────────────────────────────────────────── */
    .auth-story {
      padding: 40px 36px;
      background: linear-gradient(160deg, rgba(255,255,255,.05) 0%, rgba(255,255,255,.02) 100%), rgba(6,13,26,.78);
      backdrop-filter: blur(22px);
    }
    /* Radial accent top-right */
    .auth-story::before {
      content: ""; position: absolute; inset: 0; pointer-events: none;
      background:
        radial-gradient(circle 360px at 110% -10%, rgba(255,191,116,.13) 0%, transparent 60%),
        radial-gradient(circle 280px at -10% 110%, rgba(97,212,255,.06) 0%, transparent 60%);
    }

    /* brand */
    .auth-brand {
      display: flex; align-items: center; gap: 14px;
      margin-bottom: 32px; position: relative; z-index: 1;
    }
    .auth-brand img { width: 48px; height: 48px; object-fit: contain; }
    .auth-brand-name {
      font-size: 14px; font-weight: 800; letter-spacing: .18em;
      text-transform: uppercase; color: var(--text);
    }
    .auth-brand-sub {
      font-size: 11px; color: var(--muted); letter-spacing: .08em; margin-top: 2px;
    }

    .auth-kicker {
      display: inline-flex; align-items: center;
      font-size: 10px; font-weight: 800; letter-spacing: .2em; text-transform: uppercase;
      color: var(--amber); margin-bottom: 18px; position: relative; z-index: 1;
    }

    .auth-story h1 {
      font-family: "Fraunces", serif;
      font-size: clamp(2rem, 2.8vw, 2.75rem);
      font-weight: 800; line-height: 1.05; letter-spacing: -.01em;
      margin: 0 0 20px; position: relative; z-index: 1; color: #fff;
    }
    .auth-story h1 em {
      font-style: normal; font-weight: 800; color: #fff;
    }
    .auth-story > p {
      color: var(--muted); font-size: 13.5px; line-height: 1.85;
      position: relative; z-index: 1; margin: 0 0 30px;
    }

    /* tier mini-cards */
    .tier-mini-grid { display: flex; flex-direction: column; gap: 10px; position: relative; z-index: 1; }
    .tier-mini-card {
      padding: 14px 18px; border-radius: var(--radius-l);
      border: 1px solid rgba(255,255,255,.08);
      background: rgba(255,255,255,.03);
      display: flex; justify-content: space-between; align-items: center;
      transition: border-color .2s, background .2s;
    }
    .tier-mini-card:hover { border-color: rgba(255,191,116,.22); background: rgba(255,191,116,.04); }
    .tier-mini-card.highlight { border-color: rgba(255,191,116,.28); background: rgba(255,191,116,.055); }
    .tier-mini-name { font-weight: 700; font-size: 13.5px; }
    .tier-mini-desc { font-size: 11.5px; color: var(--muted); margin-top: 2px; }
    .tier-mini-price { font-size: 12.5px; font-weight: 700; color: var(--amber); white-space: nowrap; margin-left: 14px; }

    /* ── RIGHT — auth card ──────────────────────────────────────────────────── */
    .auth-card {
      padding: 36px 32px;
      background: linear-gradient(168deg, rgba(255,255,255,.065) 0%, rgba(255,255,255,.025) 100%), rgba(7,14,28,.9);
      backdrop-filter: blur(28px);
    }
    .auth-card::before {
      content: ""; position: absolute; inset: 0; pointer-events: none;
      background: radial-gradient(circle 300px at 100% 0%, rgba(255,191,116,.09) 0%, transparent 55%);
    }

    /* ── Progress dots ──────────────────────────────────────────────────────── */
    .step-dots {
      display: flex; gap: 7px;
      margin-bottom: 28px; position: relative; z-index: 1;
    }
    .step-dot {
      height: 6px; border-radius: 999px;
      background: rgba(255,255,255,.12);
      transition: width .35s cubic-bezier(.34,1.56,.64,1), background .25s;
      width: 20px;
    }
    .step-dot.active { background: var(--amber); width: 36px; }

    /* ── Step containers ────────────────────────────────────────────────────── */
    .auth-step {
      position: relative; z-index: 1;
      animation: stepIn .38s cubic-bezier(.16,1,.3,1) both;
    }
    .auth-step.hidden { display: none !important; }
    .auth-step.step-exit {
      animation: stepOut .28s cubic-bezier(.4,0,1,1) both;
    }

    @keyframes stepIn {
      from { opacity: 0; transform: translateX(22px); }
      to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes stepOut {
      from { opacity: 1; transform: translateX(0); }
      to   { opacity: 0; transform: translateX(-18px); }
    }

    /* ── Step head ──────────────────────────────────────────────────────────── */
    .step-head { margin-bottom: 24px; }
    .step-head h2 {
      font-family: "Manrope", sans-serif;
      font-size: 22px; font-weight: 800; margin: 0 0 7px; color: var(--text);
      line-height: 1.15; letter-spacing: -.01em;
    }
    .step-head p { font-size: 13px; color: var(--muted); margin: 0; line-height: 1.65; }

    .step-back {
      display: inline-flex; align-items: center; gap: 7px;
      font-size: 12px; font-weight: 600; color: var(--muted);
      cursor: pointer; background: none; border: none; padding: 0;
      margin-bottom: 20px; letter-spacing: .03em;
      transition: color .18s, gap .18s;
    }
    .step-back:hover { color: var(--text); gap: 10px; }

    /* ── Alerts ─────────────────────────────────────────────────────────────── */
    .alert-danger {
      background: var(--danger-bg); border: 1px solid var(--danger-bd); border-radius: var(--radius-m);
      padding: 12px 16px; margin-bottom: 20px;
      color: rgba(255,220,220,.92); font-size: 12.5px; line-height: 1.65;
      position: relative; z-index: 1;
    }

    /* ── Path chooser (step 0) ──────────────────────────────────────────────── */
    .path-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .path-btn {
      display: flex; flex-direction: column; align-items: flex-start; gap: 8px;
      padding: 20px 18px; border-radius: var(--radius-l);
      border: 1px solid var(--border);
      background: var(--glass); cursor: pointer; text-align: left;
      color: var(--text); transition: .22s cubic-bezier(.34,1.56,.64,1);
      position: relative; overflow: hidden;
    }
    .path-btn::after {
      content: ""; position: absolute; inset: 0;
      background: radial-gradient(circle at 50% 0%, rgba(255,191,116,.10), transparent 70%);
      opacity: 0; transition: opacity .3s;
    }
    .path-btn:hover { border-color: var(--border-h); background: rgba(255,191,116,.05); transform: translateY(-2px); }
    .path-btn:hover::after { opacity: 1; }
    .path-btn i { font-size: 22px; color: var(--amber); }
    .path-btn strong { font-size: 14px; font-weight: 700; }
    .path-btn span { font-size: 12px; color: var(--muted); line-height: 1.5; }

    .auth-switch {
      font-size: 12.5px; color: var(--muted);
      text-align: center; margin-top: 20px; position: relative; z-index: 1;
    }
    .auth-switch a { color: var(--amber); text-decoration: none; font-weight: 700; }
    .auth-switch a:hover { text-decoration: underline; }

    /* ── Billing toggle ─────────────────────────────────────────────────────── */
    .billing-toggle {
      display: inline-flex;
      border: 1px solid var(--border); border-radius: 999px;
      overflow: hidden; margin-bottom: 16px;
      background: rgba(255,255,255,.03);
    }
    .billing-btn {
      padding: 9px 22px; font-size: 11.5px; font-weight: 700; cursor: pointer;
      border: none; background: transparent; color: var(--muted);
      letter-spacing: .05em; transition: .2s; font-family: "Manrope", sans-serif;
    }
    .billing-btn.active {
      background: linear-gradient(135deg, var(--bronze), var(--amber));
      color: #08111f; border-radius: 999px;
    }

    /* ── Tier cards ─────────────────────────────────────────────────────────── */
    .tier-grid { display: flex; flex-direction: column; gap: 9px; margin-bottom: 4px; }
    .tier-card {
      padding: 16px 18px; border-radius: var(--radius-l);
      border: 1.5px solid rgba(255,255,255,.07);
      background: rgba(255,255,255,.028);
      cursor: pointer; transition: .22s cubic-bezier(.34,1.56,.64,1);
      position: relative; overflow: hidden;
    }
    .tier-card::before {
      content: ""; position: absolute; inset: 0;
      background: radial-gradient(circle at 80% 20%, rgba(255,191,116,.07), transparent 55%);
      opacity: 0; transition: opacity .25s;
    }
    .tier-card:hover { border-color: rgba(255,191,116,.3); transform: translateY(-1px); }
    .tier-card:hover::before { opacity: 1; }
    .tier-card.selected {
      border-color: var(--amber);
      background: rgba(255,191,116,.06);
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(167,96,37,.18), 0 0 0 1px rgba(255,191,116,.15);
    }
    .tier-card.selected::before { opacity: 1; }
    .tier-card-top {
      display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 5px;
    }
    .tier-name { font-size: 14.5px; font-weight: 800; }
    .tier-price { font-size: 14.5px; font-weight: 700; color: var(--amber); }
    .tier-price-unit { font-size: 10px; color: var(--muted); margin-left: 3px; font-weight: 500; }
    .tier-features { font-size: 11.5px; color: var(--muted); line-height: 1.75; }
    .tier-check {
      position: absolute; top: 13px; right: 14px;
      display: none; color: var(--amber); font-size: 17px;
      animation: popIn .25s cubic-bezier(.34,1.56,.64,1);
    }
    .tier-card.selected .tier-check { display: block; }
    @keyframes popIn {
      from { transform: scale(0) rotate(-20deg); opacity: 0; }
      to   { transform: scale(1) rotate(0deg);   opacity: 1; }
    }

    /* ── Floating label inputs ──────────────────────────────────────────────── */
    .auth-field { margin-bottom: 16px; position: relative; }
    .auth-field label {
      display: block; font-size: 10.5px; font-weight: 700;
      letter-spacing: .08em; text-transform: uppercase;
      color: var(--muted); margin-bottom: 7px;
    }
    .auth-input {
      width: 100%; padding: 13px 16px;
      border-radius: var(--radius-m);
      border: 1.5px solid rgba(255,255,255,.10);
      background: rgba(255,255,255,.05);
      color: var(--text); font-size: 13.5px; font-family: "Manrope", sans-serif;
      outline: none; transition: border-color .2s, background .2s, box-shadow .2s;
      -webkit-appearance: none; appearance: none;
    }
    .auth-input:focus {
      border-color: var(--amber);
      background: rgba(255,255,255,.07);
      box-shadow: 0 0 0 3px rgba(255,191,116,.10);
    }
    .auth-input::placeholder { color: rgba(255,255,255,.22); }
    .auth-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media(max-width:400px) { .auth-field-row { grid-template-columns: 1fr; } }
    textarea.auth-input { resize: vertical; min-height: 82px; line-height: 1.6; }

    /* Input left-icon variant */
    .auth-input-wrap { position: relative; }
    .auth-input-wrap .auth-input { padding-left: 42px; }
    .auth-input-wrap .fi-icon {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      color: var(--muted); font-size: 15px; pointer-events: none; transition: color .2s;
    }
    .auth-input-wrap:focus-within .fi-icon { color: var(--amber); }

    /* ── Submit button ──────────────────────────────────────────────────────── */
    .auth-submit {
      width: 100%; padding: 14px 20px;
      border-radius: var(--radius-m); border: none; cursor: pointer;
      background: linear-gradient(130deg, #ffe4b0 0%, var(--amber) 45%, var(--bronze) 100%);
      color: #07111e; font-size: 14.5px; font-weight: 800;
      font-family: "Manrope", sans-serif; letter-spacing: .03em;
      transition: opacity .18s, transform .18s, box-shadow .18s;
      margin-top: 6px; position: relative; overflow: hidden;
    }
    .auth-submit::after {
      content: "";
      position: absolute; top: 0; left: -80%;
      width: 50%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,.22), transparent);
      transform: skewX(-20deg);
      transition: left 0s;
    }
    .auth-submit:hover { opacity: .93; transform: translateY(-1px); box-shadow: 0 10px 28px rgba(167,96,37,.35); }
    .auth-submit:hover::after { left: 160%; transition: left .55s ease; }
    .auth-submit:active { transform: translateY(0); }

    /* ── Info note ──────────────────────────────────────────────────────────── */
    .auth-note {
      font-size: 11.5px; color: var(--muted); line-height: 1.75;
      margin-bottom: 16px; position: relative; z-index: 1;
      padding: 10px 14px; border-radius: 10px;
      border: 1px solid rgba(255,255,255,.06); background: rgba(255,255,255,.025);
    }

    /* ── Cinematic loader ───────────────────────────────────────────────────── */
    #auth-loader {
      position: fixed; inset: 0; z-index: 99999;
      background: #060d1a;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      transition: opacity .55s ease, visibility .55s ease;
    }
    #auth-loader img {
      width: 48px; height: 48px; object-fit: contain; margin-bottom: 22px;
      filter: drop-shadow(0 0 18px rgba(167,96,37,.5));
      animation: logoFloat 2s ease-in-out infinite alternate;
    }
    @keyframes logoFloat {
      from { transform: translateY(0); }
      to   { transform: translateY(-5px); }
    }
    #auth-bar-wrap {
      width: 150px; height: 2px;
      background: rgba(255,255,255,.08); border-radius: 999px; overflow: hidden;
    }
    #auth-bar {
      height: 100%; width: 0%;
      background: linear-gradient(90deg, var(--bronze), var(--amber) 50%, var(--cyan));
      border-radius: 999px;
      transition: width .9s cubic-bezier(.4,0,.2,1);
    }

    /* ── Entrance animations ────────────────────────────────────────────────── */
    .anim-fade-up {
      opacity: 0; transform: translateY(16px);
      animation: fadeUp .5s cubic-bezier(.16,1,.3,1) forwards;
    }
    @keyframes fadeUp {
      to { opacity: 1; transform: translateY(0); }
    }
    .auth-story .auth-brand    { animation-delay: .05s; }
    .auth-story .auth-kicker   { animation-delay: .12s; }
    .auth-story h1             { animation-delay: .18s; }
    .auth-story > p            { animation-delay: .24s; }
    .auth-story .tier-mini-grid{ animation-delay: .30s; }
    .auth-card .step-dots      { animation-delay: .10s; }
    .auth-card .auth-step      { animation-delay: .16s; }
  </style>
  <style>
    .gp-lang-pill {
      position: absolute; top: 16px; right: 18px; z-index: 10;
      display: inline-flex; border-radius: 999px;
      border: 1px solid rgba(255,255,255,.18); overflow: hidden;
      background: rgba(255,255,255,.07); backdrop-filter: blur(8px);
    }
    .gp-lang-pill button {
      padding: 5px 12px; font-family: 'Manrope', sans-serif;
      font-size: 11px; font-weight: 700; letter-spacing: .06em;
      color: rgba(255,255,255,.45); background: transparent;
      border: none; cursor: pointer; transition: color .15s, background .15s;
    }
    .gp-lang-pill button.is-active { background: rgba(255,255,255,.16); color: #fff; }
    .gp-lang-pill button:hover:not(.is-active) { color: rgba(255,255,255,.8); }
  </style>
  <script>
  (function(){
    var T = {
      id: {
        'sg.brand.sub':'Geoportal Gravimetri',
        'sg.kicker':'Mulai Berlangganan',
        'sg.h1':'Data gravitasi untuk ', 'sg.h1.em':'setiap kebutuhan.',
        'sg.intro':'Pilih paket yang sesuai, dari eksplorasi individu hingga penggunaan tim profesional. Semua paket memberikan akses ke 628K+ titik data gravitasi Jawa-Bali.',
        'sg.lite.desc':'Level 1+2 | 2 GB/minggu | 1 akun',
        'sg.pro.desc':'Level 1+2 | Unlimited | 1 akun',
        'sg.ent.desc':'Level 1+2 | Unlimited | hingga 10 akun',
        'sg.h2.0':'Buat Akun GravPort', 'sg.p.0':'Pilih jenis akun yang ingin Anda buat.',
        'sg.path.user.t':'Pengguna', 'sg.path.user.s':'Akses & unduh data gravitasi',
        'sg.path.adm.t':'Admin Platform', 'sg.path.adm.s':'Kelola & kontribusi data',
        'sg.switch':'Sudah punya akun?', 'sg.switch.lnk':'Login di sini',
        'sg.back':'Kembali',
        'sg.h2.admin':'Permintaan Akses Admin',
        'sg.p.admin':'Isi data Anda; tim kami akan menghubungi untuk verifikasi dan pengaturan akses.',
        'sg.lbl.name':'Nama Lengkap', 'sg.lbl.email':'Email', 'sg.lbl.msg':'Pesan (opsional)',
        'sg.submit.adm':'Kirim Permintaan',
        'sg.h2.pkg':'Pilih Paket', 'sg.p.pkg':'Bayar bulanan atau hemat 2 bulan dengan paket tahunan.',
        'sg.h2.user':'Buat Akun Pengguna',
        'sg.lbl.fn':'Nama Depan', 'sg.lbl.ln':'Nama Belakang',
        'sg.lbl.pw':'Password', 'sg.lbl.pw2':'Konfirmasi Password',
      },
      en: {
        'sg.brand.sub':'Gravity Geoportal',
        'sg.kicker':'Start Subscribing',
        'sg.h1':'Gravity data for ', 'sg.h1.em':'every need.',
        'sg.intro':'Choose the plan that fits, from individual exploration to professional team use. All plans provide access to 628K+ gravity data points across Java-Bali.',
        'sg.lite.desc':'Level 1+2 | 2 GB/week | 1 account',
        'sg.pro.desc':'Level 1+2 | Unlimited | 1 account',
        'sg.ent.desc':'Level 1+2 | Unlimited | up to 10 accounts',
        'sg.h2.0':'Create GravPort Account', 'sg.p.0':'Choose the account type you want to create.',
        'sg.path.user.t':'User', 'sg.path.user.s':'Access & download gravity data',
        'sg.path.adm.t':'Platform Admin', 'sg.path.adm.s':'Manage & contribute data',
        'sg.switch':'Already have an account?', 'sg.switch.lnk':'Login here',
        'sg.back':'Back',
        'sg.h2.admin':'Admin Access Request',
        'sg.p.admin':'Fill in your details; our team will contact you for verification and access setup.',
        'sg.lbl.name':'Full Name', 'sg.lbl.email':'Email', 'sg.lbl.msg':'Message (optional)',
        'sg.submit.adm':'Send Request',
        'sg.h2.pkg':'Choose a Plan', 'sg.p.pkg':'Pay monthly or save 2 months with an annual plan.',
        'sg.h2.user':'Create User Account',
        'sg.lbl.fn':'First Name', 'sg.lbl.ln':'Last Name',
        'sg.lbl.pw':'Password', 'sg.lbl.pw2':'Confirm Password',
      }
    };
    function gpApply(lang) {
      document.querySelectorAll('[data-i18n]').forEach(function(el){
        var k = el.getAttribute('data-i18n');
        el.textContent = (T[lang]&&T[lang][k])||(T.id&&T.id[k])||k;
      });
      document.querySelectorAll('.gp-lang-pill button').forEach(function(b){
        b.classList.toggle('is-active', b.dataset.lang === lang);
      });
    }
    window.gpLgSet = function(lang) {
      localStorage.setItem('gp_lang', lang); gpApply(lang);
    };
    document.addEventListener('DOMContentLoaded', function(){
      gpApply(localStorage.getItem('gp_lang')||'id');
    });
  })();
  </script>
</head>

<body>
<!-- Background layers -->
<div class="gp-bg"></div>
<div class="gp-grid"></div>

<!-- Cinematic loader -->
<div id="auth-loader">
  <img src="<?= base_url('images/gravport_logo_color.png') ?>" alt="GravPort">
  <div id="auth-bar-wrap"><div id="auth-bar"></div></div>
</div>

<div class="auth-shell">
  <div class="auth-frame">

    <!-- ══════════════════════════════════════════════════════════════════════
         KIRI — Pitch / Tier Overview
    ══════════════════════════════════════════════════════════════════════════ -->
    <div class="auth-story">
      <div class="auth-brand anim-fade-up">
        <img src="<?= base_url('images/gravport_logo_color.png') ?>" alt="GravPort">
        <div>
          <div class="auth-brand-name">GravPort</div>
          <div class="auth-brand-sub" data-i18n="sg.brand.sub">Geoportal Gravimetri</div>
        </div>
      </div>

      <div class="auth-kicker anim-fade-up" <?= !$isRenewing ? 'data-i18n="sg.kicker"' : '' ?>>
        <?= $isRenewing ? 'Perpanjang Langganan' : 'Mulai Berlangganan' ?>
      </div>

      <h1 class="anim-fade-up">
        <span data-i18n="sg.h1">Data gravitasi untuk </span><em data-i18n="sg.h1.em">setiap kebutuhan.</em>
      </h1>

      <p class="anim-fade-up" data-i18n="sg.intro">
        Pilih paket yang sesuai, dari eksplorasi individu hingga penggunaan tim profesional.
      </p>

      <div class="tier-mini-grid anim-fade-up">
        <div class="tier-mini-card">
          <div>
            <div class="tier-mini-name">Lite</div>
            <div class="tier-mini-desc" data-i18n="sg.lite.desc">Level 1+2 | 2 GB/minggu | 1 akun</div>
          </div>
          <div class="tier-mini-price">Rp 99rb/bln</div>
        </div>
        <div class="tier-mini-card highlight">
          <div>
            <div class="tier-mini-name">Pro</div>
            <div class="tier-mini-desc" data-i18n="sg.pro.desc">Level 1+2 | Unlimited | 1 akun</div>
          </div>
          <div class="tier-mini-price">Rp 349rb/bln</div>
        </div>
        <div class="tier-mini-card">
          <div>
            <div class="tier-mini-name">Enterprise</div>
            <div class="tier-mini-desc" data-i18n="sg.ent.desc">Level 1+2 | Unlimited | hingga 10 akun</div>
          </div>
          <div class="tier-mini-price">Rp 999rb/bln</div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         KANAN — Multi-step form
    ══════════════════════════════════════════════════════════════════════════ -->
    <div class="auth-card">

      <div class="gp-lang-pill">
        <button data-lang="id" onclick="gpLgSet('id')">ID</button>
        <button data-lang="en" onclick="gpLgSet('en')">EN</button>
      </div>

      <?php if (session()->getFlashdata('errors')): ?>
      <div class="alert-danger">
        <?php foreach ((array)session()->getFlashdata('errors') as $err): ?>
          <div><?= esc($err) ?></div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?>
      <div class="alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
      <?php endif; ?>

      <!-- Progress dots -->
      <div class="step-dots anim-fade-up" id="stepDots">
        <div class="step-dot active" id="dot0"></div>
        <div class="step-dot" id="dot1"></div>
        <div class="step-dot" id="dot2"></div>
      </div>

      <!-- ── LANGKAH 0: Pilih Jenis Akun ──────────────────────────────────── -->
      <div class="auth-step anim-fade-up" id="step0">
        <div class="step-head">
          <h2 data-i18n="sg.h2.0">Buat Akun GravPort</h2>
          <p data-i18n="sg.p.0">Pilih jenis akun yang ingin Anda buat.</p>
        </div>

        <div class="path-grid">
          <button class="path-btn" onclick="choosePath('user')">
            <i class="bi bi-person-fill"></i>
            <strong data-i18n="sg.path.user.t">Pengguna</strong>
            <span data-i18n="sg.path.user.s">Akses &amp; unduh data gravitasi</span>
          </button>
          <button class="path-btn" onclick="choosePath('admin')">
            <i class="bi bi-shield-lock-fill"></i>
            <strong data-i18n="sg.path.adm.t">Admin Platform</strong>
            <span data-i18n="sg.path.adm.s">Kelola &amp; kontribusi data</span>
          </button>
        </div>

        <div class="auth-switch"><span data-i18n="sg.switch">Sudah punya akun?</span> <a href="<?= site_url('login') ?>" data-i18n="sg.switch.lnk">Login di sini</a></div>
      </div>

      <!-- ── LANGKAH ADMIN: Inquiry ────────────────────────────────────────── -->
      <div class="auth-step hidden" id="stepAdmin">
        <button class="step-back" onclick="goBack(0)"><i class="bi bi-arrow-left"></i> <span data-i18n="sg.back">Kembali</span></button>
        <div class="step-head">
          <h2 data-i18n="sg.h2.admin">Permintaan Akses Admin</h2>
          <p data-i18n="sg.p.admin">Isi data Anda; tim kami akan menghubungi untuk verifikasi dan pengaturan akses.</p>
        </div>
        <form action="<?= site_url('register/admin-inquiry') ?>" method="POST">
          <?= csrf_field() ?>
          <div class="auth-field">
            <label data-i18n="sg.lbl.name">Nama Lengkap</label>
            <div class="auth-input-wrap">
              <i class="bi bi-person fi-icon"></i>
              <input class="auth-input" type="text" name="full_name" value="<?= esc(old('full_name')) ?>" required placeholder="Nama Anda">
            </div>
          </div>
          <div class="auth-field">
            <label data-i18n="sg.lbl.email">Email</label>
            <div class="auth-input-wrap">
              <i class="bi bi-envelope fi-icon"></i>
              <input class="auth-input" type="email" name="email" value="<?= esc(old('email')) ?>" required placeholder="email@instansi.id">
            </div>
          </div>
          <div class="auth-field">
            <label data-i18n="sg.lbl.msg">Pesan (opsional)</label>
            <textarea class="auth-input" name="message" rows="3" placeholder="Ceritakan kebutuhan Anda..."><?= esc(old('message')) ?></textarea>
          </div>
          <button type="submit" class="auth-submit" data-i18n="sg.submit.adm">Kirim Permintaan</button>
        </form>
        <div class="auth-switch" style="margin-top:16px;">
          Ingin berlangganan sebagai pengguna?
          <a href="#" onclick="choosePath('user');return false;">Kembali ke pilihan paket</a>
        </div>
      </div>

      <!-- ── LANGKAH 1: Pilih Paket ────────────────────────────────────────── -->
      <div class="auth-step hidden" id="step1">
        <?php if (!$isRenewing): ?>
        <button class="step-back" onclick="goBack(0)"><i class="bi bi-arrow-left"></i> <span data-i18n="sg.back">Kembali</span></button>
        <?php endif; ?>
        <div class="step-head">
          <?php if ($isRenewing): ?>
          <h2>Perpanjang / Upgrade Langganan</h2>
          <p>Pilih paket untuk melanjutkan akses Anda ke data gravitasi.</p>
          <?php else: ?>
          <h2 data-i18n="sg.h2.pkg">Pilih Paket</h2>
          <p data-i18n="sg.p.pkg">Bayar bulanan atau hemat 2 bulan dengan paket tahunan.</p>
          <?php endif; ?>
        </div>

        <div class="billing-toggle">
          <button class="billing-btn active" id="btnMonthly" onclick="setBilling('monthly')">Bulanan</button>
          <button class="billing-btn" id="btnAnnual" onclick="setBilling('annual')">
            Tahunan <span style="color:var(--cyan);font-size:10px;margin-left:4px;">−17%</span>
          </button>
        </div>

        <div class="tier-grid">
          <!-- Lite -->
          <div class="tier-card" id="cardSolo" onclick="selectTier('solo')">
            <div class="tier-card-top">
              <span class="tier-name">Lite</span>
              <span class="tier-price" id="priceSolo">Rp 99.000<span class="tier-price-unit">/bulan</span></span>
            </div>
            <div class="tier-features">Level 1+2 (FAA &amp; CBA, GeoTIFF) | Maks. 2 GB/minggu | 1 akun | Metadata ISO 19115</div>
            <i class="bi bi-check-circle-fill tier-check"></i>
          </div>

          <!-- Pro (no POPULER badge) -->
          <div class="tier-card" id="cardPro" onclick="selectTier('pro')">
            <div class="tier-card-top">
              <span class="tier-name">Pro</span>
              <span class="tier-price" id="pricePro">Rp 349.000<span class="tier-price-unit">/bulan</span></span>
            </div>
            <div class="tier-features">Level 1+2 (GeoTIFF) | Unduhan unlimited | REST API | 1 akun</div>
            <i class="bi bi-check-circle-fill tier-check"></i>
          </div>

          <!-- Enterprise -->
          <div class="tier-card" id="cardEnterprise" onclick="selectTier('Enterprise')">
            <div class="tier-card-top">
              <span class="tier-name">Enterprise</span>
              <span class="tier-price" id="priceEnterprise">Rp 999.000<span class="tier-price-unit">/bulan</span></span>
            </div>
            <div class="tier-features">Semua fitur Pro | Hingga 10 Akun Enterprise | Priority support</div>
            <i class="bi bi-check-circle-fill tier-check"></i>
          </div>
        </div>

        <button class="auth-submit" onclick="goToDetails()" style="margin-top:14px;">
          Lanjutkan <i class="bi bi-arrow-right" style="margin-left:4px;"></i>
        </button>
      </div>

      <!-- ── LANGKAH 2A: Detail Individu ───────────────────────────────────── -->
      <div class="auth-step hidden" id="step2Individual">
        <button class="step-back" onclick="goBack(1)"><i class="bi bi-arrow-left"></i> <span data-i18n="sg.back">Kembali</span></button>
        <div class="step-head">
          <?php if ($isRenewing): ?>
          <h2>Konfirmasi Perpanjangan</h2>
          <?php else: ?>
          <h2 data-i18n="sg.h2.user">Informasi Pendaftaran</h2>
          <?php endif; ?>
          <p>Paket <strong id="lblTierIndividual" style="color:var(--amber);"></strong> ·
             tagihan <strong id="lblCycleIndividual" style="color:var(--amber);"></strong></p>
        </div>
        <form action="<?= site_url($isRenewing ? 'register/renew' : 'register/individual') ?>" method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="tier_name" id="inputTierName">
          <input type="hidden" name="billing_cycle" id="inputBillingCycle">
          <?php if ($isRenewing): ?>
          <div class="auth-note" style="margin-bottom:16px;">
            <i class="bi bi-person-check-fill" style="color:var(--amber);margin-right:6px;"></i>
            Subscription baru akan ditautkan ke akun Anda yang sudah terdaftar. Tidak diperlukan data tambahan.
          </div>
          <?php else: ?>
          <div class="auth-field">
            <label data-i18n="sg.lbl.name">Nama Lengkap</label>
            <div class="auth-input-wrap">
              <i class="bi bi-person fi-icon"></i>
              <input class="auth-input" type="text" name="full_name" value="<?= esc(old('full_name')) ?>" required placeholder="Nama lengkap Anda">
            </div>
          </div>
          <div class="auth-field">
            <label data-i18n="sg.lbl.email">Email</label>
            <div class="auth-input-wrap">
              <i class="bi bi-envelope fi-icon"></i>
              <input class="auth-input" type="email" name="email" value="<?= esc(old('email')) ?>" required placeholder="email@example.com">
            </div>
          </div>
          <div class="auth-field">
            <label data-i18n="sg.lbl.pw">Password</label>
            <div class="auth-input-wrap">
              <i class="bi bi-lock fi-icon"></i>
              <input class="auth-input" type="password" name="password" required placeholder="Min. 12 karakter (Gunakan simbol, angka, huruf besar, huruf kecil." autocomplete="new-password">
            </div>
          </div>
          <div class="auth-field">
            <label data-i18n="sg.lbl.pw2">Konfirmasi Password</label>
            <div class="auth-input-wrap">
              <i class="bi bi-lock-fill fi-icon"></i>
              <input class="auth-input" type="password" name="password_confirmation" required placeholder="Ulangi password" autocomplete="new-password">
            </div>
          </div>
          <?php endif; ?>
          <div class="auth-note">
            Setelah mendaftar, instruksi pembayaran akan dikirim ke email Anda.
            Akun aktif dalam 1×24 jam kerja setelah pembayaran dikonfirmasi.
          </div>
          <button type="submit" class="auth-submit">
            <?= $isRenewing ? 'Perpanjang &amp; Lanjutkan ke Pembayaran' : 'Daftar &amp; Lanjutkan ke Pembayaran' ?>
          </button>
        </form>
      </div>

      <!-- ── LANGKAH 2B: Detail Tim ────────────────────────────────────────── -->
      <div class="auth-step hidden" id="step2Team">
        <button class="step-back" onclick="goBack(1)"><i class="bi bi-arrow-left"></i> Kembali</button>
        <div class="step-head">
          <h2>Informasi Organisasi</h2>
          <p>Paket <strong style="color:var(--amber);">Enterprise</strong> ·
             tagihan <strong id="lblCycleTeam" style="color:var(--amber);"></strong></p>
        </div>
        <form action="<?= site_url('register/team') ?>" method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="billing_cycle" id="inputBillingCycleTeam">
          <div class="auth-field">
            <label>Nama Organisasi / Perusahaan</label>
            <div class="auth-input-wrap">
              <i class="bi bi-building fi-icon"></i>
              <input class="auth-input" type="text" name="org_name" value="<?= esc(old('org_name')) ?>" required placeholder="PT. Contoh / Universitas XYZ">
            </div>
          </div>
          <div class="auth-field">
            <label>Email Organisasi</label>
            <div class="auth-input-wrap">
              <i class="bi bi-envelope fi-icon"></i>
              <input class="auth-input" type="email" name="org_email" value="<?= esc(old('org_email')) ?>" required placeholder="admin@organisasi.id">
            </div>
          </div>
          <div class="auth-field">
            <label>Nama Kontak PIC</label>
            <div class="auth-input-wrap">
              <i class="bi bi-person fi-icon"></i>
              <input class="auth-input" type="text" name="contact_name" value="<?= esc(old('contact_name')) ?>" required placeholder="Nama Anda">
            </div>
          </div>
          <div class="auth-field">
            <label>Jumlah Akun yang Dibutuhkan</label>
            <div class="auth-input-wrap">
              <i class="bi bi-people fi-icon"></i>
              <input class="auth-input" type="number" name="seat_count"
                value="<?= esc(old('seat_count', '5')) ?>" required min="1" max="100" placeholder="5"
                style="-webkit-appearance:textfield;appearance:textfield;">
            </div>
            <p style="font-size:11px;color:var(--muted);margin:6px 0 0;line-height:1.6;padding:0 2px;">
              Paket Enterprise dasar mencakup 10 akun. Lebih dari 10 akun dapat diatur setelah aktivasi.
            </p>
          </div>
          <div class="auth-note">
            Setelah mendaftar, instruksi pembayaran dikirim ke email organisasi.
            Akun admin tim dibuat otomatis setelah pembayaran dikonfirmasi.
          </div>
          <button type="submit" class="auth-submit">Daftar Tim &amp; Lanjutkan ke Pembayaran</button>
        </form>
      </div>

    </div><!-- /auth-card -->
  </div><!-- /auth-frame -->
</div><!-- /auth-shell -->

<script>
  /* ── State ──────────────────────────────────────────────────────────────── */
  const IS_RENEWING = <?= ($isRenewing ? 'true' : 'false') ?>;
  let currentPath  = null;
  let selectedTier = null;
  let billingCycle = 'monthly';

  const prices = {
    monthly: { solo: 'Rp 99.000',      pro: 'Rp 349.000',      Enterprise: 'Rp 999.000'    },
    annual:  { solo: 'Rp 990.000',     pro: 'Rp 3.490.000',    Enterprise: 'Rp 9.990.000'  },
  };

  /* ── Animated step transition ───────────────────────────────────────────── */
  function showStep(id) {
    const incoming = document.getElementById(id);
    const current  = document.querySelector('.auth-step:not(.hidden)');

    if (current && current !== incoming) {
      current.classList.add('step-exit');
      setTimeout(() => {
        current.classList.add('hidden');
        current.classList.remove('step-exit');
        revealStep(incoming);
      }, 240);
    } else {
      revealStep(incoming);
    }
  }

  function revealStep(el) {
    el.classList.remove('hidden');
    el.style.animation = 'none';
    el.offsetHeight;  /* reflow */
    el.style.animation = '';
  }

  /* ── Dots ───────────────────────────────────────────────────────────────── */
  function updateDots(active) {
    for (let i = 0; i < 3; i++) {
      document.getElementById('dot' + i).classList.toggle('active', i <= active);
    }
  }

  /* ── Navigation ─────────────────────────────────────────────────────────── */
  function choosePath(path) {
    currentPath = path;
    showStep(path === 'admin' ? 'stepAdmin' : 'step1');
    updateDots(1);
  }

  function goBack(toStep) {
    showStep(toStep === 0 ? 'step0' : 'step1');
    updateDots(toStep);
  }

  /* ── Billing toggle ─────────────────────────────────────────────────────── */
  function setBilling(cycle) {
    billingCycle = cycle;
    document.getElementById('btnMonthly').classList.toggle('active', cycle === 'monthly');
    document.getElementById('btnAnnual').classList.toggle('active', cycle === 'annual');
    updatePriceDisplay();
  }

  function updatePriceDisplay() {
    const suffix = billingCycle === 'annual' ? '/tahun' : '/bulan';
    ['solo', 'pro', 'Enterprise'].forEach(t => {
      const el = document.getElementById('price' + t.charAt(0).toUpperCase() + t.slice(1));
      if (el) el.innerHTML = prices[billingCycle][t] + '<span class="tier-price-unit">' + suffix + '</span>';
    });
  }

  /* ── Tier selection ─────────────────────────────────────────────────────── */
  function selectTier(tier) {
    selectedTier = tier;
    ['solo', 'pro', 'Enterprise'].forEach(t => {
      const card = document.getElementById('card' + t.charAt(0).toUpperCase() + t.slice(1));
      if (card) card.classList.toggle('selected', t === tier);
    });
  }

  function goToDetails() {
    if (!selectedTier) { alert('Pilih paket terlebih dahulu.'); return; }

    if (selectedTier === 'Enterprise') {
      if (IS_RENEWING) {
        alert('Untuk perpanjangan paket Enterprise, silakan hubungi admin melalui email atau Admin Hub.');
        return;
      }
      document.getElementById('inputBillingCycleTeam').value = billingCycle;
      document.getElementById('lblCycleTeam').textContent = billingCycle === 'annual' ? 'Tahunan' : 'Bulanan';
      showStep('step2Team');
    } else {
      document.getElementById('inputTierName').value      = selectedTier;
      document.getElementById('inputBillingCycle').value  = billingCycle;
      document.getElementById('lblTierIndividual').textContent  = selectedTier === 'solo' ? 'Lite' : 'Pro';
      document.getElementById('lblCycleIndividual').textContent = billingCycle === 'annual' ? 'Tahunan' : 'Bulanan';
      showStep('step2Individual');
    }
    updateDots(2);
  }

  /* ── Auto-skip step 0 for logged-in renewal users ──────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    if (IS_RENEWING) {
      choosePath('user');
    }
  });

  /* ── Cinematic loader ───────────────────────────────────────────────────── */
  setTimeout(() => document.getElementById('auth-bar').style.width = '100%', 60);
  window.addEventListener('load', () => {
    const l = document.getElementById('auth-loader');
    setTimeout(() => { l.style.opacity = '0'; l.style.visibility = 'hidden'; }, 420);
  });
</script>
</body>
</html>
