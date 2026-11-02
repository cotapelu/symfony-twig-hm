<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class ReadOnlyEntityManagerProvider
{
    public function __construct(private ManagerRegistry $registry)
    {
    }

    public function get()
    {
        return $this->registry->getManager('read_only');
    }
}
