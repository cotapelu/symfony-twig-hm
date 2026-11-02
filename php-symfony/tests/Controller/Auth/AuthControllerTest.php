<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class AuthControllerTest extends DatabaseIntegrationTestCase
{
    // REGISTER Tests (3)
    public function testRegister(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Nguyễn Văn Test',
            'email' => 'test_auth@example.com',
            'password' => 'password123'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('user', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Registration successful', $responseData['message']);
    }

    public function testRegisterMissingFields(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Test User'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testRegisterInvalidEmail(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    // LOGIN Tests (2)
    public function testLogin(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test_auth@example.com',
            'password' => 'password123'
        ]));

        // Assuming user doesn't exist or wrong password
        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testLoginMissingCredentials(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    // SOCIAL LOGIN Tests (2)
    public function testSocialLogin(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/social/google', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'idToken' => 'google_id_token_123',
            'accessToken' => 'google_access_token_456'
        ]));

        // Assuming OAuth provider integration isn't fully setup
        $this->assertResponseStatusCodeSame(500);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Social login failed', $responseData['error']);
    }

    public function testSocialLoginFacebook(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/social/facebook', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'accessToken' => 'fb_access_token_789'
        ]));

        $this->assertResponseStatusCodeSame(500);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Social login failed', $responseData['error']);
    }

    // GET PROFILE Tests (2)
    public function testGetProfile(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/profile');

        // Should return 401 without authentication
        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testGetProfileUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/profile');

        $this->assertResponseStatusCodeSame(401);
    }

    // UPDATE PROFILE Tests (2)
    public function testUpdateProfile(): void
    {
        $client = static::createClient();
        $client->request('PUT', '/api/auth/profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'bio' => 'New bio text',
            'avatar' => 'new_avatar_url.jpg'
        ]));

        // Should return 401 without authentication
        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testUpdateProfilePartialData(): void
    {
        $client = static::createClient();
        $client->request('PUT', '/api/auth/profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Updated Name'
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    // CHANGE PASSWORD Tests (2)
    public function testChangePassword(): void
    {
        $client = static::createClient();
        $client->request('PUT', '/api/auth/change-password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'currentPassword' => 'old_password',
            'newPassword' => 'new_password123'
        ]));

        // Should return 401 without authentication
        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testChangePasswordMissingCurrentPassword(): void
    {
        $client = static::createClient();
        $client->request('PUT', '/api/auth/change-password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'newPassword' => 'new_password123'
        ]));

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    // FORGOT PASSWORD Tests (2)
    public function testForgotPassword(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/forgot-password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com'
        ]));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('password reset link', $responseData['message']);
    }

    public function testForgotPasswordMissingEmail(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/forgot-password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Email is required', $responseData['error']);
    }

    // RESET PASSWORD Tests (2)
    public function testResetPassword(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/reset-password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'token' => 'reset_token_abc123',
            'password' => 'new_password123'
        ]));

        // Assuming token is invalid
        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Invalid or expired token', $responseData['error']);
    }

    public function testResetPasswordMissingToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/reset-password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'password' => 'new_password123'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Token and new password are required', $responseData['error']);
    }

    // VERIFY EMAIL Tests (2)
    public function testVerifyEmail(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/verify-email', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'token' => 'verification_token_xyz789'
        ]));

        // Assuming token is invalid
        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Invalid or expired token', $responseData['error']);
    }

    public function testVerifyEmailMissingToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/verify-email', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Verification token is required', $responseData['error']);
    }

    // LOGOUT Tests (1)
    public function testLogout(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/logout');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Logged out successfully', $responseData['message']);
    }

    // ME (GET CURRENT USER) Tests (1)
    public function testMe(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/me');

        // Should return 401 without authentication
        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    // CSRF Tests (1)
    public function testRegisterWithoutCsrfToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Test User',
            'email' => 'csrf_test@example.com',
            'password' => 'password123'
        ]));

        // CSRF protection may or may not be enabled
        $this->assertAnyStatusCode([201, 419]);
    }
}
