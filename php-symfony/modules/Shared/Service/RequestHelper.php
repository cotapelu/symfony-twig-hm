<?php

declare(strict_types=1);

namespace App\Shared\Service;

use Symfony\Component\HttpFoundation\Request;

class RequestHelper
{
    /**
     * Safely decode JSON from request content
     */
    public static function getJsonData(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON data');
        }
        return $data ?? [];
    }

    /**
     * Validate required fields in request data
     */
    public static function validateRequired(array $data, array $requiredFields)
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                throw new \InvalidArgumentException("Field '{$field}' is required");
            }
        }
    }

    /**
     * Get client IP with fallback
     */
    public static function getClientIp(Request $request)
    {
        return $request->getClientIp() ?? '127.0.0.1';
    }
}