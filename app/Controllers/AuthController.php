<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\AuthApiClient;
use CodeIgniter\HTTP\RedirectResponse;

class AuthController extends BaseController
{
    private const LOGIN_WINDOW_SECONDS = 900;
    private const LOGIN_MAX_ATTEMPTS = 8;
    private const REGISTER_WINDOW_SECONDS = 3600;
    private const REGISTER_MAX_ATTEMPTS = 5;

    private AuthApiClient $authApi;

    public function __construct()
    {
        $this->authApi = new AuthApiClient();
    }

    public function loginForm()
    {
        if (auth_is_logged_in()) {
            return $this->redirectAfterLogin(auth_current_role());
        }

        return view('v_login');
    }

    public function signupForm()
    {
        if (auth_is_logged_in()) {
            return $this->redirectAfterLogin(auth_current_role());
        }

        return view('v_signup');
    }

    public function loginPost()
    {
        if (auth_is_logged_in()) {
            return $this->redirectAfterLogin(auth_current_role());
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $pass  = (string) $this->request->getPost('password');

        if ($email === '' || $pass === '') {
            return redirect()->back()->withInput()->with('error', 'Email and password are required.');
        }

        if (! $this->passesThrottle('login', [$this->request->getIPAddress(), $email], self::LOGIN_MAX_ATTEMPTS, self::LOGIN_WINDOW_SECONDS)) {
            return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.');
        }

        try {
            $user = $this->authApi->login([
                'email' => $email,
                'password' => $pass,
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $roleName = (string) ($user['role'] ?? 'user');

        session()->regenerate(true);

        session()->set([
            'logged_in'   => true,
            'isLoggedIn'  => true,
            'user_id'      => (int) $user['id'],
            'email'        => (string) $user['email'],
            'full_name'    => (string) ($user['full_name'] ?? ''),
            'role'         => (string) $roleName,  // 'admin' or 'user'
        ]);

        // Redirect by role
        if ($roleName === 'admin') {
            return redirect()->to(site_url('dataset/manage'));
        }

        return redirect()->to(site_url('catalog'));
    }

    public function signupPost()
    {
        if (auth_is_logged_in()) {
            return $this->redirectAfterLogin(auth_current_role());
        }

        $fullName = preg_replace('/\s+/u', ' ', trim((string) $this->request->getPost('full_name'))) ?? '';
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        if (! $this->passesThrottle('signup', [$this->request->getIPAddress(), $email], self::REGISTER_MAX_ATTEMPTS, self::REGISTER_WINDOW_SECONDS)) {
            return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan pendaftaran. Coba lagi nanti.');
        }

        $rules = [
            'full_name' => 'required|min_length[3]|max_length[120]',
            'email' => 'required|valid_email|max_length[160]',
            'password' => 'required|min_length[12]|max_length[72]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/]',
            'password_confirmation' => 'required|matches[password]',
        ];

        $messages = [
            'password' => [
                'regex_match' => 'Password harus memuat huruf besar, huruf kecil, angka, dan simbol.',
            ],
            'password_confirmation' => [
                'matches' => 'Konfirmasi password harus sama dengan password.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $this->authApi->signup([
                'full_name' => $fullName,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => (string) $this->request->getPost('password_confirmation'),
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to(site_url('login'))->with('success', 'Akun user berhasil dibuat. Silakan login.');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('login'))->with('success', 'Logged out.');
    }

    private function passesThrottle(string $action, array $identifiers, int $maxAttempts, int $windowSeconds): bool
    {
        $throttler = service('throttler');

        foreach ($identifiers as $identifier) {
            $normalized = strtolower(trim((string) $identifier));
            if ($normalized === '') {
                continue;
            }

            $key = 'auth_' . $action . '_' . hash('sha256', $normalized);
            if (! $throttler->check($key, $maxAttempts, $windowSeconds)) {
                return false;
            }
        }

        return true;
    }

    private function redirectAfterLogin(string $role): RedirectResponse
    {
        if ($role === 'admin') {
            return redirect()->to(site_url('dataset/manage'));
        }

        return redirect()->to(site_url('catalog'));
    }
}
