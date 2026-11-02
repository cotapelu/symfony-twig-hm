<?php declare(strict_types=1);
namespace App\Education\Repository;
use App\Education\Entity\Tutor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TutorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tutor::class);
    }

    public function findBySubject(string $subject): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.subject = :subject')
            ->setParameter('subject', $subject)
            ->andWhere('t.verified = :verified')
            ->setParameter('verified', true)
            ->orderBy('t.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findVerifiedTutors(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.verified = :verified')
            ->setParameter('verified', true)
            ->orderBy('t.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEducation(string $education): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.education = :education')
            ->setParameter('education', $education)
            ->orderBy('t.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Tutor $tutor): void
    {
        $this->getEntityManager()->persist($tutor);
        $this->getEntityManager()->flush();
    }
}
