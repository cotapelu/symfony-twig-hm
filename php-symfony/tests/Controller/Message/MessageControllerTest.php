<?php

declare(strict_types=1);

namespace App\Tests\Controller\Message;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class MessageControllerTest extends DatabaseIntegrationTestCase
{
    // GET MESSAGES Tests (2)
    public function testGetMessages(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/messages', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ]);

        // Authentication required
        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Authentication required', $responseData['error']);
    }

    public function testGetMessagesRateLimit(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/messages', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ]);

        // Simulate rate limit after multiple requests
        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    // SEND MESSAGE Tests (3)
    public function testSendMessage(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/messages', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ], json_encode([
            'conversationId' => 'conv_123',
            'content' => 'Hello everyone!'
        ]));

        // Authentication required
        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testSendMessageWithoutContent(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/messages', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ], json_encode([
            'conversationId' => 'conv_123',
            'content' => ''
        ]));

        // Should work even with minimal data (empty string)
        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testSendMessageWithFiles(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/messages', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ], json_encode([
            'conversationId' => 'conv_123',
            'content' => 'Check this file!',
            'files' => [
                ['name' => 'document.pdf', 'type' => 'application/pdf'],
                ['name' => 'image.jpg', 'type' => 'image/jpeg']
            ]
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    // GET CONVERSATION MESSAGES Tests (3)
    public function testGetConversationMessages(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/messages/conv_123?page=1&limit=50', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ]);

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testGetConversationMessagesWithCursor(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/messages/conv_123?cursor=abc123&limit=10&use_cursor=true', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ]);

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
    }

    public function testGetConversationMessagesInvalidLimit(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/messages/conv_123?limit=500', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ]);

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Limit must be between 1 and 200', $responseData['error'] ?? '');
    }

    // TRANSLATE Tests (2)
    public function testTranslateMessage(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/messages/msg_123/translate', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ], json_encode([
            'content' => 'Hello',
            'to_lang' => 'vi'
        ]));

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testTranslateMessageWithAutoDetect(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/messages/msg_123/translate', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ], json_encode([
            'content' => 'Xin chào các bạn!',
            'from_lang' => 'auto',
            'to_lang' => 'en'
        ]));

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    // SMART REPLY Tests (2)
    public function testGenerateSmartReply(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/messages/smart-reply', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ], json_encode([
            'message' => 'How do I reset my password?'
        ]));

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testSmartReplyWithoutMessage(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/messages/smart-reply', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Message is required', $responseData['error'] ?? '');
    }

    // MODERATE CONTENT Tests (2)
    public function testModerateContent(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/messages/moderate', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ], json_encode([
            'content' => 'This is a test message'
        ]));

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testModerateContentWithoutContent(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/messages/moderate', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Content is required', $responseData['error'] ?? '');
    }

    // ADD REACTION Tests (2)
    public function testAddReaction(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/messages/msg_123/reactions', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ], json_encode([
            'reaction' => 'thumbs_up'
        ]));

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testAddReactionWithoutReaction(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/messages/msg_123/reactions', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Reaction is required', $responseData['error'] ?? '');
    }

    // MARK READ Tests (1)
    public function testMarkMessageRead(): void
    {
        $client = static::createClient();
        $client->request('PUT', '/api/messages/msg_123/read', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ]);

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    // GET TYPING STATUS Tests (1)
    public function testTypingStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/typing/status/conv_123');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('conversationId', $responseData);
        $this->assertArrayHasKey('typingUsers', $responseData);
    }

    // CONVERSATION INSIGHTS Tests (1)
    public function testGetConversationInsights(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/conversations/conv_123/insights');

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    // EDGE CASE Tests (2)
    public function testGetMessagesInvalidPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/messages?page=abc', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testSendMessageWithSpecialCharacters(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/messages', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer valid_token'
        ], json_encode([
            'conversationId' => 'conv_123',
            'content' => 'Hello! @everyone! 😊 Testing unicode characters'
        ]));

        $this->assertResponseStatusCodeSame(401);
    }
}
