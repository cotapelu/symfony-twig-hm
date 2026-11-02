<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure;

use App\Shared\Infrastructure\CacheService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AllowMockObjectsWithoutExpectations]
class CacheServiceTest extends TestCase
{
    private CacheService $service;
    private CacheInterface&MockObject $cacheMock;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->service = new CacheService($this->cacheMock);
    }

    public function testGetConversationListReturnsNullWhenCacheMiss(): void
    {
        $userId = 'user123';
        $key = "conversations:list:$userId";

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with($key, $this->callback(fn($callback) => is_callable($callback)))
            ->willReturnCallback(function($k, $callback) {
                return $callback();
            });

        $result = $this->service->getConversationList($userId);
        $this->assertNull($result);
    }

    public function testGetConversationListReturnsCachedValue(): void
    {
        $userId = 'user123';
        $key = "conversations:list:$userId";
        $cached = ['conv1', 'conv2'];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with($key, $this->anything())
            ->willReturn($cached);

        $result = $this->service->getConversationList($userId);
        $this->assertSame($cached, $result);
    }

    public function testGetConversationListHandlesExceptionAndReturnsNull(): void
    {
        $userId = 'user123';
        $key = "conversations:list:$userId";

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with($key, $this->anything())
            ->willThrowException(new \Exception('Cache error'));

        $result = $this->service->getConversationList($userId);
        $this->assertNull($result);
    }

    public function testSetConversationListStoresInCache(): void
    {
        $userId = 'user123';
        $key = "conversations:list:$userId";
        $conversations = ['conv1', 'conv2'];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with($key, $this->callback(fn($callback) => is_callable($callback)))
            ->willReturnCallback(function($k, $callback) use ($conversations) {
                return $callback();
            });

        $this->service->setConversationList($userId, $conversations);
        // No assertion on return, just ensure no exception
        $this->assertTrue(true);
    }

    public function testSetConversationListHandlesException(): void
    {
        $userId = 'user123';
        $key = "conversations:list:$userId";
        $conversations = ['conv1'];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with($key, $this->anything())
            ->willThrowException(new \Exception('Cache error'));

        $this->service->setConversationList($userId, $conversations);
        $this->assertTrue(true); // Should not throw
    }

    public function testGetConversationMessageCountReturnsZeroOnCacheMiss(): void
    {
        $userId = 'user123';
        $key = "conversations:count:$userId";

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with($key, $this->callback(fn($callback) => is_callable($callback)))
            ->willReturnCallback(function($k, $callback) {
                return $callback();
            });

        $result = $this->service->getConversationMessageCount($userId);
        $this->assertSame(0, $result);
    }

    public function testGetConversationMessageCountReturnsCachedValue(): void
    {
        $userId = 'user123';
        $key = "conversations:count:$userId";

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with($key, $this->anything())
            ->willReturn(42);

        $result = $this->service->getConversationMessageCount($userId);
        $this->assertSame(42, $result);
    }

    public function testGetConversationMessageCountHandlesExceptionAndReturnsZero(): void
    {
        $userId = 'user123';
        $key = "conversations:count:$userId";

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with($key, $this->anything())
            ->willThrowException(new \Exception('Cache error'));

        $result = $this->service->getConversationMessageCount($userId);
        $this->assertSame(0, $result);
    }

    public function testIncrementConversationCount(): void
    {
        $userId = 'user123';
        $key = "conversations:count:$userId";

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with($key, $this->callback(fn($callback) => is_callable($callback)))
            ->willReturnCallback(function($k, $callback) {
                return $callback();
            });

        $this->service->incrementConversationCount($userId);
        $this->assertTrue(true);
    }

    public function testInvalidateUserCachesDeletesBothKeys(): void
    {
        $userId = 'user123';
        $listKey = "conversations:list:$userId";
        $countKey = "conversations:count:$userId";

        $this->cacheMock->expects($this->exactly(2))
            ->method('delete')
            ->with($this->callback(function($key) use ($listKey, $countKey) {
                static $call = 0;
                $call++;
                return $call === 1 ? $key === $listKey : $key === $countKey;
            }))
            ->willReturn(true);

        $this->service->invalidateUserCaches($userId);
        $this->assertTrue(true);
    }

    public function testInvalidateUserCachesHandlesException(): void
    {
        $userId = 'user123';
        $listKey = "conversations:list:$userId";

        $this->cacheMock->expects($this->atLeastOnce())
            ->method('delete')
            ->willThrowException(new \Exception('Cache error'));

        $this->service->invalidateUserCaches($userId);
        $this->assertTrue(true);
    }

    public function testInvalidateConversationListDeletesOnlyListKey(): void
    {
        $userId = 'user123';
        $listKey = "conversations:list:$userId";

        $this->cacheMock->expects($this->once())
            ->method('delete')
            ->with($listKey)
            ->willReturn(true);

        $this->service->invalidateConversationList($userId);
        $this->assertTrue(true);
    }

    public function testInvalidateConversationCachesDoesNotThrow(): void
    {
        // The method currently has a try-catch and contains incomplete logic
        // We test that it doesn't throw even if cache operations fail
        $this->cacheMock->expects($this->never())->method('delete'); // no-op currently

        $this->service->invalidateConversationCaches('conv123');
        $this->assertTrue(true);
    }

    public function testGetRecentMessagesReturnsNullOnCacheMiss(): void
    {
        $userId = 'user123';
        $key = "messages:recent:$userId";

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with($key, $this->callback(fn($callback) => is_callable($callback)))
            ->willReturnCallback(fn($k, $callback) => $callback());

        $result = $this->service->getRecentMessages($userId);
        $this->assertNull($result);
    }

    public function testGetRecentMessagesWithLimitReturnsCached(): void
    {
        $userId = 'user123';
        $key = "messages:recent:$userId";
        $messages = ['msg1', 'msg2'];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with($key, $this->anything())
            ->willReturn($messages);

        $result = $this->service->getRecentMessages($userId, 5);
        $this->assertSame($messages, $result);
    }

    public function testSetRecentMessagesStoresInCache(): void
    {
        $userId = 'user123';
        $key = "messages:recent:$userId";
        $messages = ['msg1', 'msg2'];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with($key, $this->callback(fn($callback) => is_callable($callback)))
            ->willReturnCallback(fn($k, $callback) => $callback());

        $this->service->setRecentMessages($userId, $messages);
        $this->assertTrue(true);
    }

    public function testClearAllIsNoOp(): void
    {
        // CacheInterface doesn't have clear; this method does nothing
        $this->service->clearAll();
        $this->assertTrue(true);
    }
}
