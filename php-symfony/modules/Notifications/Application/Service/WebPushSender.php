<?php declare(strict_types=1);

namespace App\Notifications\Application\Service;

class WebPushSender implements DeviceSenderInterface
{
    public function __construct(private ?string $endpoint = null) {}

    public function supports(string $deviceType): bool
    {
        return $deviceType === \App\Notifications\Domain\Entity\PushSubscription::DEVICE_TYPE_WEB;
    }

    public function send(string $token, array $payload): bool
    {
        // Not implemented; mock for now
        error_log('WebPush not implemented; token: ' . substr($token, 0, 10) . '...');
        return true;
    }
}
