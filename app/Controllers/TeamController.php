<?php

namespace App\Controllers;

use App\Libraries\EmailService;
use App\Libraries\MarketplaceService;
use App\Models\OrganizationModel;

/**
 * TeamController — Team Seat Management
 *
 * Available to users on the 'team' subscription tier who are org admins.
 * Routes (all under /team, protected by role:user filter):
 *   GET  /team                           → index() — team dashboard
 *   POST /team/invite                    → invite() — invite member by email
 *   POST /team/member/{user_id}/remove   → removeMember() — remove seat
 *   POST /team/member/{user_id}/toggle-admin → toggleAdmin() — flip is_admin flag
 *   GET  /join-team/{token}              → acceptInvite() — accept email invite
 */
class TeamController extends BaseController
{
    private OrganizationModel $orgs;
    private MarketplaceService $marketplace;
    private EmailService $email;

    public function __construct()
    {
        $this->orgs        = new OrganizationModel();
        $this->marketplace = new MarketplaceService();
        $this->email       = new EmailService();
    }

    // ────────────────────────────────────────────────────────────────
    // GET /team — Team dashboard
    // ────────────────────────────────────────────────────────────────
    public function index()
    {
        $userId = (int)(session()->get('user_id') ?? 0);

        // Access guard: must be on team tier
        $quota = $this->marketplace->checkQuota($userId);
        if (!in_array($quota['tier'] ?? '', ['team', 'enterprise', 'government'])) {
            return redirect()->to(site_url('account'))
                ->with('error', 'Fitur Team Management hanya tersedia untuk pengguna tier Team, Enterprise, atau Government.');
        }

        $org = $this->orgs->orgForUser($userId);
        if (!$org) {
            return redirect()->to(site_url('account'))
                ->with('error', 'Akun Anda belum terhubung ke organisasi. Hubungi admin gravport.');
        }

        $members        = $this->orgs->membersOf((int)$org['organization_id']);
        $pendingInvites = $this->getPendingInvites((int)$org['organization_id']);
        $isAdmin        = (bool)($org['is_admin'] ?? false);
        $seatCount      = (int)($org['seat_count'] ?? 1);
        $usedSeats      = count($members);

        return view('v_team', [
            'org'            => $org,
            'members'        => $members,
            'pendingInvites' => $pendingInvites,
            'isAdmin'        => $isAdmin,
            'seatCount'      => $seatCount,
            'usedSeats'      => $usedSeats,
            'userId'         => $userId,
            'activePage'     => 'team',
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // POST /team/invite — Invite member by email
    // ────────────────────────────────────────────────────────────────
    public function invite()
    {
        $userId = (int)(session()->get('user_id') ?? 0);
        $org    = $this->orgs->orgForUser($userId);

        if (!$org || !$org['is_admin']) {
            return redirect()->to(site_url('team'))
                ->with('error', 'Hanya admin organisasi yang dapat mengundang anggota.');
        }

        $orgId     = (int)$org['organization_id'];
        $seatCount = (int)($org['seat_count'] ?? 1);
        $members   = $this->orgs->membersOf($orgId);

        if (count($members) >= $seatCount) {
            return redirect()->to(site_url('team'))
                ->with('error', "Slot penuh ({$seatCount} seat). Hubungi GravPort untuk menambah seat.");
        }

        $invitedEmail = trim($this->request->getPost('email') ?? '');
        if (!filter_var($invitedEmail, FILTER_VALIDATE_EMAIL)) {
            return redirect()->to(site_url('team'))
                ->with('error', 'Alamat email tidak valid.');
        }

        // Check if already a member
        $db = \Config\Database::connect();

        $existingUser = $db->query(
            'SELECT acc_id AS id, acc_name AS full_name FROM geoportal.accounts WHERE acc_email = ? AND is_active = true',
            [$invitedEmail]
        )->getRowArray();

        if ($existingUser) {
            // User already exists → add directly
            $targetUserId = (int)$existingUser['id'];
            $alreadyMember = $db->query(
                'SELECT 1 FROM geoportal.organization_members WHERE organization_id = ? AND user_id = ?',
                [$orgId, $targetUserId]
            )->getRowArray();

            if ($alreadyMember) {
                return redirect()->to(site_url('team'))
                    ->with('error', "{$invitedEmail} sudah menjadi anggota tim ini.");
            }

            $this->orgs->addMember($orgId, $targetUserId, false);
            $this->sendMemberAddedEmail($invitedEmail, $existingUser['full_name'], $org['organization_name']);

            return redirect()->to(site_url('team'))
                ->with('success', "{$invitedEmail} berhasil ditambahkan ke tim.");
        }

        // User not found → create invitation
        $token = bin2hex(random_bytes(32));

        // Check if already invited
        $existingInvite = $db->query(
            'SELECT 1 FROM geoportal.team_invitations WHERE org_id = ? AND invited_email = ? AND accepted_at IS NULL AND cancelled_at IS NULL AND expires_at > now()',
            [$orgId, $invitedEmail]
        )->getRowArray();

        if ($existingInvite) {
            return redirect()->to(site_url('team'))
                ->with('error', "{$invitedEmail} sudah memiliki undangan aktif.");
        }

        $db->query(
            'INSERT INTO geoportal.team_invitations (org_id, invited_email, token, invited_by) VALUES (?,?,?,?)',
            [$orgId, $invitedEmail, $token, $userId]
        );

        $this->sendInviteEmail($invitedEmail, $org['organization_name'], $token);

        return redirect()->to(site_url('team'))
            ->with('success', "Undangan dikirim ke {$invitedEmail}. Mereka harus mendaftar GravPort terlebih dahulu.");
    }

    // ────────────────────────────────────────────────────────────────
    // POST /team/member/{user_id}/remove
    // ────────────────────────────────────────────────────────────────
    public function removeMember(int $targetUserId)
    {
        $userId = (int)(session()->get('user_id') ?? 0);
        $org    = $this->orgs->orgForUser($userId);

        if (!$org || !$org['is_admin']) {
            return redirect()->to(site_url('team'))
                ->with('error', 'Hanya admin organisasi yang dapat menghapus anggota.');
        }
        if ($targetUserId === $userId) {
            return redirect()->to(site_url('team'))
                ->with('error', 'Anda tidak dapat menghapus diri sendiri dari tim.');
        }

        $db = \Config\Database::connect();
        $db->query(
            'DELETE FROM geoportal.organization_members WHERE organization_id = ? AND user_id = ?',
            [(int)$org['organization_id'], $targetUserId]
        );

        return redirect()->to(site_url('team'))
            ->with('success', 'Anggota berhasil dihapus dari tim.');
    }

    // ────────────────────────────────────────────────────────────────
    // POST /team/member/{user_id}/toggle-admin
    // ────────────────────────────────────────────────────────────────
    public function toggleAdmin(int $targetUserId)
    {
        $userId = (int)(session()->get('user_id') ?? 0);
        $org    = $this->orgs->orgForUser($userId);

        if (!$org || !$org['is_admin']) {
            return redirect()->to(site_url('team'))
                ->with('error', 'Hanya admin organisasi yang dapat mengubah hak admin.');
        }
        if ($targetUserId === $userId) {
            return redirect()->to(site_url('team'))
                ->with('error', 'Anda tidak dapat mengubah status admin diri sendiri.');
        }

        $db  = \Config\Database::connect();
        $db->query(
            'UPDATE geoportal.organization_members SET is_admin = NOT is_admin WHERE organization_id = ? AND user_id = ?',
            [(int)$org['organization_id'], $targetUserId]
        );

        return redirect()->to(site_url('team'))
            ->with('success', 'Status admin anggota berhasil diperbarui.');
    }

    // ────────────────────────────────────────────────────────────────
    // GET /join-team/{token} — Accept email invite
    // ────────────────────────────────────────────────────────────────
    public function acceptInvite(string $token)
    {
        $db = \Config\Database::connect();

        $invite = $db->query(
            "SELECT * FROM geoportal.team_invitations
             WHERE token = ? AND accepted_at IS NULL AND cancelled_at IS NULL AND expires_at > now()",
            [$token]
        )->getRowArray();

        if (!$invite) {
            return redirect()->to(site_url('login'))
                ->with('error', 'Link undangan tidak valid atau sudah kadaluarsa.');
        }

        // If user is logged in, try to accept directly
        if (session()->get('logged_in')) {
            $userId    = (int)session()->get('user_id');
            $userEmail = session()->get('email');

            if (strtolower($userEmail) !== strtolower($invite['invited_email'])) {
                return redirect()->to(site_url('team'))
                    ->with('error', "Undangan ini untuk {$invite['invited_email']}. Anda login sebagai {$userEmail}.");
            }

            $orgId = (int)$invite['org_id'];
            $this->orgs->addMember($orgId, $userId, false);

            $db->query(
                'UPDATE geoportal.team_invitations SET accepted_at = now() WHERE token = ?',
                [$token]
            );

            return redirect()->to(site_url('team'))
                ->with('success', 'Anda berhasil bergabung dengan tim!');
        }

        // Not logged in → store token in session, redirect to login
        session()->set('pending_team_token', $token);
        return redirect()->to(site_url('login'))
            ->with('info', "Silakan login dengan akun {$invite['invited_email']} untuk menerima undangan tim.");
    }

    // ────────────────────────────────────────────────────────────────
    // POST /team/invite/{invite_id}/cancel — Cancel pending invite
    // ────────────────────────────────────────────────────────────────
    public function cancelInvite(int $inviteId)
    {
        $userId = (int)(session()->get('user_id') ?? 0);
        $org    = $this->orgs->orgForUser($userId);

        if (!$org || !$org['is_admin']) {
            return redirect()->to(site_url('team'))
                ->with('error', 'Akses ditolak.');
        }

        $db = \Config\Database::connect();
        $db->query(
            'UPDATE geoportal.team_invitations SET cancelled_at = now() WHERE invite_id = ? AND org_id = ?',
            [$inviteId, (int)$org['organization_id']]
        );

        return redirect()->to(site_url('team'))
            ->with('success', 'Undangan dibatalkan.');
    }

    // ════════════════════════════════════════════════════════════════
    // Private helpers
    // ════════════════════════════════════════════════════════════════

    private function getPendingInvites(int $orgId): array
    {
        $db = \Config\Database::connect();
        return $db->query(
            "SELECT * FROM geoportal.team_invitations
             WHERE org_id = ? AND accepted_at IS NULL AND cancelled_at IS NULL AND expires_at > now()
             ORDER BY created_at DESC",
            [$orgId]
        )->getResultArray();
    }

    private function sendInviteEmail(string $toEmail, string $orgName, string $token): void
    {
        $acceptUrl = site_url('join-team/' . $token);
        try {
            $email = service('email');
            $email->setFrom(env('email.fromEmail'), env('email.fromName', 'GravPort'));
            $email->setTo($toEmail);
            $email->setSubject("Undangan bergabung dengan tim {$orgName} di GravPort");
            $email->setMessage("
                <p>Halo,</p>
                <p>Anda diundang bergabung dengan tim <strong>{$orgName}</strong> di gravport.</p>
                <p><a href='{$acceptUrl}' style='background:#a76025;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;'>Terima Undangan</a></p>
                <p>Link berlaku selama 7 hari. Jika Anda belum punya akun GravPort, daftar terlebih dahulu di <a href='" . site_url('signup') . "'>sini</a>.</p>
                <p style='color:#888;font-size:12px;'>Jika bukan Anda yang dimaksud, abaikan email ini.</p>
            ");
            $email->setMailType('html');
            $email->send(false);
        } catch (\Throwable $e) {
            log_message('error', 'TeamController::sendInviteEmail failed: ' . $e->getMessage());
        }
    }

    private function sendMemberAddedEmail(string $toEmail, string $name, string $orgName): void
    {
        try {
            $email = service('email');
            $email->setFrom(env('email.fromEmail'), env('email.fromName', 'GravPort'));
            $email->setTo($toEmail);
            $email->setSubject("Anda telah ditambahkan ke tim {$orgName}");
            $email->setMessage("
                <p>Halo <strong>{$name}</strong>,</p>
                <p>Anda telah ditambahkan sebagai anggota tim <strong>{$orgName}</strong> di gravport.</p>
                <p><a href='" . site_url('team') . "'>Lihat Tim Saya</a></p>
            ");
            $email->setMailType('html');
            $email->send(false);
        } catch (\Throwable $e) {
            log_message('error', 'TeamController::sendMemberAddedEmail failed: ' . $e->getMessage());
        }
    }
}
