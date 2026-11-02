<?php

namespace App\Module\Sample\Infrastructure;

interface SampleRepositoryInterface
{
    public function find(int $id): ?SampleEntity;
    public function save(SampleEntity $entity): void;
}