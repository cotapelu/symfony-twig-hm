<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\CacheWarmupService;
use App\Conversation\Repository\ConversationRepositoryInterface;
use App\Message\Repository\MessageRepositoryInterface;
use App\User\Repository\UserRepositoryInterface;
use App\Conversation\Entity\Conversation;
use App\Message\Entity\Message;
use App\User\Entity\User;
use Symfony\Contracts\Cache\CacheInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CacheWarmupServiceTest extends TestCase
{
    private CacheInterface&MockObject $cache;
    private ConversationRepositoryInterface&MockObject $conversationRepo;
    private MessageRepositoryInterface&MockObject $messageRepo;
    private UserRepositoryInterface&MockObject $userRepo;
    private CacheWarmupService $service;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->conversationRepo = $this->createMock(ConversationRepositoryInterface::class);
        $this->messageRepo = $this->createMock(MessageRepositoryInterface::class);
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);

        $this->service = new CacheWarmupService(
            $this->cache,
            $this->conversationRepo,
            $this->messageRepo,
            $this->userRepo
        );
    }

    public function testWarmupUserCacheBuildsConversationsAndRecentMessages(): void
    {
        $user = $this->createMock(User::class);
        $userId = 'user-123';
        $user->method('getId')->willReturn($userId);

        $convId1 = 1;
        $convId2 = 2;

        // Mock conversation IDs
        $this->conversationRepo->method('findConversationIdsByParticipant')
            ->with($userId)
            ->willReturn([$convId1, $convId2]);

        // Mock conversations
        $conversation1 = $this->createMock(Conversation::class);
        $conversation1->method('getId')->willReturn($convId1);
        $conversation1->method('getType')->willReturn('direct');
        $conversation1->method('isArchived')->willReturn(false);
        $conversation2 = $this->createMock(Conversation::class);
        $conversation2->method('getId')->willReturn($convId2);
        $conversation2->method('getType')->willReturn('group');
        $conversation2->method('isArchived')->willReturn(true);

        $this->conversationRepo->method('findById')
            ->willReturnMap([
                [$convId1, $conversation1],
                [$convId2, $conversation2],
            ]);

        // Mock latest messages
        $msg1 = $this->createMock(Message::class);
        $msg1->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2025-01-01 12:00:00'));
        $msg2 = $this->createMock(Message::class);
        $msg2->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2025-01-02 13:00:00'));

        $this->messageRepo->method('findLatestMessagesByConversationIds')
            ->willReturnMap([
                [[(string)$convId1], [(string)$convId1 => $msg1]],
                [[(string)$convId2], []],
            ]);
        $this->messageRepo->method('countUnreadMessagesInConversation')
            ->willReturnMap([
                [(string)$convId1, $userId, 5],
                [(string)$convId2, $userId, 0],
            ]);

        // Mock recent messages
        $recentMsg1 = $this->createMock(Message::class);
        $recentMsg2 = $this->createMock(Message::class);
        $this->messageRepo->method('findRecentByUserId')
            ->with($userId, 20)
            ->willReturn([$recentMsg1, $recentMsg2]);

        // Cache get will capture callbacks
        $cacheCalls = [];
        $this->cache->method('get')->willReturnCallback(function ($key, $callback) use (&$cacheCalls) {
            $cacheCalls[$key] = $callback();
            return $cacheCalls[$key];
        });

        // Call service method
        $reflection = new \ReflectionMethod($this->service, 'warmupUserCache');
        $reflection->setAccessible(true);
        $reflection->invoke($this->service, $userId);

        // Assert cache calls
        $this->assertArrayHasKey("conversations:{$userId}", $cacheCalls);
        $this->assertArrayHasKey("messages:recent:{$userId}", $cacheCalls);

        // Verify conversations structure
        $conversations = $cacheCalls["conversations:{$userId}"];
        $this->assertCount(2, $conversations);
        $this->assertSame($convId1, $conversations[0]['id']);
        $this->assertSame('direct', $conversations[0]['type']);
        $this->assertFalse($conversations[0]['is_archived']);
        $this->assertNotNull($conversations[0]['last_message_at']); // should be formatted date
        $this->assertSame(5, $conversations[0]['unread_count']);

        $this->assertSame($convId2, $conversations[1]['id']);
        $this->assertSame('group', $conversations[1]['type']);
        $this->assertTrue($conversations[1]['is_archived']);
        $this->assertNull($conversations[1]['last_message_at']); // no latest message for conv2? Actually we didn't provide a message for conv2, so it should be null
        $this->assertSame(0, $conversations[1]['unread_count']);

        // Verify recent messages
        $recent = $cacheCalls["messages:recent:{$userId}"];
        $this->assertCount(2, $recent);
        $this->assertSame($recentMsg1, $recent[0]);
        $this->assertSame($recentMsg2, $recent[1]);
    }

    public function testWarmupAllCallsWarmupUserCacheForAllUsers(): void
    {
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn('user1');
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn('user2');

        $this->userRepo->method('findUsersPaginated')
            ->with(1, 100)
            ->willReturn(['data' => [$user1, $user2]]);

        // Expect conversationRepo called twice (once per user) with any user ID, return empty IDs
        $this->conversationRepo->expects($this->exactly(2))
            ->method('findConversationIdsByParticipant')
            ->withAnyParameters()
            ->willReturn([]);
        $this->messageRepo->method('findRecentByUserId')->willReturn([]);
        $this->cache->method('get')->willReturn([]);

        // Use reflection to call warmupAll
        $reflection = new \ReflectionMethod($this->service, 'warmupAll');
        $reflection->setAccessible(true);
        $reflection->invoke($this->service);


    }

    public function testIsWarmupCompleteReturnsTrueWhenCompleted(): void
    {
        $this->cache->method('get')
            ->with('cache:warmup:status', $this->anything())
            ->willReturn('completed');

        $reflection = new \ReflectionMethod($this->service, 'isWarmupComplete');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->service);
        $this->assertTrue($result);
    }

    public function testIsWarmupCompleteReturnsFalseWhenNotCompleted(): void
    {
        $this->cache->method('get')
            ->with('cache:warmup:status', $this->anything())
            ->willReturn('pending');

        $reflection = new \ReflectionMethod($this->service, 'isWarmupComplete');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->service);
        $this->assertFalse($result);
    }

    public function testClearWarmupStatusDeletesStatusKey(): void
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('cache:warmup:status');

        $reflection = new \ReflectionMethod($this->service, 'clearWarmupStatus');
        $reflection->setAccessible(true);
        $reflection->invoke($this->service);
    }
}
