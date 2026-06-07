<?php

namespace App\Controllers;

use App\Controllers\RegistrationController;
use App\Libraries\DatasetImportService;
use App\Libraries\MarketplaceService;
use App\Models\DataProviderModel;
use App\Models\DownloadTransactionModel;
use App\Models\PendingRegistrationModel;
use App\Models\RevenueShareModel;
use App\Models\SubscriptionModel;

class AdminHub extends BaseController
{
    private MarketplaceService       $marketplace;
    private DataProviderModel        $providerModel;
    private SubscriptionModel        $subscriptionModel;
    private DownloadTransactionModel $txModel;
    private RevenueShareModel        $revenueModel;
    private PendingRegistrationModel $pendingModel;
    private RegistrationController   $regCtrl;

    public function __construct()
    {
        $this->marketplace       = new MarketplaceService();
        $this->providerModel     = new DataProviderModel();
        $this->subscriptionModel = new SubscriptionModel();
        $this->txModel           = new DownloadTransactionModel();
        $this->revenueModel      = new RevenueShareModel();
        $this->pendingModel      = new PendingRegistrationModel();
        $this->regCtrl           = new RegistrationController();
    }

    // ── Metadata Submissions ─────────────────────────────────────────

    public function metadataSubmissions()
    {
        $db = \Config\Database::connect();
        $rows = $db->query("
            SELECT id, metadata_file_identifier, jenis_data, provinsi, level_data,
                   organisation_name, individual_name, submitted_by_role,
                   submitted_at
            FROM geoportal.dataset_user_submissions
            ORDER BY submitted_at DESC
            LIMIT 200
        ")->getResultArray();

        return view('v_admin_metadata_submissions', ['submissions' => $rows]);
    }

    public function metadataSubmissionDetail(int $id)
    {
        $db = \Config\Database::connect();
        $row = $db->query(
            'SELECT * FROM geoportal.dataset_user_submissions WHERE id = ? LIMIT 1',
            [$id]
        )->getRowArray();

        if (!$row) {
            return redirect()->to(site_url('admin/metadata-submissions'))
                ->with('error', "Submission #{$id} tidak ditemukan.");
        }

        return view('v_admin_metadata_submission_detail', ['submission' => $row]);
    }

    public function metadataApprove(int $id)
    {
        $db     = \Config\Database::connect();
        $userId = (int) (session()->get('user_id') ?? 0);

        $row = $db->query(
            'SELECT id, review_status FROM geoportal.dataset_user_submissions WHERE id = ? LIMIT 1',
            [$id]
        )->getRowArray();

        if (!$row) {
            return redirect()->to(site_url('admin/metadata-submissions'))
                ->with('error', "Submission #{$id} tidak ditemukan.");
        }

        $db->query(
            "UPDATE geoportal.dataset_user_submissions
             SET review_status = 'approved', reviewed_by = ?, reviewed_at = NOW(), reviewer_notes = NULL
             WHERE id = ?",
            [$userId, $id]
        );

        // Cascade: pindahkan semua staging points terkait ke produksi
        $db->query("
            INSERT INTO geoportal.point_grav_anom
                (dataset_id, point_value, point_obs_type, source_file, lat, lon, geom)
            SELECT dataset_id, point_value, point_obs_type, source_file, lat, lon, geom
            FROM geoportal.staging_gravity_points
            WHERE submission_id = ? AND review_status = 'pending'
        ", [$id]);

        $db->query(
            "UPDATE geoportal.staging_gravity_points
             SET review_status = 'approved', reviewed_by = ?, reviewed_at = NOW()
             WHERE submission_id = ? AND review_status = 'pending'",
            [$userId, $id]
        );

        // Cascade: approve staging rasters terkait (status only — raster2pgsql manual)
        $db->query(
            "UPDATE geoportal.staging_gravity_rasters
             SET review_status = 'approved', reviewed_by = ?, reviewed_at = NOW()
             WHERE submission_id = ? AND review_status = 'pending'",
            [$userId, $id]
        );

        return redirect()->to(site_url("admin/metadata-submissions/{$id}"))
            ->with('success', "Metadata #{$id} di-approve. Data titik dipindahkan ke produksi.");
    }

    public function metadataReject(int $id)
    {
        $db     = \Config\Database::connect();
        $userId = (int) (session()->get('user_id') ?? 0);
        $notes  = trim((string) ($this->request->getPost('reviewer_notes') ?? ''));

        $row = $db->query(
            'SELECT id FROM geoportal.dataset_user_submissions WHERE id = ? LIMIT 1',
            [$id]
        )->getRowArray();

        if (!$row) {
            return redirect()->to(site_url('admin/metadata-submissions'))
                ->with('error', "Submission #{$id} tidak ditemukan.");
        }

        $db->query(
            "UPDATE geoportal.dataset_user_submissions
             SET review_status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), reviewer_notes = ?
             WHERE id = ?",
            [$userId, $notes ?: null, $id]
        );

        // Cascade: hapus staging points dan rasters terkait yang masih pending
        $db->query(
            "DELETE FROM geoportal.staging_gravity_points WHERE submission_id = ? AND review_status = 'pending'",
            [$id]
        );
        $db->query(
            "DELETE FROM geoportal.staging_gravity_rasters WHERE submission_id = ? AND review_status = 'pending'",
            [$id]
        );

        return redirect()->to(site_url("admin/metadata-submissions/{$id}"))
            ->with('success', "Metadata #{$id} ditolak. Data staging terkait dihapus.");
    }

    // ── Download file dari submission ─────────────────────────────────

    public function downloadSubmissionFile(int $id, string $type)
    {
        $db  = \Config\Database::connect();
        $row = $db->query(
            'SELECT shapefile_zip_path, tabular_file_path, raster_file_path
             FROM geoportal.dataset_user_submissions WHERE id = ? LIMIT 1',
            [$id]
        )->getRowArray();

        if (!$row) {
            return $this->response->setStatusCode(404)->setBody('Submission tidak ditemukan.');
        }

        $colMap = [
            'shapefile' => 'shapefile_zip_path',
            'tabular'   => 'tabular_file_path',
            'raster'    => 'raster_file_path',
        ];

        if (!isset($colMap[$type])) {
            return $this->response->setStatusCode(400)->setBody('Tipe file tidak valid.');
        }

        $relativePath = $row[$colMap[$type]] ?? '';
        if ($relativePath === '') {
            return $this->response->setStatusCode(404)->setBody('File tidak dilampirkan pada submission ini.');
        }

        $fullPath = ROOTPATH . $relativePath;
        if (!is_file($fullPath)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan di server: ' . esc($relativePath));
        }

        return $this->response->download($fullPath, null)->setFileName(basename($fullPath));
    }

    // ── Upload XML Metadata ───────────────────────────────────────────

    public function uploadMetadataXml()
    {
        $validCodes  = ['faa_l1', 'cba_l1', 'faa_l2', 'cba_l2'];
        $datasetCode = $this->request->getPost('dataset_code');
        if (!in_array($datasetCode, $validCodes, true)) {
            return redirect()->back()->with('xml_error', 'Pilih dataset yang valid.');
        }

        $file = $this->request->getFile('metadata_xml');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('xml_error', 'File XML tidak valid atau tidak diunggah.');
        }

        if (strtolower($file->getClientExtension()) !== 'xml') {
            return redirect()->back()->with('xml_error', 'Hanya file .xml yang diterima.');
        }

        $uploadDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'metadata_xml';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $safeName = 'metadata_' . $datasetCode . '_' . date('Ymd_His') . '.xml';
        $file->move($uploadDir, $safeName);
        $xmlPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

        try {
            $importer = new DatasetImportService();
            $report   = $importer->importMetadataXmlFile($datasetCode, $xmlPath);

            $labels = [
                'faa_l1' => 'Free Air Anomaly Level 1',
                'cba_l1' => 'Complete Bouguer Anomaly Level 1',
                'faa_l2' => 'Free Air Anomaly Level 2',
                'cba_l2' => 'Complete Bouguer Anomaly Level 2',
            ];
            return redirect()->to(site_url('dataset/manage'))
                ->with('xml_success', "Metadata {$labels[$datasetCode]} berhasil diupload. File identifier: {$report['file_identifier']}");
        } catch (\Throwable $e) {
            return redirect()->back()->with('xml_error', 'Gagal memproses XML: ' . $e->getMessage());
        }
    }

    // ── Dashboard stats (JSON — loaded by v_admin_manage) ────────────

    public function stats()
    {
        return $this->response->setJSON($this->marketplace->dashboardStats());
    }

    // ═════════════════════════════════════════════════════════════════
    // DATA PROVIDERS
    // ═════════════════════════════════════════════════════════════════

    public function providers()
    {
        return view('v_admin_providers', [
            'providers' => $this->providerModel->all(),
        ]);
    }

    public function providerStore()
    {
        $data = [
            'provider_name'     => trim((string) $this->request->getPost('provider_name')),
            'provider_type'     => $this->request->getPost('provider_type') ?: 'government',
            'contact_email'     => trim((string) $this->request->getPost('contact_email')),
            'contact_person'    => trim((string) $this->request->getPost('contact_person')),
            'revenue_share_pct' => (float) ($this->request->getPost('revenue_share_pct') ?: 75),
            'bank_name'         => trim((string) $this->request->getPost('bank_name')),
            'bank_account'      => trim((string) $this->request->getPost('bank_account')),
            'notes'             => trim((string) $this->request->getPost('notes')),
        ];

        if ($data['provider_name'] === '') {
            return redirect()->to(site_url('admin/providers'))
                ->with('error', 'Nama provider tidak boleh kosong.');
        }

        $this->providerModel->create($data);

        return redirect()->to(site_url('admin/providers'))
            ->with('success', "Provider \"{$data['provider_name']}\" berhasil ditambahkan.");
    }

    public function providerUpdate(int $id)
    {
        $data = [
            'provider_name'     => trim((string) $this->request->getPost('provider_name')),
            'provider_type'     => $this->request->getPost('provider_type') ?: 'government',
            'contact_email'     => trim((string) $this->request->getPost('contact_email')),
            'contact_person'    => trim((string) $this->request->getPost('contact_person')),
            'revenue_share_pct' => (float) ($this->request->getPost('revenue_share_pct') ?: 75),
            'bank_name'         => trim((string) $this->request->getPost('bank_name')),
            'bank_account'      => trim((string) $this->request->getPost('bank_account')),
            'notes'             => trim((string) $this->request->getPost('notes')),
        ];

        $this->providerModel->update($id, $data);

        return redirect()->to(site_url('admin/providers'))
            ->with('success', 'Data provider berhasil diperbarui.');
    }

    public function providerToggle(int $id)
    {
        $this->providerModel->toggleActive($id);
        return redirect()->to(site_url('admin/providers'))
            ->with('success', 'Status provider berhasil diubah.');
    }

    // ═════════════════════════════════════════════════════════════════
    // SUBSCRIPTIONS
    // ═════════════════════════════════════════════════════════════════

    public function subscriptions()
    {
        return view('v_admin_subscriptions', [
            'subscriptions' => $this->subscriptionModel->allWithTier(),
            'tiers'         => $this->subscriptionModel->allTiers(),
            'distribution'  => $this->subscriptionModel->tierDistribution(),
        ]);
    }

    public function subscriptionAssign()
    {
        $userId    = (int) $this->request->getPost('user_id');
        $tierId    = (int) $this->request->getPost('tier_id');
        $endDate   = trim((string) $this->request->getPost('end_date'));
        $method    = trim((string) $this->request->getPost('payment_method'));
        $ref       = trim((string) $this->request->getPost('payment_ref'));
        $notes     = trim((string) $this->request->getPost('notes'));
        $adminId   = (int) (session()->get('user_id') ?? 0);

        if ($userId <= 0 || $tierId <= 0 || $endDate === '') {
            return redirect()->to(site_url('admin/subscriptions'))
                ->with('error', 'User ID, tier, dan tanggal berakhir wajib diisi.');
        }

        $tier = $this->subscriptionModel->findTierById($tierId);
        if ($tier === null) {
            return redirect()->to(site_url('admin/subscriptions'))
                ->with('error', 'Tier tidak ditemukan.');
        }

        $this->subscriptionModel->assign($userId, $tierId, $endDate, $adminId, [
            'payment_method' => $method,
            'payment_ref'    => $ref,
            'notes'          => $notes,
        ]);

        return redirect()->to(site_url('admin/subscriptions'))
            ->with('success', "Subscription {$tier['tier_name']} berhasil ditetapkan untuk user #{$userId}.");
    }

    public function subscriptionCancel(int $id)
    {
        $this->subscriptionModel->cancel($id);
        return redirect()->to(site_url('admin/subscriptions'))
            ->with('success', 'Subscription dibatalkan.');
    }

    // ═════════════════════════════════════════════════════════════════
    // REVENUE SHARE
    // ═════════════════════════════════════════════════════════════════

    public function revenue()
    {
        $year  = (int) ($this->request->getGet('year')  ?: date('Y'));
        $month = (int) ($this->request->getGet('month') ?: date('n'));

        return view('v_admin_revenue', [
            'year'      => $year,
            'month'     => $month,
            'rows'      => $this->revenueModel->forMonth($year, $month),
            'recent'    => $this->txModel->recent(50),
            'stats'     => $this->marketplace->dashboardStats(),
        ]);
    }

    public function revenueGenerate()
    {
        $year  = (int) ($this->request->getPost('year')  ?: date('Y'));
        $month = (int) ($this->request->getPost('month') ?: date('n'));

        $results = $this->marketplace->generateMonthlyRevenue($year, $month);

        $label = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        $total = count($results);

        return redirect()->to(site_url("admin/revenue?year={$year}&month={$month}"))
            ->with('success', "Revenue share {$label} berhasil di-generate untuk {$total} provider.");
    }

    public function revenueMarkPaid(int $id)
    {
        $this->revenueModel->markPaid($id);
        return redirect()->back()->with('success', 'Status pembayaran diperbarui menjadi Lunas.');
    }

    // ═════════════════════════════════════════════════════════════════
    // PENDING REGISTRATIONS
    // ═════════════════════════════════════════════════════════════════

    public function pendingRegistrations()
    {
        return view('v_admin_pending', [
            'individualPending' => $this->pendingModel->allPending(),
            'teamPending'       => $this->pendingModel->allPendingOrgs(),
        ]);
    }

    public function approvePending(string $type, int $id)
    {
        $adminId = (int) (session()->get('user_id') ?? 0);

        $ok = $type === 'team'
            ? $this->regCtrl->activateTeam($id, $adminId)
            : $this->regCtrl->activateIndividual($id, $adminId);

        if ($ok) {
            return redirect()->to(site_url('admin/pending'))
                ->with('success', 'Akun berhasil diaktifkan dan email aktivasi telah dikirim.');
        }

        return redirect()->to(site_url('admin/pending'))
            ->with('error', 'Gagal mengaktifkan akun. Cek log untuk detail error.');
    }

    public function rejectPending(string $type, int $id)
    {
        $adminId = (int) (session()->get('user_id') ?? 0);
        $note    = trim((string) ($this->request->getPost('rejection_note') ?? ''));

        $ok = $type === 'team'
            ? $this->regCtrl->rejectTeam($id, $adminId, $note)
            : $this->regCtrl->rejectIndividual($id, $adminId, $note);

        if ($ok) {
            return redirect()->to(site_url('admin/pending'))
                ->with('success', 'Pendaftaran ditolak dan notifikasi telah dikirim ke pemohon.');
        }

        return redirect()->to(site_url('admin/pending'))
            ->with('error', 'Gagal memproses penolakan. Cek log untuk detail error.');
    }

    // ═════════════════════════════════════════════════════════════════
    // ACCOUNT MANAGEMENT
    // ═════════════════════════════════════════════════════════════════

    public function accounts()
    {
        $db = \Config\Database::connect();

        $accounts = $db->query("
            SELECT a.acc_id, a.acc_name, a.acc_email, a.role, a.is_active, a.is_admin, a.created_at,
                   (SELECT COUNT(*) FROM geoportal.download_transactions dt WHERE dt.acc_id = a.acc_id) AS download_count,
                   (SELECT t.tier_name
                    FROM geoportal.subscriptions s
                    JOIN geoportal.subscriptions_tier t ON t.tier_id = s.tier_id
                    WHERE s.acc_id = a.acc_id
                      AND s.payment_status = 'S'
                      AND (s.end_at IS NULL OR s.end_at >= current_date)
                    ORDER BY s.created_at DESC LIMIT 1) AS active_tier,
                   (SELECT s.end_at
                    FROM geoportal.subscriptions s
                    WHERE s.acc_id = a.acc_id
                      AND s.payment_status = 'S'
                      AND (s.end_at IS NULL OR s.end_at >= current_date)
                    ORDER BY s.created_at DESC LIMIT 1) AS sub_end_at
            FROM geoportal.accounts a
            ORDER BY a.acc_id DESC
        ")->getResultArray();

        return view('v_admin_accounts', ['accounts' => $accounts]);
    }

    public function accountCreate()
    {
        $name     = trim((string) $this->request->getPost('acc_name'));
        $email    = strtolower(trim((string) $this->request->getPost('acc_email')));
        $password = (string) $this->request->getPost('acc_password');
        $role     = $this->request->getPost('role') === 'superadmin' ? 'superadmin'
                  : ($this->request->getPost('role') === 'admin' ? 'admin' : 'user');

        if ($name === '' || $email === '' || $password === '') {
            return redirect()->to(site_url('admin/accounts'))
                ->with('error', 'Nama, email, dan password wajib diisi.');
        }

        if (strlen($password) < 8) {
            return redirect()->to(site_url('admin/accounts'))
                ->with('error', 'Password minimal 8 karakter.');
        }

        $db = \Config\Database::connect();

        $exists = $db->query(
            'SELECT acc_id FROM geoportal.accounts WHERE acc_email = ? LIMIT 1',
            [$email]
        )->getRowArray();

        if ($exists) {
            return redirect()->to(site_url('admin/accounts'))
                ->with('error', "Email {$email} sudah terdaftar.");
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $db->table('geoportal.accounts')->insert([
            'acc_name'     => $name,
            'acc_email'    => $email,
            'acc_password' => $hash,
            'role'         => $role,
            'is_admin'     => in_array($role, ['admin', 'superadmin'], true),
            'is_active'    => true,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('admin/accounts'))
            ->with('success', "Akun {$role} untuk {$email} berhasil dibuat.");
    }

    public function accountSetRole(int $id)
    {
        $role = $this->request->getPost('role');
        if (!in_array($role, ['user', 'admin', 'superadmin'], true)) {
            return redirect()->to(site_url('admin/accounts'))->with('error', 'Role tidak valid.');
        }

        $db = \Config\Database::connect();
        $db->query(
            "UPDATE geoportal.accounts
             SET role = ?, is_admin = ?
             WHERE acc_id = ?",
            [$role, in_array($role, ['admin', 'superadmin'], true), $id]
        );

        return redirect()->to(site_url('admin/accounts'))
            ->with('success', "Role akun #{$id} diubah menjadi {$role}.");
    }

    public function accountToggleActive(int $id)
    {
        $db = \Config\Database::connect();
        $db->query(
            'UPDATE geoportal.accounts SET is_active = NOT is_active WHERE acc_id = ?',
            [$id]
        );

        return redirect()->to(site_url('admin/accounts'))
            ->with('success', "Status aktif akun #{$id} berhasil diubah.");
    }
}
