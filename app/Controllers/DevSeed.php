<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Config\Database;

class DevSeed extends BaseController
{
    public function seedAuth()
    {
        $db = Database::connect('auth');

        // create two users
        $adminEmail = 'admin@gravport.local';
        $userEmail  = 'user@gravport.local';

        $adminPass = password_hash('Admin123!', PASSWORD_BCRYPT);
        $userPass  = password_hash('User123!', PASSWORD_BCRYPT);

        // upsert users
        $db->query("
            INSERT INTO users (email, password_hash, full_name, is_active)
            VALUES (?, ?, 'Admin', true)
            ON CONFLICT (email) DO UPDATE SET password_hash = EXCLUDED.password_hash
        ", [$adminEmail, $adminPass]);

        $db->query("
            INSERT INTO users (email, password_hash, full_name, is_active)
            VALUES (?, ?, 'User', true)
            ON CONFLICT (email) DO UPDATE SET password_hash = EXCLUDED.password_hash
        ", [$userEmail, $userPass]);

        // roles
        $db->query("INSERT INTO roles (name) VALUES ('admin') ON CONFLICT (name) DO NOTHING");
        $db->query("INSERT INTO roles (name) VALUES ('user') ON CONFLICT (name) DO NOTHING");

        // assign roles
        $adminId = $db->query("SELECT id FROM users WHERE email = ?", [$adminEmail])->getRowArray()['id'];
        $userId  = $db->query("SELECT id FROM users WHERE email = ?", [$userEmail])->getRowArray()['id'];
        $adminRoleId = $db->query("SELECT id FROM roles WHERE name='admin'")->getRowArray()['id'];
        $userRoleId  = $db->query("SELECT id FROM roles WHERE name='user'")->getRowArray()['id'];

        $db->query("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?) ON CONFLICT DO NOTHING", [$adminId, $adminRoleId]);
        $db->query("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?) ON CONFLICT DO NOTHING", [$userId, $userRoleId]);

        return $this->response->setBody("Seeded.\nAdmin: admin@gravport.local / Admin123!\nUser: user@gravport.local / User123!");
    }
}
