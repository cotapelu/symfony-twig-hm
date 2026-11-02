<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class UserControllerTest extends DatabaseIntegrationTestCase
{
    // LOGIN Tests (3)
    public function testLogin(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'Passw0rd!'
        ]));

        // Assuming user doesn't exist yet
        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testLoginInvalidJson(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid json');

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Invalid JSON', $responseData['error']);
    }

    public function testLoginMissingCredentials(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Email and password required', $responseData['error']);
    }

    // REGISTER Tests (3)
    public function testRegister(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Nguyễn Văn A',
            'email' => 'nguyena@example.com',
            'password' => 'Passw0rd!'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('user', $responseData);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertEquals('Nguyễn Văn A', $responseData['user']['name']);
    }

    public function testRegisterInvalidJson(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid json');

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Invalid JSON', $responseData['error']);
    }

    public function testRegisterMissingFields(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Test User'
            // Missing email and password
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('All fields required', $responseData['error']);
    }

    // GET PROFILE Tests (2)
    public function testGetProfile(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/user/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid_token'
        ]);

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testGetProfileNoAuthorization(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/user/profile');

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Unauthorized', $responseData['error']);
    }

    // REGISTER DEVICE TOKEN Tests (2)
    public function testRegisterDeviceToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/user/device-token', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer invalid_token'
        ], json_encode([
            'token' => 'device_token_abc123',
            'platform' => 'ios'
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRegisterDeviceTokenMissingToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/user/device-token', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer invalid_token'
        ], json_encode([
            'platform' => 'android'
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRegisterDeviceTokenSuccess(): void
    {
        // First, register a user to obtain a valid token
        $client = static::createClient();
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Device User',
            'email' => 'device@example.com',
            'password' => 'Passw0rd!'
        ]));
        $this->assertResponseStatusCodeSame(201);
        $regData = json_decode($client->getResponse()->getContent(), true);
        $token = $regData['token'];

        // Register device token
        $client->request('POST', '/api/user/device-token', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], json_encode([
            'token' => 'device_token_abc123',
            'platform' => 'ios'
        ]));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
    }

    // LOGOUT Tests (1)
    public function testLogout(): void
    {
        $client = static::createClient();
        $client->request('POST', '/logout');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Logged out successfully', $responseData['message']);
    }

    // EDGE CASE Tests (1)
    public function testLoginWithWrongPassword(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]));

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Invalid credentials', $responseData['error']);
    }
}
