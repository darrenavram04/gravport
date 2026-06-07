<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class PendingRegistrationModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ── Individual (Solo/Pro) ────────────────────────────────────────

    public function create(array $data): int
    {
        $this->db->table('geoportal.pending_registrations')->insert([
            'full_name'     => (string) ($data['full_name']     ?? ''),
            'email'         => strtolower(trim((string) ($data['email'] ?? ''))),
            'password_hash' => (string) ($data['password_hash'] ?? ''),
            'tier_name'     => (string) ($data['tier_name']     ?? 'solo'),
            'billing_cycle' => (string) ($data['billing_cycle'] ?? 'monthly'),
            'status'        => 'pending_payment',
            'expires_at'    => date('Y-m-d H:i:s', strtotime('+7 days')),
        ]);
        return (int) $this->db->insertID();
    }

    public function findById(int $id): ?array
    {
        return $this->db->query(
            'SELECT * FROM geoportal.pending_registrations WHERE pending_id = ? LIMIT 1',
            [$id]
        )->getRowArray() ?: null;
    }

    public function findActiveByEmail(string $email): ?array
    {
        return $this->db->query(
            "SELECT * FROM geoportal.pending_registrations
             WHERE email = ? AND status = 'pending_payment'
             LIMIT 1",
            [strtolower(trim($email))]
        )->getRowArray() ?: null;
    }

    public function allPending(): array
    {
        return $this->db->query(
            "SELECT * FROM geoportal.pending_registrations
             WHERE status = 'pending_payment'
             ORDER BY created_at DESC"
        )->getResultArray();
    }

    public function approve(int $id, int $reviewedBy): void
    {
        $this->db->query(
            "UPDATE geoportal.pending_registrations
             SET status = 'approved', reviewed_by = ?, updated_at = NOW()
             WHERE pending_id = ?",
            [$reviewedBy, $id]
        );
    }

    public function reject(int $id, int $reviewedBy, string $note = ''): void
    {
        $this->db->query(
            "UPDATE geoportal.pending_registrations
             SET status = 'rejected', reviewed_by = ?, rejection_note = ?, updated_at = NOW()
             WHERE pending_id = ?",
            [$reviewedBy, $note, $id]
        );
    }

    public function setOrderId(int $id, string $orderId, string $type = 'individual'): void
    {
        $table = $type === 'team' ? 'geoportal.pending_organizations' : 'geoportal.pending_registrations';
        $this->db->query(
            "UPDATE {$table} SET midtrans_order_id = ?, updated_at = NOW() WHERE pending_id = ?",
            [$orderId, $id]
        );
    }

    public function expireOld(): int
    {
        $this->db->query(
            "UPDATE geoportal.pending_registrations
             SET status = 'expired', updated_at = NOW()
             WHERE status = 'pending_payment' AND expires_at < NOW()"
        );
        return $this->db->affectedRows();
    }

    // ── Organization (Team) ──────────────────────────────────────────

    public function createOrg(array $data): int
    {
        $this->db->table('geoportal.pending_organizations')->insert([
            'org_name'      => (string) ($data['org_name']      ?? ''),
            'org_email'     => strtolower(trim((string) ($data['org_email'] ?? ''))),
            'contact_name'  => (string) ($data['contact_name']  ?? ''),
            'seat_count'    => max(1, (int) ($data['seat_count'] ?? 5)),
            'billing_cycle' => (string) ($data['billing_cycle'] ?? 'monthly'),
            'status'        => 'pending_payment',
            'expires_at'    => date('Y-m-d H:i:s', strtotime('+7 days')),
        ]);
        return (int) $this->db->insertID();
    }

    public function findOrgById(int $id): ?array
    {
        return $this->db->query(
            'SELECT * FROM geoportal.pending_organizations WHERE pending_id = ? LIMIT 1',
            [$id]
        )->getRowArray() ?: null;
    }

    public function findActiveOrgByEmail(string $email): ?array
    {
        return $this->db->query(
            "SELECT * FROM geoportal.pending_organizations
             WHERE org_email = ? AND status = 'pending_payment'
             LIMIT 1",
            [strtolower(trim($email))]
        )->getRowArray() ?: null;
    }

    public function allPendingOrgs(): array
    {
        return $this->db->query(
            "SELECT * FROM geoportal.pending_organizations
             WHERE status = 'pending_payment'
             ORDER BY created_at DESC"
        )->getResultArray();
    }

    public function approveOrg(int $id, int $reviewedBy): void
    {
        $this->db->query(
            "UPDATE geoportal.pending_organizations
             SET status = 'approved', reviewed_by = ?, updated_at = NOW()
             WHERE pending_id = ?",
            [$reviewedBy, $id]
        );
    }

    public function rejectOrg(int $id, int $reviewedBy, string $note = ''): void
    {
        $this->db->query(
            "UPDATE geoportal.pending_organizations
             SET status = 'rejected', reviewed_by = ?, rejection_note = ?, updated_at = NOW()
             WHERE pending_id = ?",
            [$reviewedBy, $note, $id]
        );
    }
}
