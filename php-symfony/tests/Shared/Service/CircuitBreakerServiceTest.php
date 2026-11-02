<?php

declare(strict_types=1);

namespace App\Tests\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\CircuitBreakerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class CircuitBreakerServiceTest extends TestCase
{
    private CircuitBreakerService $circuitBreaker;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->circuitBreaker = new CircuitBreakerService($this->logger);
    }

    public function testExecuteSuccessInClosedState(): void
    {
        $operation = fn () => 'success';

        $result = $this->circuitBreaker->execute(
            $operation,
            'test_service',
            3,
            60,
            null
        );

        $this->assertEquals('success', $result);
        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $this->circuitBreaker->getState('test_service'));
    }

    public function testExecuteFailureInClosedStateIncrementsFailureCount(): void
    {
        $operation = fn () => throw new \RuntimeException('Service error');

        $this->expectException(\RuntimeException::class);

        try {
            $this->circuitBreaker->execute(
                $operation,
                'test_service',
                3,
                60,
                null
            );
        } catch (\Exception $e) {
            $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $this->circuitBreaker->getState('test_service'));
            throw $e;
        }
    }

    public function testExecuteOpensCircuitAfterFailureThreshold(): void
    {
        $operation = fn () => throw new \RuntimeException('Service error');

        for ($i = 0; $i < 5; $i++) {
            try {
                $this->circuitBreaker->execute(
                    $operation,
                    'test_service',
                    3,
                    60,
                    null
                );
            } catch (\Exception $e) {
            }
        }

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $this->circuitBreaker->getState('test_service'));
    }

    public function testExecuteSkipsOperationWhenCircuitIsOpen(): void
    {
        $operation = fn () => 'should not execute';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circuit breaker is OPEN for service: test_service');

        for ($i = 0; $i < 5; $i++) {
            try {
                $this->circuitBreaker->execute(
                    fn () => throw new \RuntimeException('Service error'),
                    'test_service',
                    3,
                    60,
                    null
                );
            } catch (\Exception $e) {
            }
        }

        $this->circuitBreaker->execute($operation, 'test_service', 3, 60, null);
    }

    public function testExecuteCallsFallbackWhenCircuitIsOpen(): void
    {
        $fallback = fn () => 'fallback result';
        $operation = fn () => throw new \RuntimeException('Service error');

        for ($i = 0; $i < 5; $i++) {
            try {
                $this->circuitBreaker->execute(
                    $operation,
                    'test_service',
                    3,
                    60,
                    null
                );
            } catch (\Exception $e) {
            }
        }

        $result = $this->circuitBreaker->execute($operation, 'test_service', 3, 60, $fallback);

        $this->assertEquals('fallback result', $result);
        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $this->circuitBreaker->getState('test_service'));
    }

    public function testExecuteAttemptsResetAfterTimeout(): void
    {
        $operation = fn () => throw new \RuntimeException('Service error');

        for ($i = 0; $i < 5; $i++) {
            try {
                $this->circuitBreaker->execute(
                    $operation,
                    'test_service',
                    3,
                    0,
                    null
                );
            } catch (\Exception $e) {
            }
        }

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $this->circuitBreaker->getState('test_service'));

        sleep(1);

        $successOperation = fn () => 'success';
        try {
            $this->circuitBreaker->execute($successOperation, 'test_service', 3, 0, null);
        } catch (\Exception $e) {
        }

        $this->assertEquals(CircuitBreakerService::STATE_HALF_OPEN, $this->circuitBreaker->getState('test_service'));
    }

    public function testExecuteClosesCircuitAfterSuccessfulReset(): void
    {
        $successOperation = fn () => 'success';

        for ($i = 0; $i < 5; $i++) {
            try {
                $this->circuitBreaker->execute(
                    fn () => throw new \RuntimeException('Service error'),
                    'test_service',
                    3,
                    0,
                    null
                );
            } catch (\Exception $e) {
            }
        }

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $this->circuitBreaker->getState('test_service'));

        sleep(1);

        for ($i = 0; $i < 3; $i++) {
            $this->circuitBreaker->execute($successOperation, 'test_service', 3, 0, null);
        }

        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $this->circuitBreaker->getState('test_service'));
    }

    public function testResetClearsCircuitState(): void
    {
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->circuitBreaker->execute(
                    fn () => throw new \RuntimeException('Service error'),
                    'test_service',
                    3,
                    60,
                    null
                );
            } catch (\Exception $e) {
            }
        }

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $this->circuitBreaker->getState('test_service'));

        $this->circuitBreaker->reset('test_service');

        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $this->circuitBreaker->getState('test_service'));
    }

    public function testGetStateReturnsClosedForNonExistentService(): void
    {
        $state = $this->circuitBreaker->getState('non_existent_service');

        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $state);
    }

    public function testGetCircuitInfoReturnsAllCircuits(): void
    {
        $operation = fn () => 'success';

        $this->circuitBreaker->execute($operation, 'service_1', 3, 60, null);
        $this->circuitBreaker->execute($operation, 'service_2', 5, 120, null);

        $info = $this->circuitBreaker->getCircuitInfo();

        $this->assertArrayHasKey('service_1', $info);
        $this->assertArrayHasKey('service_2', $info);
        $this->assertEquals(3, $info['service_1']['failure_threshold']);
        $this->assertEquals(5, $info['service_2']['failure_threshold']);
    }

    public function testExecuteResetsFailureCountOnSuccess(): void
    {
        $successOperation = fn () => 'success';
        $failureOperation = fn () => throw new \RuntimeException('Service error');

        for ($i = 0; $i < 2; $i++) {
            try {
                $this->circuitBreaker->execute($failureOperation, 'test_service', 5, 60, null);
            } catch (\Exception $e) {
            }
        }

        $this->circuitBreaker->execute($successOperation, 'test_service', 5, 60, null);

        $info = $this->circuitBreaker->getCircuitInfo();
        $this->assertEquals(0, $info['test_service']['failure_count']);
    }
}
