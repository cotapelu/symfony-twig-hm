<?php

declare(strict_types=1);

namespace App\BusinessAccount\Service;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Repository\BusinessRepository;
use Doctrine\ORM\EntityManagerInterface;

class BusinessVerificationService
{
    private EntityManagerInterface $em;
    private BusinessRepository $businessRepo;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        /** @var BusinessRepository $businessRepo */
        $businessRepo = $em->getRepository(Business::class);
        $this->businessRepo = $businessRepo;
    }

    public function verifyBusiness(Business $business)
    {
        $business->setIsVerified(true);
        $business->setStatus('active');
        $this->em->flush();

        return $business;
    }

    public function approveBusiness(Business $business)
    {
        $business->setStatus('active');
        $this->em->flush();

        return $business;
    }

    public function suspendBusiness(Business $business)
    {
        $business->setStatus('suspended');
        $this->em->flush();

        return $business;
    }

    public function getBusinessById(int $id)
    {
        return $this->businessRepo->find($id);
    }
}
