<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

/**
 * Auth integration test covering registration, login, and protected profile access.
 * Uses real HTTP endpoints against the test kernel.
 */
#[AllowMockObjectsWithoutExpectations]
class AuthIntegrationTest extends DatabaseIntegrationTestCase
{
    public function testRegisterAndLoginFlow(): void
    {
        $client = static::$client;
        $email = 'test.' . time() . '@example.com';
        $password = 'StrongPass123!'; // meets complexity

        // Register
        $client->request('POST', '/api/register', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
            'name' => 'Test User',
            'phoneNumber' => '0123456789',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $registerResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $registerResponse);
        $this->assertEquals($email, $registerResponse['email']);
        $this->assertArrayHasKey('token', $registerResponse);
        $token = $registerResponse['token'];

        // Login
        $client->request('POST', '/api/login', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));

        $this->assertResponseStatusCodeSame(200);
        $loginResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('user', $loginResponse);
        $this->assertArrayHasKey('token', $loginResponse);
        $this->assertEquals($email, $loginResponse['user']['email']);
        $token = $loginResponse['token'];

        // Access protected profile endpoint
        $client->request('GET', '/api/user/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $profileResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $profileResponse);
        $this->assertEquals($email, $profileResponse['email']);
    }

    public function testLoginFailsWithInvalidCredentials(): void
    {
        $client = static::$client;

        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ];

        $client->request('POST', '/api/login', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode($loginData));

        $this->assertResponseStatusCodeSame(401);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }
}
