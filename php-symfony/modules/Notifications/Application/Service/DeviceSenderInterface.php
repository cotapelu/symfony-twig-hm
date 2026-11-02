<?php declare(strict_types=1);

namespace App\Notifications\Application\Service;

interface DeviceSenderInterface
{
    /**
     * Checks if this sender supports the given device type.
     */
    public function supports(string $deviceType): bool;

    /**
     * Sends a push notification to the specified device token.
     * Returns true on success (HTTP 200 and success count > 0 for FCM/APNs).
     */
    public function send(string $token, array $payload): bool;
}
