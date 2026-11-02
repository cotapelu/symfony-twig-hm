<?php

declare(strict_types=1);

namespace App\Shared\Service;

class ResponseHelper
{
    /**
     * Standard success response
     */
    public static function success(array $data = [], string $message = 'Success')
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * Standard error response
     */
    public static function error(string $message, int $code = 400)
    {
        return [
            'success' => false,
            'error' => $message,
            'code' => $code,
        ];
    }

    /**
     * Authentication error response
     */
    public static function authError()
    {
        return self::error('Authentication required', 401);
    }

    /**
     * Validation error response
     */
    public static function validationError(array $errors)
    {
        return [
            'success' => false,
            'error' => 'Validation failed',
            'details' => $errors,
            'code' => 400,
        ];
    }

    /**
     * Not found error response
     */
    public static function notFound(string $resource = 'Resource')
    {
        return self::error("{$resource} not found", 404);
    }
}