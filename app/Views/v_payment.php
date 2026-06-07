<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Pembayaran</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css'); ?>">
  <style>
    :root { --c-amber: #ffbf74; --c-cyan: #61d4ff; --c-text: rgba(245,248,255,.96); --c-muted: rgba(219,226,242,.72); }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; font-family: "Manrope", sans-serif; color: var(--c-text);
      background:
        radial-gradient(ellipse at top left, rgba(30, 50, 200, 0.28), transparent 42%),
        radial-gradient(ellipse at 80% 10%, rgba(20, 30, 150, 0.2), transparent 38%),
        linear-gradient(160deg, #07093d 0%, #060a28 45%, #040612 100%);
      display: grid; place-items: center; padding: 28px; }
    .card { width: min(480px, 100%); background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03)), rgba(8,17,31,.88);
      border: 1px solid rgba(255,255,255,.1); border-radius: 32px; padding: 40px 36px;
      box-shadow: 0 28px 80px rgba(2,10,28,.42); position: relative; overflow: hidden; }
    .card::before { content: ''; position: absolute; inset: 0; pointer-events: none;
      background: radial-gradient(circle at top right, rgba(255,191,116,.10), transparent 36%); }
    .eyebrow { display: inline-flex; align-items: center; gap: 8px; color: rgba(255,255,255,.6);
      font-size: 12px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
    .eyebrow::before { content: ''; width: 28px; height: 1px; background: rgba(255,255,255,.36); }
    h2 { margin: 12px 0 6px; font-size: 26px; line-height: 1.1; color: #fff; }
    .summary-box { background: rgba(255,191,116,.06); border: 1px solid rgba(255,191,116,.18);
      border-radius: 18px; padding: 20px 22px; margin: 22px 0; }
    .summary-box .label { font-size: 12px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--c-amber); margin-bottom: 12px; }
    .summary-row { display: flex; justify-content: space-between; align-items: baseline;
      color: var(--c-muted); font-size: 14px; padding: 4px 0; }
    .summary-row.total { border-top: 1px solid rgba(255,255,255,.1); margin-top: 10px; padding-top: 12px;
      color: #fff; font-size: 18px; font-weight: 800; }
    .summary-row.total span:last-child { color: var(--c-amber); }
    .btn-pay { width: 100%; min-height: 54px; border: 0; border-radius: 999px; cursor: pointer;
      background: linear-gradient(135deg, #fff4e7 0%, var(--c-amber) 58%, #ffd095 100%);
      color: #08111f; font: inherit; font-weight: 800; font-size: 16px;
      box-shadow: 0 20px 40px rgba(255,191,116,.22); transition: transform .18s, box-shadow .18s, opacity .18s;
      display: flex; align-items: center; justify-content: center; gap: 10px; }
    .btn-pay:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 24px 50px rgba(255,191,116,.3); }
    .btn-pay:disabled { opacity: .6; cursor: not-allowed; }
    .spinner { width: 20px; height: 20px; border: 2px solid rgba(8,17,31,.3); border-top-color: #08111f;
      border-radius: 50%; animation: spin .7s linear infinite; display: none; }
    .btn-pay.loading .spinner { display: block; }
    .btn-pay.loading .btn-text { display: none; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .notice { font-size: 12px; color: rgba(255,255,255,.4); text-align: center; margin-top: 14px; line-height: 1.7; }
    .notice a { color: rgba(255,255,255,.6); }
    .secure-row { display: flex; align-items: center; justify-content: center; gap: 8px;
      margin-top: 20px; color: rgba(255,255,255,.35); font-size: 12px; }
    .secure-row svg { opacity: .5; }
    @media (max-width: 480px) { body { padding: 14px; } .card { padding: 28px 20px; border-radius: 26px; } }
  </style>
</head>
<body>
  <div class="card">
    <span class="eyebrow">Pembayaran</span>
    <h2>Selesaikan Pembayaran</h2>
    <p style="color:var(--c-muted);font-size:14px;margin:6px 0 0;">Klik tombol di bawah untuk membuka halaman pembayaran yang aman.</p>

    <div class="summary-box">
      <div class="label">Ringkasan Pesanan</div>

      <?php if ($type === 'individual'): ?>
        <div class="summary-row"><span>Nama</span><span><?= esc($record['full_name']) ?></span></div>
        <div class="summary-row"><span>Email</span><span><?= esc($record['email']) ?></span></div>
        <div class="summary-row"><span>Paket</span><span><?= ucfirst(esc($record['tier_name'])) ?> - <?= $record['billing_cycle'] === 'annual' ? 'Tahunan' : 'Bulanan' ?></span></div>
      <?php else: ?>
        <div class="summary-row"><span>Organisasi</span><span><?= esc($record['org_name']) ?></span></div>
        <div class="summary-row"><span>Email</span><span><?= esc($record['org_email']) ?></span></div>
        <div class="summary-row"><span>Jumlah Akun</span><span><?= (int) $record['seat_count'] ?> akun</span></div>
        <div class="summary-row"><span>Paket</span><span>Enterprise - <?= $record['billing_cycle'] === 'annual' ? 'Tahunan' : 'Bulanan' ?></span></div>
      <?php endif; ?>

      <div class="summary-row total">
        <span>Total</span>
        <span>Rp <?= number_format($amount, 0, ',', '.') ?></span>
      </div>
    </div>

    <button id="pay-btn" class="btn-pay" onclick="startPayment()">
      <span class="btn-text">Bayar Sekarang</span>
      <span class="spinner"></span>
    </button>

    <p class="notice">
      Pembayaran diproses secara aman oleh <strong>Midtrans</strong>.<br>
      Mendukung kartu kredit, transfer bank, GoPay, QRIS, dan lainnya.
    </p>

    <div class="secure-row">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
      </svg>
      <span>Transaksi dienkripsi SSL/TLS</span>
    </div>
  </div>

  <script src="<?= esc($snap_js_url) ?>" data-client-key="<?= esc($client_key) ?>"></script>
  <script>
    function startPayment() {
      const btn = document.getElementById('pay-btn');
      btn.disabled = true;
      btn.classList.add('loading');

      snap.pay('<?= esc($snap_token) ?>', {
        onSuccess: function(result) {
          window.location.href = '<?= esc($finish_url) ?>?transaction_status=settlement&order_id=<?= esc($order_id) ?>';
        },
        onPending: function(result) {
          window.location.href = '<?= esc($finish_url) ?>?transaction_status=pending&order_id=<?= esc($order_id) ?>';
        },
        onError: function(result) {
          btn.disabled = false;
          btn.classList.remove('loading');
          alert('Pembayaran gagal. Silakan coba lagi.');
          console.error('Midtrans error:', result);
        },
        onClose: function() {
          btn.disabled = false;
          btn.classList.remove('loading');
        }
      });
    }
  </script>
</body>
</html>



