<?php

declare(strict_types=1);

namespace App\Notification\Service;

use App\Notifications\Domain\Entity\PushNotification;

class FCMService
{
    public function __construct(
        private string $serverKey,
        private string $senderId
    ) {}

    /**
     * Sends a push notification via Firebase Cloud Messaging.
     */
    public function send(PushNotification $notification)
    {
        // Expect device token in data['device_token']
        $deviceToken = $notification->getData()['device_token'] ?? null;
        if (!$deviceToken) {
            return false;
        }

        $payload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $notification->getTitle(),
                'body' => $notification->getBody(),
                'sound' => 'default',
            ],
            'data' => $notification->getData() ?? [],
        ];

        $url = 'https://fcm.googleapis.com/fcm/send';
        return $this->executeRequest($url, $payload);
    }

    protected function executeRequest(string $url, array $payload): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: key=' . $this->serverKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }
}
