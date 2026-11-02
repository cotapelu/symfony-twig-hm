<?php

declare(strict_types=1);

namespace App\Tests\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Message\Service\MessageService;
use App\Message\Repository\MessageRepositoryInterface;
use App\Conversation\Service\ConversationAccessVerifierInterface;
use App\Shared\EventBus\EventBus;
use App\Shared\Service\UuidService;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MessageServiceTest extends TestCase
{
    private MessageService $service;

    protected function setUp(): void
    {
        $repo = $this->createMock(MessageRepositoryInterface::class);
        $access = $this->createMock(ConversationAccessVerifierInterface::class);
        $eventBus = $this->createMock(EventBus::class);
        $uuid = $this->createMock(UuidService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $this->service = new MessageService($repo, $access, $eventBus, $uuid, $em);
    }

    public function testGetMessagesForUserReturnsArray(): void
    {
        $result = $this->service->getMessagesForUser('user_123', 10);
        
        $this->assertIsArray($result);
    }

    public function testGetMessagesForUserWithDefaultLimit(): void
    {
        $result = $this->service->getMessagesForUser('user_123');
        
        $this->assertIsArray($result);
    }
}
