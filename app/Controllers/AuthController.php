<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AuthUserModel;
use App\Models\AuthUserRoleModel;

class AuthController extends BaseController
{
    public function loginForm()
    {
        return view('v_login');
    }

    public function loginPost()
    {
        $email = trim((string) $this->request->getPost('email'));
        $pass  = (string) $this->request->getPost('password');

        if ($email === '' || $pass === '') {
            return redirect()->back()->withInput()->with('error', 'Email and password are required.');
        }

        $userModel = new AuthUserModel();
        $user = $userModel->findByEmail($email);

        if (!$user || empty($user['is_active'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid credentials.');
        }

        if (!password_verify($pass, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid credentials.');
        }

        $userRoleModel = new AuthUserRoleModel();
        $roleName = $userRoleModel->getPrimaryRoleName((int)$user['id']);

        session()->regenerate(true);

        session()->set([
            'logged_in'   => true,
            'isLoggedIn'  => true,
            'user_id'      => (int) $user['id'],
            'email'        => (string) $user['email'],
            'full_name'    => (string) $user['full_name'] ?? '',
            'role'         => (string) $roleName,  // 'admin' or 'user'
        ]);

        // Redirect by role
        if ($roleName === 'admin') {
            return redirect()->to(site_url('dataset/manage'));
        }

        return redirect()->to(site_url('catalog'));
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('login'))->with('success', 'Logged out.');
    }
}
