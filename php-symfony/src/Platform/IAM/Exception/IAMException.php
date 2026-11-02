<?php

declare(strict_types=1);

namespace Platform\IAM\Exception;

use RuntimeException;

class IAMException extends RuntimeException
{
    public static function authenticationFailed(string $reason = ''): self
    {
        $message = 'Authentication failed';
        if (!empty($reason)) {
            $message .= ': ' . $reason;
        }
        return new self($message);
    }

    public static function authorizationFailed(string $userId, string $permission): self
    {
        return new self(sprintf(
            'User "%s" is not authorized for permission "%s"',
            $userId,
            $permission
        ));
    }

    public static function invalidCredentials(): self
    {
        return new self('Invalid credentials provided');
    }

    public static function tokenInvalidOrExpired(): self
    {
        return new self('Token is invalid or has expired');
    }
}