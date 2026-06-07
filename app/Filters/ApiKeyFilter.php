<?php

namespace App\Filters;

use App\Models\ApiKeyModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

/**
 * ApiKeyFilter
 *
 * Validates Bearer token in Authorization header for /api/v1/* routes.
 * On success, stores $request->apiUserId and $request->apiKeyId for use in controller.
 * On failure, returns 401 JSON immediately.
 */
class ApiKeyFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Extract "Authorization: Bearer gp_xxxxx..." header
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Missing or malformed Authorization header. Expected: Authorization: Bearer gp_...');
        }

        $plainKey = trim(substr($authHeader, 7));

        if (empty($plainKey) || !str_starts_with($plainKey, 'gp_')) {
            return $this->unauthorized('Invalid API key format. GravPort keys start with gp_');
        }

        // Lookup key by hash
        $model = new ApiKeyModel();
        $keyRow = $model->findByPlainKey($plainKey);

        if ($keyRow === null) {
            return $this->unauthorized('Invalid or revoked API key.');
        }

        // Attach user context to request (accessible in controller)
        $request->apiUserId = (int) ($keyRow->acc_id ?? $keyRow->user_id ?? 0);
        $request->apiKeyId  = (int) $keyRow->key_id;
        $request->apiScopes = is_array($keyRow->scopes)
            ? $keyRow->scopes
            : explode(',', trim((string)$keyRow->scopes, '{}'));

        // Async-safe: update last_used_at (best-effort, no error thrown)
        try {
            $model->touchLastUsed($keyRow->key_id);
        } catch (\Throwable $e) {
            // non-critical
        }

        return null; // proceed
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function unauthorized(string $message): ResponseInterface
    {
        $response = service('response');
        $response->setStatusCode(401);
        $response->setContentType('application/json');
        $response->setBody(json_encode([
            'status'  => 'error',
            'code'    => 401,
            'message' => $message,
            'hint'    => 'Generate an API key at ' . site_url('account') . ' (Pro+ subscription required)',
        ], JSON_UNESCAPED_UNICODE));
        return $response;
    }
}
