<?php

declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\FeatureFlag;
use App\Repository\Common\FeatureFlagRepository;
use Doctrine\ORM\EntityManagerInterface;

class FeatureFlagService
{
    private EntityManagerInterface $em;
    private FeatureFlagRepository $repository;

    public function __construct(
        EntityManagerInterface $em,
        FeatureFlagRepository $repository
    ) {
        $this->em = $em;
        $this->repository = $repository;
    }

    public function getFlag(string $key, ?string $environment = null)
    {
        if ($environment) {
            return $this->repository->findByKeyAndEnvironment($key, $environment);
        }
        return $this->repository->findOneBy(['key' => $key, 'enabled' => true]);
    }

    public function isEnabled(string $key, ?string $environment = null)
    {
        $flag = $this->getFlag($key, $environment);
        
        if (!$flag) {
            return false;
        }

        return $flag->isEnabled();
    }

    public function createFlag(string $key, string $environment = 'default', array $rules = [])
    {
        $flag = new FeatureFlag(
            bin2hex(random_bytes(16)),
            $key,
            $environment
        );
        $flag->setRules($rules);
        $flag->setEnabled(true);
        $flag->setStatus('ACTIVE');

        $this->em->persist($flag);
        $this->em->flush();

        return $flag;
    }

    public function updateFlag(string $key, array $rules)
    {
        $flag = $this->repository->findOneBy(['key' => $key]);
        
        if (!$flag) {
            return null;
        }

        $flag->setRules($rules);
        $this->em->flush();

        return $flag;
    }

    public function toggleFlag(string $key, bool $enabled)
    {
        $flag = $this->repository->findOneBy(['key' => $key]);
        
        if (!$flag) {
            return null;
        }

        $flag->setEnabled($enabled);
        $this->em->flush();

        return $flag;
    }

    public function deleteFlag(string $key)
    {
        $flag = $this->repository->findOneBy(['key' => $key]);
        
        if (!$flag) {
            return false;
        }

        $this->em->remove($flag);
        $this->em->flush();

        return true;
    }

    public function getAllFlags()
    {
        return $this->repository->findAll();
    }

    public function getFlagsByEnvironment(string $environment)
    {
        return $this->repository->findBy(['enabled' => true]);
    }
}