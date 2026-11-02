<?php

declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class AIServiceIntegrationDbTest extends DatabaseIntegrationTestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::getClient();
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'ai-test@example.com',
            'name' => 'AI Test',
            'password' => 'SecurePassword123!',
        ]));

        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->token = $data['token'];
    }

    public function testGenerateSmartReplies(): void
    {
        $client = static::getClient();
        $client->request('POST', '/api/ai/smart-replies', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ], json_encode([
            'message' => 'Hello, how are you?',
            'conversationId' => 'conv123',
        ]));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('replies', $data);
        $this->assertIsArray($data['replies']);
        $this->assertNotEmpty($data['replies']);
    }

    public function testModerateContent(): void
    {
        $client = static::getClient();
        $client->request('POST', '/api/ai/moderate', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ], json_encode([
            'content' => 'This is a test message.',
        ]));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('isFlagged', $data);
        $this->assertIsBool($data['isFlagged']);
        $this->assertArrayHasKey('categories', $data);
        $this->assertIsArray($data['categories']);
    }

    public function testTranslateContent(): void
    {
        $client = static::getClient();
        $client->request('POST', '/api/ai/translate', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ], json_encode([
            'text' => 'Hello world',
            'targetLanguage' => 'es',
        ]));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('translatedText', $data);
        $this->assertSame('Hello world', $data['translatedText']); // stub returns original
    }
}
