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
        radial-gradient(circle at top left, rgba(167, 96, 37, 0.32), transparent 28%),
        radial-gradient(circle at 82% 16%, rgba(97, 212, 255, 0.18), transparent 24%),
        linear-gradient(135deg, #04101d 0%, #091427 45%, #0b1b34 100%);
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

    .login-card__eyebrow::before {
      content: "";
      width: 28px;
      height: 1px;
      background: rgba(255, 255, 255, 0.36);
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

    .login-demo {
      position: relative;
      z-index: 1;
      margin-top: 18px;
      padding: 18px;
      border-radius: 22px;
      border: 1px solid rgba(255, 255, 255, 0.08);
      background: rgba(255, 255, 255, 0.04);
    }

    .login-demo strong {
      display: block;
      margin-bottom: 8px;
      font-size: 13px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.82);
    }

    .login-demo code {
      display: block;
      padding: 10px 12px;
      border-radius: 14px;
      background: rgba(0, 0, 0, 0.18);
      color: #fff;
      font-size: 13px;
      margin-top: 8px;
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
</head>
<body>

<main class="login-shell">
  <section class="login-frame">
    <article class="login-story">
      <div class="login-brand">
        <img src="<?= base_url('images/itb.png'); ?>" alt="Logo ITB">
        <div>
          <strong>GravPort</strong>
          <span>Jawa-Bali Geoportal</span>
        </div>
      </div>

      <span class="login-kicker">Geoportal Access</span>
      <h1>Masuk ke workspace GravPort.</h1>
      <p>
        Akses ini digunakan untuk membuka katalog dataset, WebMap, dan area administrasi metadata. Hak akses tambahan akan ditampilkan sesuai peran akun yang digunakan saat login.
      </p>

      <div class="login-story__grid">
        <div class="login-story__stat">
          <small>Katalog</small>
          <strong>Dataset Level 1 & 2</strong>
          <span>Akses halaman katalog untuk meninjau data FAA dan CBA yang tersedia.</span>
        </div>
        <div class="login-story__stat">
          <small>Admin</small>
          <strong>Metadata Workspace</strong>
          <span>Role admin dapat membuka pengisian metadata dan halaman operasional internal.</span>
        </div>
        <div class="login-story__stat">
          <small>Workflow</small>
          <strong>Role-based Access</strong>
          <span>Tampilan menu dan alur kerja akan menyesuaikan hak akses user yang aktif.</span>
        </div>
      </div>
    </article>

    <article class="login-card">
      <div class="login-card__head">
        <span class="login-card__eyebrow">Secure Sign In</span>
        <h2>Masuk ke akun Anda</h2>
        <p>Gunakan akun yang sesuai untuk mengakses fitur umum atau area administrasi GravPort.</p>
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
          <input id="email" name="email" type="email" value="<?= esc(old('email')) ?>" placeholder="admin@gravport.test" required>
        </div>

        <div class="login-field">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" placeholder="Masukkan password" required>
        </div>

        <button class="login-submit" type="submit">Login to GravPort</button>
      </form>

      <div class="login-demo">
        <strong>Info Login</strong>
        <code>Admin: admin@gravport.test / admin123</code>
        <code>User: client@gravport.test / client123</code>
      </div>

      <a class="login-back" href="<?= site_url('/') ?>">
        <span>&larr;</span>
        <span>Kembali ke home</span>
      </a>
    </article>
  </section>
</main>

</body>
</html>
