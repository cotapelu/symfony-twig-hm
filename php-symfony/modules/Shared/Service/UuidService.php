<?php

declare(strict_types=1);

namespace App\Shared\Service;

use Ramsey\Uuid\Uuid;

class UuidService
{
    public function generate()
    {
        return Uuid::uuid4()->toString();
    }

    public function generateWithPrefix(string $prefix)
    {
        return $prefix . $this->generate();
    }

    public static function randomHex()
    {
        return bin2hex(random_bytes(16));
    }
}
