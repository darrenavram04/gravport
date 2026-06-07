<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class ApiKeyModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function generate(int $accId, string $name, array $scopes = ['read']): array
    {
        $raw    = bin2hex(random_bytes(32));
        $plain  = 'gp_' . $raw;
        $prefix = substr($raw, 0, 8);
        $hash   = hash('sha256', $plain);

        $validScopes = array_values(array_intersect($scopes, ['read', 'download']));
        if (empty($validScopes)) {
            $validScopes = ['read'];
        }
        $pgArray = '{' . implode(',', $validScopes) . '}';

        $this->db->query(
            'INSERT INTO geoportal.api_keys (acc_id, key_name, key_prefix, key_hash, scopes)
             VALUES (?, ?, ?, ?, ?)',
            [$accId, $name, $prefix, $hash, $pgArray]
        );

        $keyId = (int) $this->db->query('SELECT lastval() AS id')->getRow()->id;

        return ['plain_key' => $plain, 'key_id' => $keyId, 'prefix' => $prefix];
    }

    public function findByPlainKey(string $plainKey): ?object
    {
        $hash = hash('sha256', $plainKey);
        $row  = $this->db->query(
            'SELECT * FROM geoportal.api_keys WHERE key_hash = ? AND revoked_at IS NULL',
            [$hash]
        )->getRow();

        return $row ?: null;
    }

    public function allForUser(int $accId): array
    {
        return $this->db->query(
            "SELECT key_id, key_name, key_prefix, scopes, created_at, last_used_at
             FROM geoportal.api_keys
             WHERE acc_id = ? AND revoked_at IS NULL
             ORDER BY created_at DESC",
            [$accId]
        )->getResultArray();
    }

    public function revoke(int $keyId, int $accId): bool
    {
        $this->db->query(
            'UPDATE geoportal.api_keys SET revoked_at = now()
             WHERE key_id = ? AND acc_id = ? AND revoked_at IS NULL',
            [$keyId, $accId]
        );
        return $this->db->affectedRows() > 0;
    }

    public function touchLastUsed(int $keyId): void
    {
        $this->db->query(
            'UPDATE geoportal.api_keys SET last_used_at = now() WHERE key_id = ?',
            [$keyId]
        );
    }

    public function countForUser(int $accId): int
    {
        return (int) $this->db->query(
            'SELECT COUNT(*) AS cnt FROM geoportal.api_keys WHERE acc_id = ? AND revoked_at IS NULL',
            [$accId]
        )->getRow()->cnt;
    }
}
