<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('auth_is_logged_in')) {
    function auth_is_logged_in(): bool
    {
        return (bool) (session()->get('logged_in') ?? session()->get('isLoggedIn') ?? false);
    }
}

if (! function_exists('auth_is_guest')) {
    function auth_is_guest(): bool
    {
        return auth_is_logged_in() && session()->get('role') === 'guest';
    }
}

if (! function_exists('auth_current_role')) {
    function auth_current_role(): string
    {
        if (! auth_is_logged_in()) {
            return 'guest';
        }

        $session = session();
        $sessionRole = (string) ($session->get('role') ?? '');

        // Guest session — role already set to 'guest' at login time, no DB lookup needed.
        if ($sessionRole === 'guest') {
            return 'guest';
        }

        $email = trim((string) ($session->get('email') ?? ''));

        if ($email === '') {
            return $sessionRole !== '' ? $sessionRole : 'user';
        }

        // Superadmin check — resolved once at login and stored in session.
        // This block only runs if role was not already set to 'superadmin' in session.
        if ($sessionRole === 'superadmin') {
            return 'superadmin';
        }

        try {
            $userModel = new \App\Models\AuthUserModel();
            $user = $userModel->findByEmail($email);

            if ($user && isset($user['id'])) {
                $roleModel = new \App\Models\AuthUserRoleModel();
                $resolvedRole = $roleModel->getPrimaryRoleName((int) $user['id']);

                if ($resolvedRole !== '' && $resolvedRole !== $sessionRole) {
                    $session->set('role', $resolvedRole);
                    $sessionRole = $resolvedRole;
                }
            }
        } catch (\Throwable $e) {
            // Keep session role as a safe fallback if auth tables are unavailable.
        }

        return $sessionRole !== '' ? $sessionRole : 'user';
    }
}

if (! function_exists('auth_role_level')) {
    function auth_role_level(string $role): int
    {
        return match ($role) {
            'guest'      => 0,
            'user'       => 1,
            'admin'      => 2,
            'superadmin' => 3,
            default      => -1,
        };
    }
}
