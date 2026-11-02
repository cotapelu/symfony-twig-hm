<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class ValidationException extends Exception
{
    private array $errors;

    public function __construct(string $message, array $errors = [], int $code = 422)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'error' => $this->getMessage(),
            'code' => $this->getCode(),
            'errors' => $this->errors,
        ];
    }
}
