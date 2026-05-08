<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        // If already logged in, go home
        if (session('isLoggedIn')) {
            return redirect()->to(site_url('/'));
        }

        return view('v_login', [
            'error' => session()->getFlashdata('error'),
        ]);
    }

    public function attemptLogin()
    {
        $email    = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');

        if ($email === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'Email and password are required.');
        }

        $users = new UserModel();
        $user  = $users->findByEmail($email);

        if (!$user || empty($user['is_active'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid credentials or inactive account.');
        }

        if (!password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid credentials.');
        }

        session()->regenerate(true);

        session()->set([
            'isLoggedIn' => true,
            'user_id'    => (int) $user['id'],
            'email'      => $user['email'],
            'username'   => $user['username'],
            'role'       => $user['role'], // admin|user
        ]);

        return redirect()->to(site_url('/'));
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('/login'));
    }
}
