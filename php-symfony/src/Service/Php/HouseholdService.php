<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\Household;
use App\Repository\Php\HouseholdRepository;
use Doctrine\ORM\EntityManagerInterface;

class HouseholdService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HouseholdRepository $repository
    ) {}

    public function createHousehold(string $address, ?string $wardId = null, ?string $headId = null)
    {
        if (empty(trim($address))) throw new \InvalidArgumentException('Address is required');
        $id = bin2hex(random_bytes(16));
        $household = new Household($id, $address);
        $household->setWardId($wardId);
        $household->setHeadId($headId);
        $this->em->persist($household);
        $this->em->flush();
        return $household;
    }

    public function getHousehold(string $id)
    {
        $household = $this->repository->find($id);
        if (!$household) throw new \RuntimeException('Household not found');
        return $household;
    }

    public function updateHousehold(string $id, array $data)
    {
        $household = $this->getHousehold($id);
        $this->applyHouseholdUpdates($household, $data);
        $this->em->flush();
        return $household;
    }

    public function deleteHousehold(string $id)
    {
        $household = $this->getHousehold($id);
        $this->em->remove($household);
        $this->em->flush();
    }

    public function findByWardId(string $wardId)
    {
        return $this->repository->findBy(['wardId' => $wardId]);
    }

    private function applyHouseholdUpdates(Household $household, array $data): void
    {
        if (isset($data['address'])) {
            $addr = trim($data['address']);
            if ($addr === '') throw new \InvalidArgumentException('Address is required');
            $household->setAddress($addr);
        }
        if (array_key_exists('wardId', $data)) {
            $household->setWardId($data['wardId']);
        }
        if (array_key_exists('headId', $data)) {
            $household->setHeadId($data['headId']);
        }
    }
}
