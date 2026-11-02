<?php declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\UserVerification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserVerificationRepository extends ServiceEntityRepository implements UserVerificationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserVerification::class);
    }

    public function findByPhoneNumber(string $phoneNumber): ?UserVerification
    {
        return $this->findOneBy(['phoneNumber' => $phoneNumber]);
    }

    public function save(UserVerification $verification): void
    {
        $this->getEntityManager()->persist($verification);
        $this->getEntityManager()->flush();
    }

    public function countRecentByPhone(string $phoneNumber, \DateTime $since): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.phoneNumber = :phone')
            ->andWhere('v.lastPhoneSentAt >= :since')
            ->setParameter('phone', $phoneNumber)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
