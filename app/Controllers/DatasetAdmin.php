<?php

namespace App\Controllers;

use App\Libraries\DatasetImportService;
use Config\Database;

class DatasetAdmin extends BaseController
{
    private DatasetImportService $importer;

    public function __construct()
    {
        $this->importer = new DatasetImportService();
    }

    // ── Admin: halaman kelola dataset ────────────────────────────────
    public function index()
    {
        return view('v_admin_manage', [
            'packageSummary' => $this->importer->summarizeLatestPackage(),
            'importReport'   => session()->getFlashdata('importReport'),
        ]);
    }

    // ── Admin: import paket dataset ke PostgreSQL ────────────────────
    public function upload()
    {
        try {
            $package = trim((string) $this->request->getPost('package'));
            $report  = $this->importer->importPackage($package !== '' ? $package : null);

            return redirect()->to(site_url('dataset/manage'))
                ->with('success', "Import paket {$report['package']} berhasil.")
                ->with('importReport', $report);
        } catch (\Throwable $e) {
            return redirect()->to(site_url('dataset/manage'))
                ->with('error', $e->getMessage());
        }
    }

    // ── Superadmin: hapus data staging ───────────────────────────────
    public function delete(string $source, int $id)
    {
        $db = Database::connect();

        if ($source === 'point') {
            $db->query('DELETE FROM geoportal.staging_gravity_points WHERE staged_point_id = ?', [$id]);
        } elseif ($source === 'raster') {
            $db->query('DELETE FROM geoportal.staging_gravity_rasters WHERE staged_raster_id = ?', [$id]);
        }

        return redirect()->to(site_url('dataset/staging'))
            ->with('success', 'Data staging dihapus.');
    }

    // ── Superadmin: daftar semua data staging ────────────────────────
    public function stagingIndex()
    {
        $db = Database::connect();

        // Group by (submission_id, source_file) → satu baris per file CSV
        $points = $db->query("
            SELECT
                sgp.submission_id,
                COALESCE(sgp.source_file, '-') AS source_file,
                COALESCE(MAX(d.dataset_anom_type), 'FAA') AS point_anom_type,
                MAX(sgp.point_obs_type) AS point_obs_type,
                COUNT(*) AS point_count,
                CASE
                    WHEN COUNT(*) FILTER (WHERE sgp.review_status = 'pending')  > 0 THEN 'pending'
                    WHEN COUNT(*) FILTER (WHERE sgp.review_status = 'approved') > 0 THEN 'approved'
                    ELSE 'rejected'
                END AS review_status,
                MAX(sgp.staged_at)  AS submitted_at,
                MAX(a.acc_name)     AS submitted_by,
                COALESCE(MAX(d.dataset_level), 1) AS data_level
            FROM geoportal.staging_gravity_points sgp
            LEFT JOIN geoportal.datasets_grav_anom d ON d.dataset_id = sgp.dataset_id
            LEFT JOIN geoportal.accounts a ON a.acc_id = sgp.acc_id
            GROUP BY sgp.submission_id, sgp.source_file
            ORDER BY MAX(sgp.staged_at) DESC
            LIMIT 100
        ")->getResultArray();

        $rasters = $db->query("
            SELECT
                sgr.staged_raster_id AS id,
                sgr.submission_id,
                COALESCE(sgr.source_file, '-') AS source_file,
                COALESCE(d.dataset_anom_type, 'FAA') AS raster_anom_type,
                COALESCE(d.dataset_level, 2) AS data_level,
                sgr.review_status,
                sgr.review_notes AS rejection_reason,
                sgr.staged_at   AS submitted_at,
                a.acc_name      AS submitted_by
            FROM geoportal.staging_gravity_rasters sgr
            LEFT JOIN geoportal.datasets_grav_anom d ON d.dataset_id = sgr.dataset_id
            LEFT JOIN geoportal.accounts a ON a.acc_id = sgr.acc_id
            ORDER BY sgr.staged_at DESC
            LIMIT 100
        ")->getResultArray();

        $stats = [
            'points_pending'   => count(array_filter($points,  fn($r) => $r['review_status'] === 'pending')),
            'points_approved'  => count(array_filter($points,  fn($r) => $r['review_status'] === 'approved')),
            'points_rejected'  => count(array_filter($points,  fn($r) => $r['review_status'] === 'rejected')),
            'rasters_pending'  => count(array_filter($rasters, fn($r) => $r['review_status'] === 'pending')),
            'rasters_approved' => count(array_filter($rasters, fn($r) => $r['review_status'] === 'approved')),
            'rasters_rejected' => count(array_filter($rasters, fn($r) => $r['review_status'] === 'rejected')),
        ];

        return view('v_admin_staging', compact('points', 'rasters', 'stats'));
    }

    // ── Superadmin: approve data staging ────────────────────────────
    public function stagingApprove(string $source, int $id)
    {
        $db     = Database::connect();
        $userId = (int) (session()->get('user_id') ?? 0);

        if ($source === 'point') {
            $row = $db->query(
                'SELECT * FROM geoportal.staging_gravity_points WHERE staged_point_id = ? LIMIT 1',
                [$id]
            )->getRowArray();

            if (!$row) {
                return redirect()->to(site_url('dataset/staging'))->with('error', 'Data tidak ditemukan.');
            }

            // Insert into production — lat/lon/geom populated only if staging has coordinates
            $db->query("
                INSERT INTO geoportal.point_grav_anom
                    (dataset_id, point_value, point_obs_type, source_file, lat, lon, geom)
                SELECT
                    dataset_id,
                    point_value,
                    point_obs_type,
                    source_file,
                    lat,
                    lon,
                    CASE
                        WHEN lat IS NOT NULL AND lon IS NOT NULL
                        THEN ST_SetSRID(ST_MakePoint(lon, lat), 4326)
                        ELSE NULL
                    END
                FROM geoportal.staging_gravity_points
                WHERE staged_point_id = ?
            ", [$id]);

            $db->query(
                "UPDATE geoportal.staging_gravity_points
                 SET review_status = 'approved', reviewed_by = ?, reviewed_at = NOW()
                 WHERE staged_point_id = ?",
                [$userId, $id]
            );

            return redirect()->to(site_url('dataset/staging'))
                ->with('success', "Data titik #{$id} berhasil di-approve dan dipindahkan ke produksi.");
        }

        // Raster: hanya ubah status (tile harus diload manual)
        $db->query(
            "UPDATE geoportal.staging_gravity_rasters
             SET review_status = 'approved', reviewed_by = ?, reviewed_at = NOW()
             WHERE staged_raster_id = ?",
            [$userId, $id]
        );

        return redirect()->to(site_url('dataset/staging'))
            ->with('success', "Data raster #{$id} di-approve. Tile raster perlu diload manual dengan raster2pgsql.");
    }

    // ── Superadmin: tolak data staging ──────────────────────────────
    public function stagingReject(string $source, int $id)
    {
        $db     = Database::connect();
        $userId = (int) (session()->get('user_id') ?? 0);
        $reason = trim((string) ($this->request->getPost('rejection_reason') ?? ''));

        if ($source === 'point') {
            $db->query(
                "UPDATE geoportal.staging_gravity_points
                 SET review_status = 'rejected', review_notes = ?, reviewed_by = ?, reviewed_at = NOW()
                 WHERE staged_point_id = ?",
                [$reason ?: null, $userId, $id]
            );
        } else {
            $db->query(
                "UPDATE geoportal.staging_gravity_rasters
                 SET review_status = 'rejected', review_notes = ?, reviewed_by = ?, reviewed_at = NOW()
                 WHERE staged_raster_id = ?",
                [$reason ?: null, $userId, $id]
            );
        }

        return redirect()->to(site_url('dataset/staging'))
            ->with('success', "Data #{$id} ditolak.");
    }

    // ── Admin: lihat status submission milik sendiri ─────────────────
    public function mySubmissions()
    {
        $accId = (int) (session()->get('user_id') ?? 0);
        $db    = Database::connect();

        $pointSubmissions = $db->query("
            SELECT
                MIN(sgp.staged_point_id) AS id,
                'point' AS data_type,
                COALESCE(MAX(d.dataset_anom_type), '-') AS anom_type,
                COALESCE(MAX(d.dataset_level), 1) AS data_level,
                sgp.source_file,
                COUNT(*) AS point_count,
                CASE
                    WHEN bool_or(sgp.review_status = 'pending')  THEN 'pending'
                    WHEN bool_or(sgp.review_status = 'rejected') THEN 'rejected'
                    ELSE 'approved'
                END AS review_status,
                MAX(sgp.review_notes) AS catatan_reviewer,
                MIN(sgp.staged_at)    AS submitted_at,
                MAX(sgp.reviewed_at)  AS reviewed_at
            FROM geoportal.staging_gravity_points sgp
            LEFT JOIN geoportal.datasets_grav_anom d ON d.dataset_id = sgp.dataset_id
            WHERE sgp.acc_id = ?
            GROUP BY sgp.source_file
            ORDER BY MIN(sgp.staged_at) DESC
        ", [$accId])->getResultArray();

        $rasterSubmissions = $db->query("
            SELECT
                sgr.staged_raster_id AS id,
                'raster' AS data_type,
                COALESCE(d.dataset_anom_type, '-') AS anom_type,
                COALESCE(d.dataset_level, 2) AS data_level,
                sgr.source_file,
                NULL AS nilai,
                sgr.review_status,
                sgr.review_notes AS catatan_reviewer,
                sgr.staged_at AS submitted_at,
                sgr.reviewed_at
            FROM geoportal.staging_gravity_rasters sgr
            LEFT JOIN geoportal.datasets_grav_anom d ON d.dataset_id = sgr.dataset_id
            WHERE sgr.acc_id = ?
            ORDER BY sgr.staged_at DESC
        ", [$accId])->getResultArray();

        $metaSubmissions = $db->query("
            SELECT
                id,
                'metadata' AS data_type,
                metadata_file_identifier,
                jenis_data,
                provinsi,
                level_data,
                submitted_by_role,
                submitted_at,
                NULL AS review_status,
                NULL AS catatan_reviewer,
                NULL AS reviewed_at
            FROM geoportal.dataset_user_submissions
            WHERE acc_id = ?
            ORDER BY submitted_at DESC
        ", [$accId])->getResultArray();

        return view('v_dataset_my_submissions', [
            'pointSubmissions'  => $pointSubmissions,
            'rasterSubmissions' => $rasterSubmissions,
            'metaSubmissions'   => $metaSubmissions,
            'activePage'        => 'dataset',
        ]);
    }
}
