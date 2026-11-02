<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\DashboardService;
use App\Call\Repository\CallRepository;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DashboardServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private Connection&MockObject $conn;
    private Statement&MockObject $stmt;
    private CallRepository&MockObject $callRepo;
    private DashboardService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->conn = $this->createMock(Connection::class);
        $this->stmt = $this->createMock(Statement::class);
        $this->callRepo = $this->createMock(CallRepository::class);

        $this->em->method('getConnection')->willReturn($this->conn);
        $this->conn->method('prepare')->willReturn($this->stmt);

        $this->service = new DashboardService($this->em, $this->callRepo);
    }

    public function testGetDashboardStatsReturnsStructure(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);

        // Mock main stats query
        $this->stmt->method('executeQuery')->willReturn($this->mockResult([
            'messages_count' => '150',
            'groups_count' => '12',
            'calls_count' => '45',
            'notifications_unread' => '3',
            'conversations_count' => '8',
            'missed_calls_count' => '2',
        ]));

        // Mock recent activity calls
        $call1 = $this->createMockCall('2025-01-01 10:00:00', 'audio', 120);
        $call2 = $this->createMockCall('2025-01-01 09:30:00', 'video', 300);
        $this->callRepo->method('findUserCalls')->with($user, 1, 5)->willReturn([$call1, $call2]);

        // Mock countCallsByDate to return a consistent number for any date
        $this->callRepo->method('countCallsByDate')->willReturn(3);

        // Mock activity breakdown query
        $this->conn->method('executeQuery')->willReturn($this->mockResult([
            'messages' => '150',
            'calls' => '45',
            'groups' => '12',
        ]));

        $result = $this->service->getDashboardStats($user);

        $this->assertArrayHasKey('stats', $result);
        $this->assertCount(5, $result['stats']);
        $this->assertEquals(150, $result['stats'][0]['value']);
        $this->assertArrayHasKey('recentActivity', $result);
        $this->assertCount(2, $result['recentActivity']);
        $this->assertArrayHasKey('charts', $result);
        $this->assertCount(7, $result['charts']['callsPerDay']);
        $this->assertArrayHasKey('activityBreakdown', $result['charts']);
        $this->assertCount(3, $result['charts']['activityBreakdown']);
    }

    public function testGetRecentActivitySortsByTimestampDesc(): void
    {
        $user = $this->createMock(User::class);

        $call1 = $this->createMockCall('2025-01-01 10:00:00', 'audio', 120);
        $call2 = $this->createMockCall('2025-01-01 09:30:00', 'video', 300);
        $call3 = $this->createMockCall('2025-01-01 11:00:00', 'audio', 60);

        $this->callRepo->method('findUserCalls')->with($user, 1, 5)->willReturn([$call1, $call2, $call3]);

        $ref = new \ReflectionMethod($this->service, 'getRecentActivity');
        $ref->setAccessible(true);
        $activities = $ref->invoke($this->service, $user);

        $this->assertCount(3, $activities);
        // The service formats timestamps with format('c'), verify ordering by comparing DateTimeImmutable
        $times = array_map(fn($a) => new \DateTimeImmutable($a['timestamp']), $activities);
        $this->assertTrue($times[0] >= $times[1]);
        $this->assertTrue($times[1] >= $times[2]);
        // Also verify latest is 2025-01-01T11:00:00
        $this->assertSame('2025-01-01T11:00:00+00:00', $activities[0]['timestamp']);
    }

    public function testGetCallsPerDayChartReturnsSevenDays(): void
    {
        $user = $this->createMock(User::class);
        // Provide any count to satisfy loop
        $this->callRepo->method('countCallsByDate')->willReturn(3);

        $ref = new \ReflectionMethod($this->service, 'getCallsPerDayChart');
        $ref->setAccessible(true);
        $chart = $ref->invoke($this->service, $user);
        $this->assertCount(7, $chart);
        foreach ($chart as $day) {
            $this->assertArrayHasKey('date', $day);
            $this->assertArrayHasKey('count', $day);
            $this->assertIsInt($day['count']);
        }
    }

    public function testGetActivityBreakdownChartReturnsThreeCategories(): void
    {
        $user = $this->createMock(User::class);
        // For the getDashboardStats call, but also directly we can test
        $this->conn->method('executeQuery')->willReturn($this->mockResult([
            'messages' => '100',
            'calls' => '50',
            'groups' => '25',
        ]));

        $ref = new \ReflectionMethod($this->service, 'getActivityBreakdownChart');
        $ref->setAccessible(true);
        $chart = $ref->invoke($this->service, $user);
        $this->assertCount(3, $chart);
        $names = array_column($chart, 'name');
        $this->assertContains('Messages', $names);
        $this->assertContains('Calls', $names);
        $this->assertContains('Groups', $names);
    }

    private function createMockCall(string $startedAt, string $type, ?int $duration)
    {
        $call = $this->createMock(\App\Call\Entity\CallRecord::class);
        $call->method('getStartedAt')->willReturn(new \DateTimeImmutable($startedAt));
        $call->method('getType')->willReturn($type);
        $call->method('getDuration')->willReturn($duration);
        return $call;
    }

    private function mockResult(array $row): MockObject&Result
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn($row);
        return $result;
    }
}
