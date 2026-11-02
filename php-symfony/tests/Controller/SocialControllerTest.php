<?php declare(strict_types=1);

namespace App\Tests\Controller;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class SocialControllerTest extends DatabaseIntegrationTestCase
{
    private string $authToken;
    private ?int $testUserId = null;
    private ?string $postId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::$client;

        // Register a test user
        $email = 'test.' . uniqid() . '@example.com';
        $password = 'StrongPass123!';

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
            'name' => 'Test User',
            'phoneNumber' => '0123456789',
        ]));

        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->authToken = $data['token'];
        $this->testUserId = (int) $data['id'];
        $this->assertNotNull($this->testUserId);
    }

    public function testCreatePost(): void
    {
        $client = static::$client;
        $client->request('POST', '/api/social/posts', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'content' => 'Hello world!',
            'is_public' => true,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('content', $responseData);
        $this->assertEquals('Hello world!', $responseData['content']);
        $this->assertArrayHasKey('id', $responseData);
        $this->postId = (string) $responseData['id'];
    }

    public function testGetPosts(): void
    {
        // Ensure there is at least one post by creating one
        if ($this->postId === null) {
            $client = static::$client;
            $client->request('POST', '/api/social/posts', [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
            ], json_encode([
                'content' => 'Sample post',
                'is_public' => true,
            ]));
            $this->assertEquals(201, $client->getResponse()->getStatusCode());
            $data = json_decode($client->getResponse()->getContent(), true);
            $this->postId = (string) $data['id'];
        }

        $client = static::$client;
        $client->request('GET', '/api/social/posts?limit=5&offset=0', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testGetPostById(): void
    {
        // Ensure a post exists
        if ($this->postId === null) {
            $client = static::$client;
            $client->request('POST', '/api/social/posts', [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
            ], json_encode([
                'content' => 'Sample post for get',
                'is_public' => true,
            ]));
            $this->assertEquals(201, $client->getResponse()->getStatusCode());
            $data = json_decode($client->getResponse()->getContent(), true);
            $this->postId = (string) $data['id'];
        }

        $client = static::$client;
        $client->request('GET', '/api/social/posts/' . $this->postId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertResponseIsSuccessful();
    }
}
