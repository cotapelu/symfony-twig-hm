<?php

declare(strict_types=1);

namespace App\Repository\Common;

trait CommonRepositoryTrait
{
    public function save(object $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function remove(object $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?object
    {
        return $this->find($id);
    }
}
