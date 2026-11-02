<?php

declare(strict_types=1);

namespace App\Tests\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\RetryService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class RetryServiceTest extends TestCase
{
    private RetryService $retryService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->retryService = new RetryService($this->logger);
    }

    public function testExecuteSucceedsOnFirstAttempt(): void
    {
        $operation = fn () => 'success';

        $result = $this->retryService->execute($operation, 'test_operation');

        $this->assertEquals('success', $result);
    }

    public function testExecuteRetriesOnFailure(): void
    {
        $attempt = 0;
        $operation = function ($currentAttempt) use (&$attempt) {
            $attempt = $currentAttempt;
            if ($currentAttempt < 2) {
                throw new \RuntimeException('Temporary error');
            }
            return 'success';
        };

        $result = $this->retryService->execute(
            $operation,
            'test_operation',
            3,
            10,
            1,
            [\RuntimeException::class]
        );

        $this->assertEquals('success', $result);
        $this->assertEquals(2, $attempt);
    }

    public function testExecuteFailsAfterMaxAttempts(): void
    {
        $operation = fn () => throw new \RuntimeException('Persistent error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Persistent error');

        $this->retryService->execute(
            $operation,
            'test_operation',
            3,
            10,
            1,
            [\RuntimeException::class]
        );
    }

    public function testExecuteDoesNotRetryNonRetryableExceptions(): void
    {
        $operation = fn () => throw new \InvalidArgumentException('Invalid argument');

        $this->expectException(\InvalidArgumentException::class);

        $this->retryService->execute(
            $operation,
            'test_operation',
            3,
            10,
            1,
            [\RuntimeException::class]
        );
    }

    public function testExecuteUsesDefaultRetryableExceptions(): void
    {
        $attempt = 0;
        $operation = function ($currentAttempt) use (&$attempt) {
            $attempt = $currentAttempt;
            if ($currentAttempt < 2) {
                throw new \RuntimeException('Temporary error');
            }
            return 'success';
        };

        $result = $this->retryService->execute($operation, 'test_operation');

        $this->assertEquals('success', $result);
        $this->assertEquals(2, $attempt);
    }

    public function testExecuteAppliesExponentialBackoff(): void
    {
        $attempts = [];
        $startTime = microtime(true);

        $operation = function ($currentAttempt) use (&$attempts) {
            $attempts[] = $currentAttempt;
            if (count($attempts) < 3) {
                throw new \RuntimeException('Temporary error');
            }
            return 'success';
        };

        $this->retryService->execute(
            $operation,
            'test_operation',
            5,
            100,
            2,
            [\RuntimeException::class]
        );

        $duration = (microtime(true) - $startTime) * 1000;

        $this->assertGreaterThan(300, $duration);
    }

    public function testExecuteRespectsMaxAttempts(): void
    {
        $attempts = 0;
        $operation = function ($currentAttempt) use (&$attempts) {
            $attempts = $currentAttempt;
            throw new \RuntimeException('Error');
        };

        try {
            $this->retryService->execute(
                $operation,
                'test_operation',
                2,
                10,
                1,
                [\RuntimeException::class]
            );
        } catch (\Exception $e) {
        }

        $this->assertEquals(2, $attempts);
    }

    public function testExecuteWithCustomInitialDelay(): void
    {
        $startTime = microtime(true);

        $operation = function ($currentAttempt) {
            if ($currentAttempt < 2) {
                throw new \RuntimeException('Temporary error');
            }
            return 'success';
        };

        $this->retryService->execute(
            $operation,
            'test_operation',
            3,
            200,
            1,
            [\RuntimeException::class]
        );

        $duration = (microtime(true) - $startTime) * 1000;

        $this->assertGreaterThan(200, $duration);
    }

    public function testExecuteWithCustomBackoffMultiplier(): void
    {
        $startTime = microtime(true);

        $operation = function ($currentAttempt) {
            if ($currentAttempt < 3) {
                throw new \RuntimeException('Temporary error');
            }
            return 'success';
        };

        $this->retryService->execute(
            $operation,
            'test_operation',
            4,
            50,
            3,
            [\RuntimeException::class]
        );

        $duration = (microtime(true) - $startTime) * 1000;

        $this->assertGreaterThan(200, $duration);
    }

    public function testExecuteReturnsOperationResult(): void
    {
        $operation = fn () => ['key' => 'value'];

        $result = $this->retryService->execute($operation, 'test_operation');

        $this->assertEquals(['key' => 'value'], $result);
    }

    public function testExecutePassesAttemptNumberToOperation(): void
    {
        $attempt = 0;

        $operation = function ($currentAttempt) use (&$attempt) {
            $attempt = $currentAttempt;
            return "Attempt {$currentAttempt}";
        };

        $result = $this->retryService->execute($operation, 'test_operation');

        $this->assertEquals('Attempt 1', $result);
        $this->assertEquals(1, $attempt);
    }

    public function testExecuteWithMultipleRetryableExceptions(): void
    {
        $attempt = 0;

        $operation = function ($currentAttempt) use (&$attempt) {
            $attempt = $currentAttempt;
            if ($currentAttempt === 1) {
                throw new \RuntimeException('Runtime error');
            } elseif ($currentAttempt === 2) {
                throw new \LogicException('Logic error');
            }
            return 'success';
        };

        $result = $this->retryService->execute(
            $operation,
            'test_operation',
            3,
            10,
            1,
            [\RuntimeException::class, \LogicException::class]
        );

        $this->assertEquals('success', $result);
        $this->assertEquals(3, $attempt);
    }
}
