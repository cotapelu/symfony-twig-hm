<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByPhoneNumber(string $phoneNumber): ?User
    {
        return $this->findOneBy(['phoneNumber' => $phoneNumber]);
    }

    public function findBySocial(string $provider, string $socialId): ?User
    {
        return $this->findOneBy(['socialProvider' => $provider, 'socialId' => $socialId]);
    }

    public function findByVerificationToken(string $token): ?User
    {
        return $this->findOneBy(['verificationToken' => $token]);
    }

    public function findByPhoneAndVerification(string $phoneNumber, string $code): ?User
    {
        return $this->findOneBy(['phoneNumber' => $phoneNumber, 'verificationToken' => $code]);
    }

    public function findByEmailAndBiometric(string $email, string $biometricData): ?User
    {
        return $this->findOneBy(['email' => $email, 'biometricData' => $biometricData]);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActiveUsersToday(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :today')
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countNewUsersToday(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :today')
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.profile', 'p')
            ->addSelect('p')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findUsersPaginated(int $page, int $limit, string $search = '', string $status = 'all'): array
    {
        $qb = $this->createQueryBuilder('u');

        if ($search) {
            $qb->where($qb->expr()->orX(
                $qb->expr()->like('u.email', ':search'),
                $qb->expr()->like('u.id', ':search')
            ))->setParameter('search', "%{$search}%");
        }

        if ($status === 'active') {
            $qb->andWhere('u.isActive = true');
        } elseif ($status === 'inactive') {
            $qb->andWhere('u.isActive = false');
        }

        $qbClone = clone $qb;
        $total = count($qbClone->getQuery()->getResult());

        $qb->orderBy('u.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'data' => $qb->getQuery()->getResult(),
            'total' => $total,
        ];
    }

    public function getUserGrowth(?string $startDate = null, ?string $endDate = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('DATE(u.createdAt) as date, COUNT(u.id) as count')
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        if ($startDate) {
            $qb->andWhere('u.createdAt >= :startDate')
                ->setParameter('startDate', new \DateTime($startDate));
        }

        if ($endDate) {
            $qb->andWhere('u.createdAt <= :endDate')
                ->setParameter('endDate', new \DateTime($endDate));
        }

        return $qb->getQuery()->getResult();
    }

    public function findById(string $id): ?User
    {
        return $this->find($id);
    }

    public function findOne(string $id): ?User
    {
        return $this->find($id);
    }
}
