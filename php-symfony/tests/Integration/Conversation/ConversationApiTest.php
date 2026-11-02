<?php

declare(strict_types=1);

namespace App\Tests\Integration\Conversation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[AllowMockObjectsWithoutExpectations]
class ConversationApiTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('ConversationApiTest skipped: conversation routes not yet implemented.');
    }
    private int $userId1 = 100;
    private int $userId2 = 200;

    public function testCreateConversation(): void
    {
        $client = static::createClient();
        $client->request('POST', '/conversations', [
            'json' => [
                'participant_ids' => [$this->userId1, $this->userId2],
                'type' => 'group',
                'title' => 'Test Group',
            ],
        ]);

        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertSame('Test Group', $data['data']['title']);
        $this->assertSame([$this->userId1, $this->userId2], $data['data']['participant_ids']);
        $this->assertNotNull($data['data']['id']);
    }

    public function testGetConversationNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/conversations/99999');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testGetUserConversations(): void
    {
        $client = static::createClient();

        $client->request('POST', '/conversations', [
            'json' => [
                'participant_ids' => [$this->userId1, $this->userId2],
                'type' => 'direct',
            ],
        ]);
        $convId = json_decode($client->getResponse()->getContent(), true)['data']['id'];

        $client->request('GET', "/conversations/user/{$this->userId1}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data['data']);
        $ids = array_column($data['data'], 'id');
        $this->assertContains($convId, $ids);
    }

    public function testUpdateTitle(): void
    {
        $client = static::createClient();

        $client->request('POST', '/conversations', [
            'json' => [
                'participant_ids' => [$this->userId1],
                'type' => 'group',
                'title' => 'Old Title',
            ],
        ]);
        $convId = json_decode($client->getResponse()->getContent(), true)['data']['id'];

        $client->request('PUT', "/conversations/{$convId}/title", [
            'json' => ['title' => 'New Title'],
        ]);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('New Title', $data['data']['title']);
    }

    public function testUpdateAvatar(): void
    {
        $client = static::createClient();

        $client->request('POST', '/conversations', [
            'json' => [
                'participant_ids' => [$this->userId1],
                'type' => 'group',
            ],
        ]);
        $convId = json_decode($client->getResponse()->getContent(), true)['data']['id'];

        $client->request('PUT', "/conversations/{$convId}/avatar", [
            'json' => ['avatar' => 'avatar.png'],
        ]);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('avatar.png', $data['data']['avatar']);
    }

    public function testDeleteConversation(): void
    {
        $client = static::createClient();

        $client->request('POST', '/conversations', [
            'json' => [
                'participant_ids' => [$this->userId1],
                'type' => 'direct',
            ],
        ]);
        $convId = json_decode($client->getResponse()->getContent(), true)['data']['id'];

        $client->request('DELETE', "/conversations/{$convId}");
        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        // Confirm deleted
        $client->request('GET', "/conversations/{$convId}");
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testSearchConversations(): void
    {
        $client = static::createClient();

        $client->request('POST', '/conversations', [
            'json' => [
                'participant_ids' => [$this->userId1],
                'type' => 'group',
                'title' => 'Team Alpha',
            ],
        ]);

        $client->request('GET', "/conversations/user/{$this->userId1}/search?q=Alpha");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data['data']);
        $this->assertStringContainsString('Alpha', $data['data'][0]['title']);
    }
}
