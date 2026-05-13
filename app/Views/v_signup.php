<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Sign Up</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css'); ?>">

  <style>
    :root {
      --auth-bronze: #a76025;
      --auth-amber: #ffbf74;
      --auth-ink: #08111f;
      --auth-navy: #0b1b34;
      --auth-cyan: #61d4ff;
      --auth-text: rgba(245, 248, 255, 0.96);
      --auth-muted: rgba(219, 226, 242, 0.72);
      --auth-shadow: 0 28px 80px rgba(2, 10, 28, 0.42);
      --auth-danger-bg: rgba(255, 116, 116, 0.18);
      --auth-danger-border: rgba(255, 116, 116, 0.28);
      --auth-danger-text: #ffe5e5;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: "Manrope", sans-serif;
      color: var(--auth-text);
      background:
        radial-gradient(circle at top left, rgba(167, 96, 37, 0.32), transparent 28%),
        radial-gradient(circle at 82% 16%, rgba(97, 212, 255, 0.18), transparent 24%),
        linear-gradient(135deg, #04101d 0%, #091427 45%, #0b1b34 100%);
    }

    .auth-shell {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 28px;
      overflow: hidden;
    }

    .auth-frame {
      width: min(1180px, 100%);
      display: grid;
      grid-template-columns: minmax(0, 1.08fr) minmax(360px, 0.92fr);
      gap: 22px;
      align-items: stretch;
    }

    .auth-story,
    .auth-card {
      position: relative;
      overflow: hidden;
      border-radius: 32px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: var(--auth-shadow);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
    }

    .auth-story {
      padding: 34px;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02)),
        rgba(6, 15, 30, 0.8);
    }

    .auth-story::before,
    .auth-card::before {
      content: "";
      position: absolute;
      inset: 0;
      pointer-events: none;
    }

    .auth-story::before {
      background:
        radial-gradient(circle at top right, rgba(97, 212, 255, 0.18), transparent 34%),
        linear-gradient(135deg, rgba(255, 191, 116, 0.08), transparent 24%);
    }

    .auth-card {
      padding: 30px;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.03)),
        rgba(8, 17, 31, 0.88);
    }

    .auth-card::before {
      background: radial-gradient(circle at top right, rgba(255, 191, 116, 0.12), transparent 36%);
    }

    .auth-brand,
    .auth-kicker,
    .auth-story h1,
    .auth-story p,
    .auth-story__grid,
    .auth-card__head,
    .auth-form,
    .auth-note,
    .auth-switch,
    .auth-back {
      position: relative;
      z-index: 1;
    }

    .auth-brand {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 28px;
    }

    .auth-brand img {
      width: 52px;
      height: 52px;
      object-fit: contain;
    }

    .auth-brand strong {
      display: block;
      font-size: 15px;
      letter-spacing: 0.16em;
      text-transform: uppercase;
    }

    .auth-brand span {
      display: block;
      margin-top: 3px;
      font-size: 12px;
      color: var(--auth-muted);
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .auth-kicker {
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

    .auth-kicker::before {
      content: "";
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: linear-gradient(135deg, var(--auth-amber), var(--auth-cyan));
      box-shadow: 0 0 18px rgba(97, 212, 255, 0.45);
    }

    .auth-story h1 {
      margin: 20px 0 14px;
      font-family: "Fraunces", serif;
      font-size: clamp(42px, 5vw, 74px);
      line-height: 0.96;
      color: #fff;
    }

    .auth-story p {
      margin: 0;
      max-width: 680px;
      color: var(--auth-muted);
      font-size: 16px;
      line-height: 1.8;
    }

    .auth-story__grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      margin-top: 28px;
    }

    .auth-story__stat {
      padding: 16px;
      border-radius: 22px;
      border: 1px solid rgba(255, 255, 255, 0.08);
      background: rgba(255, 255, 255, 0.04);
    }

    .auth-story__stat small {
      display: block;
      font-size: 11px;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.58);
    }

    .auth-story__stat strong {
      display: block;
      margin-top: 8px;
      font-size: 24px;
      line-height: 1.08;
    }

    .auth-story__stat span {
      display: block;
      margin-top: 4px;
      font-size: 13px;
      color: var(--auth-muted);
      line-height: 1.6;
    }

    .auth-card__head {
      margin-bottom: 22px;
    }

    .auth-card__eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: rgba(255, 255, 255, 0.66);
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 0.14em;
      text-transform: uppercase;
    }

    .auth-card__eyebrow::before {
      content: "";
      width: 28px;
      height: 1px;
      background: rgba(255, 255, 255, 0.36);
    }

    .auth-card h2 {
      margin: 14px 0 8px;
      font-size: 34px;
      line-height: 1.08;
      font-family: "Fraunces", serif;
    }

    .auth-card p {
      margin: 0;
      color: var(--auth-muted);
      line-height: 1.7;
    }

    .alert-danger {
      margin-bottom: 18px;
      padding: 14px 16px;
      border-radius: 18px;
      background: var(--auth-danger-bg);
      border: 1px solid var(--auth-danger-border);
      color: var(--auth-danger-text);
    }

    .alert-danger ul {
      margin: 0;
      padding-left: 18px;
    }

    .auth-field {
      margin-bottom: 16px;
    }

    .auth-field label {
      display: block;
      margin-bottom: 8px;
      font-size: 13px;
      font-weight: 700;
      color: rgba(255, 255, 255, 0.82);
    }

    .auth-field input {
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

    .auth-field input::placeholder {
      color: rgba(255, 255, 255, 0.38);
    }

    .auth-field input:focus {
      outline: none;
      border-color: rgba(97, 212, 255, 0.38);
      background: rgba(255, 255, 255, 0.07);
      box-shadow: 0 0 0 4px rgba(97, 212, 255, 0.1);
    }

    .auth-submit {
      width: 100%;
      min-height: 54px;
      border: 0;
      border-radius: 999px;
      background: linear-gradient(135deg, #fff4e7 0%, var(--auth-amber) 58%, #ffd095 100%);
      color: var(--auth-ink);
      font-weight: 800;
      letter-spacing: 0.01em;
      box-shadow: 0 20px 40px rgba(255, 191, 116, 0.18);
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .auth-submit:hover {
      transform: translateY(-1px);
      box-shadow: 0 24px 50px rgba(255, 191, 116, 0.22);
    }

    .auth-note {
      margin-top: 18px;
      padding: 18px;
      border-radius: 22px;
      border: 1px solid rgba(255, 255, 255, 0.08);
      background: rgba(255, 255, 255, 0.04);
    }

    .auth-note strong {
      display: block;
      margin-bottom: 8px;
      font-size: 13px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.82);
    }

    .auth-note p {
      margin: 0;
      color: var(--auth-muted);
      font-size: 14px;
      line-height: 1.7;
    }

    .auth-switch,
    .auth-back {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 18px;
      color: rgba(255, 255, 255, 0.78);
      text-decoration: none;
      font-weight: 700;
    }

    .auth-switch:hover,
    .auth-back:hover {
      color: #fff;
    }

    .auth-links {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    @media (max-width: 980px) {
      .auth-frame { grid-template-columns: 1fr; }
      .auth-story__grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 640px) {
      .auth-shell { padding: 14px; }
      .auth-story, .auth-card { padding: 22px; border-radius: 26px; }
      .auth-links { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>

<main class="auth-shell">
  <section class="auth-frame">
    <article class="auth-story">
      <div class="auth-brand">
        <img src="<?= base_url('images/itb.png'); ?>" alt="Logo ITB">
        <div>
          <strong>GravPort</strong>
          <span>Jawa-Bali Geoportal</span>
        </div>
      </div>

      <span class="auth-kicker">User Sign Up</span>
      <h1>Buat akun user baru dengan aman.</h1>
      <p>
        Sign up hanya membuat akun <strong>user</strong>. Akses <strong>admin</strong> tetap dibuat manual oleh superadmin.
      </p>

      <div class="auth-story__grid">
        <div class="auth-story__stat">
          <small>Akses</small>
          <strong>Role User</strong>
          <span>Akses awal hanya untuk fitur umum.</span>
        </div>
        <div class="auth-story__stat">
          <small>Sekuritas</small>
          <strong>Password Kuat</strong>
          <span>Gunakan kombinasi huruf, angka, dan simbol.</span>
        </div>
        <div class="auth-story__stat">
          <small>Admin</small>
          <strong>Manual Approval</strong>
          <span>Akun admin tetap dikelola superadmin.</span>
        </div>
      </div>
    </article>

    <article class="auth-card">
      <div class="auth-card__head">
        <span class="auth-card__eyebrow">Secure Registration</span>
        <h2>Daftar akun GravPort</h2>
        <p>Gunakan email aktif dan password kuat.</p>
      </div>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
      <?php endif; ?>

      <?php $errors = session()->getFlashdata('errors'); ?>
      <?php if (is_array($errors) && $errors !== []): ?>
        <div class="alert alert-danger">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= esc($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form class="auth-form" method="post" action="<?= site_url('signup') ?>">
        <?= csrf_field() ?>

        <div class="auth-field">
          <label for="full_name">Nama Lengkap</label>
          <input id="full_name" name="full_name" type="text" value="<?= esc(old('full_name')) ?>" placeholder="Nama pengguna" required>
        </div>

        <div class="auth-field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?= esc(old('email')) ?>" placeholder="nama@domain.com" required>
        </div>

        <div class="auth-field">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" placeholder="Minimal 12 karakter" required>
        </div>

        <div class="auth-field">
          <label for="password_confirmation">Konfirmasi Password</label>
          <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi password" required>
        </div>

        <button class="auth-submit" type="submit">Create User Account</button>
      </form>

      <div class="auth-note">
        <strong>Catatan Akses</strong>
        <p>
          Jika butuh akses admin, role harus diubah langsung oleh superadmin.
        </p>
      </div>

      <div class="auth-links">
        <a class="auth-switch" href="<?= site_url('login') ?>">
          <span>Sudah punya akun?</span>
          <span>Login</span>
        </a>

        <a class="auth-back" href="<?= site_url('/') ?>">
          <span>&larr;</span>
          <span>Kembali ke home</span>
        </a>
      </div>
    </article>
  </section>
</main>

</body>
</html>
