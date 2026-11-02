<?php

declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\NotificationTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<NotificationTemplate>
 */
class NotificationTemplateRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationTemplate::class);
    }

    public function save(NotificationTemplate $template): void
    {
        $this->getEntityManager()->persist($template);
        $this->getEntityManager()->flush();
    }

    public function remove(NotificationTemplate $template): void
    {
        $this->getEntityManager()->remove($template);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?NotificationTemplate
    {
        return $this->find($id);
    }

    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type]);
    }

    public function findByChannel(string $channel): array
    {
        return $this->findBy(['channel' => $channel]);
    }

    public function findByTypeAndChannel(string $type, string $channel): ?NotificationTemplate
    {
        return $this->findOneBy(['type' => $type, 'channel' => $channel]);
    }

    public function findActiveTemplates(): array
    {
        return $this->findBy(['status' => 'ACTIVE']);
    }

    public function findInactiveTemplates(): array
    {
        return $this->findBy(['status' => 'INACTIVE']);
    }
}
