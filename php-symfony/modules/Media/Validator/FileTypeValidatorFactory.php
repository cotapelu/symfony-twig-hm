<?php

declare(strict_types=1);

namespace App\Media\Validator;

/**
 * Factory for file type validators
 * Follows Open/Closed Principle - new validators can be added without modifying existing code.
 */
class FileTypeValidatorFactory
{
    /** @var FileTypeValidatorInterface[] */
    private array $validators = [];

    public function __construct()
    {
        // Register default validators
        $this->registerValidator(new ImageFileValidator());
        $this->registerValidator(new DocumentFileValidator());
        $this->registerValidator(new VideoFileValidator());

        // Future: Load custom validators from config
        // $this->loadCustomValidators();
    }

    /**
     * Register a new file type validator.
     */
    public function registerValidator(FileTypeValidatorInterface $validator): void
    {
        $this->validators[] = $validator;
    }

    /**
     * Get validator for specific MIME type.
     */
    public function getValidator(string $mimeType): ?FileTypeValidatorInterface
    {
        foreach ($this->validators as $validator) {
            if ($validator->canValidate($mimeType)) {
                return $validator;
            }
        }

        return null;
    }

    /**
     * Get all registered validators.
     */
    public function getAllValidators(): array
    {
        return $this->validators;
    }

    /**
     * Check if a MIME type is supported.
     */
    public function isSupported(string $mimeType): bool
    {
        return null !== $this->getValidator($mimeType);
    }

    /**
     * Validate file using appropriate validator.
     */
    public function validateFile(array $file): array
    {
        $errors = [];

        // Basic validation first
        if (!isset($file['name']) || !isset($file['size']) || !isset($file['type'])) {
            $errors[] = 'Invalid file data';

            return $errors;
        }

        // Get appropriate validator
        $validator = $this->getValidator($file['type']);
        if (!$validator) {
            $errors[] = 'File type not supported: '.$file['type'];

            return $errors;
        }

        // Run type-specific validation
        return $validator->validate($file);
    }
}
