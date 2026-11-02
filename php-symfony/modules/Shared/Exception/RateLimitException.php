<?php

declare(strict_types=1);

namespace App\Shared\Exception;

/**
 * Rate limit exception.
 */
class RateLimitException extends AppException
{
    private array $remaining;
    private array $resetTime;

    public function __construct(array $remaining, array $resetTime, string $message = 'Rate limit exceeded')
    {
        parent::__construct($message);
        $this->remaining = $remaining;
        $this->resetTime = $resetTime;
    }

    public function getRemaining(): array
    {
        return $this->remaining;
    }

    public function getResetTime(): array
    {
        return $this->resetTime;
    }
}
