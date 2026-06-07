<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class DataProviderModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT p.*,
                    COUNT(dt.transaction_id) AS total_downloads,
                    COALESCE(SUM(dt.provider_revenue), 0) AS total_revenue
             FROM geoportal.data_providers p
             LEFT JOIN geoportal.download_transactions dt ON dt.provider_id = p.provider_id
             GROUP BY p.provider_id
             ORDER BY p.provider_name'
        )->getResultArray();
    }

    public function find(int $id): ?array
    {
        return $this->db->query(
            'SELECT * FROM geoportal.data_providers WHERE provider_id = ?', [$id]
        )->getRowArray() ?: null;
    }

    public function findByDatasetCode(string $code): ?array
    {
        return $this->db->query(
            'SELECT * FROM geoportal.data_providers WHERE is_active = true ORDER BY provider_id LIMIT 1'
        )->getRowArray() ?: null;
    }

    public function create(array $data): int
    {
        $allowed = ['provider_name','provider_type','contact_email','contact_person',
                    'revenue_share_pct','bank_account','bank_name','notes'];
        $row = array_intersect_key($data, array_flip($allowed));
        $this->db->table('geoportal.data_providers')->insert($row);
        return (int) $this->db->insertID();
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['provider_name','provider_type','contact_email','contact_person',
                    'revenue_share_pct','bank_account','bank_name','is_active','notes'];
        $row = array_intersect_key($data, array_flip($allowed));
        if ($row !== []) {
            $this->db->table('geoportal.data_providers')->where('provider_id', $id)->update($row);
        }
    }

    public function toggleActive(int $id): void
    {
        $this->db->query(
            'UPDATE geoportal.data_providers SET is_active = NOT is_active WHERE provider_id = ?', [$id]
        );
    }
}
