<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class DownloadTransactionModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function log(array $data): int
    {
        $row = [
            'acc_id'              => $data['user_id'] ?? null,
            'provider_id'         => $data['provider_id'] ?? null,
            'dataset_code'        => (string) ($data['dataset_code'] ?? ''),
            'dataset_type'        => (string) ($data['dataset_type'] ?? 'vector'),
            'filter_params'       => json_encode($data['filter_params'] ?? []),
            'row_count'           => $data['row_count'] ?? null,
            'download_size_bytes' => $data['download_size_bytes'] ?? null,
            'transaction_amount'  => (float) ($data['transaction_amount'] ?? 0),
            'gravport_commission' => (float) ($data['gravport_commission'] ?? 0),
            'provider_revenue'    => (float) ($data['provider_revenue'] ?? 0),
            'status'              => 'completed',
            'user_agent'          => $data['user_agent'] ?? null,
        ];

        $this->db->table('geoportal.download_transactions')->insert($row);
        return (int) $this->db->insertID();
    }

    public function dashboardStats(): array
    {
        $row = $this->db->query('
            SELECT
                COUNT(*)                                                AS total_all_time,
                COUNT(*) FILTER (
                    WHERE date_trunc(\'month\', downloaded_at) = date_trunc(\'month\', now())
                )                                                       AS total_this_month,
                COALESCE(SUM(provider_revenue) FILTER (
                    WHERE date_trunc(\'month\', downloaded_at) = date_trunc(\'month\', now())
                ), 0)                                                   AS provider_revenue_this_month,
                COALESCE(SUM(gravport_commission) FILTER (
                    WHERE date_trunc(\'month\', downloaded_at) = date_trunc(\'month\', now())
                ), 0)                                                   AS platform_revenue_this_month
            FROM geoportal.download_transactions
            WHERE status = \'completed\'
        ')->getRowArray();

        return $row ?: [];
    }

    public function datasetBreakdown(): array
    {
        return $this->db->query('
            SELECT dataset_code, dataset_type, COUNT(*) AS cnt
            FROM geoportal.download_transactions
            WHERE status = \'completed\'
              AND date_trunc(\'month\', downloaded_at) = date_trunc(\'month\', now())
            GROUP BY dataset_code, dataset_type
            ORDER BY cnt DESC
        ')->getResultArray();
    }

    public function recent(int $limit = 100): array
    {
        return $this->db->query('
            SELECT dt.*, p.provider_name
            FROM geoportal.download_transactions dt
            LEFT JOIN geoportal.data_providers p ON p.provider_id = dt.provider_id
            ORDER BY dt.downloaded_at DESC
            LIMIT ?
        ', [$limit])->getResultArray();
    }

    public function recentForUser(int $accId, int $limit = 10): array
    {
        return $this->db->query('
            SELECT dataset_code, dataset_type, downloaded_at, row_count
            FROM geoportal.download_transactions
            WHERE acc_id = ?
              AND status = \'completed\'
            ORDER BY downloaded_at DESC
            LIMIT ?
        ', [$accId, $limit])->getResultArray();
    }

    public function aggregateForPeriod(int $providerId, string $from, string $to): array
    {
        return $this->db->query('
            SELECT
                COUNT(*)                        AS total_downloads,
                COALESCE(SUM(transaction_amount), 0)  AS gross_revenue,
                COALESCE(SUM(provider_revenue), 0)    AS provider_share,
                COALESCE(SUM(gravport_commission), 0) AS platform_share
            FROM geoportal.download_transactions
            WHERE provider_id = ?
              AND status = \'completed\'
              AND downloaded_at >= ?
              AND downloaded_at <  ?
        ', [$providerId, $from, $to])->getRowArray() ?? [];
    }
}
