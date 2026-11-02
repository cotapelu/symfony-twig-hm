<?php

declare(strict_types=1);

namespace App\Government\Infrastructure\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\Government\Domain\Entity\Citizen;
use App\Government\Domain\Repository\CitizenRepositoryInterface;
use App\Government\Domain\ValueObject\CitizenId;
use App\Government\Domain\ValueObject\Address;

class CitizenRepository implements CitizenRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function findById(CitizenId $id): ?Citizen
    {
        return $this->entityManager->find(Citizen::class, $id->getValue());
    }

    public function findByIdentityNumber(string $identityNumber): ?Citizen
    {
        return $this->entityManager->getRepository(Citizen::class)
            ->findOneBy(['identityNumber' => $identityNumber]);
    }

    public function findByEmail(string $email): ?Citizen
    {
        return $this->entityManager->getRepository(Citizen::class)
            ->findOneBy(['email' => $email]);
    }

    public function findByPhone(string $phone): ?Citizen
    {
        return $this->entityManager->getRepository(Citizen::class)
            ->findOneBy(['phoneNumber' => $phone]);
    }

    public function findByAddress(Address $address): array
    {
        return $this->entityManager->getRepository(Citizen::class)
            ->findBy([
                'registeredAddress.province' => $address->province,
                'registeredAddress.district' => $address->district,
                'registeredAddress.ward' => $address->ward,
            ]);
    }

    public function findByAgeRange(int $minAge, int $maxAge): array
    {
        $qb = $this->entityManager->getRepository(Citizen::class)
            ->createQueryBuilder('c');
        return $qb->where('c.age >= :minAge AND c.age <= :maxAge')
            ->setParameter('minAge', $minAge)
            ->setParameter('maxAge', $maxAge)
            ->getQuery()
            ->getResult();
    }

    public function findDeceased(): array
    {
        return $this->entityManager->getRepository(Citizen::class)
            ->findBy(['isDeceased' => true]);
    }

    public function findActive(): array
    {
        return $this->entityManager->getRepository(Citizen::class)
            ->findBy(['isActive' => true]);
    }

    public function findByHousehold(string $householdId): array
    {
        return $this->entityManager->getRepository(Citizen::class)
            ->findBy(['householdId' => $householdId]);
    }

    public function search(string $query): array
    {
        return $this->findAll(['search' => $query]);
    }

    public function save(Citizen $citizen): void
    {
        $this->entityManager->persist($citizen);
        $this->entityManager->flush();
    }

    public function delete(CitizenId $id): void
    {
        $citizen = $this->findById($id);
        if ($citizen) {
            $this->entityManager->remove($citizen);
            $this->entityManager->flush();
        }
    }

    public function count(array $filters = []): int
    {
        $qb = $this->entityManager->getRepository(Citizen::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)');

        if (isset($filters['isActive'])) {
            $qb->andWhere('c.isActive = :isActive')
                ->setParameter('isActive', $filters['isActive']);
        }

        if (isset($filters['isDeceased'])) {
            $qb->andWhere('c.isDeceased = :isDeceased')
                ->setParameter('isDeceased', $filters['isDeceased']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $qb = $this->entityManager->getRepository(Citizen::class)->createQueryBuilder('c');
        // Apply filters
        if (isset($filters['search'])) {
            $qb->andWhere('c.name LIKE :search OR c.identityNumber LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }
        if (isset($filters['isActive'])) {
            $qb->andWhere('c.isActive = :isActive')->setParameter('isActive', $filters['isActive']);
        }
        if (isset($filters['isDeceased'])) {
            $qb->andWhere('c.isDeceased = :isDeceased')->setParameter('isDeceased', $filters['isDeceased']);
        }
        // Pagination
        $qb->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage);
        return $qb->getQuery()->getResult();
    }

    public function getCitizensByProvince(): array
    {
        $qb = $this->entityManager->getRepository(Citizen::class)->createQueryBuilder('c');
        $qb->select('c.province as province, COUNT(c.id) as count')
           ->groupBy('c.province')
           ->orderBy('c.province');
        $results = $qb->getQuery()->getResult();
        $arr = [];
        foreach ($results as $row) {
            $arr[$row['province']] = (int)$row['count'];
        }
        return $arr;
    }

    public function getCitizensByAgeRange(): array
    {
        // Age range grouping: 0-17, 18-30, 31-45, 46-60, 60+
        $qb = $this->entityManager->getRepository(Citizen::class)->createQueryBuilder('c');
        $qb->select("CASE
            WHEN c.age < 18 THEN '0-17'
            WHEN c.age <= 30 THEN '18-30'
            WHEN c.age <= 45 THEN '31-45'
            WHEN c.age <= 60 THEN '46-60'
            ELSE '60+' END as age_group,
            COUNT(c.id) as count")
           ->groupBy('age_group')
           ->orderBy('age_group');
        $results = $qb->getQuery()->getResult();
        $arr = [];
        foreach ($results as $row) {
            $arr[$row['age_group']] = (int)$row['count'];
        }
        return $arr;
    }
}