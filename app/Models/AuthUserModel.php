<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class AuthUserModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function findByEmail(string $email): ?array
    {
        $row = $this->db->query(
            'SELECT acc_id AS id, acc_email AS email, acc_name AS full_name, role, is_active
             FROM geoportal.accounts
             WHERE acc_email = ?
             LIMIT 1',
            [strtolower(trim($email))]
        )->getRowArray();

        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT acc_id AS id, acc_email AS email, acc_name AS full_name, role, is_active
             FROM geoportal.accounts
             WHERE acc_id = ?
             LIMIT 1',
            [$id]
        )->getRowArray();

        return $row ?: null;
    }
}
