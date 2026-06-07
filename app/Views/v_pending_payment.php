<?php
$tierLabels   = ['solo' => 'Lite', 'pro' => 'Pro', 'Enterprise' => 'Team / Bisnis'];
$tierFeatures = [
    'solo' => '2 GB unduhan / minggu | Data Level 1+2 (FAA & CBA, GeoTIFF)',
    'pro'  => 'Unduhan tak terbatas | Level 1 + Level 2 (GeoTIFF)',
    'Enterprise' => 'Unduhan tak terbatas | Level 1+2 | Multi-Akun Enterprise',
];
$tierLabel   = $tierLabels[$reg_tier ?? '']   ?? ucfirst((string) ($reg_tier ?? ''));
$tierFeature = $tierFeatures[$reg_tier ?? ''] ?? '';
$isTeam      = ($reg_type ?? '') === 'Enterprise';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort - Menunggu Konfirmasi Pembayaran</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Manrope', sans-serif;
      background: #040f1c;
      color: #e0eaf8;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 16px;
    }
    .card {
      max-width: 560px; width: 100%;
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 28px;
      padding: 44px 40px;
    }
    .icon-wrap {
      width: 72px; height: 72px; border-radius: 22px;
      background: rgba(255,191,116,.12);
      border: 1px solid rgba(255,191,116,.25);
      display: flex; align-items: center; justify-content: center;
      font-size: 30px; color: #ffbf74;
      margin: 0 auto 28px;
    }
    h1 { text-align: center; font-size: 26px; font-weight: 800; color: #fff; margin-bottom: 8px; }
    .subtitle {
      text-align: center;
      color: rgba(210,222,242,.72);
      font-size: 14px; line-height: 1.7;
      margin-bottom: 32px;
    }
    .info-box {
      background: rgba(255,255,255,.05);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 16px;
      padding: 20px 24px;
      margin-bottom: 28px;
      display: grid; gap: 14px;
    }
    .info-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
    .info-label {
      font-size: 11px; font-weight: 700;
      letter-spacing: .12em; text-transform: uppercase;
      color: rgba(210,222,242,.5);
      white-space: nowrap; padding-top: 2px;
    }
    .info-value { font-size: 14px; font-weight: 600; color: #e0eaf8; text-align: right; }
    .steps { display: grid; gap: 0; margin-bottom: 32px; }
    .step {
      display: flex; gap: 16px; align-items: flex-start;
      padding-bottom: 22px; position: relative;
    }
    .step:not(:last-child)::after {
      content: ''; position: absolute;
      left: 17px; top: 36px; bottom: 0;
      width: 2px; background: rgba(255,255,255,.07);
    }
    .step-num {
      width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; font-weight: 800;
      background: rgba(255,191,116,.12);
      border: 1px solid rgba(255,191,116,.25);
      color: #ffbf74;
    }
    .step-num.done {
      background: rgba(72,200,120,.12);
      border-color: rgba(72,200,120,.3);
      color: #48c878;
    }
    .step-body { padding-top: 6px; }
    .step-title { font-size: 14px; font-weight: 700; color: #fff; margin-bottom: 3px; }
    .step-desc  { font-size: 13px; color: rgba(210,222,242,.62); line-height: 1.65; }
    .btn-back {
      display: block; width: 100%;
      padding: 15px;
      background: linear-gradient(135deg, #fff4e7, #ffbf74 60%, #ffd095);
      color: #08111f; font-weight: 800; font-size: 15px;
      border-radius: 999px; text-align: center;
      text-decoration: none; transition: opacity .15s;
    }
    .btn-back:hover { opacity: .88; }
    .contact-line {
      text-align: center; font-size: 13px;
      color: rgba(210,222,242,.45);
      margin-top: 18px;
    }
    .contact-line a { color: #ffbf74; text-decoration: none; }
  </style>
</head>
<body>

<div class="card">
  <div class="icon-wrap"><i class="bi bi-envelope-check"></i></div>

  <h1>Instruksi Pembayaran Terkirim!</h1>
  <p class="subtitle">
    Terima kasih telah mendaftar geoportal. Kami sudah mengirim instruksi
    pembayaran ke inbox Anda. Akun akan diaktifkan setelah kami konfirmasi pembayaran.
  </p>

  <div class="info-box">
    <div class="info-row">
      <span class="info-label">Email</span>
      <span class="info-value"><?= esc($reg_email ?? '-') ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Paket</span>
      <span class="info-value"><?= esc($tierLabel) ?></span>
    </div>
    <?php if ($tierFeature !== ''): ?>
    <div class="info-row">
      <span class="info-label">Akses</span>
      <span class="info-value" style="font-size:12px;font-weight:500"><?= esc($tierFeature) ?></span>
    </div>
    <?php endif; ?>
  </div>

  <div class="steps">
    <div class="step">
      <div class="step-num done"><i class="bi bi-check-lg"></i></div>
      <div class="step-body">
        <div class="step-title">Pendaftaran Dikirim</div>
        <div class="step-desc">Data Anda kami terima dan instruksi pembayaran sudah dikirim ke email Anda.</div>
      </div>
    </div>
    <div class="step">
      <div class="step-num">2</div>
      <div class="step-body">
        <div class="step-title">Lakukan Transfer</div>
        <div class="step-desc">
          Ikuti instruksi di email dan lakukan transfer sesuai nominal yang tertera.
          Cantumkan nama Anda<?= $isTeam ? ' / nama organisasi' : '' ?> sebagai keterangan.
        </div>
      </div>
    </div>
    <div class="step">
      <div class="step-num">3</div>
      <div class="step-body">
        <div class="step-title">Konfirmasi Admin</div>
        <div class="step-desc">Tim kami memverifikasi pembayaran dalam 1×24 jam kerja.</div>
      </div>
    </div>
    <div class="step" style="padding-bottom:0">
      <div class="step-num">4</div>
      <div class="step-body">
        <div class="step-title">Akun Diaktifkan</div>
        <div class="step-desc">
          Anda menerima email aktivasi berisi cara login<?= $isTeam ? ' beserta kredensial sementara admin tim' : '' ?>.
        </div>
      </div>
    </div>
  </div>

  <a class="btn-back" href="<?= site_url('login') ?>">Kembali ke Halaman Login</a>

  <p class="contact-line">
    Butuh bantuan? Hubungi <a href="mailto:admin@geoportal.id">admin@geoportal.id</a>
  </p>
</div>

</body>
</html>


