<?php

declare(strict_types=1);

namespace App\BusinessAccount\Service;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\ApiKey;
use App\BusinessAccount\Repository\ApiKeyRepository;
use Doctrine\ORM\EntityManagerInterface;

class ApiKeyService
{
    private EntityManagerInterface $em;
    private ApiKeyRepository $apiKeyRepo;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        /** @var ApiKeyRepository $apiKeyRepo */
        $apiKeyRepo = $em->getRepository(ApiKey::class);
        $this->apiKeyRepo = $apiKeyRepo;
    }

    public function generateApiKey(Business $business, ?string $name = null)
    {
        $this->apiKeyRepo->revokeOldKeys($business->getId(), 2);

        $apiKey = new ApiKey();
        $apiKey->setBusiness($business);
        $apiKey->setName($name ?? 'API Key ' . (count($this->apiKeyRepo->findByBusiness($business->getId())) + 1));
        $apiKey->setKey(bin2hex(random_bytes(32)));
        $apiKey->setIsActive(true);

        $this->em->persist($apiKey);
        $this->em->flush();

        return $apiKey;
    }

    public function revokeApiKey(ApiKey $apiKey)
    {
        $apiKey->setIsActive(false);
        $this->em->flush();

        return $apiKey;
    }

    public function validateApiKey(string $key)
    {
        $apiKey = $this->apiKeyRepo->findByKey($key);
        if ($apiKey) {
            return $apiKey->getBusiness();
        }

        return null;
    }

    public function getActiveKeys(Business $business)
    {
        return $this->apiKeyRepo->findActiveByBusiness($business->getId());
    }
}
