<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\RetryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class RetryServiceTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private RetryService $service;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new RetryService($this->logger);
    }

    public function testExecuteSucceedsOnFirstAttempt(): void
    {
        $operation = function (int $attempt) {
            $this->assertSame(1, $attempt);
            return 'result';
        };
        $this->logger->expects($this->exactly(2))->method('info');
        $result = $this->service->execute($operation);
        $this->assertSame('result', $result);
    }

    public function testExecuteRetriesOnFailureThenSucceeds(): void
    {
        $attempts = 0;
        $operation = function (int $attempt) use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new \RuntimeException('temp error');
            }
            return 'success';
        };
        // 3 executing logs + 1 success log = 4 info calls; 2 warnings
        $this->logger->expects($this->exactly(4))->method('info');
        $this->logger->expects($this->exactly(2))->method('warning');
        $result = $this->service->execute($operation);
        $this->assertSame('success', $result);
        $this->assertSame(3, $attempts);
    }

    public function testExecuteThrowsAfterMaxAttempts(): void
    {
        $operation = function (int $attempt) {
            throw new \RuntimeException('permanent failure');
        };
        $this->logger->expects($this->exactly(3))->method('info');
        $this->logger->expects($this->exactly(2))->method('warning');
        $this->logger->expects($this->once())->method('error');
        $this->expectException(\RuntimeException::class);
        $this->service->execute($operation);
    }

    public function testExecuteWithCustomRetryableExceptions(): void
    {
        $operation = function (int $attempt) {
            throw new \InvalidArgumentException('bad arg');
        };
        // Only one info, then error (no retry)
        $this->logger->expects($this->once())->method('info');
        $this->logger->expects($this->once())->method('error');
        $this->expectException(\InvalidArgumentException::class);
        $this->service->execute($operation);
    }

    public function testExecuteWithCustomMaxAttemptsAndDelay(): void
    {
        $operation = function (int $attempt) {
            throw new \RuntimeException('fail');
        };
        // maxAttempts=2 => info 2 times, warning 1, error 1
        $this->logger->expects($this->exactly(2))->method('info');
        $this->logger->expects($this->once())->method('warning');
        $this->logger->expects($this->once())->method('error');
        $this->expectException(\RuntimeException::class);
        $this->service->execute($operation, 'custom op', 2, 500, 2.0);
    }
}
