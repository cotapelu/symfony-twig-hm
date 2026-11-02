<?php declare(strict_types=1);

namespace App\Tests\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Service\NotificationStatsService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationStatsServiceTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private EntityManagerInterface&MockObject $em;
    private Connection&MockObject $conn;
    private Result&MockObject $result;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->conn = $this->createMock(Connection::class);
        $this->result = $this->createMock(Result::class);
    }

    public function testGetUserNotificationStatsReturnsAggregatedData(): void
    {
        $userId = 123;
        $expectedData = [
            ['type' => 'EMAIL', 'count' => 5, 'last_at' => '2025-01-01 12:00:00'],
            ['type' => 'SMS', 'count' => 2, 'last_at' => '2025-01-02 13:00:00'],
        ];
        $sql = 'SELECT type, COUNT(*) as count, MAX(created_at) as last_at FROM notifications WHERE user_id = ? GROUP BY type';

        $this->registry->expects($this->once())
            ->method('getManager')
            ->with('read_only')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->conn);
        $this->conn->expects($this->once())
            ->method('executeQuery')
            ->with($sql, [$userId])
            ->willReturn($this->result);
        $this->result->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn($expectedData);

        $service = new NotificationStatsService($this->registry);
        $result = $service->getUserNotificationStats($userId);
        $this->assertSame($expectedData, $result);
    }
}
