<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Verifikasi 2FA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css'); ?>">
  <style>
    :root { --c-amber: #ffbf74; --c-cyan: #61d4ff; --c-text: rgba(245,248,255,.96); --c-muted: rgba(219,226,242,.72); }
    * { box-sizing: border-box; }
    body {
      margin: 0; min-height: 100vh; font-family: "Manrope", sans-serif; color: var(--c-text);
      background:
        radial-gradient(ellipse at top left, rgba(30, 50, 200, 0.28), transparent 42%),
        radial-gradient(ellipse at 80% 10%, rgba(20, 30, 150, 0.2), transparent 38%),
        linear-gradient(160deg, #07093d 0%, #060a28 45%, #040612 100%);
      display: grid; place-items: center; padding: 28px;
    }
    .card {
      width: min(420px, 100%);
      background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03)), rgba(8,17,31,.88);
      border: 1px solid rgba(255,255,255,.1); border-radius: 32px;
      padding: 40px 32px; box-shadow: 0 28px 80px rgba(2,10,28,.42);
      position: relative; overflow: hidden; text-align: center;
    }
    .card::before {
      content: ''; position: absolute; inset: 0; pointer-events: none;
      background: radial-gradient(circle at top right, rgba(255,191,116,.10), transparent 36%);
    }
    .icon-wrap {
      position: relative;
      display: inline-flex; align-items: center; justify-content: center;
      width: 72px; height: 72px; border-radius: 22px;
      background: rgba(97,212,255,.1); border: 1px solid rgba(97,212,255,.2);
      margin-bottom: 20px;
    }
    .icon-wrap svg { color: var(--c-cyan); }
    h2 { margin: 0 0 8px; font-size: 26px; color: #fff; position: relative; }
    .sub { margin: 0 0 28px; color: var(--c-muted); font-size: 14px; line-height: 1.7; position: relative; }
    .sub strong { color: var(--c-amber); }

    /* OTP input row */
    .otp-row {
      position: relative;
      display: flex; gap: 10px; justify-content: center; margin-bottom: 24px;
    }
    .otp-row input {
      width: 52px; height: 60px; text-align: center;
      font-size: 24px; font-weight: 800; font-family: inherit;
      border-radius: 16px; border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.05); color: #fff;
      transition: border-color .18s, box-shadow .18s, background .18s;
      caret-color: var(--c-cyan);
    }
    .otp-row input:focus {
      outline: none; border-color: rgba(97,212,255,.5);
      background: rgba(255,255,255,.08); box-shadow: 0 0 0 4px rgba(97,212,255,.12);
    }
    /* Hidden real input for form submission */
    #otp_code { display: none; }

    .btn-primary {
      width: 100%; min-height: 52px; border: 0; border-radius: 999px; cursor: pointer;
      background: linear-gradient(135deg, #fff4e7 0%, var(--c-amber) 58%, #ffd095 100%);
      color: #08111f; font: inherit; font-weight: 800; font-size: 15px;
      box-shadow: 0 20px 40px rgba(255,191,116,.18); transition: transform .18s, box-shadow .18s;
      position: relative;
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 24px 50px rgba(255,191,116,.22); }

    .resend-row { margin-top: 16px; font-size: 13px; color: var(--c-muted); position: relative; }
    .resend-row a { color: var(--c-amber); text-decoration: none; font-weight: 700; }
    .resend-row a:hover { text-decoration: underline; }

    .back-link { display: inline-flex; align-items: center; gap: 6px; margin-top: 20px; color: rgba(255,255,255,.6); text-decoration: none; font-weight: 700; font-size: 13px; position: relative; }
    .back-link:hover { color: #fff; }

    .alert { border-radius: 16px; padding: 12px 16px; margin-bottom: 20px; font-size: 14px; text-align: left; position: relative; }
    .alert-danger  { background: rgba(217,69,69,.16); color: #ffd9d9; }
    .alert-success { background: rgba(73,189,139,.18); color: #dcffef; }

    .timer { font-size: 12px; color: rgba(255,255,255,.4); margin-top: 6px; position: relative; }

    @media (max-width: 480px) {
      body { padding: 14px; }
      .card { padding: 28px 18px; border-radius: 26px; }
      .otp-row input { width: 44px; height: 54px; font-size: 20px; border-radius: 12px; }
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon-wrap">
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
      </svg>
    </div>

    <h2>Cek Email Anda</h2>
    <p class="sub">
      Kode OTP 6 digit telah dikirim ke email akun Anda.<br>
      <strong>Kode berlaku 5 menit.</strong>
    </p>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('verify-2fa') ?>" id="otpForm">
      <?= csrf_field() ?>
      <input type="hidden" name="otp_code" id="otp_code">

      <div class="otp-row" id="otpBoxes">
        <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" autocomplete="one-time-code" class="otp-digit" data-index="0">
        <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" class="otp-digit" data-index="1">
        <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" class="otp-digit" data-index="2">
        <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" class="otp-digit" data-index="3">
        <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" class="otp-digit" data-index="4">
        <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" class="otp-digit" data-index="5">
      </div>

      <button class="btn-primary" type="submit" id="submitBtn" disabled>Verifikasi</button>
    </form>

    <div class="resend-row">
      Tidak menerima kode? <a href="<?= site_url('resend-otp') ?>">Kirim ulang</a>
    </div>
    <div class="timer" id="timerDisplay"></div>

    <a class="back-link" href="<?= site_url('login') ?>">&larr; Kembali ke Login</a>
  </div>

<script>
  const digits  = document.querySelectorAll('.otp-digit');
  const hidden  = document.getElementById('otp_code');
  const submit  = document.getElementById('submitBtn');
  const EXPIRE  = 5 * 60; // seconds
  let   seconds = EXPIRE;

  function updateHidden() {
    const val = [...digits].map(d => d.value).join('');
    hidden.value = val;
    submit.disabled = val.length < 6;
  }

  digits.forEach((el, i) => {
    el.addEventListener('input', () => {
      el.value = el.value.replace(/\D/g, '').slice(-1);
      updateHidden();
      if (el.value && i < 5) digits[i + 1].focus();
    });
    el.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !el.value && i > 0) digits[i - 1].focus();
    });
    el.addEventListener('paste', e => {
      e.preventDefault();
      const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
      paste.split('').forEach((ch, j) => { if (digits[j]) digits[j].value = ch; });
      updateHidden();
      const next = Math.min(paste.length, 5);
      digits[next].focus();
    });
  });

  // Countdown timer
  function tick() {
    const m = Math.floor(seconds / 60).toString().padStart(2, '0');
    const s = (seconds % 60).toString().padStart(2, '0');
    document.getElementById('timerDisplay').textContent = seconds > 0
      ? `Kode kedaluwarsa dalam ${m}:${s}`
      : 'Kode sudah kedaluwarsa. Klik "Kirim ulang".';
    if (seconds > 0) { seconds--; setTimeout(tick, 1000); }
  }
  tick();

  digits[0].focus();
</script>
  <div id="auth-loader" style="position:fixed;inset:0;z-index:99999;background:#04101d;display:flex;flex-direction:column;align-items:center;justify-content:center;transition:opacity .5s ease,visibility .5s ease;"><img src="<?= base_url('images/gravport_logo_color.png') ?>" style="width:52px;height:52px;object-fit:contain;filter:drop-shadow(0 0 16px rgba(167,96,37,.5));margin-bottom:20px;"><div style="width:160px;height:2px;background:rgba(255,255,255,.1);border-radius:999px;overflow:hidden;"><div id="auth-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#a76025,#ffbf74 50%,#61d4ff);border-radius:999px;transition:width .8s cubic-bezier(.4,0,.2,1);"></div></div></div>
  <script>setTimeout(function(){document.getElementById('auth-bar').style.width='100%'},50);window.addEventListener('load',function(){var l=document.getElementById('auth-loader');setTimeout(function(){l.style.opacity='0';l.style.visibility='hidden'},400)});</script>
</body>
</html>

