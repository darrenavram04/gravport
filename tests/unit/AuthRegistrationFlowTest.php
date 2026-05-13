<?php

declare(strict_types=1);

use App\Libraries\AuthApiClient;
use App\Models\AuthUserRoleModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AuthRegistrationFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** @var int[] */
    private array $createdUserIds = [];

    /** @var string[] */
    private array $createdEmails = [];

    protected function setUp(): void
    {
        parent::setUp();

        AuthApiClient::setMockHandler(function (string $method, string $path, array $payload): array {
            return $this->mockAuthApi($method, $path, $payload);
        });
    }

    protected function tearDown(): void
    {
        AuthApiClient::setMockHandler(null);

        $db = Database::connect('auth');

        foreach (array_unique($this->createdUserIds) as $userId) {
            $db->table('user_roles')->where('user_id', $userId)->delete();
            $db->table('users')->where('id', $userId)->delete();
        }

        foreach (array_unique($this->createdEmails) as $email) {
            $user = $db->table('users')->select('id')->where('email', $email)->get()->getRowArray();
            if (! empty($user['id'])) {
                $db->table('user_roles')->where('user_id', (int) $user['id'])->delete();
                $db->table('users')->where('id', (int) $user['id'])->delete();
            }
        }

        $this->createdUserIds = [];
        $this->createdEmails = [];

        parent::tearDown();
    }

    public function testSignupFormLoadsForGuests(): void
    {
        $result = $this->get('signup');

        $result->assertStatus(200);
        $result->assertSee('Daftar akun GravPort');
    }

    public function testSignupCreatesUserWithDefaultUserRole(): void
    {
        $email = 'signup_' . bin2hex(random_bytes(6)) . '@gravport.test';
        $this->createdEmails[] = $email;

        $result = $this->post('signup', [
            'full_name' => 'User Registrasi',
            'email' => $email,
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $result->assertStatus(302);
        $this->assertStringEndsWith('/login', (string) $result->getRedirectUrl());

        $db = Database::connect('auth');
        $user = $db->table('users')
            ->select('id, email, full_name, is_active')
            ->where('email', $email)
            ->get()
            ->getRowArray();

        $this->assertIsArray($user);
        $this->assertSame($email, $user['email'] ?? null);
        $this->assertSame('User Registrasi', $user['full_name'] ?? null);
        $this->assertTrue((bool) ($user['is_active'] ?? false));

        $userId = (int) ($user['id'] ?? 0);
        $this->assertGreaterThan(0, $userId);
        $this->createdUserIds[] = $userId;

        $role = $db->query(
            'SELECT r.name
             FROM user_roles ur
             JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = ?
             ORDER BY r.name ASC
             LIMIT 1',
            [$userId]
        )->getRowArray();

        $this->assertSame('user', $role['name'] ?? null);
    }

    public function testSignupRejectsDuplicateEmail(): void
    {
        $result = $this->post('signup', [
            'full_name' => 'Existing User',
            'email' => 'client@gravport.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $result->assertStatus(302);

        $this->assertSame('Email sudah terdaftar.', session()->getFlashdata('error'));
    }

    public function testLoginUsesAuthApiAndCreatesSession(): void
    {
        $email = 'login_' . bin2hex(random_bytes(6)) . '@gravport.test';
        $this->createdEmails[] = $email;

        $db = Database::connect('auth');
        $db->table('users')->insert([
            'email' => $email,
            'password_hash' => password_hash('StrongPass123!', PASSWORD_DEFAULT),
            'full_name' => 'API Login User',
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $userId = (int) $db->insertID();
        $this->createdUserIds[] = $userId;

        $db->table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $this->ensureUserRoleId($db),
        ]);

        $result = $this->post('login', [
            'email' => $email,
            'password' => 'StrongPass123!',
        ]);

        $result->assertStatus(302);
        $this->assertStringEndsWith('/catalog', (string) $result->getRedirectUrl());
        $this->assertTrue((bool) session()->get('logged_in'));
        $this->assertSame($email, session()->get('email'));
        $this->assertSame('user', session()->get('role'));
    }

    public function testMissingRoleBootstrapFallsBackToUserInsteadOfAdmin(): void
    {
        $email = 'admin-lookalike-' . bin2hex(random_bytes(4)) . '@gravport.test';
        $this->createdEmails[] = $email;

        $db = Database::connect('auth');
        $db->table('users')->insert([
            'email' => $email,
            'password_hash' => password_hash('StrongPass123!', PASSWORD_DEFAULT),
            'full_name' => 'Admin Looking Name',
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $userId = (int) $db->insertID();
        $this->assertGreaterThan(0, $userId);
        $this->createdUserIds[] = $userId;

        $roleModel = new AuthUserRoleModel();
        $resolvedRole = $roleModel->getPrimaryRoleName($userId);

        $this->assertSame('user', $resolvedRole);

        $role = $db->query(
            'SELECT r.name
             FROM user_roles ur
             JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = ?
             ORDER BY r.name ASC
             LIMIT 1',
            [$userId]
        )->getRowArray();

        $this->assertSame('user', $role['name'] ?? null);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function mockAuthApi(string $method, string $path, array $payload): array
    {
        $db = Database::connect('auth');

        if ($method === 'POST' && $path === '/v1/auth/signup') {
            $email = strtolower(trim((string) ($payload['email'] ?? '')));
            $fullName = preg_replace('/\s+/u', ' ', trim((string) ($payload['full_name'] ?? ''))) ?? '';
            $password = (string) ($payload['password'] ?? '');

            $existing = $db->table('users')->select('id')->where('email', $email)->get()->getRowArray();
            if (! empty($existing['id'])) {
                throw new RuntimeException('Email sudah terdaftar.');
            }

            $db->table('users')->insert([
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'full_name' => $fullName,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $userId = (int) $db->insertID();
            $roleId = $this->ensureUserRoleId($db);

            $db->table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);

            return [
                'data' => [
                    'id' => $userId,
                    'email' => $email,
                    'full_name' => $fullName,
                    'role' => 'user',
                ],
            ];
        }

        if ($method === 'POST' && $path === '/v1/auth/login') {
            $email = strtolower(trim((string) ($payload['email'] ?? '')));
            $password = (string) ($payload['password'] ?? '');
            $user = $db->table('users')->where('email', $email)->get()->getRowArray();

            if (! is_array($user) || empty($user['password_hash']) || ! password_verify($password, (string) $user['password_hash'])) {
                throw new RuntimeException('Invalid credentials.');
            }

            $roleModel = new AuthUserRoleModel();

            return [
                'data' => [
                    'id' => (int) ($user['id'] ?? 0),
                    'email' => (string) ($user['email'] ?? ''),
                    'full_name' => (string) ($user['full_name'] ?? ''),
                    'role' => $roleModel->getPrimaryRoleName((int) ($user['id'] ?? 0)),
                ],
            ];
        }

        throw new RuntimeException('Mock route Auth API belum didefinisikan.');
    }

    private function ensureUserRoleId($db): int
    {
        $role = $db->table('roles')->select('id')->where('name', 'user')->get()->getRowArray();

        if (! empty($role['id'])) {
            return (int) $role['id'];
        }

        $db->table('roles')->insert(['name' => 'user']);

        $role = $db->table('roles')->select('id')->where('name', 'user')->get()->getRowArray();

        if (empty($role['id'])) {
            throw new RuntimeException('Role user gagal dibuat untuk test.');
        }

        return (int) $role['id'];
    }
}
