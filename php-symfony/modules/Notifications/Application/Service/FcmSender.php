<?php declare(strict_types=1);

namespace App\Notifications\Application\Service;

use InvalidArgumentException;

class FcmSender implements DeviceSenderInterface
{
    public function __construct(private string $serverKey)
    {
        if (empty(trim($this->serverKey))) {
            throw new InvalidArgumentException('FCM server key is required');
        }
    }

    public function supports(string $deviceType): bool
    {
        return $deviceType === \App\Notifications\Domain\Entity\PushSubscription::DEVICE_TYPE_ANDROID;
    }

    public function send(string $token, array $payload): bool
    {
        $headers = [
            'Authorization: key=' . $this->serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('FCM cURL error: ' . $error);
            return false;
        }

        if ($httpCode !== 200) {
            error_log('FCM API error: HTTP ' . $httpCode . ' - ' . $result);
            return false;
        }

        $response = json_decode($result, true);
        return isset($response['success']) && $response['success'] > 0;
    }
}
