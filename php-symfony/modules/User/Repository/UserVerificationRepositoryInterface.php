<?php declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\UserVerification;

interface UserVerificationRepositoryInterface
{
    public function findByPhoneNumber(string $phoneNumber): ?UserVerification;

    public function save(UserVerification $verification): void;

    public function countRecentByPhone(string $phoneNumber, \DateTime $since): int;
}
