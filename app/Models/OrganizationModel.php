<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class OrganizationModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function create(array $data): int
    {
        $this->db->table('geoportal.organizations')->insert([
            'org_name'   => (string) ($data['org_name']  ?? ''),
            'org_email'  => strtolower(trim((string) ($data['org_email'] ?? ''))),
            'org_type'   => 'subscriber_com',
            'seat_count' => max(1, (int) ($data['seat_count'] ?? 5)),
            'is_active'  => true,
        ]);
        return (int) $this->db->insertID();
    }

    public function findById(int $id): ?array
    {
        return $this->db->query(
            'SELECT * FROM geoportal.organizations WHERE org_id = ? LIMIT 1',
            [$id]
        )->getRowArray() ?: null;
    }

    public function addMember(int $orgId, int $accId, bool $isAdmin = false): void
    {
        $this->db->query(
            'INSERT INTO geoportal.organization_members (organization_id, acc_id, is_admin)
             VALUES (?, ?, ?)
             ON CONFLICT (organization_id, acc_id) DO NOTHING',
            [$orgId, $accId, $isAdmin ? 'TRUE' : 'FALSE']
        );
    }

    public function membersOf(int $orgId): array
    {
        return $this->db->query(
            'SELECT * FROM geoportal.organization_members WHERE organization_id = ? ORDER BY joined_at ASC',
            [$orgId]
        )->getResultArray();
    }

    public function orgForUser(int $accId): ?array
    {
        return $this->db->query(
            'SELECT o.*, om.is_admin
             FROM geoportal.organizations o
             JOIN geoportal.organization_members om ON om.organization_id = o.org_id
             WHERE om.acc_id = ? AND o.is_active = TRUE
             LIMIT 1',
            [$accId]
        )->getRowArray() ?: null;
    }
}
