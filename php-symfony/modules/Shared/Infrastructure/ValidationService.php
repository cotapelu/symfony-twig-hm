<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Media\Validator\FileTypeValidatorFactory;

class ValidationService
{
    private FileTypeValidatorFactory $fileValidatorFactory;

    public function __construct(?FileTypeValidatorFactory $fileValidatorFactory = null)
    {
        $this->fileValidatorFactory = $fileValidatorFactory ?? new FileTypeValidatorFactory();
    }

    public function validateMessageContent(string $content): array
    {
        $errors = [];

        if (empty(trim($content))) {
            $errors[] = 'Message content cannot be empty';
        }

        if (strlen($content) > 1000) {
            $errors[] = 'Message content cannot exceed 1000 characters';
        }

        // Check for potentially harmful content
        if ($this->containsMaliciousContent($content)) {
            $errors[] = 'Message contains inappropriate content';
        }

        return $errors;
    }

    public function validateConversationId(string $conversationId): array
    {
        $errors = [];

        if (empty($conversationId)) {
            $errors[] = 'Conversation ID is required';
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $conversationId)) {
            $errors[] = 'Invalid conversation ID format';
        }

        return $errors;
    }

    public function validateFileUpload(array $file): array
    {
        // Use the factory for comprehensive file validation
        return $this->fileValidatorFactory->validateFile($file);
    }

    public function validateUserCredentials(string $email, string $password): array
    {
        $errors = [];

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required';
        }

        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        return $errors;
    }

    public function validateGroupData(string $title, array $participantIds): array
    {
        $errors = [];

        if (empty(trim($title))) {
            $errors[] = 'Group title is required';
        }

        if (strlen($title) > 100) {
            $errors[] = 'Group title cannot exceed 100 characters';
        }

        if (empty($participantIds)) {
            $errors[] = 'Group must have at least one participant';
        }

        if (count($participantIds) > 1000) {
            $errors[] = 'Group cannot have more than 1000 participants';
        }

        return $errors;
    }

    private function containsMaliciousContent(string $content): bool
    {
        // Basic checks for potentially harmful content
        $patterns = [
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/eval\s*\(/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }
}
