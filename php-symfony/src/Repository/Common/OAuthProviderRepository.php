<?php declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\OAuthProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<OAuthProvider>
 */
class OAuthProviderRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    use FindByUserTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthProvider::class);
    }




    public function findByProvider(string $userId, string $provider): ?OAuthProvider
    {
        return $this->findOneBy(['userId' => $userId, 'provider' => $provider]);
    }

    public function findByProviderUserId(string $provider, string $providerUserId): ?OAuthProvider
    {
        return $this->findOneBy(['provider' => $provider, 'providerUserId' => $providerUserId]);
    }

    public function countByProvider(string $provider): int
    {
        return $this->count(['provider' => $provider]);
    }
}