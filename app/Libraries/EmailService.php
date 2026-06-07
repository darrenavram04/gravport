<?php

namespace App\Libraries;

/**
 * EmailService - wrapper di atas CI4 Email library untuk template GravPort.
 *
 * Konfigurasi SMTP diambil dari .env:
 *   email.SMTPHost / email.SMTPUser / email.SMTPPass / email.SMTPPort
 *   email.SMTPCrypto / email.fromEmail / email.fromName
 *
 * Semua metode non-fatal: kegagalan kirim email hanya dicatat ke log,
 * tidak melempar exception ke caller.
 */
class EmailService
{
    private const TIER_LABELS = [
        'solo' => 'Lite', 'lite' => 'Lite',
        'pro'  => 'Pro',
        'Enterprise' => 'Enterprise',
    ];

    private const TIER_PRICES = [
        'lite' => ['monthly' => 'Rp 99.000', 'annual' => 'Rp 990.000'],
        'solo' => ['monthly' => 'Rp 99.000', 'annual' => 'Rp 990.000'],
        'pro'  => ['monthly' => 'Rp 349.000', 'annual' => 'Rp 3.490.000'],
        'Enterprise' => ['monthly' => 'Rp 999.000', 'annual' => 'Rp 9.990.000'],
    ];

    private string $adminEmail;

    public function __construct()
    {
        $this->adminEmail = (string) (env('app.adminEmail', 'admin@gravport.id'));
    }

    // ─────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────────────────

    /**
     * Instruksi pembayaran ke calon pengguna individu (Solo/Pro).
     */
    public function sendPaymentInstructions(array $data): bool
    {
        $tierLabel = self::TIER_LABELS[$data['tier_name']] ?? $data['tier_name'];
        $cycle     = $data['billing_cycle'] === 'annual' ? 'Tahunan' : 'Bulanan';
        $price     = self::TIER_PRICES[$data['tier_name']][$data['billing_cycle']] ?? '-';
        $deadline  = date('d M Y', strtotime('+7 days'));

        $html = $this->wrap(
            "Instruksi Pembayaran - Paket {$tierLabel}",
            "Halo <strong>" . esc($data['full_name']) . "</strong>,",
            "<p style='color:#c8d8f0;line-height:1.8;'>
                Terima kasih telah mendaftar di <strong>GravPort</strong>.
                Pendaftaran Anda untuk paket <strong>{$tierLabel} ({$cycle})</strong>
                telah diterima dan sedang menunggu konfirmasi pembayaran.
             </p>
             <div style='background:rgba(255,191,116,.08);border:1px solid rgba(255,191,116,.2);border-radius:12px;padding:20px 24px;margin:20px 0;'>
               <p style='color:#ffbf74;font-weight:700;margin:0 0 12px;font-size:15px;'>Ringkasan Pendaftaran</p>
               <table style='color:#c8d8f0;font-size:14px;border-collapse:collapse;width:100%;'>
                 <tr><td style='padding:4px 0;opacity:.7;width:50%'>Nama</td><td style='padding:4px 0;font-weight:600;'>" . esc($data['full_name']) . "</td></tr>
                 <tr><td style='padding:4px 0;opacity:.7;'>Email</td><td style='padding:4px 0;font-weight:600;'>" . esc($data['email']) . "</td></tr>
                 <tr><td style='padding:4px 0;opacity:.7;'>Paket</td><td style='padding:4px 0;font-weight:600;'>{$tierLabel} - {$cycle}</td></tr>
                 <tr><td style='padding:4px 0;opacity:.7;'>Jumlah</td><td style='padding:4px 0;font-weight:700;color:#ffbf74;font-size:16px;'>{$price}</td></tr>
                 <tr><td style='padding:4px 0;opacity:.7;'>Batas bayar</td><td style='padding:4px 0;color:#fca5a5;font-weight:600;'>{$deadline}</td></tr>
               </table>
             </div>
             <div style='background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:20px 24px;margin:20px 0;'>
               <p style='color:#fff;font-weight:700;margin:0 0 10px;'>Cara Pembayaran</p>
               <p style='color:#c8d8f0;font-size:14px;line-height:1.9;margin:0;'>
                 Transfer ke rekening berikut, lalu kirim bukti transfer ke
                 <a href='mailto:{$this->adminEmail}' style='color:#ffbf74;'>{$this->adminEmail}</a>
                 dengan subjek <strong>BAYAR-{$tierLabel}-" . strtoupper(substr(md5($data['email']), 0, 6)) . "</strong>:<br><br>
                 <strong>Bank</strong>: [ISIAN ADMIN]<br>
                 <strong>No. Rekening</strong>: [ISIAN ADMIN]<br>
                 <strong>Atas Nama</strong>: GravPort / [ISIAN ADMIN]
               </p>
             </div>
             <p style='color:#c8d8f0;font-size:13px;'>
               Akun Anda akan diaktifkan dalam 1×24 jam kerja setelah pembayaran dikonfirmasi.
               Jika ada pertanyaan, hubungi <a href='mailto:{$this->adminEmail}' style='color:#ffbf74;'>{$this->adminEmail}</a>.
             </p>"
        );

        $sent = $this->send($data['email'], "GravPort - Instruksi Pembayaran Paket {$tierLabel}", $html);
        $this->notifyAdmin(
            "Pendaftaran baru: {$data['full_name']} ({$data['email']}) - Paket {$tierLabel} {$cycle}",
            "Harap verifikasi pembayaran dan aktifkan akun via panel admin."
        );
        return $sent;
    }

    /**
     * Instruksi pembayaran ke calon organisasi (Team).
     */
    public function sendTeamPaymentInstructions(array $data): bool
    {
        $cycle    = $data['billing_cycle'] === 'annual' ? 'Tahunan' : 'Bulanan';
        $price    = self::TIER_PRICES['Enterprise'][$data['billing_cycle']] ?? '-';
        $deadline = date('d M Y', strtotime('+7 days'));

        $html = $this->wrap(
            "Instruksi Pembayaran - Paket Enterprise",
            "Halo <strong>" . esc($data['contact_name']) . "</strong>,",
            "<p style='color:#c8d8f0;line-height:1.8;'>
                Pendaftaran organisasi <strong>" . esc($data['org_name']) . "</strong>
                untuk paket <strong>Team ({$cycle})</strong> telah diterima.
             </p>
             <div style='background:rgba(255,191,116,.08);border:1px solid rgba(255,191,116,.2);border-radius:12px;padding:20px 24px;margin:20px 0;'>
               <p style='color:#ffbf74;font-weight:700;margin:0 0 12px;font-size:15px;'>Ringkasan Pendaftaran</p>
               <table style='color:#c8d8f0;font-size:14px;border-collapse:collapse;width:100%;'>
                 <tr><td style='padding:4px 0;opacity:.7;width:50%'>Organisasi</td><td style='padding:4px 0;font-weight:600;'>" . esc($data['org_name']) . "</td></tr>
                 <tr><td style='padding:4px 0;opacity:.7;'>Email</td><td style='padding:4px 0;font-weight:600;'>" . esc($data['org_email']) . "</td></tr>
                 <tr><td style='padding:4px 0;opacity:.7;'>Jumlah Akun</td><td style='padding:4px 0;font-weight:600;'>" . (int)($data['seat_count']) . " akun</td></tr>
                 <tr><td style='padding:4px 0;opacity:.7;'>Paket</td><td style='padding:4px 0;font-weight:600;'>Enterprise - {$cycle}</td></tr>
                 <tr><td style='padding:4px 0;opacity:.7;'>Jumlah</td><td style='padding:4px 0;font-weight:700;color:#ffbf74;font-size:16px;'>{$price}</td></tr>
                 <tr><td style='padding:4px 0;opacity:.7;'>Batas bayar</td><td style='padding:4px 0;color:#fca5a5;font-weight:600;'>{$deadline}</td></tr>
               </table>
             </div>
             <p style='color:#c8d8f0;font-size:13px;'>
               Silakan transfer dan kirim bukti ke
               <a href='mailto:{$this->adminEmail}' style='color:#ffbf74;'>{$this->adminEmail}</a>.
               Kami akan mengaktifkan akun admin organisasi Anda beserta slot akun sesuai yang didaftarkan.
             </p>"
        );

        $sent = $this->send($data['org_email'], 'GravPort - Instruksi Pembayaran Paket Enterprise', $html);
        $this->notifyAdmin(
            "Pendaftaran enterprise baru: {$data['org_name']} ({$data['org_email']}) - {$data['seat_count']} akun, {$cycle}",
            "Harap verifikasi pembayaran dan aktifkan organisasi via panel admin."
        );
        return $sent;
    }

    /**
     * Akun individu (Solo/Pro) berhasil diaktifkan.
     */
    public function sendActivation(array $data): bool
    {
        $tierLabel = self::TIER_LABELS[$data['tier_name']] ?? $data['tier_name'];
        $loginUrl  = site_url('login');

        $html = $this->wrap(
            "Akun Anda Aktif - Paket {$tierLabel}",
            "Halo <strong>" . esc($data['full_name']) . "</strong>,",
            "<p style='color:#c8d8f0;line-height:1.8;'>
                Pembayaran Anda telah dikonfirmasi! Akun GravPort paket <strong>{$tierLabel}</strong>
                kini sudah aktif dan siap digunakan.
             </p>
             " . $this->tierFeatureList($data['tier_name']) . "
             <div style='margin-top:28px;'>
               <a href='{$loginUrl}'
                  style='display:inline-block;padding:13px 32px;background:linear-gradient(135deg,#fff4e7,#ffbf74);color:#08111f;font-weight:700;border-radius:999px;text-decoration:none;font-size:15px;'>
                 Login ke GravPort &rarr;
               </a>
             </div>"
        );

        return $this->send($data['email'], "GravPort - Akun {$tierLabel} Anda Sudah Aktif! 🎉", $html);
    }

    /**
     * Akun Enterprise/organisasi berhasil diaktifkan - kirim kredensial sementara.
     */
    public function sendTeamActivation(array $data): bool
    {
        $loginUrl = site_url('login');
        $resetUrl = site_url('forgot-password');

        $html = $this->wrap(
            'Akun Enterprise Anda Aktif - Paket Enterprise',
            "Halo <strong>" . esc($data['contact_name']) . "</strong>,",
            "<p style='color:#c8d8f0;line-height:1.8;'>
                Pendaftaran organisasi <strong>" . esc($data['org_name']) . "</strong>
                telah dikonfirmasi! Akun admin tim sudah dibuat.
             </p>
             <div style='background:rgba(255,191,116,.08);border:1px solid rgba(255,191,116,.2);border-radius:12px;padding:20px 24px;margin:20px 0;'>
               <p style='color:#ffbf74;font-weight:700;margin:0 0 12px;'>Kredensial Login Admin</p>
               <table style='color:#c8d8f0;font-size:14px;border-collapse:collapse;width:100%;'>
                 <tr><td style='padding:4px 0;opacity:.7;width:40%;'>Email</td><td style='padding:4px 0;font-weight:600;'>" . esc($data['org_email']) . "</td></tr>
                 <tr><td style='padding:4px 0;opacity:.7;'>Password Sementara</td><td style='padding:4px 0;font-weight:700;font-family:monospace;font-size:16px;letter-spacing:2px;'>" . esc($data['temp_password']) . "</td></tr>
               </table>
             </div>
             <p style='color:#fca5a5;font-size:13px;font-weight:600;'>
               &#9888; Segera ubah password Anda setelah login pertama kali.
             </p>
             <div style='margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;'>
               <a href='{$loginUrl}'
                  style='display:inline-block;padding:12px 28px;background:linear-gradient(135deg,#fff4e7,#ffbf74);color:#08111f;font-weight:700;border-radius:999px;text-decoration:none;'>
                 Login Sekarang
               </a>
               <a href='{$resetUrl}'
                  style='display:inline-block;padding:12px 28px;border:1px solid rgba(255,255,255,.2);color:#e8eef8;font-weight:600;border-radius:999px;text-decoration:none;'>
                 Atur Ulang Password
               </a>
             </div>"
        );

        return $this->send($data['org_email'], 'GravPort - Akun Enterprise Anda Sudah Aktif! 🎉', $html);
    }

    /**
     * Pendaftaran ditolak.
     */
    public function sendRejection(array $data): bool
    {
        $name  = $data['full_name'] ?? $data['contact_name'] ?? $data['org_name'] ?? '';
        $email = $data['email'] ?? $data['org_email'] ?? '';
        $note  = $data['rejection_note'] ?? '';

        $html = $this->wrap(
            'Pembaruan Status Pendaftaran GravPort',
            "Halo <strong>" . esc($name) . "</strong>,",
            "<p style='color:#c8d8f0;line-height:1.8;'>
                Mohon maaf, pendaftaran Anda di GravPort tidak dapat diproses saat ini.
             </p>"
            . ($note !== '' ? "<div style='background:rgba(255,100,100,.08);border:1px solid rgba(255,100,100,.2);border-radius:12px;padding:16px 20px;margin:20px 0;'>
               <p style='color:#fca5a5;font-size:14px;margin:0;'><strong>Keterangan:</strong> " . esc($note) . "</p>
             </div>" : '')
            . "<p style='color:#c8d8f0;font-size:13px;'>
                Jika ada pertanyaan atau ingin mendaftar ulang, hubungi kami di
                <a href='mailto:{$this->adminEmail}' style='color:#ffbf74;'>{$this->adminEmail}</a>.
             </p>"
        );

        return $this->send($email, 'GravPort - Status Pendaftaran Anda', $html);
    }

    /**
     * Notifikasi ke superadmin saat admin submit metadata baru.
     */
    public function sendMetadataSubmissionNotice(array $data): bool
    {
        $html = $this->wrap(
            'Metadata Submission Baru #' . esc($data['submission_id']),
            'Ada submission metadata baru yang perlu direview:',
            "<div style='background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:20px;margin:16px 0;'>
               <table style='color:#c8d8f0;font-size:14px;border-collapse:collapse;width:100%;'>
                 <tr><td style='padding:5px 0;opacity:.7;width:35%;'>ID Submission</td><td style='padding:5px 0;font-weight:700;color:#ffbf74;'>#" . esc($data['submission_id']) . "</td></tr>
                 <tr><td style='padding:5px 0;opacity:.7;'>Disubmit oleh</td><td style='padding:5px 0;font-weight:600;'>" . esc($data['submitter_name']) . " (" . esc($data['submitter_email']) . ")</td></tr>
                 <tr><td style='padding:5px 0;opacity:.7;'>Jenis Data</td><td style='padding:5px 0;'>" . esc($data['jenis_data'] ?? '-') . "</td></tr>
                 <tr><td style='padding:5px 0;opacity:.7;'>Provinsi</td><td style='padding:5px 0;'>" . esc($data['provinsi'] ?? '-') . "</td></tr>
                 <tr><td style='padding:5px 0;opacity:.7;'>Level Data</td><td style='padding:5px 0;'>" . esc($data['level_data'] ?? '-') . "</td></tr>
               </table>
             </div>
             <div style='margin-top:20px;'>
               <a href='" . esc($data['review_url']) . "'
                  style='display:inline-block;padding:12px 28px;background:linear-gradient(135deg,#fff4e7,#ffbf74);color:#08111f;font-weight:700;border-radius:999px;text-decoration:none;font-size:14px;'>
                 Review di Admin Hub &rarr;
               </a>
             </div>"
        );
        return $this->send($this->adminEmail, '[GravPort] Metadata Submission Baru #' . $data['submission_id'], $html);
    }

    /**
     * Notifikasi ke admin@gravport.id saat ada permintaan akses admin platform.
     */
    public function sendAdminInquiry(array $data): bool
    {
        $html = $this->wrap(
            'Permintaan Akses Admin Platform',
            'Ada permintaan akses admin baru:',
            "<div style='background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:20px;margin:16px 0;'>
               <table style='color:#c8d8f0;font-size:14px;border-collapse:collapse;width:100%;'>
                 <tr><td style='padding:5px 0;opacity:.7;width:30%;'>Nama</td><td style='padding:5px 0;font-weight:600;'>" . esc($data['full_name']) . "</td></tr>
                 <tr><td style='padding:5px 0;opacity:.7;'>Email</td><td style='padding:5px 0;font-weight:600;'>" . esc($data['email']) . "</td></tr>
                 <tr><td style='padding:5px 0;opacity:.7;'>Pesan</td><td style='padding:5px 0;'>" . esc($data['message'] ?? '-') . "</td></tr>
               </table>
             </div>
             <p style='color:#c8d8f0;font-size:13px;'>
               Hubungi pelamar langsung untuk negosiasi lebih lanjut.
             </p>"
        );

        // Kirim acknowledgment ke pelamar
        $ackHtml = $this->wrap(
            'Permintaan Akses Admin Diterima',
            "Halo <strong>" . esc($data['full_name']) . "</strong>,",
            "<p style='color:#c8d8f0;line-height:1.8;'>
                Permintaan akses admin platform GravPort Anda telah kami terima.
                Tim kami akan menghubungi Anda ke <strong>" . esc($data['email']) . "</strong>
                untuk negosiasi lebih lanjut.
             </p>
             <p style='color:#c8d8f0;font-size:13px;'>
                Jika ada pertanyaan, hubungi <a href='mailto:{$this->adminEmail}' style='color:#ffbf74;'>{$this->adminEmail}</a>.
             </p>"
        );

        $this->send($data['email'], 'GravPort - Permintaan Admin Anda Diterima', $ackHtml);
        return $this->send($this->adminEmail, '[GravPort] Permintaan Akses Admin Baru dari ' . $data['full_name'], $html);
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────

    private function notifyAdmin(string $subject, string $body): void
    {
        $html = $this->wrap('Notifikasi Admin', 'Notifikasi sistem GravPort:', "<p style='color:#c8d8f0;'>{$body}</p>");
        $this->send($this->adminEmail, '[GravPort Admin] ' . $subject, $html);
    }

    private function tierFeatureList(string $tier): string
    {
        $features = match($tier) {
            'lite' => [
                '628K+ titik data gravitasi Level 1 (FAA & CBA)',
                'WebMap interaktif Jawa-Bali',
                'Unduhan CSV hingga <strong>2 GB/minggu</strong>',
                'Metadata ISO 19115 tiap dataset',
                '1 akun pengguna',
            ],
            'pro'  => [
                'Semua fitur Solo, plus:',
                'Level 2 - GeoTIFF raster tanpa batas',
                'Unduhan <strong>unlimited</strong>',
                'Akses REST API',
                '1 akun pengguna',
            ],
            'Enterprise' => [
                'Semua fitur Pro, plus:',
                'Hingga <strong>' . '10' . ' akun</strong> dalam satu langganan',
                'Manajemen anggota tim',
                'Priority support',
            ],
            default => ['Akses data gravitasi GravPort'],
        };

        $items = implode('', array_map(
            fn ($f) => "<li style='padding:5px 0;color:#c8d8f0;'>{$f}</li>",
            $features
        ));

        return "<ul style='list-style:none;padding:0;margin:16px 0;'>{$items}</ul>";
    }

    private function wrap(string $title, string $greeting, string $body): string
    {
        return "
        <div style='font-family:sans-serif;max-width:580px;margin:auto;padding:36px;background:#0b1b34;color:#e8eef8;border-radius:16px;'>
          <div style='margin-bottom:24px;'>
            <span style='font-size:11px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:#ffbf74;'>
              GravPort Geoportal
            </span>
          </div>
          <h2 style='color:#fff;margin:0 0 18px;font-size:22px;line-height:1.3;'>{$title}</h2>
          <p style='color:#c8d8f0;margin:0 0 8px;'>{$greeting}</p>
          {$body}
          <hr style='border:none;border-top:1px solid rgba(255,255,255,.08);margin:28px 0;'>
          <p style='color:#4a6080;font-size:12px;margin:0;'>
            Email ini dikirim otomatis oleh sistem GravPort. Jangan balas email ini.<br>
            &copy; " . date('Y') . " GravPort - Geoportal Gravitasi Jawa-Bali
          </p>
        </div>";
    }

    private function send(string $to, string $subject, string $html): bool
    {
        try {
            $email = \Config\Services::email();
            $email->clear();
            $email->setFrom(
                (string) (env('email.fromEmail', 'noreply@gravport.id')),
                (string) (env('email.fromName', 'GravPort'))
            );
            $email->setTo($to);
            $email->setSubject($subject);
            $email->setMessage($html);
            $email->setMailType('html');
            $result = $email->send(false);
            if (!$result) {
                log_message('warning', '[EmailService] send failed to ' . $to . ': ' . $email->printDebugger(['headers']));
            }
            return $result;
        } catch (\Throwable $e) {
            log_message('error', '[EmailService] ' . $e->getMessage() . ' → to: ' . $to);
            return false;
        }
    }
}


