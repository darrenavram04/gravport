<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $isAjax = $request->hasHeader('X-Requested-With')
            || str_contains((string) $request->getHeaderLine('Accept'), 'application/json')
            || str_contains((string) $request->getHeaderLine('Content-Type'), 'application/json');

        if (! auth_is_logged_in()) {
            if ($isAjax) {
                return service('response')
                    ->setStatusCode(401)
                    ->setContentType('application/json')
                    ->setBody(json_encode(['error' => 'Login diperlukan untuk mengakses fitur ini.', 'redirect' => site_url('login')]));
            }
            return redirect()->to(site_url('login'))->with('error', 'Silakan login untuk mengakses halaman ini.');
        }

        $allowed = $arguments ?? [];
        if (empty($allowed)) {
            return;
        }

        $userLevel = auth_role_level(auth_current_role());
        $minRequired = min(array_map('auth_role_level', $allowed));

        if ($userLevel < $minRequired) {
            if ($isAjax) {
                return service('response')
                    ->setStatusCode(403)
                    ->setContentType('application/json')
                    ->setBody(json_encode(['error' => 'Akses ditolak. Upgrade akun Anda untuk menggunakan fitur ini.', 'upgrade_url' => site_url('account/upgrade')]));
            }
            return redirect()->to(site_url('/'))->with('error', 'Akses ditolak.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
