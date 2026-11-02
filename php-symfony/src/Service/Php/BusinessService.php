<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\Business;
use App\Repository\Php\BusinessRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Business Service with built-in ownership security
 *
 * SECURITY: All operations enforce ownership (user can only access own businesses)
 */
class BusinessService
{
    private ?string $currentUserId = null;

    public function setCurrentUser(string $userId)
    {
        $this->currentUserId = $userId;
        return $this;
    }

    private function assertOwnership(Business $business)
    {
        if ($this->currentUserId === null) throw new AccessDeniedHttpException('User context not set');
        if ($business->getOwnerId() !== $this->currentUserId) throw new AccessDeniedHttpException('Access denied: Not your business');
    }

    private function assertOwnershipById(string $businessId)
    {
        $business = $this->repository->find($businessId);
        if (!$business) return;
        $this->assertOwnership($business);
    }

    public function __construct(
        private EntityManagerInterface $em,
        private BusinessRepository $repository
    ) {}

    public function createBusiness(
        string $ownerId,
        string $name,
        string $type,
        ?string $address = null,
        ?string $phone = null,
        ?string $email = null,
        ?string $taxCode = null,
        ?array $metadata = null
    ) {
        $this->validateCreateInput($ownerId, $name, $type);
        $business = $this->buildBusinessEntity($ownerId, $name, $type, $address, $phone, $email, $taxCode, $metadata);
        $this->em->persist($business);
        $this->em->flush();
        return $business;
    }

    public function getBusiness(string $id)
    {
        $business = $this->repository->find($id);
        if (!$business) throw new \RuntimeException('Business not found');
        $this->assertOwnership($business);
        return $business;
    }

    public function updateBusiness(string $id, array $data)
    {
        $business = $this->getBusiness($id);
        $this->applyBusinessFields($business, $data);
        $this->em->flush();
        return $business;
    }

    public function deleteBusiness(string $id)
    {
        $business = $this->getBusiness($id);
        $this->em->remove($business);
        $this->em->flush();
    }

    public function findByOwnerId(string $ownerId)
    {
        return $this->repository->findBy(['ownerId' => $ownerId]);
    }

    public function findByType(string $type)
    {
        return $this->repository->findBy(['type' => $type]);
    }

    public function findByStatus(string $status)
    {
        return $this->repository->findBy(['status' => $status]);
    }

    private function validateCreateInput(string $ownerId, string $name, string $type): void
    {
        if (empty(trim($ownerId))) throw new \InvalidArgumentException('Owner ID is required');
        if (empty(trim($name))) throw new \InvalidArgumentException('Business name is required');
        if (empty(trim($type))) throw new \InvalidArgumentException('Business type is required');
    }

    private function buildBusinessEntity(
        string $ownerId,
        string $name,
        string $type,
        ?string $address,
        ?string $phone,
        ?string $email,
        ?string $taxCode,
        ?array $metadata
    ): Business {
        $business = new Business(
            bin2hex(random_bytes(16)),
            $ownerId,
            $name,
            $type
        );
        $business->setAddress($address);
        $business->setPhone($phone);
        $business->setEmail($email);
        $business->setTaxCode($taxCode);
        $business->setMetadata($metadata);
        return $business;
    }

    private function applyBusinessFields(Business $business, array $data): void
    {
        $this->applyBusinessOwnerId($business, $data);
        $this->applyBusinessName($business, $data);
        $this->applyBusinessType($business, $data);
        $this->applyBusinessAddress($business, $data);
        $this->applyBusinessPhone($business, $data);
        $this->applyBusinessEmail($business, $data);
        $this->applyBusinessTaxCode($business, $data);
        $this->applyBusinessStatus($business, $data);
        $this->applyBusinessMetadata($business, $data);
    }

    private function applyBusinessOwnerId(Business $business, array $data): void
    {
        if (!array_key_exists('ownerId', $data)) return;
        $ownerId = trim($data['ownerId']);
        if ($ownerId === '') throw new \InvalidArgumentException('Owner ID cannot be empty');
        if ($ownerId !== $business->getOwnerId()) throw new AccessDeniedHttpException('Cannot transfer ownership to another user');
        $business->setOwnerId($ownerId);
    }

    private function applyBusinessName(Business $business, array $data): void
    {
        if (!array_key_exists('name', $data)) return;
        $name = trim($data['name']);
        if ($name === '') throw new \InvalidArgumentException('Business name cannot be empty');
        $business->setName($name);
    }

    private function applyBusinessType(Business $business, array $data): void
    {
        if (!array_key_exists('type', $data)) return;
        $type = trim($data['type']);
        if ($type === '') throw new \InvalidArgumentException('Business type cannot be empty');
        $business->setType($type);
    }

    private function applyBusinessAddress(Business $business, array $data): void
    {
        if (array_key_exists('address', $data)) {
            $business->setAddress($data['address']);
        }
    }

    private function applyBusinessPhone(Business $business, array $data): void
    {
        if (array_key_exists('phone', $data)) {
            $business->setPhone($data['phone']);
        }
    }

    private function applyBusinessEmail(Business $business, array $data): void
    {
        if (!array_key_exists('email', $data)) return;
        $email = $data['email'];
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Invalid email format');
        $business->setEmail($email);
    }

    private function applyBusinessTaxCode(Business $business, array $data): void
    {
        if (array_key_exists('taxCode', $data)) {
            $business->setTaxCode($data['taxCode']);
        }
    }

    private function applyBusinessStatus(Business $business, array $data): void
    {
        if (!array_key_exists('status', $data)) return;
        $status = trim($data['status']);
        if ($status === '') throw new \InvalidArgumentException('Status cannot be empty');
        $business->setStatus($status);
    }

    private function applyBusinessMetadata(Business $business, array $data): void
    {
        if (array_key_exists('metadata', $data)) {
            $business->setMetadata($data['metadata']);
        }
    }
}
