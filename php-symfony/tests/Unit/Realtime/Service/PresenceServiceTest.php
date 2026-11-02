<?php declare(strict_types=1);

namespace App\Tests\Unit\Realtime\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Service\PresenceService;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AllowMockObjectsWithoutExpectations]
class PresenceServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private HubInterface&MockObject $hub;
    private PresenceService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->hub = $this->createMock(HubInterface::class);
        $this->service = new PresenceService($this->em, $this->hub);
    }

    public function testSetUserOnlineThrowsWhenUserNotFound(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->with('user123')->willReturn(null);
        $this->em->method('getRepository')->with(User::class)->willReturn($repo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User not found');
        $this->service->setUserOnline('user123');
    }

    public function testSetUserOnlineSetsStatusAndPublishesUpdate(): void
    {
        $user = $this->createMock(User::class);
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->with('user456')->willReturn($user);
        $this->em->method('getRepository')->with(User::class)->willReturn($repo);

        // Capture the Update passed to publish
        $updateArg = null;
        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) use (&$updateArg) {
                $updateArg = $update;
                return true;
            }));

        $result = $this->service->setUserOnline('user456');
        $this->assertSame(['status' => 'online'], $result);
        $topics = $updateArg->getTopics();
        $this->assertSame('user/user456/presence', $topics[0]);
        $payload = json_decode($updateArg->getData(), true);
        $this->assertSame('user_online', $payload['type']);
        $this->assertSame('user456', $payload['userId']);
        $this->assertArrayHasKey('timestamp', $payload);
    }

    public function testSetUserOfflineRemovesUserAndPublishesUpdate(): void
    {
        // We'll call setUserOnline first to add the user, but we can mock internal state directly using reflection
        $ref = new \ReflectionProperty($this->service, 'onlineUsers');
        $ref->setAccessible(true);
        $ref->setValue($this->service, ['user789' => time()]);
        $ref2 = new \ReflectionProperty($this->service, 'userStatus');
        $ref2->setAccessible(true);
        $ref2->setValue($this->service, ['user789' => 'online']);

        // Expect hub publish called once
        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $payload = json_decode($update->getData(), true);
                $topics = $update->getTopics();
                return $topics[0] === 'user/user789/presence' && $payload['type'] === 'user_offline';
            }));

        $result = $this->service->setUserOffline('user789');
        $this->assertSame(['status' => 'offline'], $result);
        $this->assertArrayNotHasKey('user789', $ref->getValue($this->service));
        $this->assertSame('offline', $ref2->getValue($this->service)['user789'] ?? null);
    }

    public function testGetOnlineUsersReturnsKeysAndCount(): void
    {
        $ref = new \ReflectionProperty($this->service, 'onlineUsers');
        $ref->setAccessible(true);
        $ref->setValue($this->service, ['u1' => time(), 'u2' => time()]);
        $ref2 = new \ReflectionProperty($this->service, 'userStatus');
        $ref2->setAccessible(true);
        $ref2->setValue($this->service, ['u1' => 'online', 'u2' => 'online']);

        $result = $this->service->getOnlineUsers();
        $this->assertSame(['online_users' => ['u1', 'u2'], 'count' => 2], $result);
    }

    public function testGetOnlineUsersRemovesStaleEntries(): void
    {
        $now = time();
        $ref = new \ReflectionProperty($this->service, 'onlineUsers');
        $ref->setAccessible(true);
        $ref->setValue($this->service, [
            'fresh' => $now,
            'stale' => $now - 301, // beyond 300s timeout
        ]);
        $ref2 = new \ReflectionProperty($this->service, 'userStatus');
        $ref2->setAccessible(true);
        $ref2->setValue($this->service, [
            'fresh' => 'online',
            'stale' => 'online',
        ]);

        $result = $this->service->getOnlineUsers();
        $this->assertSame(['online_users' => ['fresh'], 'count' => 1], $result);
        // Also check internal state: stale should be removed and status set to offline
        $online = $ref->getValue($this->service);
        $this->assertArrayNotHasKey('stale', $online);
        $status = $ref2->getValue($this->service);
        $this->assertSame('offline', $status['stale'] ?? null);
    }
}
