<?php

declare(strict_types=1);

namespace Platform\MDM\Exception;

use RuntimeException;

class MDMException extends RuntimeException
{
    public static function referenceDataNotFound(string $type, string $code): self
    {
        return new self(sprintf(
            'Reference data not found for type "%s" and code "%s"',
            $type,
            $code
        ));
    }

    public static function invalidReferenceData(string $type, string $code): self
    {
        return new self(sprintf(
            'Invalid reference data for type "%s" and code "%s"',
            $type,
            $code
        ));
    }

    public static function searchFailed(string $type, array $criteria, \Exception $previous): self
    {
        return new self(
            sprintf('Failed to search reference data for type "%s"', $type),
            0,
            $previous
        );
    }
}