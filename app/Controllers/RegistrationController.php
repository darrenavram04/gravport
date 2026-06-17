<?php

namespace App\Controllers;

use App\Libraries\AuthApiClient;
use App\Libraries\EmailService;
use App\Models\OrganizationModel;
use App\Models\PendingRegistrationModel;
use App\Models\SubscriptionModel;

/**
 * RegistrationController — alur pendaftaran baru sesuai flowchart bisnis:
 *
 *   User path:
 *     1. Pilih tier (Solo/Pro) → isi data pribadi → POST /register/individual → pending
 *     2. Pilih Team            → isi data org    → POST /register/team        → pending
 *
 *   Admin path:
 *     → isi nama + email       → POST /register/admin-inquiry                → email dikirim
 *
 *   Semua path non-admin berakhir di /pending-payment (halaman menunggu konfirmasi).
 */
class RegistrationController extends BaseController
{
    private const VALID_TIERS    = ['lite', 'solo', 'pro', 'team'];
    private const VALID_CYCLES   = ['monthly', 'annual'];

    private PendingRegistrationModel $pending;
    private OrganizationModel        $orgModel;
    private SubscriptionModel        $subModel;
    private AuthApiClient            $authApi;
    private EmailService             $mailer;

    public function __construct()
    {
        $this->pending  = new PendingRegistrationModel();
        $this->orgModel = new OrganizationModel();
        $this->subModel = new SubscriptionModel();
        $this->authApi  = new AuthApiClient();
        $this->mailer   = new EmailService();
    }

    /** GET /signup — formulir pendaftaran atau upgrade tier */
    public function showForm()
    {
        // Guest sessions may not upgrade (no account yet).
        // Logged-in real users are allowed here so they can upgrade their tier.
        if (auth_is_logged_in() && auth_is_guest()) {
            return redirect()->to(site_url('catalog'));
        }
        return view('v_signup');
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /register/individual (Solo atau Pro)
    // ─────────────────────────────────────────────────────────────────

    public function submitIndividual()
    {
        // Guest-only sessions may not upgrade.
        if (auth_is_logged_in() && auth_is_guest()) {
            return redirect()->to(site_url('catalog'));
        }

        $fullName     = trim((string) $this->request->getPost('full_name'));
        $email        = strtolower(trim((string) $this->request->getPost('email')));
        $password     = (string) $this->request->getPost('password');
        $passwordConf = (string) $this->request->getPost('password_confirmation');
        $tier         = (string) $this->request->getPost('tier_name');
        $cycle        = (string) $this->request->getPost('billing_cycle');

        if (!in_array($tier, ['lite', 'solo', 'pro'], true)) {
            return redirect()->back()->withInput()->with('error', 'Pilih paket Lite atau Pro.');
        }
        if (!in_array($cycle, self::VALID_CYCLES, true)) {
            $cycle = 'monthly';
        }

        $rules = [
            'full_name'             => 'required|min_length[3]|max_length[120]',
            'email'                 => 'required|valid_email|max_length[160]',
            'password'              => 'required|min_length[12]|max_length[72]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/]',
            'password_confirmation' => 'required|matches[password]',
        ];
        $messages = [
            'password'              => ['regex_match' => 'Password harus memuat huruf besar, huruf kecil, angka, dan simbol.'],
            'password_confirmation' => ['matches' => 'Konfirmasi password tidak cocok.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Cek apakah email sudah terdaftar sebagai akun aktif
        $db = \Config\Database::connect();
        $existing = $db->query(
            'SELECT acc_id FROM geoportal.accounts WHERE acc_email = ? LIMIT 1',
            [strtolower($email)]
        )->getRowArray();
        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'Email ini sudah terdaftar. Silakan login atau gunakan email lain.');
        }

        // Cek apakah email sudah punya pending aktif — langsung lanjut ke payment
        $existingPending = $this->pending->findActiveByEmail($email);
        if ($existingPending !== null) {
            return redirect()->to(site_url('payment/pay/individual/' . $existingPending['pending_id']));
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $pendingId = $this->pending->create([
            'full_name'     => $fullName,
            'email'         => $email,
            'password_hash' => $passwordHash,
            'tier_name'     => $tier,
            'billing_cycle' => $cycle,
        ]);

        return redirect()->to(site_url('payment/pay/individual/' . $pendingId));
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /register/team (paket Team/Bisnis)
    // ─────────────────────────────────────────────────────────────────

    public function submitTeam()
    {
        if (auth_is_logged_in() && !auth_is_guest()) {
            return redirect()->to(site_url('catalog'));
        }

        $orgName     = trim((string) $this->request->getPost('org_name'));
        $orgEmail    = strtolower(trim((string) $this->request->getPost('org_email')));
        $contactName = trim((string) $this->request->getPost('contact_name'));
        $seatCount   = max(1, min(100, (int) $this->request->getPost('seat_count')));
        $cycle       = (string) $this->request->getPost('billing_cycle');

        if (!in_array($cycle, self::VALID_CYCLES, true)) {
            $cycle = 'monthly';
        }

        $rules = [
            'org_name'     => 'required|min_length[3]|max_length[200]',
            'org_email'    => 'required|valid_email|max_length[160]',
            'contact_name' => 'required|min_length[3]|max_length[120]',
            'seat_count'   => 'required|is_natural_no_zero|less_than_equal_to[100]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Cek apakah email organisasi sudah terdaftar sebagai akun aktif
        $db = \Config\Database::connect();
        $existingAcc = $db->query(
            'SELECT acc_id FROM geoportal.accounts WHERE acc_email = ? LIMIT 1',
            [strtolower($orgEmail)]
        )->getRowArray();
        if ($existingAcc) {
            return redirect()->back()->withInput()->with('error', 'Email ini sudah terdaftar. Silakan login atau gunakan email lain.');
        }

        $existingOrg = $this->pending->findActiveOrgByEmail($orgEmail);
        if ($existingOrg !== null) {
            return redirect()->to(site_url('payment/pay/team/' . $existingOrg['pending_id']));
        }

        $pendingId = $this->pending->createOrg([
            'org_name'      => $orgName,
            'org_email'     => $orgEmail,
            'contact_name'  => $contactName,
            'seat_count'    => $seatCount,
            'billing_cycle' => $cycle,
        ]);

        return redirect()->to(site_url('payment/pay/team/' . $pendingId));
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /register/admin-inquiry (permintaan akses admin platform)
    // ─────────────────────────────────────────────────────────────────

    public function submitAdminInquiry()
    {
        $fullName = trim((string) $this->request->getPost('full_name'));
        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $message  = trim((string) $this->request->getPost('message'));

        $rules = [
            'full_name' => 'required|min_length[3]|max_length[120]',
            'email'     => 'required|valid_email|max_length[160]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->mailer->sendAdminInquiry([
            'full_name' => $fullName,
            'email'     => $email,
            'message'   => $message,
        ]);

        return redirect()->to(site_url('login'))
            ->with('success', 'Permintaan Anda telah dikirim. Tim kami akan menghubungi ' . $email . ' untuk negosiasi lebih lanjut.');
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /pending-payment
    // ─────────────────────────────────────────────────────────────────

    public function pendingPayment()
    {
        return view('v_pending_payment', [
            'reg_email' => session()->getFlashdata('reg_email') ?? '',
            'reg_tier'  => session()->getFlashdata('reg_tier')  ?? '',
            'reg_type'  => session()->getFlashdata('reg_type')  ?? 'individual',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Admin: aktivasi akun individu (Solo/Pro)
    // Dipanggil dari AdminHub::approvePending()
    // ─────────────────────────────────────────────────────────────────

    public function activateIndividual(int $pendingId, int $reviewedBy): bool
    {
        $record = $this->pending->findById($pendingId);
        if (!$record || $record['status'] !== 'pending_payment') {
            return false;
        }

        try {
            // Buat akun di auth-api (tanpa welcome email)
            $user = $this->authApi->createUser([
                'email'         => $record['email'],
                'full_name'     => $record['full_name'],
                'password_hash' => $record['password_hash'],
            ]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            // Email already exists in auth DB — look up the existing user and reuse their ID
            if (str_contains($msg, 'sudah terdaftar') || str_contains($msg, 'already')) {
                $db = \Config\Database::connect();
                $existing = $db->query(
                    'SELECT acc_id AS id, acc_email AS email, acc_name AS full_name
                     FROM geoportal.accounts WHERE acc_email = ? LIMIT 1',
                    [strtolower(trim($record['email']))]
                )->getRowArray();

                if (!$existing) {
                    log_message('error', '[RegistrationController] activateIndividual: email exists but user not found in accounts');
                    return false;
                }

                $user = [
                    'id'        => (int) $existing['id'],
                    'email'     => (string) $existing['email'],
                    'full_name' => (string) $existing['full_name'],
                ];
            } else {
                log_message('error', '[RegistrationController] activateIndividual auth-api error: ' . $msg);
                return false;
            }
        }

        // Tentukan end_date berdasarkan billing_cycle
        $endDate = $record['billing_cycle'] === 'annual'
            ? date('Y-m-d', strtotime('+1 year'))
            : date('Y-m-d', strtotime('+1 month'));

        $tier = $this->subModel->findTier($record['tier_name']);
        if ($tier) {
            $this->subModel->assign(
                $user['id'],
                (int) $tier['tier_id'],
                $endDate,
                $reviewedBy,
                ['notes' => 'Diaktifkan via admin panel', 'payment_cycle' => $record['billing_cycle'] === 'annual' ? 'A' : 'M']
            );
        } else {
            log_message('error', '[RegistrationController] activateIndividual: tier "' . $record['tier_name'] . '" tidak ditemukan di subscriptions_tier. Akun dibuat tanpa subscription.');
        }

        $this->pending->approve($pendingId, $reviewedBy);

        // Kirim email aktivasi
        $this->mailer->sendActivation([
            'full_name'  => $record['full_name'],
            'email'      => $record['email'],
            'tier_name'  => $record['tier_name'],
        ]);

        return true;
    }

    // ─────────────────────────────────────────────────────────────────
    // Admin: aktivasi tim (Team)
    // ─────────────────────────────────────────────────────────────────

    public function activateTeam(int $pendingId, int $reviewedBy): bool
    {
        $record = $this->pending->findOrgById($pendingId);
        if (!$record || $record['status'] !== 'pending_payment') {
            return false;
        }

        // Generate temp password untuk akun admin organisasi
        $tempPassword = $this->generateTempPassword();
        $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $user = $this->authApi->createUser([
                'email'         => $record['org_email'],
                'full_name'     => $record['contact_name'],
                'password_hash' => $passwordHash,
            ]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'sudah terdaftar') || str_contains($msg, 'already')) {
                $db = \Config\Database::connect();
                $existing = $db->query(
                    'SELECT acc_id AS id, acc_email AS email, acc_name AS full_name
                     FROM geoportal.accounts WHERE acc_email = ? LIMIT 1',
                    [strtolower(trim($record['org_email']))]
                )->getRowArray();

                if (!$existing) {
                    log_message('error', '[RegistrationController] activateTeam: email exists but user not found in accounts');
                    return false;
                }

                $user = [
                    'id'        => (int) $existing['id'],
                    'email'     => (string) $existing['email'],
                    'full_name' => (string) $existing['full_name'],
                ];
            } else {
                log_message('error', '[RegistrationController] activateTeam auth-api error: ' . $msg);
                return false;
            }
        }

        // Buat organisasi
        $orgId = $this->orgModel->create([
            'org_name'   => $record['org_name'],
            'org_email'  => $record['org_email'],
            'seat_count' => (int) $record['seat_count'],
        ]);

        $this->orgModel->addMember($orgId, $user['id'], true);

        // Buat subscription Team untuk admin organisasi
        $endDate = $record['billing_cycle'] === 'annual'
            ? date('Y-m-d', strtotime('+1 year'))
            : date('Y-m-d', strtotime('+1 month'));

        $tier = $this->subModel->findTier('team');
        if ($tier) {
            $this->subModel->assign(
                $user['id'],
                (int) $tier['tier_id'],
                $endDate,
                $reviewedBy,
                ['notes' => 'Org admin — diaktifkan via admin panel']
            );
        }

        $this->pending->approveOrg($pendingId, $reviewedBy);

        // Kirim email aktivasi dengan temp password
        $this->mailer->sendTeamActivation([
            'contact_name' => $record['contact_name'],
            'org_name'     => $record['org_name'],
            'org_email'    => $record['org_email'],
            'temp_password'=> $tempPassword,
        ]);

        return true;
    }

    // ─────────────────────────────────────────────────────────────────
    // Admin: tolak pendaftaran
    // ─────────────────────────────────────────────────────────────────

    public function rejectIndividual(int $pendingId, int $reviewedBy, string $note): bool
    {
        $record = $this->pending->findById($pendingId);
        if (!$record) {
            return false;
        }
        $this->pending->reject($pendingId, $reviewedBy, $note);
        $this->mailer->sendRejection([
            'full_name'      => $record['full_name'],
            'email'          => $record['email'],
            'rejection_note' => $note,
        ]);
        return true;
    }

    public function rejectTeam(int $pendingId, int $reviewedBy, string $note): bool
    {
        $record = $this->pending->findOrgById($pendingId);
        if (!$record) {
            return false;
        }
        $this->pending->rejectOrg($pendingId, $reviewedBy, $note);
        $this->mailer->sendRejection([
            'contact_name'   => $record['contact_name'],
            'org_name'       => $record['org_name'],
            'org_email'      => $record['org_email'],
            'rejection_note' => $note,
        ]);
        return true;
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE
    // ─────────────────────────────────────────────────────────────────

    private function generateTempPassword(): string
    {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$';
        $pass  = '';
        for ($i = 0; $i < 16; $i++) {
            $pass .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $pass;
    }
}
