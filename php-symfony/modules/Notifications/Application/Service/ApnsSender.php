<?php declare(strict_types=1);

namespace App\Notifications\Application\Service;

use InvalidArgumentException;
use RuntimeException;

class ApnsSender implements DeviceSenderInterface
{
    public function __construct(
        private string $keyPath,
        private string $keyId,
        private string $teamId,
        private string $topic
    ) {
        if (empty(trim($this->keyPath)) || empty(trim($this->keyId)) || empty(trim($this->teamId)) || empty(trim($this->topic))) {
            throw new InvalidArgumentException('APNs credentials (keyPath, keyId, teamId, topic) are required');
        }
    }

    public function supports(string $deviceType): bool
    {
        return $deviceType === \App\Notifications\Domain\Entity\PushSubscription::DEVICE_TYPE_IOS;
    }

    public function send(string $token, array $payload): bool {
        $privateKey = @file_get_contents($this->keyPath);
        if ($privateKey === false) { error_log('APNS private key not found at ' . $this->keyPath); return false; }
        $jwt = $this->generateApnsJwt($privateKey, $this->keyId, $this->teamId);
        $apnsPayload = $this->buildApnsPayload($payload);
        $jsonPayload = json_encode($apnsPayload, JSON_THROW_ON_ERROR);
        $ch = curl_init("https://api.push.apple.com/3/device/{$token}");
        curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_HTTPHEADER=>['apns-topic: '.$this->topic,'authorization: bearer '.$jwt,'content-type: application/json'], CURLOPT_RETURNTRANSFER=>true, CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_HTTP_VERSION=>CURL_HTTP_VERSION_2_0, CURLOPT_POSTFIELDS=>$jsonPayload]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) { error_log('APNs cURL error: ' . $error); return false; }
        if ($httpCode !== 200) { error_log('APNs API error: HTTP ' . $httpCode . ' - ' . $result); return false; }
        return true;
    }

    private function buildApnsPayload(array $payload): array {
        $title = $payload['notification']['title'] ?? 'Notification';
        $body = $payload['notification']['body'] ?? '';
        $data = $payload['data'] ?? [];
        return [
            'aps' => ['alert'=>['title'=>$title,'body'=>$body], 'sound'=>'default'],
            'data' => $data,
        ];
    }

    private function generateApnsJwt(string $privateKey, string $keyId, string $teamId): string
    {
        $header = ['alg' => 'ES256', 'kid' => $keyId];
        $payload = ['iss' => $this->teamId, 'iat' => time()];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $message = $headerEncoded . '.' . $payloadEncoded;

        $signature = '';
        $success = openssl_sign($message, $signature, $privateKey, 'sha256');
        if (!$success) {
            throw new RuntimeException('Failed to sign APNs JWT');
        }

        return $message . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
