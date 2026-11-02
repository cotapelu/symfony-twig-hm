<?php

declare(strict_types=1);

namespace App\SampleModule\Application;

/**
 * Sample Application Service (Use Case)
 * Contains business logic and orchestrates domain objects
 */
class SampleApplicationService
{
    private SampleDomainService $domainService;
    private SampleRepositoryInterface $repository;

    public function __construct(
        SampleDomainService $domainService,
        SampleRepositoryInterface $repository
    ) {
        $this->domainService = $domainService;
        $this->repository = $repository;
    }

    public function createSample(string $name, int $value): void
    {
        // Business logic validation
        if (empty($name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }
        
        if ($value < 0) {
            throw new \InvalidArgumentException('Value must be non-negative');
        }

        // Create domain entity
        $entity = new \App\Modules\SampleModule\Domain\SampleEntity(
            $name,
            $value
        );

        // Persist via repository
        $this->repository->save($entity);
    }

    public function getSampleById(int $id): ?SampleEntity
    {
        return $this->repository->findById($id);
    }
}