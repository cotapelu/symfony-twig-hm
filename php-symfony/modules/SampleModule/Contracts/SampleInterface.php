<?php

declare(strict_types=1);

namespace App\SampleModule\Contracts;

/**
 * Sample Interface defining contracts between layers
 * Used for dependency injection and loose coupling
 */
interface SampleRepositoryInterface
{
    public function save(\App\Modules\SampleModule\Domain\SampleEntity $entity): void;
    
    public function findById(int $id): ?\App\Modules\SampleModule\Domain\SampleEntity;
    
    public function findAll(): array;
    
    public function deleteById(int $id): void;
}