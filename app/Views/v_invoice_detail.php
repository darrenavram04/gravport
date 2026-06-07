<?php
// Passed: $invoice (array), $user (array with full_name, email)
$statusLabel = match($invoice['status']) {
  'paid'      => 'LUNAS',
  'cancelled' => 'DIBATALKAN',
  default     => 'BELUM LUNAS',
};
$statusColor = match($invoice['status']) {
  'paid'      => '#1a7a4a',
  'cancelled' => '#b91c1c',
  default     => '#92400e',
};
$tierLabel = \App\Models\InvoiceModel::tierLabel($invoice['tier_name'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice <?= esc($invoice['invoice_number']) ?> - GravPort</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Poppins', sans-serif; background: #f3f4f6; color: #142033; }

.invoice-wrap { max-width: 760px; margin: 32px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 40px rgba(0,0,0,.1); }

/* Header */
.inv-header { background: #142033; color: #fff; padding: 32px 40px; display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; }
.inv-brand { font-size: 1.6rem; font-weight: 800; color: #ffbf74; letter-spacing: -.02em; }
.inv-brand-sub { font-size: .78rem; color: rgba(255,255,255,.5); margin-top: 2px; }
.inv-number { text-align: right; }
.inv-number h2 { font-size: 1.1rem; font-weight: 700; color: #fff; }
.inv-number .inv-date { font-size: .82rem; color: rgba(255,255,255,.55); margin-top: 4px; }

/* Status badge */
.inv-status-bar { background: <?= $invoice['status'] === 'paid' ? 'rgba(62,207,142,.12)' : ($invoice['status'] === 'cancelled' ? 'rgba(248,113,113,.12)' : 'rgba(251,191,36,.12)') ?>; padding: 10px 40px; display: flex; align-items: center; justify-content: space-between; }
.inv-status-badge { font-size: .85rem; font-weight: 800; color: <?= $statusColor ?>; letter-spacing: .08em; }

/* Body */
.inv-body { padding: 36px 40px; }
.inv-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px; }
.inv-party-label { font-size: .7rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #a76025; margin-bottom: 6px; }
.inv-party-name { font-size: 1rem; font-weight: 700; color: #142033; }
.inv-party-detail { font-size: .82rem; color: #6b7280; margin-top: 3px; }

/* Line items */
.inv-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
.inv-table th { text-align: left; padding: 10px 12px; font-size: .78rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #4a6080; border-bottom: 2px solid #e5e7eb; }
.inv-table td { padding: 14px 12px; border-bottom: 1px solid #f3f4f6; font-size: .9rem; color: #142033; }
.inv-table tr:last-child td { border-bottom: none; }

/* Totals */
.inv-totals { display: flex; justify-content: flex-end; }
.inv-totals-inner { width: 280px; }
.inv-total-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: .87rem; color: #4a6080; }
.inv-total-row.grand { font-size: 1.1rem; font-weight: 800; color: #142033; border-top: 2px solid #e5e7eb; padding-top: 12px; margin-top: 4px; }

/* Payment info */
.inv-payment { background: #f8fafc; border-radius: 12px; padding: 20px 24px; margin-top: 28px; }
.inv-payment-title { font-size: .82rem; font-weight: 700; color: #a76025; margin-bottom: 12px; text-transform: uppercase; letter-spacing: .06em; }
.inv-payment-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: .85rem; }
.inv-payment-row .lbl { color: #6b7280; }
.inv-payment-row .val { font-weight: 600; color: #142033; }

/* Footer */
.inv-footer { padding: 24px 40px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
.inv-footer-note { font-size: .78rem; color: #9ca3af; }

/* Print actions */
.print-actions { text-align: center; padding: 20px; display: flex; justify-content: center; gap: 12px; }
.btn-print { background: #142033; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-size: .88rem; font-weight: 700; cursor: pointer; font-family: inherit; }
.btn-back  { background: rgba(20,32,51,.07); color: #142033; border: none; padding: 10px 24px; border-radius: 10px; font-size: .88rem; font-weight: 600; cursor: pointer; font-family: inherit; text-decoration: none; display: inline-flex; align-items: center; }

@media print {
  body { background: #fff; }
  .print-actions { display: none; }
  .invoice-wrap { margin: 0; box-shadow: none; border-radius: 0; }
  @page { margin: 1cm; }
}
</style>
</head>
<body>

<div class="print-actions">
  <a href="<?= site_url('account/invoice') ?>" class="btn-back">← Kembali</a>
  <button class="btn-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
</div>

<div class="invoice-wrap">

  <!-- Header -->
  <div class="inv-header">
    <div>
      <div class="inv-brand">GravPort</div>
      <div class="inv-brand-sub">Platform Data Gravitasi Indonesia</div>
      <div style="margin-top:12px;font-size:.8rem;color:rgba(255,255,255,.55);line-height:1.6">
        gravportadmin@gmail.com<br>
        geoportal.id
      </div>
    </div>
    <div class="inv-number">
      <div style="font-size:.72rem;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px">Invoice</div>
      <h2><?= esc($invoice['invoice_number']) ?></h2>
      <div class="inv-date">Diterbitkan: <?= esc(date('d M Y', strtotime($invoice['issued_at']))) ?></div>
      <?php if ($invoice['due_date']): ?>
      <div class="inv-date">Jatuh tempo: <?= esc(date('d M Y', strtotime($invoice['due_date']))) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Status bar -->
  <div class="inv-status-bar">
    <span class="inv-status-badge"><?= $statusLabel ?></span>
    <?php if ($invoice['paid_at']): ?>
    <span style="font-size:.8rem;color:#1a7a4a">Dibayar: <?= esc(date('d M Y H:i', strtotime($invoice['paid_at']))) ?></span>
    <?php endif; ?>
  </div>

  <!-- Body -->
  <div class="inv-body">

    <!-- Parties -->
    <div class="inv-parties">
      <div>
        <div class="inv-party-label">Dari</div>
        <div class="inv-party-name">GravPort</div>
        <div class="inv-party-detail">Platform Data Gravitasi Indonesia</div>
        <div class="inv-party-detail">gravportadmin@gmail.com</div>
      </div>
      <div>
        <div class="inv-party-label">Kepada</div>
        <div class="inv-party-name"><?= esc($user['full_name'] ?? 'N/A') ?></div>
        <div class="inv-party-detail"><?= esc($user['email'] ?? 'N/A') ?></div>
      </div>
    </div>

    <!-- Line items -->
    <table class="inv-table">
      <thead>
        <tr>
          <th>Deskripsi</th>
          <th>Siklus</th>
          <th style="text-align:right">Jumlah</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <div style="font-weight:700">Langganan GravPort - Paket <?= esc($tierLabel) ?></div>
            <div style="font-size:.78rem;color:#9ca3af;margin-top:2px">Data Gravitasi Level <?= $tierLabel === 'Lite' ? '1' : '1 & 2' ?> - Akses platform GravPort</div>
          </td>
          <td style="color:#6b7280"><?= $invoice['billing_cycle'] === 'annual' ? 'Tahunan' : 'Bulanan' ?></td>
          <td style="text-align:right;font-weight:700">Rp <?= number_format((float)$invoice['subtotal'], 0, ',', '.') ?></td>
        </tr>
      </tbody>
    </table>

    <!-- Totals -->
    <div class="inv-totals">
      <div class="inv-totals-inner">
        <div class="inv-total-row">
          <span>Subtotal</span>
          <span>Rp <?= number_format((float)$invoice['subtotal'], 0, ',', '.') ?></span>
        </div>
        <div class="inv-total-row">
          <span>PPN <?= number_format((float)$invoice['vat_pct'], 0) ?>%</span>
          <span>Rp <?= number_format((float)$invoice['vat_amount'], 0, ',', '.') ?></span>
        </div>
        <div class="inv-total-row grand">
          <span>Total</span>
          <span>Rp <?= number_format((float)$invoice['total_amount'], 0, ',', '.') ?></span>
        </div>
      </div>
    </div>

    <!-- Payment instructions -->
    <?php if ($invoice['status'] !== 'paid'): ?>
    <div class="inv-payment">
      <div class="inv-payment-title">Instruksi Pembayaran</div>
      <div class="inv-payment-row"><span class="lbl">Metode</span><span class="val">Transfer Bank</span></div>
      <div class="inv-payment-row"><span class="lbl">Nama Bank</span><span class="val">-</span></div>
      <div class="inv-payment-row"><span class="lbl">No. Rekening</span><span class="val">-</span></div>
      <div class="inv-payment-row"><span class="lbl">Atas Nama</span><span class="val">GravPort</span></div>
      <div class="inv-payment-row"><span class="lbl">Nominal</span><span class="val" style="color:#a76025;font-size:1rem">Rp <?= number_format((float)$invoice['total_amount'], 0, ',', '.') ?></span></div>
      <div class="inv-payment-row"><span class="lbl">Berita Transfer</span><span class="val" style="font-family:monospace"><?= esc($invoice['invoice_number']) ?></span></div>
      <p style="font-size:.75rem;color:#9ca3af;margin-top:10px">
        Setelah transfer, kirim bukti pembayaran ke gravportadmin@gmail.com dengan subjek: <?= esc($invoice['invoice_number']) ?>.
        Admin akan mengkonfirmasi dalam 1×24 jam kerja.
      </p>
    </div>
    <?php endif; ?>

    <?php if ($invoice['notes']): ?>
    <div style="margin-top:16px;padding:14px;background:#f8fafc;border-radius:10px;font-size:.82rem;color:#6b7280">
      <strong>Catatan:</strong> <?= esc($invoice['notes']) ?>
    </div>
    <?php endif; ?>

  </div>

  <!-- Footer -->
  <div class="inv-footer">
    <div class="inv-footer-note">Invoice ini diterbitkan secara elektronik oleh geoportal.</div>
    <div class="inv-footer-note">geoportal.id</div>
  </div>

</div>

</body>
</html>

