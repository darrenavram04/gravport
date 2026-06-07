<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Sign In</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css'); ?>">

  <style>
    :root {
      --login-bronze: #a76025;
      --login-amber: #ffbf74;
      --login-ink: #08111f;
      --login-navy: #0b1b34;
      --login-cyan: #61d4ff;
      --login-text: rgba(245, 248, 255, 0.96);
      --login-muted: rgba(219, 226, 242, 0.72);
      --login-shadow: 0 28px 80px rgba(2, 10, 28, 0.42);
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: "Manrope", sans-serif;
      color: var(--login-text);
      background:
        radial-gradient(ellipse at top left, rgba(30, 50, 200, 0.28), transparent 42%),
        radial-gradient(ellipse at 80% 10%, rgba(20, 30, 150, 0.2), transparent 38%),
        linear-gradient(160deg, #07093d 0%, #060a28 45%, #040612 100%);
    }

    .login-shell {
      position: relative;
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 28px;
      overflow: hidden;
    }

    .login-shell::before,
    .login-shell::after {
      content: "";
      position: absolute;
      border-radius: 999px;
      filter: blur(18px);
      pointer-events: none;
    }

    .login-shell::before {
      width: 540px;
      height: 540px;
      left: -180px;
      top: 10%;
      background: radial-gradient(circle, rgba(167, 96, 37, 0.34), rgba(167, 96, 37, 0));
    }

    .login-shell::after {
      width: 600px;
      height: 600px;
      right: -180px;
      bottom: -120px;
      background: radial-gradient(circle, rgba(97, 212, 255, 0.24), rgba(97, 212, 255, 0));
    }

    .login-frame {
      position: relative;
      z-index: 1;
      width: min(1180px, 100%);
      display: grid;
      grid-template-columns: minmax(0, 1.12fr) minmax(360px, 0.88fr);
      gap: 22px;
      align-items: stretch;
    }

    .login-story,
    .login-card {
      position: relative;
      overflow: hidden;
      border-radius: 32px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: var(--login-shadow);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
    }

    .login-story {
      padding: 34px;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02)),
        rgba(6, 15, 30, 0.8);
    }

    .login-story::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at top right, rgba(97, 212, 255, 0.18), transparent 34%),
        linear-gradient(135deg, rgba(255, 191, 116, 0.08), transparent 24%);
      pointer-events: none;
    }

    .login-brand {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 28px;
    }

    .login-brand img {
      width: 52px;
      height: 52px;
      object-fit: contain;
    }

    .login-brand strong {
      display: block;
      font-size: 15px;
      letter-spacing: 0.16em;
      text-transform: uppercase;
    }

    .login-brand span {
      display: block;
      margin-top: 3px;
      font-size: 12px;
      color: var(--login-muted);
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .login-kicker {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 0.14em;
      text-transform: uppercase;
    }

    .login-kicker::before {
      content: "";
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: linear-gradient(135deg, var(--login-amber), var(--login-cyan));
      box-shadow: 0 0 18px rgba(97, 212, 255, 0.45);
    }

    .login-story h1 {
      position: relative;
      margin: 20px 0 14px;
      font-family: "Fraunces", serif;
      font-size: clamp(42px, 5vw, 76px);
      line-height: 0.96;
      color: #fff;
    }

    .login-story p {
      position: relative;
      margin: 0;
      max-width: 680px;
      color: var(--login-muted);
      font-size: 16px;
      line-height: 1.8;
    }

    .login-story__grid {
      position: relative;
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      margin-top: 28px;
    }

    .login-story__stat {
      padding: 16px;
      border-radius: 22px;
      border: 1px solid rgba(255, 255, 255, 0.08);
      background: rgba(255, 255, 255, 0.04);
    }

    .login-story__stat small {
      display: block;
      font-size: 11px;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.58);
    }

    .login-story__stat strong {
      display: block;
      margin-top: 8px;
      font-size: 24px;
      line-height: 1.08;
    }

    .login-story__stat span {
      display: block;
      margin-top: 4px;
      font-size: 13px;
      color: var(--login-muted);
      line-height: 1.6;
    }

    .login-card {
      padding: 30px;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.03)),
        rgba(8, 17, 31, 0.88);
    }

    .login-card::before {
      content: "";
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at top right, rgba(255, 191, 116, 0.12), transparent 36%);
      pointer-events: none;
    }

    .login-card__head {
      position: relative;
      margin-bottom: 22px;
    }

    .login-card__eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: rgba(255, 255, 255, 0.66);
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 0.14em;
      text-transform: uppercase;
    }


    .login-card h2 {
      margin: 14px 0 8px;
      font-size: 34px;
      line-height: 1.05;
      color: #fff;
    }

    .login-card p {
      margin: 0;
      color: var(--login-muted);
      line-height: 1.7;
    }

    .alert {
      position: relative;
      z-index: 1;
      border: 0;
      border-radius: 18px;
      margin-bottom: 16px;
    }

    .alert-danger {
      background: rgba(217, 69, 69, 0.16);
      color: #ffd9d9;
    }

    .alert-success {
      background: rgba(73, 189, 139, 0.18);
      color: #dcffef;
    }

    .login-form {
      position: relative;
      z-index: 1;
    }

    .login-field {
      margin-bottom: 16px;
    }

    .login-field label {
      display: block;
      margin-bottom: 8px;
      font-size: 13px;
      font-weight: 700;
      color: rgba(255, 255, 255, 0.82);
    }

    .login-field input {
      width: 100%;
      min-height: 54px;
      padding: 0 16px;
      border-radius: 18px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: rgba(255, 255, 255, 0.05);
      color: #fff;
      font: inherit;
      transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .login-field input::placeholder {
      color: rgba(255, 255, 255, 0.38);
    }

    .login-field input:focus {
      outline: none;
      border-color: rgba(97, 212, 255, 0.38);
      background: rgba(255, 255, 255, 0.07);
      box-shadow: 0 0 0 4px rgba(97, 212, 255, 0.1);
    }

    .login-submit {
      width: 100%;
      min-height: 54px;
      border: 0;
      border-radius: 999px;
      background: linear-gradient(135deg, #fff4e7 0%, var(--login-amber) 58%, #ffd095 100%);
      color: #08111f;
      font-weight: 800;
      letter-spacing: 0.01em;
      box-shadow: 0 20px 40px rgba(255, 191, 116, 0.18);
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .login-submit:hover {
      transform: translateY(-1px);
      box-shadow: 0 24px 50px rgba(255, 191, 116, 0.22);
    }

    .login-back {
      position: relative;
      z-index: 1;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 18px;
      color: rgba(255, 255, 255, 0.78);
      text-decoration: none;
      font-weight: 700;
    }

    .login-back:hover {
      color: #fff;
    }

    .login-switch {
      position: relative;
      z-index: 1;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 14px;
      color: rgba(255, 255, 255, 0.78);
      text-decoration: none;
      font-weight: 700;
    }

    .login-switch:hover {
      color: #fff;
    }

    /* Password field wrapper for show/hide toggle */
    .login-field--pw .input-wrap {
      position: relative;
    }

    .login-field--pw .input-wrap input {
      padding-right: 50px;
    }

    .pw-toggle {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px;
      color: rgba(255, 255, 255, 0.5);
      line-height: 1;
      transition: color 0.18s ease;
    }

    .pw-toggle:hover { color: rgba(255, 255, 255, 0.9); }

    .login-forgot {
      position: relative;
      z-index: 1;
      display: block;
      text-align: right;
      margin-top: -6px;
      margin-bottom: 12px;
      font-size: 12px;
      color: rgba(255, 191, 116, 0.82);
      text-decoration: none;
      font-weight: 600;
      transition: color 0.18s ease;
    }

    .login-forgot:hover { color: var(--login-amber); }

    /* Guest button */
    .login-guest {
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      min-height: 48px;
      margin-top: 10px;
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.04);
      color: rgba(255, 255, 255, 0.78);
      font: inherit;
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease;
    }

    .login-guest:hover {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(255, 255, 255, 0.24);
      color: #fff;
    }

    .login-divider {
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 14px 0 4px;
      color: rgba(255, 255, 255, 0.3);
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }

    .login-divider::before,
    .login-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: rgba(255, 255, 255, 0.1);
    }

    .login-links {
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 14px;
    }

    @media (max-width: 980px) {
      .login-frame {
        grid-template-columns: 1fr;
      }

      .login-story__grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 640px) {
      .login-shell {
        padding: 14px;
      }

      .login-story,
      .login-card {
        border-radius: 26px;
      }

      .login-story,
      .login-card {
        padding: 22px;
      }
    }
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
        'lg.h1': 'Masuk ke workspace GravPort.',
        'lg.intro': 'Masuk untuk membuka katalog, WebMap, dan fitur sesuai role akun.',
        'lg.h2': 'Masuk ke akun Anda', 'lg.sub': 'Gunakan akun yang sesuai.',
        'lg.lbl.pw': 'Password', 'lg.pw.ph': 'Masukkan password',
        'lg.forgot': 'Lupa password?', 'lg.submit': 'Login ke GravPort',
        'lg.divider': 'atau', 'lg.guest': 'Lanjutkan sebagai Tamu',
        'lg.s1.lbl': 'Katalog', 'lg.s1.desc': 'Lihat FAA dan BA yang tersedia.',
        'lg.s2.lbl': 'Role',    'lg.s2.desc': 'Lihat & Unduh FAA dan BA lengkap dengan metadata',
        'lg.s3.desc': 'Integrasikan data gravimetri ke dalam basis data terstandarisasi',
      },
      en: {
        'lg.h1': 'Sign In to GravPort.',
        'lg.intro': 'Sign in to access the catalog, WebMap, and account features.',
        'lg.h2': 'Sign in to your account', 'lg.sub': 'Use your registered account.',
        'lg.lbl.pw': 'Password', 'lg.pw.ph': 'Enter password',
        'lg.forgot': 'Forgot password?', 'lg.submit': 'Login to GravPort',
        'lg.divider': 'or', 'lg.guest': 'Continue as Guest',
        'lg.s1.lbl': 'Catalog', 'lg.s1.desc': 'View available FAA and BA datasets.',
        'lg.s2.lbl': 'Role',    'lg.s2.desc': 'View & Download FAA and BA with full metadata',
        'lg.s3.desc': 'Integrate gravity data into a standardized data infrastructure',
      }
    };
    function gpApply(lang) {
      document.querySelectorAll('[data-i18n]').forEach(function(el){
        var k = el.getAttribute('data-i18n');
        el.textContent = (T[lang]&&T[lang][k])||(T.id&&T.id[k])||k;
      });
      document.querySelectorAll('[data-i18n-ph]').forEach(function(el){
        var k = el.getAttribute('data-i18n-ph');
        el.placeholder = (T[lang]&&T[lang][k])||(T.id&&T.id[k])||k;
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
<main class="login-shell">
  <section class="login-frame">
    <article class="login-story">
      <div class="login-brand">
        <img src="<?= base_url('images/gravport_logo_color.png'); ?>" alt="GravPort">
        <div>
          <strong>GravPort</strong>
          <span>Jawa-Bali Geoportal</span>
        </div>
      </div>

      <h1 data-i18n="lg.h1">Masuk ke workspace GravPort.</h1>
      <p data-i18n="lg.intro">
        Masuk untuk membuka katalog, WebMap, dan fitur sesuai role akun.
      </p>

      <div class="login-story__grid">
        <div class="login-story__stat">
          <small data-i18n="lg.s1.lbl">Katalog</small>
          <strong>Dataset Level 1 &amp; 2</strong>
          <span data-i18n="lg.s1.desc">Lihat FAA dan BA yang tersedia.</span>
        </div>
        <div class="login-story__stat">
          <small data-i18n="lg.s2.lbl">Role</small>
          <strong>User</strong>
          <span data-i18n="lg.s2.desc">Lihat &amp; Unduh FAA dan BA lengkap dengan metadata</span>
        </div>
        <div class="login-story__stat">
          <small></small>
          <strong>Be Part of GravPORT</strong>
          <span data-i18n="lg.s3.desc">Integrasikan data gravimetri ke dalam basis data terstandarisasi</span>
        </div>
      </div>
    </article>

    <article class="login-card">
      <div class="gp-lang-pill">
        <button data-lang="id" onclick="gpLgSet('id')">ID</button>
        <button data-lang="en" onclick="gpLgSet('en')">EN</button>
      </div>
      <div class="login-card__head">
        <span class="login-card__eyebrow">Secure Sign In</span>
        <h2 data-i18n="lg.h2">Masuk ke akun Anda</h2>
        <p data-i18n="lg.sub">Gunakan akun yang sesuai.</p>
      </div>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
      <?php endif; ?>

      <form class="login-form" method="post" action="<?= site_url('login') ?>">
        <?= csrf_field() ?>

        <div class="login-field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?= esc(old('email')) ?>" placeholder="email@contoh.com" required autocomplete="email">
        </div>

        <div class="login-field login-field--pw">
          <label for="password" data-i18n="lg.lbl.pw">Password</label>
          <div class="input-wrap">
            <input id="password" name="password" type="password" data-i18n-ph="lg.pw.ph" placeholder="Masukkan password" required autocomplete="current-password">
            <button type="button" class="pw-toggle" aria-label="Tampilkan/sembunyikan password" onclick="togglePassword('password', this)">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
        </div>

        <a class="login-forgot" href="<?= site_url('forgot-password') ?>" data-i18n="lg.forgot">Lupa password?</a>

        <button class="login-submit" type="submit" data-i18n="lg.submit">Login ke GravPort</button>
      </form>

      <div class="login-divider" data-i18n="lg.divider">atau</div>

      <a class="login-guest" href="<?= site_url('login/guest') ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        <span data-i18n="lg.guest">Lanjutkan sebagai Tamu</span>
      </a>

      <div class="login-links">
        <a class="login-switch" href="<?= site_url('signup') ?>">
          Belum punya akun? <strong>Daftar</strong>
        </a>
        <a class="login-back" href="<?= site_url('/') ?>">
          &larr; Beranda
        </a>
      </div>
    </article>
  </section>
</main>

<script>
  function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.innerHTML = isHidden
      ? '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>'
      : '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
    btn.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
  }
</script>
  <div id="auth-loader" style="position:fixed;inset:0;z-index:99999;background:#04101d;display:flex;flex-direction:column;align-items:center;justify-content:center;transition:opacity .5s ease,visibility .5s ease;">
    <img src="<?= base_url('images/gravport_logo_color.png') ?>" style="width:52px;height:52px;object-fit:contain;filter:drop-shadow(0 0 16px rgba(167,96,37,.5));margin-bottom:20px;">
    <div style="width:160px;height:2px;background:rgba(255,255,255,.1);border-radius:999px;overflow:hidden;"><div id="auth-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#a76025,#ffbf74 50%,#61d4ff);border-radius:999px;transition:width .8s cubic-bezier(.4,0,.2,1);"></div></div>
  </div>
  <script>setTimeout(function(){document.getElementById('auth-bar').style.width='100%'},50);window.addEventListener('load',function(){var l=document.getElementById('auth-loader');setTimeout(function(){l.style.opacity='0';l.style.visibility='hidden'},400)});</script>
</body>
</html>
