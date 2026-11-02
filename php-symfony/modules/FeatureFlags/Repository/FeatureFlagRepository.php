<?php

declare(strict_types=1);

namespace App\FeatureFlags\Repository;

use App\FeatureFlags\Entity\FeatureFlag;
use Doctrine\ORM\EntityManagerInterface;

class FeatureFlagRepository implements FeatureFlagRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function findByName(string $name): ?FeatureFlag
    {
        return $this->em->getRepository(FeatureFlag::class)->findOneBy(['name' => $name]);
    }

    public function save(FeatureFlag $flag): FeatureFlag
    {
        $this->em->persist($flag);
        $this->em->flush();
        return $flag;
    }

    public function findAll(): array
    {
        return $this->em->getRepository(FeatureFlag::class)->findAll();
    }
}
