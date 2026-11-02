<?php

declare(strict_types=1);

namespace App\Notification\Repository;

use App\Notification\Entity\NotificationTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationTemplate::class);
    }

    public function save(NotificationTemplate $template): void
    {
        $this->getEntityManager()->persist($template);
        $this->getEntityManager()->flush();
    }

    public function delete(NotificationTemplate $template): void
    {
        $this->getEntityManager()->remove($template);
        $this->getEntityManager()->flush();
    }

    public function findByName(string $name): ?NotificationTemplate
    {
        return $this->createQueryBuilder('t')
            ->where('t.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    public function findByChannel(string $channel): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.channel = :channel')
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getResult();
    }

    public function findByLanguage(string $language): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.language = :language')
            ->setParameter('language', $language)
            ->getQuery()
            ->getResult();
    }

    public function findActiveTemplates(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.isEnabled = true')
            ->getQuery()
            ->getResult();
    }

    public function findByTypeAndChannel(string $type, string $channel): ?NotificationTemplate
    {
        return $this->createQueryBuilder('t')
            ->where('t.type = :type')
            ->andWhere('t.channel = :channel')
            ->andWhere('t.isEnabled = true')
            ->setParameter('type', $type)
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function enableTemplate(string $name): void
    {
        $template = $this->findByName($name);
        if ($template) {
            $template->setIsEnabled(true);
            $this->save($template);
        }
    }

    public function disableTemplate(string $name): void
    {
        $template = $this->findByName($name);
        if ($template) {
            $template->setIsEnabled(false);
            $this->save($template);
        }
    }
}