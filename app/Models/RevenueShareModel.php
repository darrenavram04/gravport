<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class RevenueShareModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function upsert(int $providerId, string $periodStart, string $periodEnd, array $agg): void
    {
        $this->db->query('
            INSERT INTO geoportal.revenue_shares
                (provider_id, period_start, period_end,
                 total_downloads, gross_revenue, provider_share, platform_share)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT (provider_id, period_start, period_end) DO UPDATE
            SET total_downloads = EXCLUDED.total_downloads,
                gross_revenue   = EXCLUDED.gross_revenue,
                provider_share  = EXCLUDED.provider_share,
                platform_share  = EXCLUDED.platform_share,
                created_at      = now()
        ', [
            $providerId,
            $periodStart,
            $periodEnd,
            (int)   ($agg['total_downloads'] ?? 0),
            (float) ($agg['gross_revenue']   ?? 0),
            (float) ($agg['provider_share']  ?? 0),
            (float) ($agg['platform_share']  ?? 0),
        ]);
    }

    public function forMonth(int $year, int $month): array
    {
        $periodStart = sprintf('%04d-%02d-01', $year, $month);
        $periodEnd   = date('Y-m-d', strtotime($periodStart . ' +1 month'));

        return $this->db->query('
            SELECT rs.*, p.provider_name, p.contact_email, p.bank_name, p.bank_account
            FROM geoportal.revenue_shares rs
            JOIN geoportal.data_providers p ON p.provider_id = rs.provider_id
            WHERE rs.period_start = ?
              AND rs.period_end   = ?
            ORDER BY rs.provider_share DESC
        ', [$periodStart, $periodEnd])->getResultArray();
    }

    public function all(int $limit = 24): array
    {
        return $this->db->query('
            SELECT rs.*, p.provider_name
            FROM geoportal.revenue_shares rs
            JOIN geoportal.data_providers p ON p.provider_id = rs.provider_id
            ORDER BY rs.period_start DESC, rs.provider_share DESC
            LIMIT ?
        ', [$limit])->getResultArray();
    }

    public function markPaid(int $revenueId): void
    {
        $this->db->query(
            'UPDATE geoportal.revenue_shares
             SET payment_status = \'paid\', paid_at = now()
             WHERE revenue_id = ?',
            [$revenueId]
        );
    }
}
