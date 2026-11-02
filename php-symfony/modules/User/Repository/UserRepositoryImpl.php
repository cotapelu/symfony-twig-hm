<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserRepositoryImpl implements UserRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(string $id): ?User
    {
        return $this->entityManager->getRepository(User::class)->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    public function findByPhoneNumber(string $phoneNumber): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['phoneNumber' => $phoneNumber]);
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(User::class)->findAll();
    }

    public function find(string $id): ?User
    {
        return $this->entityManager->getRepository(User::class)->find($id);
    }

    public function countAll(): int
    {
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u');
        $qb->select('COUNT(u.id)');
        $result = $qb->getQuery()->getSingleScalarResult();

        return (int) $result;
    }

    public function countActiveUsersToday(): int
    {
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u');
        $qb->select('COUNT(u.id)');
        $qb->where('u.lastLoginAt >= :today');
        $qb->setParameter('today', new \DateTime('today midnight'));

        $result = $qb->getQuery()->getSingleScalarResult();

        return (int) $result;
    }

    public function countNewUsersToday(): int
    {
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u');
        $qb->select('COUNT(u.id)');
        $qb->where('u.createdAt >= :today');
        $qb->setParameter('today', new \DateTime('today midnight'));

        $result = $qb->getQuery()->getSingleScalarResult();

        return (int) $result;
    }

    public function findRecent(int $limit = 10): array
    {
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u');
        $qb->orderBy('u.createdAt', 'DESC');
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function findUsersPaginated(int $page, int $limit, string $search = '', string $status = 'all'): array
    {
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u');

        if (!empty($search)) {
            $qb->andWhere('
                u.email LIKE :search OR
                u.id LIKE :search OR
                EXISTS (
                    SELECT 1 FROM App\User\Entity\UserProfile p 
                    WHERE p.userId = u.id 
                    AND p.displayName LIKE :search
                )
            ');
            $qb->setParameter('search', '%' . $search . '%');
        }

        if ($status === 'active') {
            $qb->andWhere('u.isActive = true');
        } elseif ($status === 'inactive') {
            $qb->andWhere('u.isActive = false');
        }

        $qb->orderBy('u.createdAt', 'DESC');

        $countQb = clone $qb;
        $countQb->select('COUNT(u.id)');
        $totalCount = (int) $countQb->getQuery()->getSingleScalarResult();

        $qb->setFirstResult(($page - 1) * $limit);
        $qb->setMaxResults($limit);

        $users = $qb->getQuery()->getResult();

        return [
            'data' => $users,
            'total' => $totalCount,
        ];
    }

    public function getUserGrowth(?string $startDate = null, ?string $endDate = null): array
    {
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u');
        $qb->select('DATE(u.createdAt) as date, COUNT(u.id) as count');
        $qb->groupBy('DATE(u.createdAt)');
        $qb->orderBy('DATE(u.createdAt)', 'ASC');

        if ($startDate) {
            $qb->andWhere('u.createdAt >= :startDate');
            $qb->setParameter('startDate', new \DateTime($startDate));
        }

        if ($endDate) {
            $qb->andWhere('u.createdAt <= :endDate');
            $qb->setParameter('endDate', new \DateTime($endDate));
        }

        return $qb->getQuery()->getResult();
    }

    public function findBySocial(string $provider, string $socialId): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy([
            'socialProvider' => $provider,
            'socialId' => $socialId,
        ]);
    }

    public function findByVerificationToken(string $token): ?User
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')
            ->from(User::class, 'u')
            ->innerJoin('u.verification', 'v')
            ->where('v.verificationToken = :token')
            ->setParameter('token', $token)
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByPhoneAndVerification(string $phoneNumber, string $code): ?User
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')
            ->from(User::class, 'u')
            ->innerJoin('u.verification', 'v')
            ->where('u.phoneNumber = :phone')
            ->andWhere('v.phoneCode = :code')
            ->setParameter('phone', $phoneNumber)
            ->setParameter('code', $code)
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByEmailAndBiometric(string $email, string $biometricData): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'biometricData' => $biometricData,
        ]);
    }
}
