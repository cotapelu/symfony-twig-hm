<?php

declare(strict_types=1);

namespace App\Shared\Service;

class CircuitBreakerService
{
    public const STATE_CLOSED = 'closed';
    public const STATE_OPEN = 'open';
    public const STATE_HALF_OPEN = 'half-open';

    private array $circuits = [];

    public function __construct()
    {
    }

    /**
     * Execute a callback with circuit breaker protection
     *
     * @param callable $operation - The operation to execute
     * @param string $serviceName - Service name for tracking
     * @param int $failureThreshold - Failures before opening circuit
     * @param int $timeoutSeconds - Timeout in seconds before attempting retry
     * @param callable|null $fallback - Fallback callback if circuit is open
     */
    public function execute(
        callable $operation,
        string $serviceName = 'default_service',
        int $failureThreshold = 3,
        int $timeoutSeconds = 60,
        ?callable $fallback = null
    ) {
        $circuit = &$this->getOrInitCircuit($serviceName, $failureThreshold, $timeoutSeconds);
        if (null !== ($fallbackResult = $this->checkOpenState($circuit, $fallback, $serviceName))) {
            return $fallbackResult;
        }
        try {
            $result = $operation();
        } catch (\Exception $e) {
            $this->handleFailure($circuit, $failureThreshold);
            return $this->executeFallback($fallback, $serviceName);
        }
        $this->handleSuccess($circuit, $failureThreshold);
        return $result;
    }

    private function &getOrInitCircuit(string $serviceName, int $failureThreshold, int $timeoutSeconds): array
    {
        if (!isset($this->circuits[$serviceName])) {
            $this->circuits[$serviceName] = [
                'state' => self::STATE_CLOSED,
                'failureCount' => 0,
                'halfOpenSuccessCount' => 0,
                'lastAttemptTime' => 0,
                'failureThreshold' => $failureThreshold,
                'timeoutSeconds' => $timeoutSeconds,
            ];
        }
        return $this->circuits[$serviceName];
    }

    private function checkOpenState(array &$circuit, ?callable $fallback, string $serviceName)
    {
        if ($circuit['state'] !== self::STATE_OPEN) {
            return null;
        }
        $now = time();
        if ($now - $circuit['lastAttemptTime'] >= $circuit['timeoutSeconds']) {
            $circuit['state'] = self::STATE_HALF_OPEN;
            $circuit['halfOpenSuccessCount'] = 0;
            return null;
        }
        return $this->executeFallback($fallback, $serviceName);
    }

    private function handleFailure(array &$circuit, int $failureThreshold): void
    {
        $circuit['failureCount']++;
        $circuit['lastAttemptTime'] = time();
        if ($circuit['state'] === self::STATE_HALF_OPEN) {
            $circuit['state'] = self::STATE_OPEN;
            $circuit['halfOpenSuccessCount'] = 0;
        } elseif ($circuit['failureCount'] >= $failureThreshold) {
            $circuit['state'] = self::STATE_OPEN;
        }
    }

    private function handleSuccess(array &$circuit, int $failureThreshold): void
    {
        if ($circuit['state'] === self::STATE_HALF_OPEN) {
            $circuit['halfOpenSuccessCount']++;
            if ($circuit['halfOpenSuccessCount'] >= $failureThreshold) {
                $circuit['state'] = self::STATE_CLOSED;
                $circuit['failureCount'] = 0;
                $circuit['halfOpenSuccessCount'] = 0;
            }
        } elseif ($circuit['state'] === self::STATE_CLOSED) {
            $circuit['failureCount'] = 0;
        }
    }

    public function isServiceAvailable(string $service): bool
    {
        return $this->getState($service) === self::STATE_CLOSED;
    }

    public function getState(string $service): string
    {
        return $this->circuits[$service]['state'] ?? self::STATE_CLOSED;
    }

    public function getCircuitInfo(): array
    {
        $info = [];
        foreach ($this->circuits as $service => $circuit) {
            $info[$service] = [
                'state' => $circuit['state'],
                'failure_count' => $circuit['failureCount'],
                'last_attempt' => $circuit['lastAttemptTime'] ? date('Y-m-d H:i:s', $circuit['lastAttemptTime']) : null,
                'timeout_seconds' => $circuit['timeoutSeconds'],
                'failure_threshold' => $circuit['failureThreshold'],
            ];
        }
        return $info;
    }

    public function reset(string $service): void
    {
        unset($this->circuits[$service]);
    }

    private function executeFallback(?callable $fallback, string $serviceName)
    {
        if (null !== $fallback) {
            return $fallback();
        }
        throw new \RuntimeException("Circuit breaker is OPEN for service: {$serviceName}");
    }
}
