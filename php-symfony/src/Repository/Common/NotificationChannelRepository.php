<?php declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\NotificationChannel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<NotificationChannel>
 */
class NotificationChannelRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    use FindByUserTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationChannel::class);
    }




    public function findByUserAndChannel(string $userId, string $channel): ?NotificationChannel
    {
        return $this->findOneBy(['userId' => $userId, 'channel' => $channel]);
    }

    public function findActiveByUserId(string $userId): array
    {
        return $this->findBy(['userId' => $userId, 'status' => 'ACTIVE']);
    }

    public function findByChannel(string $channel): array
    {
        return $this->findBy(['channel' => $channel, 'status' => 'ACTIVE']);
    }
}