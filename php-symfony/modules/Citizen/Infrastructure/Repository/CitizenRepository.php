<?php

declare(strict_types=1);

namespace App\Citizen\Infrastructure\Repository;

use App\Citizen\Application\Repository\CitizenRepositoryInterface;
use App\Citizen\Domain\Entity\Citizen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Citizen>
 */
class CitizenRepository extends ServiceEntityRepository implements CitizenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Citizen::class);
    }

    public function findById(string $id): ?Citizen
    {
        return $this->find($id);
    }

    public function findByIdentityNumber(string $identityNumber): ?Citizen
    {
        return $this->findOneBy(['identityNumber' => $identityNumber]);
    }

    public function findByFullName(string $fullName): array
    {
        return $this->findBy(['fullName' => $fullName]);
    }

    public function findByPhoneNumber(string $phoneNumber): array
    {
        return $this->findBy(['phoneNumber' => $phoneNumber]);
    }

    public function findByEmail(string $email): array
    {
        return $this->findBy(['email' => $email]);
    }

    public function findByHouseholdId(string $householdId, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.household', 'h')
            ->where('h.id = :householdId')
            ->setParameter('householdId', $householdId)
            ->setMaxResults($limit)
            ->setFirstResult($offset);
        
        return $qb->getQuery()->getResult();
    }

    public function search(string $query, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('c')->where('c.fullName LIKE :query OR c.identityNumber LIKE :query')->setParameter('query', '%' . $query . '%');
        if (!empty($filters['gender'])) { $qb->andWhere('c.gender = :gender')->setParameter('gender', $filters['gender']); }
        if (!empty($filters['minAge'])) { $maxDob = (new \DateTime())->sub(new \DateInterval('P' . $filters['minAge'] . 'Y')); $qb->andWhere('c.dateOfBirth <= :maxDob')->setParameter('maxDob', $maxDob); }
        if (!empty($filters['maxAge'])) { $minDob = (new \DateTime())->sub(new \DateInterval('P' . $filters['maxAge'] . 'Y')); $qb->andWhere('c.dateOfBirth >= :minDob')->setParameter('minDob', $minDob); }
        if (!empty($filters['specialCategories'])) {
            $first = $filters['specialCategories'][0]; $qb->andWhere('JSON_CONTAINS(c.specialCategories, :category) = 1')->setParameter('category', json_encode($first));
            for ($i = 1; $i < count($filters['specialCategories']); $i++) { $qb->orWhere('JSON_CONTAINS(c.specialCategories, :category' . $i . ') = 1')->setParameter('category' . $i, json_encode($filters['specialCategories'][$i])); }
        }
        if (isset($filters['householdId'])) { $qb->innerJoin('c.household', 'h')->andWhere('h.id = :householdId')->setParameter('householdId', $filters['householdId']); }
        return $qb->getQuery()->getResult();
    }

    public function findBySpecialCategories(array $categories): array
    {
        if (empty($categories)) {
            return [];
        }

        $qb = $this->createQueryBuilder('c');
        
        foreach ($categories as $i => $category) {
            if ($i === 0) {
                $qb->where('JSON_CONTAINS(c.specialCategories, :category' . $i . ') = 1')
                   ->setParameter('category' . $i, json_encode($category));
            } else {
                $qb->orWhere('JSON_CONTAINS(c.specialCategories, :category' . $i . ') = 1')
                   ->setParameter('category' . $i, json_encode($category));
            }
        }

        return $qb->getQuery()->getResult();
    }

    public function findElderly(int $minAge = 60, int $limit = 50, int $offset = 0): array
    {
        $maxDob = (new \DateTime())->sub(new \DateInterval('P' . $minAge . 'Y'));
        
        return $this->createQueryBuilder('c')
            ->where('c.dateOfBirth <= :maxDob')
            ->setParameter('maxDob', $maxDob)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findChildren(int $maxAge = 16, int $limit = 50, int $offset = 0): array
    {
        $minDob = (new \DateTime())->sub(new \DateInterval('P' . $maxAge . 'Y'));
        
        return $this->createQueryBuilder('c')
            ->where('c.dateOfBirth >= :minDob')
            ->setParameter('minDob', $minDob)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findAll(int $limit = 50, int $offset = 0): array
    {
        return $this->findBy([], [], ['id' => 'DESC'], $limit, $offset);
    }

    public function count(array $criteria = []): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)');

        if (!empty($criteria['specialCategories'])) {
            foreach ($criteria['specialCategories'] as $i => $category) {
                if ($i === 0) {
                    $qb->where('JSON_CONTAINS(c.specialCategories, :category' . $i . ') = 1')
                       ->setParameter('category' . $i, json_encode($category));
                } else {
                    $qb->orWhere('JSON_CONTAINS(c.specialCategories, :category' . $i . ') = 1')
                       ->setParameter('category' . $i, json_encode($category));
                }
            }
        }

        if (!empty($criteria['minAge'])) {
            $maxDob = (new \DateTime())->sub(new \DateInterval('P' . $criteria['minAge'] . 'Y'));
            $qb->andWhere('c.dateOfBirth <= :maxDob')
               ->setParameter('maxDob', $maxDob);
        }

        if (!empty($criteria['maxAge'])) {
            $minDob = (new \DateTime())->sub(new \DateInterval('P' . $criteria['maxAge'] . 'Y'));
            $qb->andWhere('c.dateOfBirth >= :minDob')
               ->setParameter('minDob', $minDob);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function save(Citizen $citizen): void
    {
        $this->getEntityManager()->persist($citizen);
        $this->getEntityManager()->flush();
    }

    public function delete(Citizen $citizen): void
    {
        $this->getEntityManager()->remove($citizen);
        $this->getEntityManager()->flush();
    }

    public function getStatistics(): array
    {
        $total = $this->count();
        $elderlyCount = $this->count(['minAge' => 60]);
        $childrenCount = $this->count(['maxAge' => 16]);
        $maleCount = $this->createQueryBuilder('c')->select('COUNT(c.id)')->where('c.gender = :gender')->setParameter('gender', 'Male')->getQuery()->getSingleScalarResult();
        $femaleCount = $this->createQueryBuilder('c')->select('COUNT(c.id)')->where('c.gender = :gender')->setParameter('gender', 'Female')->getQuery()->getSingleScalarResult();
        $poorCount = $this->count(['specialCategories' => ['Poor']]);
        $households = (int) $this->createQueryBuilder('c')->select('COUNT(DISTINCT c.household)')->where('c.household IS NOT NULL')->getQuery()->getSingleScalarResult();
        return ['total'=>$total, 'elderly'=>(int)$elderlyCount, 'children'=>(int)$childrenCount, 'male'=>(int)$maleCount, 'female'=>(int)$femaleCount, 'poor'=>(int)$poorCount, 'households'=>$households];
    }
}