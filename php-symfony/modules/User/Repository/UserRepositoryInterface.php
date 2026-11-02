<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByPhoneNumber(string $phoneNumber): ?User;

    public function findBySocial(string $provider, string $socialId): ?User;

    public function findByVerificationToken(string $token): ?User;

    public function findByPhoneAndVerification(string $phoneNumber, string $code): ?User;

    public function findByEmailAndBiometric(string $email, string $biometricData): ?User;

    public function save(User $user): void;

    public function getEntityManager(): \Doctrine\ORM\EntityManagerInterface;

    public function findAll(): array;

    public function find(string $id): ?User;

    public function countAll(): int;

    public function countActiveUsersToday(): int;

    public function countNewUsersToday(): int;

    public function findRecent(int $limit = 10): array;

    public function findUsersPaginated(int $page, int $limit, string $search = '', string $status = 'all'): array;

    public function getUserGrowth(?string $startDate = null, ?string $endDate = null): array;
}
