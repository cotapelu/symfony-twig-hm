<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\User;

interface UserRepositoryInterface
{
    public function find(string $id): ?User;
}
