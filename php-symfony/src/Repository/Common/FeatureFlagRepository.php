<?php

declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\FeatureFlag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FeatureFlagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureFlag::class);
    }

    public function findOneByKey(string $key): ?FeatureFlag
    {
        return $this->findOneBy(['key' => $key]);
    }

    public function findByKeyAndEnvironment(string $key, string $environment): ?FeatureFlag
    {
        return $this->findOneBy([
            'key' => $key,
            'environment' => $environment,
            'enabled' => true,
        ]);
    }

    public function findEnabledFlags(?string $environment = null): array
    {
        $criteria = ['enabled' => true];
        
        if ($environment !== null) {
            $criteria['environment'] = $environment;
        }

        return $this->findBy($criteria);
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }
}