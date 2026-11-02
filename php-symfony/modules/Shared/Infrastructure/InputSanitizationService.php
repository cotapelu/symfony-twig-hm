<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

/**
 * Input sanitization service for preventing XSS and other injection attacks.
 */
class InputSanitizationService
{
    /**
     * Sanitize string input for general use.
     */
    public function sanitizeString(string $input): string
    {
        // Trim whitespace
        $input = trim($input);

        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $input;
    }

    /**
     * Sanitize string for database storage (no HTML entities).
     */
    public function sanitizeForDatabase(string $input): string
    {
        // Trim whitespace
        $input = trim($input);

        // Remove potentially dangerous characters
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $input;
    }

    /**
     * Sanitize email input.
     */
    public function sanitizeEmail(string $email): string
    {
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        return $email;
    }

    /**
     * Sanitize content that may contain HTML (like message content).
     */
    public function sanitizeHtmlContent(string $content): string
    {
        // Allow basic formatting but strip dangerous tags
        $allowedTags = '<p><br><strong><b><em><i><u><s><ul><ol><li><blockquote><code><pre>';
        $content = strip_tags($content, $allowedTags);

        // Convert line breaks to <br> tags
        $content = nl2br($content);

        return $content;
    }

    /**
     * Check if input contains potentially dangerous patterns.
     */
    public function containsDangerousPatterns(string $input): bool
    {
        $dangerousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/<form/i',
            '/<input/i',
            '/<meta/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate and sanitize user input data array.
     */
    public function sanitizeUserInput(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Special handling for different field types
                if (str_contains(strtolower($key), 'email')) {
                    $sanitized[$key] = $this->sanitizeEmail($value);
                } elseif (str_contains(strtolower($key), 'content') || str_contains(strtolower($key), 'message')) {
                    $sanitized[$key] = $this->sanitizeHtmlContent($value);
                } else {
                    // For regular string fields, check for dangerous patterns BEFORE sanitizing
                    if ($this->containsDangerousPatterns($value)) {
                        throw new \InvalidArgumentException("Input contains dangerous content in field: {$key}");
                    }
                    $sanitized[$key] = $this->sanitizeString($value);
                }
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeUserInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
