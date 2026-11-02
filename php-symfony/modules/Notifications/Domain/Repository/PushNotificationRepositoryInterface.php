<?php declare(strict_types=1);

namespace App\Notifications\Domain\Repository;

use App\Notifications\Domain\Entity\PushNotification;

interface PushNotificationRepositoryInterface
{
    public function findById(string $id): ?PushNotification;
    public function findByUserId(string $userId, ?string $status = null, ?int $limit = null): array;
    public function findPendingScheduled(\DateTimeInterface $now): array;
    public function findFailedRetryable(): array;
    public function save(PushNotification $notification): void;
    public function remove(PushNotification $notification): void;
}
