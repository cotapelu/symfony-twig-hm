<?php

declare(strict_types=1);

namespace App\BusinessAccounts\Service;

use App\BusinessAccounts\Entity\BusinessAccount;
use App\BusinessAccounts\Repository\BusinessAccountRepositoryInterface;
use App\Shared\Exception\AuthorizationException;
use App\Shared\Exception\NotFoundException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class BusinessAccountsService
{
    private BusinessAccountRepositoryInterface $repository;
    private LoggerInterface $logger;

    public function __construct(
        BusinessAccountRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function createAccount(int $userId, string $businessName, string $businessType): BusinessAccount
    {
        $account = new BusinessAccount($userId, $businessName, $businessType);
        $saved = $this->repository->save($account);
        $this->logger->info('Business account created', [
            'account_id' => $saved->getId(),
            'user_id' => $userId,
            'business_name' => $businessName,
            'business_type' => $businessType,
        ]);
        return $saved;
    }

    public function getAccount(int $id, int $userId): BusinessAccount
    {
        return $this->requireOwnership($id, $userId);
    }

    public function getUserAccounts(int $userId, int $limit = 20, int $offset = 0): array
    {
        return $this->repository->findByUserId((string)$userId, $limit, $offset);
    }

    public function updateTaxId(int $id, ?string $taxId, int $userId): BusinessAccount
    {
        $account = $this->requireOwnership($id, $userId);
        $account->setTaxId($taxId);
        $saved = $this->repository->save($account);
        $this->logger->info('Business account tax ID updated', [
            'account_id' => $id,
            'user_id' => $userId,
            'tax_id' => $taxId,
        ]);
        return $saved;
    }

    public function updateContact(int $id, ?string $phone, ?string $email, int $userId): BusinessAccount
    {
        $account = $this->requireOwnership($id, $userId);
        $account->setPhone($phone);
        $account->setEmail($email);
        $saved = $this->repository->save($account);
        $this->logger->info('Business account contact updated', [
            'account_id' => $id,
            'user_id' => $userId,
            'phone' => $phone,
            'email' => $email,
        ]);
        return $saved;
    }

    public function setWhatsappBusiness(int $id, ?string $whatsappId, int $userId): BusinessAccount
    {
        $account = $this->requireOwnership($id, $userId);
        $account->setWhatsappBusinessId($whatsappId);
        $saved = $this->repository->save($account);
        $this->logger->info('Business account WhatsApp ID updated', [
            'account_id' => $id,
            'user_id' => $userId,
            'whatsapp_business_id' => $whatsappId,
        ]);
        return $saved;
    }

    public function verifyAccount(int $id, int $userId): BusinessAccount
    {
        $account = $this->requireOwnership($id, $userId);
        $account->setVerified(true);
        $saved = $this->repository->save($account);
        $this->logger->info('Business account verified', [
            'account_id' => $id,
            'user_id' => $userId,
        ]);
        return $saved;
    }

    public function deleteAccount(int $id, int $userId): bool
    {
        $account = $this->requireOwnership($id, $userId);
        $this->logger->info('Business account deleted', [
            'account_id' => $id,
            'user_id' => $userId,
        ]);
        return $this->repository->delete($account->getId());
    }



    public function searchUserAccounts(int $userId, string $name, int $limit = 20, int $offset = 0): array
    {
        return $this->repository->searchByUserIdAndName($userId, $name, $limit, $offset);
    }



    /**
     * Assert that the business account belongs to the given user.
     * Throws NotFoundException if not found, AuthorizationException if not owner.
     */
    private function requireOwnership(int $accountId, int $userId): BusinessAccount
    {
        $account = $this->repository->findById($accountId);
        if (!$account) {
            throw new NotFoundException('Business account not found');
        }
        if ($account->getUserId() !== $userId) {
            throw new AuthorizationException('Access denied: you do not own this business account');
        }
        return $account;
    }

    public function getVerifiedAccounts(): array
    {
        return $this->repository->findVerified();
    }

    public function searchByName(string $name): array
    {
        return $this->repository->searchByName($name);
    }

    public function getAccountsByType(string $type): array
    {
        return $this->repository->findByType($type);
    }
}
