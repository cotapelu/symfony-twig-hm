<?php

declare(strict_types=1);

namespace App\Shared\Service;

use Psr\Log\LoggerInterface;

class RetryService
{
    private const DEFAULT_MAX_ATTEMPTS = 3;
    private const DEFAULT_DELAY_MS = 1000;
    private const DEFAULT_BACKOFF_MULTIPLIER = 2;

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function execute(
        callable $operation,
        string $operationName = 'operation',
        int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS,
        int $initialDelayMs = self::DEFAULT_DELAY_MS,
        float $backoffMultiplier = self::DEFAULT_BACKOFF_MULTIPLIER,
        ?array $retryableExceptions = null
    ) {
        if ($retryableExceptions === null) {
            $retryableExceptions = [
                \RuntimeException::class,
            ];
        }

        $attempt = 0;
        $delayMs = $initialDelayMs;

        while (true) {
            $attempt++;
            $this->logger->info("Executing {$operationName} (attempt {$attempt}/{$maxAttempts})");

            try {
                $result = $operation($attempt);
                $this->logger->info("{$operationName} succeeded on attempt {$attempt}");
                return $result;
            } catch (\Exception $e) {
                if ($this->isRetryable($e, $retryableExceptions) && $attempt < $maxAttempts) {
                    $this->logger->warning("{$operationName} failed on attempt {$attempt}, retrying in {$delayMs}ms", [
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                        'attempt' => $attempt,
                    ]);

                    usleep($delayMs * 1000); // Convert to microseconds
                    $delayMs = (int) ($delayMs * $backoffMultiplier);
                } else {
                    $this->logger->error("{$operationName} failed after {$attempt} attempts", [
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                        'attempts' => $attempt,
                    ]);

                    throw $e;
                }
            }
        }
    }

    private function isRetryable(\Exception $e, array $retryableExceptions)
    {
        foreach ($retryableExceptions as $retryableException) {
            if ($e instanceof $retryableException) {
                return true;
            }
        }

        return false;
    }
}
