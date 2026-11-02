<?php

declare(strict_types=1);

namespace App\SampleModule\Infrastructure;

/**
 * Sample Repository implementing data access
 * Responsible for persistence and retrieval of entities
 */
class SampleRepository implements SampleRepositoryInterface
{
    // In a real implementation, this would use Doctrine ORM or another persistence mechanism
    private array $storage = [];
    private int $nextId = 1;

    public function save(SampleEntity $entity): void
    {
        if ($entity->getId() === 0) {
            // New entity
            $entity->id = $this->nextId++;
            $this->storage[$entity->getId()] = $entity;
        } else {
            // Existing entity
            $this->storage[$entity->getId()] = $entity;
        }
    }

    public function findById(int $id): ?SampleEntity
    {
        return $this->storage[$id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->storage);
    }

    public function deleteById(int $id): void
    {
        unset($this->storage[$id]);
    }
}