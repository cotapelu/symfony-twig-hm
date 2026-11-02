<?php

declare(strict_types=1);

namespace App\Tests\Support\Helper;

use Doctrine\ORM\EntityManagerInterface;

class DatabaseHelper
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function clearDatabase(): void
    {
        // Disable SQL filters if any
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        if (empty($metadata)) {
            return;
        }

        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        try {
            $platform = $connection->getDatabasePlatform();
            foreach ($metadata as $class) {
                $table = $class->getTableName();
                $connection->executeStatement($platform->getTruncateTableSQL($table, true));
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function persistAndFlush(object $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    public function removeAndFlush(object $entity): void
    {
        $this->em->remove($entity);
        $this->em->flush();
    }
}
