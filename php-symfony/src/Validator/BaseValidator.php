<?php

namespace App\Validator;

use InvalidArgumentException;

trait BaseValidator
{
    public static function validateUuid(string $id, string $fieldName): void
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $id)) {
            throw new InvalidArgumentException("Invalid $fieldName format");
        }
    }

    public static function validatePhone(string $phone): void
    {
        if (!preg_match('/^(\+84|0)[0-9]{9,10}$/', $phone)) {
            throw new InvalidArgumentException('Phone number must be a valid Vietnamese number');
        }
    }

    public static function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
    }

    public static function validatePositiveInt(?int $value, string $fieldName, int $min = 1, ?int $max = null): int
    {
        if ($value === null) {
            $value = 0;
        }
        if ($value < $min) {
            throw new InvalidArgumentException("$fieldName must be at least $min");
        }
        if ($max !== null && $value > $max) {
            throw new InvalidArgumentException("$fieldName must not exceed $max");
        }
        return $value;
    }

    public static function validateEnum(string $value, array $allowed, string $fieldName): void
    {
        if (!in_array($value, $allowed, true)) {
            throw new InvalidArgumentException("$fieldName must be one of: " . implode(', ', $allowed));
        }
    }

    public static function validateLength(string $value, string $fieldName, int $max, int $min = 0): void
    {
        $len = strlen($value);
        if ($len < $min) {
            throw new InvalidArgumentException("$fieldName must be at least $min characters");
        }
        if ($len > $max) {
            throw new InvalidArgumentException("$fieldName must not exceed $max characters");
        }
    }

    public static function validateDate(?string $dateStr, string $fieldName): \DateTimeImmutable
    {
        try {
            $date = new \DateTimeImmutable($dateStr);
            return $date;
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid $fieldName format");
        }
    }
}