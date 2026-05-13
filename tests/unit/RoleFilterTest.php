<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * White-box tests for AuthFilter and RoleFilter.
 *
 * Tests each decision path in both filters:
 *   AuthFilter  — redirect to /login when not authenticated
 *   RoleFilter  — redirect to /login (not authenticated) or / (wrong role)
 *
 * Routes under test:
 *   GET  /metadata          filter: role:admin,user
 *   GET  /dataset/manage    filter: role:admin   (admin only)
 *   POST /dataset/upload    filter: role:admin
 */
final class RoleFilterTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    // -------------------------------------------------------------------------
    // RoleFilter — unauthenticated access
    // -------------------------------------------------------------------------

    public function testAdminRouteRedirectsGuestToLogin(): void
    {
        $result = $this->get('dataset/manage');

        $result->assertStatus(302);
        $this->assertStringEndsWith('/login', (string) $result->getRedirectUrl());
    }

    public function testMetadataRouteRedirectsGuestToLogin(): void
    {
        $result = $this->get('metadata');

        $result->assertStatus(302);
        $this->assertStringEndsWith('/login', (string) $result->getRedirectUrl());
    }

    // -------------------------------------------------------------------------
    // RoleFilter — wrong role (user tries admin route)
    // -------------------------------------------------------------------------

    public function testAdminRouteRedirectsUserRoleToHomeWithError(): void
    {
        $result = $this
            ->withSession([
                'logged_in'  => true,
                'isLoggedIn' => true,
                'email'      => 'user@gravport.test',
                'role'       => 'user',
            ])
            ->get('dataset/manage');

        $result->assertStatus(302);
        $redirectUrl = (string) $result->getRedirectUrl();

        // Must redirect to root (/), not to /login
        $this->assertStringNotContainsString('/login', $redirectUrl);

        $error = session()->getFlashdata('error');
        $this->assertSame('Access denied.', $error);
    }

    // -------------------------------------------------------------------------
    // RoleFilter — correct role passes through
    // -------------------------------------------------------------------------

    public function testMetadataRouteAllowsUserRole(): void
    {
        $result = $this
            ->withSession([
                'logged_in'  => true,
                'isLoggedIn' => true,
                'email'      => 'user@gravport.test',
                'role'       => 'user',
            ])
            ->get('metadata');

        // Should NOT redirect to /login or /
        $this->assertNotEquals(302, $result->response()->getStatusCode(), 'user role should NOT be blocked from /metadata');
    }

    public function testMetadataRouteAllowsAdminRole(): void
    {
        $result = $this
            ->withSession([
                'logged_in'  => true,
                'isLoggedIn' => true,
                'email'      => 'admin@gravport.test',
                'role'       => 'admin',
            ])
            ->get('metadata');

        $this->assertNotEquals(302, $result->response()->getStatusCode(), 'admin role should NOT be blocked from /metadata');
    }

    public function testAdminRouteAllowsAdminRole(): void
    {
        $result = $this
            ->withSession([
                'logged_in'  => true,
                'isLoggedIn' => true,
                'email'      => 'admin@gravport.test',
                'role'       => 'admin',
            ])
            ->get('dataset/manage');

        // Admin should reach the controller (200 or any non-302-to-login/home response)
        $this->assertNotEquals(302, $result->response()->getStatusCode(), 'admin role should NOT be blocked from /dataset/manage');
    }

    // -------------------------------------------------------------------------
    // Logout clears session
    // -------------------------------------------------------------------------

    public function testLogoutDestroysSessionAndRedirectsToLogin(): void
    {
        $result = $this
            ->withSession([
                'logged_in' => true,
                'role'       => 'user',
            ])
            ->get('logout');

        $result->assertStatus(302);
        $this->assertStringEndsWith('/login', (string) $result->getRedirectUrl());
    }

    // -------------------------------------------------------------------------
    // LoginForm redirects already-authenticated users
    // -------------------------------------------------------------------------

    public function testLoginFormRedirectsAuthenticatedUserTowardsCatalog(): void
    {
        $result = $this
            ->withSession([
                'logged_in' => true,
                'role'       => 'user',
            ])
            ->get('login');

        $result->assertStatus(302);
        $this->assertStringEndsWith('/catalog', (string) $result->getRedirectUrl());
    }

    public function testLoginFormRedirectsAuthenticatedAdminTowardsDatasetManage(): void
    {
        $result = $this
            ->withSession([
                'logged_in' => true,
                'role'       => 'admin',
            ])
            ->get('login');

        $result->assertStatus(302);
        $this->assertStringEndsWith('/dataset/manage', (string) $result->getRedirectUrl());
    }

    // -------------------------------------------------------------------------
    // SignupForm redirects already-authenticated users
    // -------------------------------------------------------------------------

    public function testSignupFormRedirectsAuthenticatedUser(): void
    {
        $result = $this
            ->withSession([
                'logged_in' => true,
                'role'       => 'user',
            ])
            ->get('signup');

        $result->assertStatus(302);
        $this->assertStringEndsWith('/catalog', (string) $result->getRedirectUrl());
    }
}
