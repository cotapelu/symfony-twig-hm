<?php

declare(strict_types=1);

namespace App\Tests\Controller;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class UserControllerTest extends DatabaseIntegrationTestCase
{
    private function registerUser(array $data): string
    {
        $client = static::getClient();
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($data));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseData);

        return $responseData['token'];
    }

    public function testRegister(): void
    {
        $client = static::getClient();
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'testuser@example.com',
            'name' => 'Test User',
            'password' => 'SecurePassword123!',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('token', $responseData);
    }

    public function testLogin(): void
    {
        // Create a user first
        $this->registerUser([
            'email' => 'testuser@example.com',
            'name' => 'Test User',
            'password' => 'SecurePassword123!',
        ]);

        $client = static::getClient();
        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'testuser@example.com',
            'password' => 'SecurePassword123!'
        ]));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseData);
    }

    public function testGetProfile(): void
    {
        $token = $this->registerUser([
            'email' => 'profiletest@example.com',
            'name' => 'Profile Test',
            'password' => 'SecurePassword123!',
        ]);

        $client = static::getClient();
        $client->request('GET', '/api/user/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('displayName', $responseData);
    }

    public function testUpdateProfile(): void
    {
        $token = $this->registerUser([
            'email' => 'updatetest@example.com',
            'name' => 'Update Test',
            'password' => 'SecurePassword123!',
        ]);

        $client = static::getClient();
        $client->request('PUT', '/api/user/profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ], json_encode([
            'display_name' => 'Updated Name',
            'phone' => '0987654321',
        ]));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('displayName', $responseData);
        $this->assertEquals('Updated Name', $responseData['displayName']);
    }
}
