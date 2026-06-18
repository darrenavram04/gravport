<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Status Pembayaran</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; font-family: "Manrope", sans-serif;
      background:
        radial-gradient(ellipse at top left, rgba(30, 50, 200, 0.28), transparent 42%),
        radial-gradient(ellipse at 80% 10%, rgba(20, 30, 150, 0.2), transparent 38%),
        linear-gradient(160deg, #07093d 0%, #060a28 45%, #040612 100%);
      display: grid; place-items: center; padding: 24px; color: rgba(245,248,255,.96); }
    .card { width: min(440px,100%); background: rgba(8,17,31,.88); border: 1px solid rgba(255,255,255,.1);
      border-radius: 32px; padding: 44px 36px; text-align: center;
      box-shadow: 0 28px 80px rgba(2,10,28,.42); }
    .icon { width: 72px; height: 72px; border-radius: 50%; margin: 0 auto 22px;
      display: flex; align-items: center; justify-content: center; }
    .icon.success { background: rgba(73,189,139,.18); }
    .icon.pending { background: rgba(255,191,116,.12); }
    h2 { margin: 0 0 10px; font-size: 24px; color: #fff; }
    p { color: rgba(219,226,242,.72); font-size: 14px; line-height: 1.8; margin: 0 0 28px; }
    .btn { display: inline-block; padding: 14px 36px; border-radius: 999px; font-family: inherit;
      font-weight: 800; font-size: 15px; text-decoration: none; transition: transform .18s;
      background: linear-gradient(135deg, #fff4e7, #ffbf74); color: #08111f; }
    .btn:hover { transform: translateY(-1px); }
    .order-id { font-size: 12px; color: rgba(255,255,255,.3); margin-top: 20px; font-family: monospace; }
  </style>
</head>
<body>
  <div class="card">
    <?php
      $status = $transaction_status ?? '';
      $isPending = $status === 'pending' || $status === '';
    ?>

    <?php if (!$isPending): ?>
      <div class="icon success">
        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#49bd8b" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
      <h2>Pembayaran Berhasil!</h2>
      <p>
        Terima kasih atas pembayaran Anda.<br>
        Akun GravPort Anda akan diaktifkan dalam waktu 1 x 24 jam .<br><br>
        Cek email Anda untuk konfirmasi aktivasi dan langkah selanjutnya.
      </p>
    <?php else: ?>
      <div class="icon pending">
        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#ffbf74" stroke-width="2.5">
          <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
        </svg>
      </div>
      <h2>Menunggu Pembayaran</h2>
      <p>
        Pembayaran Anda sedang diproses.<br>
        Jika menggunakan transfer bank atau virtual account, selesaikan pembayaran sesuai instruksi yang dikirimkan Midtrans.<br><br>
        Akun Anda akan diaktifkan otomatis begitu pembayaran dikonfirmasi.
      </p>
    <?php endif; ?>

    <a href="<?= esc($login_url) ?>" class="btn">Ke Halaman Login &rarr;</a>

    <?php if (!empty($order_id)): ?>
      <p class="order-id">Order ID: <?= esc($order_id) ?></p>
    <?php endif; ?>
  </div>
</body>
</html>


