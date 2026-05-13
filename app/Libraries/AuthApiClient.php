<?php

namespace App\Libraries;

use RuntimeException;

class AuthApiClient
{
    /** @var callable|null */
    private static $mockHandler = null;

    private string $baseUrl;
    private ?string $sharedKey;

    public function __construct(?string $baseUrl = null, ?string $sharedKey = null)
    {
        $resolvedBaseUrl = $baseUrl ?? (string) (env('authApi.baseUrl') ?: 'http://127.0.0.1:4010');

        $this->baseUrl = rtrim($resolvedBaseUrl, '/');
        $this->sharedKey = $sharedKey ?? ($this->normalizeOptionalString(env('authApi.sharedKey')));
    }

    public static function setMockHandler(?callable $handler): void
    {
        self::$mockHandler = $handler;
    }

    /**
     * @param array{email:string,password:string} $payload
     * @return array{id:int,email:string,full_name:string,role:string}
     */
    public function login(array $payload): array
    {
        $response = $this->request('POST', '/v1/auth/login', $payload);

        return $this->extractDataPayload($response, 'Respons login dari Auth API tidak lengkap.');
    }

    /**
     * @param array{full_name:string,email:string,password:string,password_confirmation?:string} $payload
     * @return array{id:int,email:string,full_name:string,role:string}
     */
    public function signup(array $payload): array
    {
        $response = $this->request('POST', '/v1/auth/signup', $payload);

        return $this->extractDataPayload($response, 'Respons signup dari Auth API tidak lengkap.');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload): array
    {
        if (is_callable(self::$mockHandler)) {
            $result = call_user_func(self::$mockHandler, $method, $path, $payload);

            if (! is_array($result)) {
                throw new RuntimeException('Mock Auth API harus mengembalikan array.');
            }

            return $result;
        }

        $url = $this->baseUrl . $path;
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($jsonPayload === false) {
            throw new RuntimeException('Payload Auth API tidak dapat dikodekan menjadi JSON.');
        }

        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException('Client Auth API tidak dapat diinisialisasi.');
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        if ($this->sharedKey !== null) {
            $headers[] = 'X-Auth-Client-Key: ' . $this->sharedKey;
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
        ]);

        $body = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('Auth API belum tersedia. Pastikan service Node.js berjalan. ' . $curlError);
        }

        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Respons Auth API tidak valid.');
        }

        if ($status >= 400) {
            $message = $decoded['error']['message']
                ?? $decoded['message']
                ?? 'Auth API mengembalikan error.';

            throw new RuntimeException((string) $message);
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $response
     * @return array{id:int,email:string,full_name:string,role:string}
     */
    private function extractDataPayload(array $response, string $fallbackMessage): array
    {
        $data = $response['data'] ?? null;

        if (! is_array($data)) {
            throw new RuntimeException($fallbackMessage);
        }

        return [
            'id' => (int) ($data['id'] ?? 0),
            'email' => (string) ($data['email'] ?? ''),
            'full_name' => (string) ($data['full_name'] ?? ''),
            'role' => (string) ($data['role'] ?? 'user'),
        ];
    }

    private function normalizeOptionalString($value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
