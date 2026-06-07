<?php
// Passed: $org, $members, $pendingInvites, $isAdmin, $seatCount, $usedSeats, $userId
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Tim - <?= esc($org['organization_name'] ?? 'Tim') ?> | GravPort</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Poppins', sans-serif;
  background: radial-gradient(circle at top right, rgba(167,96,37,.14), transparent 28%),
              linear-gradient(180deg, #eff4f7 0%, #dfe7ee 100%);
  min-height: 100vh;
  color: #142033;
}

.page-wrap { max-width: 900px; margin: 0 auto; padding: 24px 16px 60px; }

.back-link {
  display: inline-flex; align-items: center; gap: 6px;
  color: #a76025; font-size: .85rem; font-weight: 600; text-decoration: none;
  margin-bottom: 20px;
}
.back-link:hover { color: #8b4a17; }

/* Hero */
.team-hero {
  background: #fff;
  border: 1px solid rgba(20,32,51,.08);
  border-radius: 20px;
  padding: 28px 32px;
  margin-bottom: 20px;
  box-shadow: 0 4px 24px rgba(16,24,40,.07);
}
.team-eyebrow {
  font-size: .75rem; font-weight: 700; letter-spacing: .12em;
  text-transform: uppercase; color: #a76025; margin-bottom: 6px;
}
.team-name { font-size: 1.6rem; font-weight: 800; color: #142033; margin-bottom: 4px; }
.team-type { font-size: .85rem; color: #6b7280; }

.seat-stats {
  display: flex; gap: 24px; flex-wrap: wrap; margin-top: 20px;
  padding-top: 16px; border-top: 1px solid rgba(20,32,51,.08);
}
.seat-stat { text-align: center; }
.seat-stat-num { font-size: 2rem; font-weight: 800; }
.seat-stat-label { font-size: .72rem; color: #6b7280; margin-top: 2px; }
.num-total   { color: #142033; }
.num-used    { color: #a76025; }
.num-free    { color: #1a7a4a; }

/* Seat progress bar */
.seat-bar-wrap { margin-top: 16px; }
.seat-bar-label { display: flex; justify-content: space-between; font-size: .78rem; color: #6b7280; margin-bottom: 5px; }
.seat-bar { background: rgba(20,32,51,.08); border-radius: 99px; height: 8px; overflow: hidden; }
.seat-bar-fill { height: 100%; border-radius: 99px; transition: width .4s ease;
  background: linear-gradient(90deg, #a76025, #ffbf74); }

/* Cards */
.card {
  background: #fff;
  border: 1px solid rgba(20,32,51,.08);
  border-radius: 20px;
  padding: 24px 28px;
  margin-bottom: 20px;
  box-shadow: 0 4px 24px rgba(16,24,40,.07);
}
.card-title {
  font-size: .95rem; font-weight: 700; color: #142033;
  display: flex; align-items: center; gap: 8px; margin-bottom: 16px;
}
.card-title i { color: #a76025; }

/* Flash messages */
.flash-ok  { background: rgba(62,207,142,.09); border: 1px solid rgba(62,207,142,.25); color: #1a7a4a; border-radius: 10px; padding: 10px 16px; margin-bottom: 16px; font-size: .87rem; }
.flash-err { background: rgba(248,113,113,.09); border: 1px solid rgba(248,113,113,.25); color: #b91c1c; border-radius: 10px; padding: 10px 16px; margin-bottom: 16px; font-size: .87rem; }

/* Members table */
.members-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
.members-table th { text-align: left; padding: 8px; color: #4a6080; font-weight: 600; border-bottom: 2px solid rgba(20,32,51,.08); font-size: .78rem; }
.members-table td { padding: 10px 8px; border-bottom: 1px solid rgba(20,32,51,.06); color: #142033; }
.members-table tr:last-child td { border-bottom: none; }
.badge-admin { background: rgba(167,96,37,.1); color: #8b4a17; padding: 2px 10px; border-radius: 99px; font-size: .72rem; font-weight: 700; }
.badge-member { background: rgba(20,32,51,.07); color: #4a6080; padding: 2px 10px; border-radius: 99px; font-size: .72rem; font-weight: 600; }
.badge-you { background: rgba(26,124,199,.1); color: #1a4c8b; padding: 2px 8px; border-radius: 99px; font-size: .7rem; font-weight: 600; }

/* Avatar */
.avatar {
  width: 32px; height: 32px; border-radius: 50%;
  display: inline-flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: .8rem; color: #fff;
  background: linear-gradient(135deg, #a76025, #ffbf74);
  flex-shrink: 0;
}

/* Invite form */
.invite-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
.invite-input {
  flex: 1; min-width: 200px; padding: 9px 14px;
  border: 1px solid rgba(20,32,51,.15); border-radius: 10px;
  font-size: .87rem; font-family: inherit; color: #142033; background: #fff;
  outline: none;
}
.invite-input:focus { border-color: #a76025; box-shadow: 0 0 0 3px rgba(167,96,37,.1); }
.btn-invite {
  background: #a76025; color: #fff; border: none; padding: 9px 20px;
  border-radius: 10px; font-size: .87rem; font-weight: 700; cursor: pointer;
  display: inline-flex; align-items: center; gap: 6px; font-family: inherit;
  white-space: nowrap;
}
.btn-invite:hover { background: #8b4a17; }
.btn-sm { padding: 4px 12px; border-radius: 7px; font-size: .75rem; font-weight: 600; cursor: pointer; border: none; }
.btn-danger { background: rgba(248,113,113,.1); color: #b91c1c; border: 1px solid rgba(248,113,113,.3); }
.btn-danger:hover { background: rgba(248,113,113,.2); }
.btn-muted { background: rgba(20,32,51,.07); color: #4a6080; border: 1px solid rgba(20,32,51,.12); }
.btn-muted:hover { background: rgba(20,32,51,.12); }

/* Pending invites */
.invite-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 0; border-bottom: 1px solid rgba(20,32,51,.06); }
.invite-row:last-child { border-bottom: none; }
.invite-email { font-size: .87rem; font-weight: 600; color: #142033; }
.invite-expires { font-size: .75rem; color: #9ca3af; }

/* Empty states */
.empty-state { text-align: center; padding: 32px; color: #9ca3af; }
.empty-state i { font-size: 2.5rem; margin-bottom: 8px; display: block; }
.empty-state p { font-size: .87rem; }
</style>
</head>
<body>
<?php echo view('partials/site_header', ['activePage' => 'team']); ?>

<div class="page-wrap">

  <a href="<?= site_url('account') ?>" class="back-link">
    <i class="bi bi-arrow-left"></i> Kembali ke Akun
  </a>

  <?php if (session()->getFlashdata('success')): ?>
  <div class="flash-ok"><i class="bi bi-check-circle-fill"></i> <?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
  <div class="flash-err"><i class="bi bi-exclamation-triangle-fill"></i> <?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <!-- ── Hero ── -->
  <div class="team-hero">
    <div class="team-eyebrow"><i class="bi bi-building"></i> Organisasi</div>
    <div class="team-name"><?= esc($org['organization_name'] ?? 'Tim Saya') ?></div>
    <div class="team-type"><?= esc(ucfirst($org['org_type'] ?? 'subscriber')) ?> | <?= esc($org['organization_email'] ?? '') ?></div>

    <div class="seat-stats">
      <div class="seat-stat"><div class="seat-stat-num num-total"><?= $seatCount ?></div><div class="seat-stat-label">Total Seat</div></div>
      <div class="seat-stat"><div class="seat-stat-num num-used"><?= $usedSeats ?></div><div class="seat-stat-label">Terpakai</div></div>
      <div class="seat-stat"><div class="seat-stat-num num-free"><?= max(0, $seatCount - $usedSeats) ?></div><div class="seat-stat-label">Tersedia</div></div>
    </div>

    <div class="seat-bar-wrap">
      <div class="seat-bar-label">
        <span>Penggunaan Seat</span>
        <span><?= $usedSeats ?> / <?= $seatCount ?></span>
      </div>
      <div class="seat-bar">
        <div class="seat-bar-fill" style="width:<?= $seatCount > 0 ? min(100, round($usedSeats / $seatCount * 100)) : 0 ?>%"></div>
      </div>
    </div>
  </div>

  <!-- ── Invite Member ── -->
  <?php if ($isAdmin): ?>
  <div class="card">
    <div class="card-title"><i class="bi bi-person-plus-fill"></i> Undang Anggota</div>
    <?php if ($usedSeats >= $seatCount): ?>
    <div class="flash-err">
      <i class="bi bi-exclamation-triangle-fill"></i>
      Semua seat sudah terisi. Hubungi GravPort untuk menambah seat.
    </div>
    <?php else: ?>
    <form method="POST" action="<?= site_url('team/invite') ?>">
      <?= csrf_field() ?>
      <div class="invite-form">
        <input type="email" name="email" class="invite-input" placeholder="email@instansi.id" required>
        <button type="submit" class="btn-invite">
          <i class="bi bi-send-fill"></i> Kirim Undangan
        </button>
      </div>
      <p style="margin-top:8px;font-size:.78rem;color:#9ca3af">
        Jika email terdaftar di GravPort, anggota langsung ditambahkan. Jika belum, link undangan dikirim via email.
      </p>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ── Members Table ── -->
  <div class="card">
    <div class="card-title"><i class="bi bi-people-fill"></i> Anggota Tim (<?= count($members) ?>)</div>

    <?php if (empty($members)): ?>
    <div class="empty-state">
      <i class="bi bi-people"></i>
      <p>Belum ada anggota tim. Undang anggota pertama Anda.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
      <table class="members-table">
        <thead>
          <tr>
            <th>Anggota</th>
            <th>Email</th>
            <th>Peran</th>
            <th>Bergabung</th>
            <?php if ($isAdmin): ?><th style="text-align:right">Aksi</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($members as $m):
            $initials = strtoupper(substr($m['full_name'] ?? $m['email'] ?? '?', 0, 2));
            $isYou    = ((int)$m['user_id'] === $userId);
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div class="avatar"><?= esc($initials) ?></div>
                <div>
                  <div style="font-weight:600"><?= esc($m['full_name'] ?? '-') ?></div>
                  <?php if ($isYou): ?><span class="badge-you">Anda</span><?php endif; ?>
                </div>
              </div>
            </td>
            <td style="color:#6b7280;font-size:.83rem"><?= esc($m['email'] ?? '-') ?></td>
            <td>
              <?php if ($m['is_admin']): ?>
              <span class="badge-admin"><i class="bi bi-shield-check"></i> Admin</span>
              <?php else: ?>
              <span class="badge-member">Member</span>
              <?php endif; ?>
            </td>
            <td style="color:#9ca3af;font-size:.8rem"><?= $m['joined_at'] ? esc(date('d M Y', strtotime($m['joined_at']))) : '-' ?></td>
            <?php if ($isAdmin): ?>
            <td style="text-align:right">
              <?php if (!$isYou): ?>
              <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap">
                <form method="POST" action="<?= site_url('team/member/' . $m['user_id'] . '/toggle-admin') ?>">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-sm btn-muted">
                    <?= $m['is_admin'] ? 'Hapus Admin' : 'Jadikan Admin' ?>
                  </button>
                </form>
                <form method="POST" action="<?= site_url('team/member/' . $m['user_id'] . '/remove') ?>" onsubmit="return confirm('Hapus anggota ini dari tim?')">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-sm btn-danger">Hapus</button>
                </form>
              </div>
              <?php else: ?>
              <span style="font-size:.75rem;color:#c4c9d4">-</span>
              <?php endif; ?>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── Pending Invitations ── -->
  <?php if ($isAdmin && !empty($pendingInvites)): ?>
  <div class="card">
    <div class="card-title"><i class="bi bi-envelope-open-fill"></i> Undangan Tertunda (<?= count($pendingInvites) ?>)</div>
    <?php foreach ($pendingInvites as $inv): ?>
    <div class="invite-row">
      <div>
        <div class="invite-email"><?= esc($inv['invited_email']) ?></div>
        <div class="invite-expires">Kadaluarsa: <?= esc(date('d M Y', strtotime($inv['expires_at']))) ?></div>
      </div>
      <form method="POST" action="<?= site_url('team/invite/' . $inv['invite_id'] . '/cancel') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn-sm btn-danger">Batalkan</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

</body>
</html>

