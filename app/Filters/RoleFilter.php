<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!auth_is_logged_in()) {
            return redirect()->to(site_url('/login'));
        }

        $role = auth_current_role();

        // Example usage: ['admin'] or ['admin','user']
        $allowed = $arguments ?? [];
        if (!empty($allowed) && !in_array($role, $allowed, true)) {
            return redirect()->to(site_url('/'))->with('error', 'Access denied.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
