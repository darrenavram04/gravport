<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Pendaftaran Menunggu</title>

  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css'); ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=31'); ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css'); ?>">

  <style>
    body.admin-hub-page {
      margin: 0;
      font-family: "Poppins", sans-serif;
      background:
        radial-gradient(circle at top right, rgba(167, 96, 37, 0.18), transparent 26%),
        linear-gradient(180deg, #eff4f7 0%, #dfe7ee 100%);
      color: #142033;
    }
    .admin-shell { max-width: 1180px; margin: 0 auto; padding: calc(var(--landing-header-offset) + 18px) 20px 38px; }
    .admin-title  { margin: 0 0 6px; font-size: 36px; line-height: 1.06; }
    .admin-subtitle { margin: 0 0 28px; font-size: 14px; color: #6b7a8f; }

    .section-label {
      display: flex; align-items: center; gap: 10px;
      font-size: 12px; font-weight: 800; letter-spacing: .12em;
      text-transform: uppercase; color: #6b7a8f;
      margin: 0 0 14px;
    }
    .section-label::after {
      content: ''; flex: 1; height: 1px; background: rgba(20,32,51,.1);
    }

    .pending-card {
      background: rgba(255,255,255,.9);
      border: 1px solid rgba(20,32,51,.08);
      border-radius: 24px;
      box-shadow: 0 12px 36px rgba(16,24,40,.09);
      overflow: hidden;
      margin-bottom: 32px;
    }

    .pending-table { width: 100%; border-collapse: collapse; }
    .pending-table th {
      padding: 14px 20px; text-align: left;
      font-size: 11px; font-weight: 800; letter-spacing: .1em;
      text-transform: uppercase; color: #6b7a8f;
      border-bottom: 1px solid rgba(20,32,51,.08);
      background: rgba(20,32,51,.03);
    }
    .pending-table td {
      padding: 16px 20px;
      font-size: 14px; color: #142033;
      border-bottom: 1px solid rgba(20,32,51,.06);
      vertical-align: top;
    }
    .pending-table tr:last-child td { border-bottom: 0; }
    .pending-table tr:hover td { background: rgba(20,32,51,.02); }

    .badge-tier {
      display: inline-block; padding: 3px 10px; border-radius: 999px;
      font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
    }
    .badge-solo, .badge-lite { background: rgba(100,149,237,.12); color: #3a6abf; }
    .badge-pro   { background: rgba(167,96,37,.12);   color: #8b4a17; }
    .badge-team  { background: rgba(72,200,120,.12);  color: #26824a; }

    .badge-cycle {
      display: inline-block; padding: 2px 8px; border-radius: 999px;
      font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
      background: rgba(20,32,51,.07); color: #6b7a8f; margin-top: 4px;
    }

    .action-wrap { display: flex; flex-direction: column; gap: 6px; min-width: 160px; }

    .btn-approve {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 16px; border-radius: 999px;
      background: rgba(72,200,120,.15); color: #26824a;
      border: 1px solid rgba(72,200,120,.3);
      font-size: 13px; font-weight: 700; cursor: pointer;
      transition: background .15s;
    }
    .btn-approve:hover { background: rgba(72,200,120,.28); }

    .btn-reject-toggle {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 16px; border-radius: 999px;
      background: rgba(220,60,60,.1); color: #b02020;
      border: 1px solid rgba(220,60,60,.25);
      font-size: 13px; font-weight: 700; cursor: pointer;
      transition: background .15s;
    }
    .btn-reject-toggle:hover { background: rgba(220,60,60,.2); }

    .reject-form {
      display: none; margin-top: 6px;
      background: rgba(220,60,60,.06);
      border: 1px solid rgba(220,60,60,.2);
      border-radius: 16px; padding: 12px;
    }
    .reject-form.is-open { display: block; }
    .reject-form textarea {
      width: 100%; min-height: 60px; resize: vertical;
      border: 1px solid rgba(20,32,51,.15); border-radius: 10px;
      padding: 8px 10px; font-size: 13px; font-family: inherit;
      background: #fff; color: #142033;
      margin-bottom: 8px;
    }
    .btn-reject-confirm {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: 999px;
      background: #b02020; color: #fff;
      border: 0; font-size: 13px; font-weight: 700; cursor: pointer;
    }
    .btn-reject-confirm:hover { background: #8e1818; }

    .empty-state { padding: 36px 24px; text-align: center; color: #6b7a8f; font-size: 14px; }
    .empty-state i { font-size: 32px; display: block; margin-bottom: 10px; opacity: .4; }

    .name-cell strong { display: block; font-weight: 700; }
    .name-cell small  { color: #6b7a8f; font-size: 12px; }

    .date-cell { font-size: 12px; color: #6b7a8f; }
  </style>
</head>
<?php
$tierDisplayNames = ['solo' => 'Lite', 'pro' => 'Pro', 'Enterprise' => 'Enterprise'];
$tierDisplayName = fn(string $t): string => $tierDisplayNames[$t] ?? ucfirst($t);
?>
<body class="admin-hub-page gravport-landing">

<?= view('partials/site_header', [
    'activePage'  => 'admin',
    'headerClass' => 'header--solid',
]) ?>

<main class="admin-shell">
  <h1 class="admin-title">Pendaftaran Menunggu</h1>
  <p class="admin-subtitle">Tinjau dan aktifkan akun setelah pembayaran dikonfirmasi.</p>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <!-- ── Individu (Solo / Pro) ──────────────────────────────────────── -->
  <div class="section-label"><i class="bi bi-person"></i> Individu - Lite &amp; Pro</div>

  <div class="pending-card">
    <?php if (empty($individualPending)): ?>
      <div class="empty-state">
        <i class="bi bi-inbox"></i>
        Tidak ada pendaftaran individu yang menunggu konfirmasi.
      </div>
    <?php else: ?>
      <div style="overflow-x:auto">
        <table class="pending-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Nama</th>
              <th>Email</th>
              <th>Paket</th>
              <th>Dikirim</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($individualPending as $p): ?>
            <tr>
              <td style="color:#6b7a8f;font-size:12px"><?= (int) $p['pending_id'] ?></td>
              <td class="name-cell">
                <strong><?= esc($p['full_name']) ?></strong>
              </td>
              <td><small><?= esc($p['email']) ?></small></td>
              <td>
                <span class="badge-tier badge-<?= esc(strtolower($p['tier_name'])) ?>">
                  <?= esc($tierDisplayName($p['tier_name'])) ?>
                </span><br>
                <span class="badge-cycle"><?= esc($p['billing_cycle'] === 'annual' ? 'Tahunan' : 'Bulanan') ?></span>
              </td>
              <td class="date-cell"><?= esc(date('d M Y, H:i', strtotime($p['created_at']))) ?></td>
              <td>
                <div class="action-wrap">
                  <!-- Approve -->
                  <form method="post" action="<?= site_url('admin/pending/individual/' . (int)$p['pending_id'] . '/approve') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-approve">
                      <i class="bi bi-check-lg"></i> Aktifkan
                    </button>
                  </form>

                  <!-- Reject toggle -->
                  <button class="btn-reject-toggle" type="button"
                          onclick="toggleReject(this, 'ri-<?= (int)$p['pending_id'] ?>')">
                    <i class="bi bi-x-lg"></i> Tolak
                  </button>
                  <div class="reject-form" id="ri-<?= (int)$p['pending_id'] ?>">
                    <form method="post" action="<?= site_url('admin/pending/individual/' . (int)$p['pending_id'] . '/reject') ?>">
                      <?= csrf_field() ?>
                      <textarea name="rejection_note" placeholder="Alasan penolakan (opsional)…"></textarea>
                      <button type="submit" class="btn-reject-confirm">
                        <i class="bi bi-x-circle"></i> Konfirmasi Tolak
                      </button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── Tim / Bisnis ──────────────────────────────────────────────── -->
  <div class="section-label"><i class="bi bi-building"></i> Tim / Bisnis</div>

  <div class="pending-card">
    <?php if (empty($teamPending)): ?>
      <div class="empty-state">
        <i class="bi bi-inbox"></i>
        Tidak ada pendaftaran tim yang menunggu konfirmasi.
      </div>
    <?php else: ?>
      <div style="overflow-x:auto">
        <table class="pending-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Organisasi</th>
              <th>Email Org.</th>
              <th>Kontak</th>
              <th>Seat</th>
              <th>Siklus</th>
              <th>Dikirim</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($teamPending as $p): ?>
            <tr>
              <td style="color:#6b7a8f;font-size:12px"><?= (int) $p['pending_id'] ?></td>
              <td class="name-cell">
                <strong><?= esc($p['org_name']) ?></strong>
              </td>
              <td><small><?= esc($p['org_email']) ?></small></td>
              <td><?= esc($p['contact_name']) ?></td>
              <td><?= (int) $p['seat_count'] ?> akun</td>
              <td>
                <span class="badge-cycle"><?= esc($p['billing_cycle'] === 'annual' ? 'Tahunan' : 'Bulanan') ?></span>
              </td>
              <td class="date-cell"><?= esc(date('d M Y, H:i', strtotime($p['created_at']))) ?></td>
              <td>
                <div class="action-wrap">
                  <form method="post" action="<?= site_url('admin/pending/team/' . (int)$p['pending_id'] . '/approve') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-approve">
                      <i class="bi bi-check-lg"></i> Aktifkan
                    </button>
                  </form>

                  <button class="btn-reject-toggle" type="button"
                          onclick="toggleReject(this, 'rt-<?= (int)$p['pending_id'] ?>')">
                    <i class="bi bi-x-lg"></i> Tolak
                  </button>
                  <div class="reject-form" id="rt-<?= (int)$p['pending_id'] ?>">
                    <form method="post" action="<?= site_url('admin/pending/team/' . (int)$p['pending_id'] . '/reject') ?>">
                      <?= csrf_field() ?>
                      <textarea name="rejection_note" placeholder="Alasan penolakan (opsional)…"></textarea>
                      <button type="submit" class="btn-reject-confirm">
                        <i class="bi bi-x-circle"></i> Konfirmasi Tolak
                      </button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <p style="margin-top:8px">
    <a href="<?= site_url('admin') ?>" style="color:#a76025;text-decoration:none;font-size:14px;font-weight:700">
      ← Kembali ke Admin Hub
    </a>
  </p>
</main>

<script>
function toggleReject(btn, id) {
  var el = document.getElementById(id);
  if (!el) return;
  var open = el.classList.toggle('is-open');
  btn.innerHTML = open
    ? '<i class="bi bi-chevron-up"></i> Batal'
    : '<i class="bi bi-x-lg"></i> Tolak';
}
</script>
</body>
</html>


