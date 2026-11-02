<?php

declare(strict_types=1);

namespace App\Notification\Service;

use App\Shared\Service\CircuitBreakerService;

class PushNotificationService
{
    private string $fcmServerKey;
    private string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
    private ?CircuitBreakerService $circuitBreaker;

    public function __construct(
        ?string $fcmServerKey = null,
        ?CircuitBreakerService $circuitBreaker = null,
    ) {
        $this->fcmServerKey = $fcmServerKey ?? getenv('FCM_SERVER_KEY') ?? '';
        $this->circuitBreaker = $circuitBreaker;
    }

    /**
     * Send push notification to a device.
     */
    public function sendNotification(string $deviceToken, array $notificationData)
    {
        if (empty($this->fcmServerKey)) {
            // Mock implementation for development
            return $this->mockSendNotification($deviceToken, $notificationData);
        }

        $payload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $notificationData['title'] ?? 'New Message',
                'body' => $notificationData['body'] ?? 'You have a new message',
                'icon' => $notificationData['icon'] ?? '/favicon.ico',
                'click_action' => $notificationData['click_action'] ?? '/messages',
            ],
            'data' => $notificationData['data'] ?? [],
        ];

        return $this->sendFCMRequest($payload);
    }

    /**
     * Send notification to multiple devices.
     */
    public function sendBulkNotification(array $deviceTokens, array $notificationData)
    {
        $results = [];

        foreach ($deviceTokens as $token) {
            $results[$token] = $this->sendNotification($token, $notificationData);
        }

        return $results;
    }

    /**
     * Send FCM request.
     */
    private function sendFCMRequest(array $payload)
    {
        if (null === $this->circuitBreaker) {
            return $this->makeFCMRequest($payload);
        }

        return $this->circuitBreaker->execute(
            fn () => $this->makeFCMRequest($payload),
            'fcm_api',
            5,
            60,
            fn () => $this->mockSendNotification('', $payload['notification'])
        );
    }

    private function makeFCMRequest(array $payload)
    {
        $headers = [
            'Authorization: key='.$this->fcmServerKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('FCM cURL error: '.$error);
        }

        if (200 !== $httpCode) {
            throw new \Exception('FCM API error: HTTP '.$httpCode.' - '.$result);
        }

        $response = json_decode($result, true);

        return isset($response['success']) && $response['success'] > 0;
    }

    /**
     * Mock implementation for development.
     */
    private function mockSendNotification(string $deviceToken, array $notificationData)
    {
        // Log the notification for development
        error_log('Mock FCM Notification: '.json_encode([
            'token' => $deviceToken,
            'notification' => $notificationData,
            'timestamp' => date('Y-m-d H:i:s'),
        ]));

        return true;
    }

    /**
     * Create notification data for new message.
     */
    public function createMessageNotificationData(
        string $senderName,
        string $messagePreview,
        string $conversationId,
        string $messageId,
    ) {
        return [
            'title' => 'New Message from '.$senderName,
            'body' => $messagePreview,
            'icon' => '/favicon.ico',
            'click_action' => '/messages?conversationId='.$conversationId,
            'data' => [
                'type' => 'new_message',
                'conversationId' => $conversationId,
                'messageId' => $messageId,
                'senderName' => $senderName,
            ],
        ];
    }

    /**
     * Create notification data for group invitation.
     */
    public function createGroupInvitationData(string $groupName, string $inviterName)
    {
        return [
            'title' => 'Group Invitation',
            'body' => $inviterName.' invited you to join '.$groupName,
            'icon' => '/favicon.ico',
            'click_action' => '/groups',
            'data' => [
                'type' => 'group_invitation',
                'groupName' => $groupName,
                'inviterName' => $inviterName,
            ],
        ];
    }

    /**
     * Create notification data for call invitation.
     */
    public function createCallInvitationData(string $callerName, string $callType = 'video')
    {
        return [
            'title' => 'Incoming '.ucfirst($callType).' Call',
            'body' => $callerName.' is calling you',
            'icon' => '/favicon.ico',
            'click_action' => '/calls',
            'data' => [
                'type' => 'call_invitation',
                'callerName' => $callerName,
                'callType' => $callType,
            ],
        ];
    }
}
