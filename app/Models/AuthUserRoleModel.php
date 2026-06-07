<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class AuthUserRoleModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getPrimaryRoleName(int $userId): string
    {
        $row = $this->db->query(
            'SELECT role FROM geoportal.accounts WHERE acc_id = ? LIMIT 1',
            [$userId]
        )->getRowArray();

        if (!empty($row['role'])) {
            return (string) $row['role'];
        }

        return 'user';
    }
}
