<?php declare(strict_types=1);

namespace App\Notifications\Domain\Repository;

use App\Notifications\Domain\Entity\PushSubscription;

interface PushSubscriptionRepositoryInterface
{
    public function findById(string $id): ?PushSubscription;
    public function findByUserId(string $userId): array;
    public function findByUserAndToken(string $userId, string $token): ?PushSubscription;
    public function save(PushSubscription $subscription): void;
    public function remove(PushSubscription $subscription): void;
}
