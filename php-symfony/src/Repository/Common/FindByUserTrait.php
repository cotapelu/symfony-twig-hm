<?php

declare(strict_types=1);

namespace App\Repository\Common;

trait FindByUserTrait
{
    public function findByUserId(string $userId): array
    {
        return $this->findBy(['userId' => $userId]);
    }
}
