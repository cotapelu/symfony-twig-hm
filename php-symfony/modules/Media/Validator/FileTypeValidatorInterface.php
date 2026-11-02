<?php

declare(strict_types=1);

namespace App\Media\Validator;

/**
 * Interface for file type validation
 * Allows adding new file type validations without modifying existing code.
 */
interface FileTypeValidatorInterface
{
    /**
     * Check if this validator can handle the given MIME type.
     */
    public function canValidate(string $mimeType): bool;

    /**
     * Validate file data for this type.
     */
    public function validate(array $file): array;

    /**
     * Get the file type name.
     */
    public function getTypeName(): string;
}
