<?php

declare(strict_types=1);

namespace App\User\Service;

class PhoneValidationService
{
    public function validate(string $phoneNumber)
    {
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);

        if (strlen($cleaned) < 10 || strlen($cleaned) > 15) {
            throw new \InvalidArgumentException('Invalid phone number format');
        }
    }
}
