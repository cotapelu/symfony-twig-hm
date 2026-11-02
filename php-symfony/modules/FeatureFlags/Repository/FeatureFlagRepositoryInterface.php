<?php

declare(strict_types=1);

namespace App\FeatureFlags\Repository;

use App\FeatureFlags\Entity\FeatureFlag;

interface FeatureFlagRepositoryInterface
{
    public function findByName(string $name): ?FeatureFlag;
    public function save(FeatureFlag $flag): FeatureFlag;
    public function findAll(): array;
}
