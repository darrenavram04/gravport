<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthUserRoleModel extends Model
{
    protected $DBGroup = 'auth';
    protected $table   = 'user_roles';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'role_id'];

    public function getPrimaryRoleName(int $userId): string
    {
        $db = $this->db;

        $sql = "
            SELECT r.name
            FROM user_roles ur
            JOIN roles r ON r.id = ur.role_id
            WHERE ur.user_id = ?
            ORDER BY r.name ASC
            LIMIT 1
        ";

        $row = $db->query($sql, [$userId])->getRowArray();

        if (!empty($row['name'])) {
            return (string) $row['name'];
        }

        return $this->bootstrapMissingRole($userId);
    }

    private function bootstrapMissingRole(int $userId): string
    {
        $user = $this->db->table('users')
            ->select('id, email, full_name')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if (!$user) {
            return 'user';
        }

        $guessedRole = $this->guessRoleName($user);
        $role = $this->db->table('roles')
            ->select('id, name')
            ->where('name', $guessedRole)
            ->get()
            ->getRowArray();

        if (!$role) {
            $this->db->table('roles')->insert(['name' => $guessedRole]);
            $role = $this->db->table('roles')
                ->select('id, name')
                ->where('name', $guessedRole)
                ->get()
                ->getRowArray();
        }

        if (!empty($role['id'])) {
            $exists = $this->where('user_id', $userId)
                ->where('role_id', (int) $role['id'])
                ->first();

            if (!$exists) {
                $this->insert([
                    'user_id' => $userId,
                    'role_id' => (int) $role['id'],
                ]);
            }
        }

        return $guessedRole;
    }

    private function guessRoleName(array $user): string
    {
        $email = strtolower(trim((string) ($user['email'] ?? '')));
        $fullName = strtolower(trim((string) ($user['full_name'] ?? '')));

        if (str_contains($email, 'admin') || str_contains($fullName, 'admin')) {
            return 'admin';
        }

        return 'user';
    }
}
