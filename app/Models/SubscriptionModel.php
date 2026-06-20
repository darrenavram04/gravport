<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class SubscriptionModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function allTiers(): array
    {
        return $this->db->query(
            'SELECT * FROM geoportal.subscriptions_tier ORDER BY tier_id'
        )->getResultArray();
    }

    public function findTier(string $name): ?array
    {
        return $this->db->query(
            'SELECT * FROM geoportal.subscriptions_tier WHERE lower(tier_name) = lower(?)', [$name]
        )->getRowArray() ?: null;
    }

    public function findTierById(int $id): ?array
    {
        return $this->db->query(
            'SELECT * FROM geoportal.subscriptions_tier WHERE tier_id = ?', [$id]
        )->getRowArray() ?: null;
    }

    public function activeFor(int $accId): ?array
    {
        return $this->db->query('
            SELECT s.*, t.tier_name, t.max_downloads, t.api_access, t.price_monthly AS monthly_fee,
                   t.download_quota_byte AS download_limit_bytes_week, t.max_acc AS max_seats,
                   t.level2_access, t.wms_wfs_access
            FROM geoportal.subscriptions s
            JOIN geoportal.subscriptions_tier t ON t.tier_id = s.tier_id
            WHERE s.acc_id = ?
              AND s.payment_status = \'S\'
              AND (s.end_at IS NULL OR s.end_at >= current_date)
            ORDER BY s.created_at DESC
            LIMIT 1
        ', [$accId])->getRowArray() ?: null;
    }

    public function allWithTier(): array
    {
        return $this->db->query('
            SELECT s.*, t.tier_name, t.price_monthly AS monthly_fee,
                   a.acc_email AS email, a.acc_name AS full_name
            FROM geoportal.subscriptions s
            LEFT JOIN geoportal.subscriptions_tier t ON t.tier_id = s.tier_id
            LEFT JOIN geoportal.accounts a ON a.acc_id = s.acc_id
            ORDER BY s.created_at DESC
            LIMIT 200
        ')->getResultArray();
    }

    public function assign(int $accId, int $tierId, string $endDate, int $createdBy, array $extra = []): int
    {
        // Cancel existing active subscriptions for this user
        $this->db->query(
            'UPDATE geoportal.subscriptions
             SET payment_status = \'E\'
             WHERE acc_id = ? AND payment_status = \'S\'',
            [$accId]
        );

        $tier = $this->findTierById($tierId);
        $quotaByte = $tier['download_quota_byte'] ?? 107374182400;

        $row = [
            'acc_id'                  => $accId,
            'tier_id'                 => $tierId,
            'midtrans_order_id'       => $extra['midtrans_order_id'] ?? ('ADMIN-' . $accId . '-' . time()),
            'payment_status'          => 'S',
            'payment_cycle'           => $extra['payment_cycle'] ?? 'M',
            'remaining_download_byte' => $quotaByte ?? 107374182400,
            'total_acc'               => 1,
            'paid_at'                 => date('Y-m-d H:i:s'),
            'end_at'                  => $endDate,
            'start_date'              => date('Y-m-d'),
        ];

        $this->db->table('geoportal.subscriptions')->insert($row);
        $newId = (int) $this->db->insertID();

        // Link account to subscription
        $this->db->query(
            'UPDATE geoportal.accounts SET subs_id = ? WHERE acc_id = ?',
            [$newId, $accId]
        );

        return $newId;
    }

    public function cancel(int $subsId): void
    {
        $this->db->query(
            'UPDATE geoportal.subscriptions SET payment_status = \'E\'
             WHERE subs_id = ?',
            [$subsId]
        );
    }

    public function weeklyDownloadBytes(int $accId): int
    {
        $row = $this->db->query('
            SELECT COALESCE(SUM(download_size_bytes), 0) AS total
            FROM geoportal.download_transactions
            WHERE acc_id = ?
              AND status = \'completed\'
              AND downloaded_at >= date_trunc(\'week\', now())
        ', [$accId])->getRowArray();

        return (int) ($row['total'] ?? 0);
    }

    public function monthlyDownloadCount(int $accId): int
    {
        $row = $this->db->query('
            SELECT COUNT(*) AS cnt
            FROM geoportal.download_transactions
            WHERE acc_id = ?
              AND status = \'completed\'
              AND date_trunc(\'month\', downloaded_at) = date_trunc(\'month\', now())
        ', [$accId])->getRowArray();

        return (int) ($row['cnt'] ?? 0);
    }

    public function tierDistribution(): array
    {
        return $this->db->query('
            SELECT t.tier_name,
                   COUNT(s.subs_id) AS user_count
            FROM geoportal.subscriptions_tier t
            LEFT JOIN geoportal.subscriptions s
                   ON s.tier_id = t.tier_id
                  AND s.payment_status = \'S\'
                  AND (s.end_at IS NULL OR s.end_at >= current_date)
            GROUP BY t.tier_id, t.tier_name
            ORDER BY t.tier_id
        ')->getResultArray();
    }
}
