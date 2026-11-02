<?php

/**
 * @group requires-kernel
 */

declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\User\Entity\User;
use App\Conversation\Entity\Conversation;

#[AllowMockObjectsWithoutExpectations]
class MessageApiIntegrationTest extends DatabaseIntegrationTestCase
{
    private string $authToken;
    private ?User $testUser = null;
    private ?int $testConversationId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $client = self::$client;
        $entityManager = self::$entityManager;

        // Register a test user
        $email = 'test.' . time() . '@example.com';
        $password = 'StrongPass123!';

        $client->request('POST', '/api/register', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
            'name' => 'Integration Test User',
            'phoneNumber' => '0123456789',
        ]));

        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->authToken = $data['token'];
        $userId = $data['id'];
        $this->assertNotNull($userId);

        // Create a conversation (direct) with the user as participant
        $client->request('POST', '/api/conversations', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'participant_ids' => [$userId],
            'type' => 'direct',
        ]));

        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $convData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $convData);
        $this->testConversationId = (int) $convData['data']['id'];

        // Load user entity for cleanup
        $this->testUser = $entityManager->find(User::class, $userId);
        $this->assertInstanceOf(User::class, $this->testUser);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGetMessagesList(): void
    {
        $client = self::$client;
        $client->request('GET', '/api/messages', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('messages', $data);
        $this->assertArrayHasKey('pagination', $data);
    }

    public function testSendMessage(): void
    {
        $client = self::$client;
        $client->request('POST', '/api/messages', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'conversationId' => $this->testConversationId,
            'content' => 'Hello from integration test',
        ]));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('conversationId', $data);
        $this->assertArrayHasKey('senderId', $data);
        $this->assertArrayHasKey('content', $data);
        $this->assertEquals($this->testUser->getId(), $data['senderId']);
    }

    public function testSendMessageValidationError(): void
    {
        $client = self::$client;
        $client->request('POST', '/api/messages', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'content' => 'Missing conversationId',
        ]));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testSendMessageTooLong(): void
    {
        $client = self::$client;
        $client->request('POST', '/api/messages', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'conversationId' => $this->testConversationId,
            'content' => str_repeat('a', 10001),
        ]));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testGetConversationMessages(): void
    {
        $client = self::$client;

        // Send a message first
        $client->request('POST', '/api/messages', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'conversationId' => $this->testConversationId,
            'content' => 'First message',
        ]));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Fetch by conversation_id
        $client->request('GET', '/api/messages?conversation_id=' . $this->testConversationId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('messages', $data);
        $this->assertIsArray($data['messages']);
    }

    public function testGetMessagesNonExistentConversation(): void
    {
        $client = self::$client;
        $nonId = 'non-existent-' . uniqid();
        $client->request('GET', '/api/messages?conversation_id=' . $nonId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('messages', $data);
        $this->assertEmpty($data['messages']);
    }

    public function testUnauthenticatedAccess(): void
    {
        $client = self::$client;
        $client->request('GET', '/api/messages');

        $response = $client->getResponse();
        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testSendMessageWithAttachments(): void
    {
        $client = self::$client;
        $client->request('POST', '/api/messages', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'conversationId' => $this->testConversationId,
            'content' => 'Message with attachment',
            'files' => [
                [
                    'name' => 'test.jpg',
                    'size' => 1024,
                    'type' => 'image/jpeg',
                ],
            ],
        ]));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $data);
    }

    public function testSendMessageWithTooManyFiles(): void
    {
        $client = self::$client;
        $client->request('POST', '/api/messages', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'conversationId' => $this->testConversationId,
            'content' => 'Too many files',
            'files' => array_fill(0, 11, [
                'name' => 'test.jpg',
                'size' => 1024,
                'type' => 'image/jpeg',
            ]),
        ]));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testSendMessageWithLargeFile(): void
    {
        $client = self::$client;
        $client->request('POST', '/api/messages', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'conversationId' => $this->testConversationId,
            'content' => 'Large file',
            'files' => [
                [
                    'name' => 'large.jpg',
                    'size' => 11 * 1024 * 1024,
                    'type' => 'image/jpeg',
                ],
            ],
        ]));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testSendMessageWithInvalidFileType(): void
    {
        $client = self::$client;
        $client->request('POST', '/api/messages', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'conversationId' => $this->testConversationId,
            'content' => 'Invalid file',
            'files' => [
                [
                    'name' => 'test.exe',
                    'size' => 1024,
                    'type' => 'application/x-executable',
                ],
            ],
        ]));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }
}
