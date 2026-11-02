<?php

declare(strict_types=1);

namespace App\User\Service;

class UserIdGeneratorService
{
    public function generate()
    {
        return 'user_' . bin2hex(random_bytes(16));
    }
}
