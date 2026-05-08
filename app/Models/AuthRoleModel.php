<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthRoleModel extends Model
{
    protected $DBGroup = 'auth';
    protected $table   = 'roles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['name'];
}
