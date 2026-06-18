<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\AuthApiClient;
use CodeIgniter\HTTP\RedirectResponse;

class AuthController extends BaseController
{
    private const LOGIN_WINDOW_SECONDS    = 900;
    private const LOGIN_MAX_ATTEMPTS      = 20;
    private const REGISTER_WINDOW_SECONDS = 3600;
    private const REGISTER_MAX_ATTEMPTS   = 5;
    private const FORGOT_WINDOW_SECONDS   = 3600;
    private const FORGOT_MAX_ATTEMPTS     = 3;

    private AuthApiClient $authApi;

    public function __construct()
    {
        $this->authApi = new AuthApiClient();
    }

    // -------------------------------------------------------------------------
    // Login / Logout / Guest
    // -------------------------------------------------------------------------

    public function loginForm()
    {
        if (auth_is_logged_in()) {
            return $this->redirectAfterLogin(auth_current_role());
        }
        return view('v_login');
    }

    public function loginPost()
    {
        if (auth_is_logged_in()) {
            return $this->redirectAfterLogin(auth_current_role());
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $pass  = (string) $this->request->getPost('password');

        if ($email === '' || $pass === '') {
            return redirect()->back()->withInput()->with('error', 'Email dan password wajib diisi.');
        }

        if (! $this->passesThrottle('login', [$this->request->getIPAddress(), $email], self::LOGIN_MAX_ATTEMPTS, self::LOGIN_WINDOW_SECONDS)) {
            return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.');
        }

        try {
            $user = $this->authApi->login(['email' => $email, 'password' => $pass]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $roleName = $this->resolveRole((string) ($user['email'] ?? $email), (string) ($user['role'] ?? 'user'));

        // If 2FA is enabled, store pending data and redirect to OTP form.
        if ($this->isTwoFactorEnabled()) {
            session()->set('pending_2fa', [
                'user_id'   => (int) $user['id'],
                'email'     => (string) $user['email'],
                'full_name' => (string) ($user['full_name'] ?? ''),
                'role'      => $roleName,
            ]);

            try {
                $this->authApi->requestOtp(['user_id' => (int) $user['id']]);
            } catch (\Throwable $e) {
                session()->remove('pending_2fa');
                return redirect()->back()->withInput()->with('error', 'Gagal mengirim OTP: ' . $e->getMessage());
            }

            return redirect()->to(site_url('verify-2fa'));
        }

        $this->createSession($user, $roleName);
        return $this->redirectAfterLogin($roleName);
    }

    public function loginAsGuest()
    {
        if (auth_is_logged_in() && ! auth_is_guest()) {
            return $this->redirectAfterLogin(auth_current_role());
        }

        session()->regenerate(true);
        session()->set([
            'logged_in'  => true,
            'isLoggedIn' => true,
            'user_id'    => 0,
            'email'      => '',
            'full_name'  => 'Tamu',
            'role'       => 'guest',
        ]);

        return redirect()->to(site_url('catalog'));
    }

    public function logout()
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId > 0) {
            try {
                $this->authApi->logout(['user_id' => $userId]);
            } catch (\Throwable) {
                // Non-fatal: session tetap dihapus meski auth API gagal
            }
        }
        session()->destroy();
        return redirect()->to(site_url('login'))->with('success', 'Berhasil keluar.');
    }

    // -------------------------------------------------------------------------
    // Signup
    // -------------------------------------------------------------------------

    public function signupForm()
    {
        if (auth_is_logged_in() && ! auth_is_guest()) {
            return $this->redirectAfterLogin(auth_current_role());
        }
        return view('v_signup');
    }

    public function signupPost()
    {
        if (auth_is_logged_in() && ! auth_is_guest()) {
            return $this->redirectAfterLogin(auth_current_role());
        }

        $fullName      = preg_replace('/\s+/u', ' ', trim((string) $this->request->getPost('full_name'))) ?? '';
        $email         = strtolower(trim((string) $this->request->getPost('email')));
        $password      = (string) $this->request->getPost('password');
        $requestedRole = in_array($this->request->getPost('requested_role'), ['user', 'admin'], true)
            ? (string) $this->request->getPost('requested_role')
            : 'user';

        if (! $this->passesThrottle('signup', [$this->request->getIPAddress(), $email], self::REGISTER_MAX_ATTEMPTS, self::REGISTER_WINDOW_SECONDS)) {
            return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan pendaftaran. Coba lagi nanti.');
        }

        $rules = [
            'full_name'             => 'required|min_length[3]|max_length[120]',
            'email'                 => 'required|valid_email|max_length[160]',
            'password'              => 'required|min_length[12]|max_length[72]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/]',
            'password_confirmation' => 'required|matches[password]',
        ];

        $messages = [
            'password'              => ['regex_match' => 'Password harus memuat huruf besar, huruf kecil, angka, dan simbol.'],
            'password_confirmation' => ['matches'     => 'Konfirmasi password harus sama dengan password.'],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $this->authApi->signup([
                'full_name'             => $fullName,
                'email'                 => $email,
                'password'              => $password,
                'password_confirmation' => (string) $this->request->getPost('password_confirmation'),
                'requested_role'        => $requestedRole,
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $successMsg = $requestedRole === 'admin'
            ? 'Permintaan akun Admin berhasil dikirim. Cek email Anda — superadmin akan mengaktifkan akses Admin setelah ditinjau.'
            : 'Akun berhasil dibuat. Silakan login.';

        return redirect()->to(site_url('login'))->with('success', $successMsg);
    }

    // -------------------------------------------------------------------------
    // Two-Factor Authentication
    // -------------------------------------------------------------------------

    public function verifyTwoFactorForm()
    {
        if (! session()->has('pending_2fa')) {
            return redirect()->to(site_url('login'));
        }
        return view('v_verify_2fa');
    }

    public function verifyTwoFactorPost()
    {
        $pending = session()->get('pending_2fa');
        if (! is_array($pending)) {
            return redirect()->to(site_url('login'));
        }

        $otpCode = trim((string) $this->request->getPost('otp_code'));
        if ($otpCode === '') {
            return redirect()->back()->with('error', 'Masukkan kode OTP.');
        }

        try {
            $result = $this->authApi->verifyOtp([
                'user_id'  => (int) $pending['user_id'],
                'otp_code' => $otpCode,
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        if (! ($result['verified'] ?? false)) {
            return redirect()->back()->with('error', 'Kode OTP tidak valid atau sudah kedaluwarsa.');
        }

        session()->remove('pending_2fa');
        $this->createSession($pending, (string) $pending['role']);
        return $this->redirectAfterLogin((string) $pending['role']);
    }

    public function resendOtp()
    {
        $pending = session()->get('pending_2fa');
        if (! is_array($pending)) {
            return redirect()->to(site_url('login'));
        }

        try {
            $this->authApi->requestOtp(['user_id' => (int) $pending['user_id']]);
        } catch (\Throwable $e) {
            return redirect()->to(site_url('verify-2fa'))->with('error', 'Gagal mengirim ulang OTP: ' . $e->getMessage());
        }

        return redirect()->to(site_url('verify-2fa'))->with('success', 'OTP baru telah dikirim ke email Anda.');
    }

    // -------------------------------------------------------------------------
    // Forgot / Reset Password
    // -------------------------------------------------------------------------

    public function forgotPasswordForm()
    {
        if (auth_is_logged_in() && ! auth_is_guest()) {
            return $this->redirectAfterLogin(auth_current_role());
        }
        return view('v_forgot_password');
    }

    public function forgotPasswordPost()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Masukkan email yang valid.');
        }

        if (! $this->passesThrottle('forgot', [$this->request->getIPAddress(), $email], self::FORGOT_MAX_ATTEMPTS, self::FORGOT_WINDOW_SECONDS)) {
            return redirect()->back()->withInput()->with('error', 'Terlalu banyak permintaan. Coba lagi nanti.');
        }

        // Always show success message regardless of whether email exists (prevent enumeration).
        try {
            $this->authApi->forgotPassword(['email' => $email]);
        } catch (\Throwable) {
            // Silently ignore — do not leak whether email exists.
        }

        return redirect()->to(site_url('forgot-password'))->with('success', 'Jika email terdaftar, link reset telah dikirim. Periksa kotak masuk Anda.');
    }

    public function resetPasswordForm()
    {
        $token = trim((string) $this->request->getGet('token'));
        if ($token === '') {
            return redirect()->to(site_url('login'))->with('error', 'Token reset tidak valid.');
        }
        return view('v_reset_password', ['token' => $token]);
    }

    public function resetPasswordPost()
    {
        $token    = trim((string) $this->request->getPost('token'));
        $password = (string) $this->request->getPost('password');

        $rules = [
            'token'                 => 'required',
            'password'              => 'required|min_length[12]|max_length[72]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/]',
            'password_confirmation' => 'required|matches[password]',
        ];

        $messages = [
            'password'              => ['regex_match' => 'Password harus memuat huruf besar, huruf kecil, angka, dan simbol.'],
            'password_confirmation' => ['matches'     => 'Konfirmasi password harus sama.'],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $this->authApi->resetPassword(['token' => $token, 'password' => $password]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to(site_url('login'))->with('success', 'Password berhasil diubah. Silakan login.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveRole(string $email, string $dbRole): string
    {
        $superadminEmail = strtolower(trim((string) env('app.superadminEmail', '')));
        if ($superadminEmail !== '' && strtolower($email) === $superadminEmail) {
            return 'superadmin';
        }
        return $dbRole;
    }

    private function isTwoFactorEnabled(): bool
    {
        return filter_var(env('app.twoFactorEnabled', 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    private function createSession(array $user, string $role): void
    {
        session()->regenerate(true);
        session()->set([
            'logged_in'  => true,
            'isLoggedIn' => true,
            'user_id'    => (int) ($user['id'] ?? $user['user_id'] ?? 0),
            'email'      => (string) ($user['email'] ?? ''),
            'full_name'  => (string) ($user['full_name'] ?? ''),
            'role'       => $role,
        ]);
    }

    private function redirectAfterLogin(string $role): RedirectResponse
    {
        return match ($role) {
            'superadmin' => redirect()->to(site_url('dataset/manage')),
            'admin'      => redirect()->to(site_url('catalog')),
            'guest'      => redirect()->to(site_url('catalog')),
            default      => redirect()->to(site_url('catalog')),
        };
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
}
