<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthUserModel extends Model
{
    protected $DBGroup = 'auth';
    protected $table   = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'email',
        'password_hash',
        'full_name',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public function findByEmail(string $email): ?array
    {
        $row = $this->where('email', $email)->first();
        return $row ?: null;
    }
}
