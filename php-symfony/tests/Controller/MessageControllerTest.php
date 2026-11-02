<?php

declare(strict_types=1);

namespace App\Tests\Controller;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class MessageControllerTest extends DatabaseIntegrationTestCase
{
    public function testSendMessage(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/messages', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'conversation_id' => 'conv123',
            'content' => 'Hello, how are you?',
            'type' => 'text'
        ]));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('content', $responseData);
    }

    public function testGetMessages(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/messages?limit=20');

        $this->assertResponseIsSuccessful();
    }
}
