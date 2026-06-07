<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Lupa Password</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css'); ?>">
  <style>
    :root {
      --c-amber: #ffbf74;
      --c-cyan:  #61d4ff;
      --c-text:  rgba(245, 248, 255, 0.96);
      --c-muted: rgba(219, 226, 242, 0.72);
      --c-shadow: 0 28px 80px rgba(2, 10, 28, 0.42);
    }
    * { box-sizing: border-box; }
    body {
      margin: 0; min-height: 100vh;
      font-family: "Manrope", sans-serif;
      color: var(--c-text);
      background:
        radial-gradient(ellipse at top left, rgba(30, 50, 200, 0.28), transparent 42%),
        radial-gradient(ellipse at 80% 10%, rgba(20, 30, 150, 0.2), transparent 38%),
        linear-gradient(160deg, #07093d 0%, #060a28 45%, #040612 100%);
      display: grid; place-items: center; padding: 28px;
    }
    .card {
      width: min(440px, 100%);
      background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03)), rgba(8,17,31,.88);
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 32px;
      padding: 36px 32px;
      box-shadow: var(--c-shadow);
      position: relative;
      overflow: hidden;
    }
    .card::before {
      content: '';
      position: absolute; inset: 0; pointer-events: none;
      background: radial-gradient(circle at top right, rgba(255,191,116,.10), transparent 36%);
    }
    .eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      color: rgba(255,255,255,.6); font-size: 12px; font-weight: 800;
      letter-spacing: .14em; text-transform: uppercase; position: relative;
    }
    .eyebrow::before { content: ''; width: 28px; height: 1px; background: rgba(255,255,255,.36); }
    h2 { margin: 12px 0 6px; font-size: 28px; line-height: 1.1; color: #fff; position: relative; }
    p  { margin: 0 0 22px; color: var(--c-muted); line-height: 1.7; font-size: 14px; position: relative; }
    .field { margin-bottom: 16px; position: relative; }
    .field label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 700; color: rgba(255,255,255,.82); }
    .field input {
      width: 100%; min-height: 52px; padding: 0 16px;
      border-radius: 18px; border: 1px solid rgba(255,255,255,.12);
      background: rgba(255,255,255,.05); color: #fff; font: inherit;
      transition: border-color .18s, box-shadow .18s, background .18s;
    }
    .field input::placeholder { color: rgba(255,255,255,.38); }
    .field input:focus { outline: none; border-color: rgba(97,212,255,.38); background: rgba(255,255,255,.07); box-shadow: 0 0 0 4px rgba(97,212,255,.1); }
    .btn-primary {
      width: 100%; min-height: 52px; border: 0; border-radius: 999px; cursor: pointer;
      background: linear-gradient(135deg, #fff4e7 0%, var(--c-amber) 58%, #ffd095 100%);
      color: #08111f; font: inherit; font-weight: 800; font-size: 15px;
      box-shadow: 0 20px 40px rgba(255,191,116,.18);
      transition: transform .18s, box-shadow .18s;
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 24px 50px rgba(255,191,116,.22); }
    .alert { border-radius: 16px; padding: 12px 16px; margin-bottom: 16px; font-size: 14px; position: relative; }
    .alert-danger  { background: rgba(217,69,69,.16); color: #ffd9d9; }
    .alert-success { background: rgba(73,189,139,.18); color: #dcffef; }
    .back-link { display: inline-flex; align-items: center; gap: 6px; margin-top: 18px; color: rgba(255,255,255,.7); text-decoration: none; font-weight: 700; font-size: 14px; position: relative; }
    .back-link:hover { color: #fff; }
    @media (max-width: 480px) { body { padding: 14px; } .card { padding: 26px 20px; border-radius: 26px; } }
  </style>
</head>
<body>
  <div class="card">
    <span class="eyebrow">Reset Password</span>
    <h2>Lupa Password?</h2>
    <p>Masukkan email Anda dan kami akan mengirimkan link untuk mereset password.</p>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('forgot-password') ?>">
      <?= csrf_field() ?>
      <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= esc(old('email')) ?>" placeholder="email@contoh.com" required autocomplete="email">
      </div>
      <button class="btn-primary" type="submit">Kirim Link Reset</button>
    </form>

    <a class="back-link" href="<?= site_url('login') ?>">&larr; Kembali ke Login</a>
  </div>
  <div id="auth-loader" style="position:fixed;inset:0;z-index:99999;background:#04101d;display:flex;flex-direction:column;align-items:center;justify-content:center;transition:opacity .5s ease,visibility .5s ease;"><img src="<?= base_url('images/gravport_logo_color.png') ?>" style="width:52px;height:52px;object-fit:contain;filter:drop-shadow(0 0 16px rgba(167,96,37,.5));margin-bottom:20px;"><div style="width:160px;height:2px;background:rgba(255,255,255,.1);border-radius:999px;overflow:hidden;"><div id="auth-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#a76025,#ffbf74 50%,#61d4ff);border-radius:999px;transition:width .8s cubic-bezier(.4,0,.2,1);"></div></div></div>
  <script>setTimeout(function(){document.getElementById('auth-bar').style.width='100%'},50);window.addEventListener('load',function(){var l=document.getElementById('auth-loader');setTimeout(function(){l.style.opacity='0';l.style.visibility='hidden'},400)});</script>
</body>
</html>

