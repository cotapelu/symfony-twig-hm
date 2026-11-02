<?php

declare(strict_types=1);

namespace App\Exception;

class AuthorizationException extends \Exception
{
    public function __construct(string $message = "Access denied", int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
