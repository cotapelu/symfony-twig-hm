<?php

declare(strict_types=1);

namespace App\Notification\Service;

use App\Notification\Entity\Notification;
use App\Notification\Entity\NotificationPreference;
use App\Notification\Repository\NotificationRepositoryInterface;

class NotificationService
{
    private array $preferences = [];

    public function __construct(
        private NotificationRepositoryInterface $notificationRepository
    ) {}

    public function sendNotification(string $userId, string $type, string $title, string $message, string $channel, array $data = [])
    {
        if (empty(trim($type))) throw new \InvalidArgumentException('Type is required');
        if (empty(trim($title))) throw new \InvalidArgumentException('Title is required');
        if (empty(trim($message))) throw new \InvalidArgumentException('Message is required');

        $allowedChannels = ['in_app', 'email', 'push', 'sms'];
        if (!in_array($channel, $allowedChannels, true)) {
            throw new \InvalidArgumentException('Invalid channel');
        }

        $notification = new Notification($userId, $type, $title, $message, $channel);
        $notification->setData($data);
        $notification->markAsSent();

        $this->notificationRepository->save($notification);

        return $notification;
    }

    public function getUserNotifications(string $userId, bool $unreadOnly = false)
    {
        return $this->notificationRepository->findByUserId($userId, $unreadOnly);
    }

    public function getUserNotificationsPaginated(string $userId, bool $unreadOnly = false, int $page = 1, int $perPage = 20)
    {
        $limit = $perPage;
        $offset = ($page - 1) * $perPage;
        $data = $this->notificationRepository->findByUserIdWithPagination($userId, $unreadOnly, $limit, $offset);
        $total = $this->notificationRepository->countByUserId($userId, $unreadOnly);
        return [
            'data' => $data,
            'total' => $total,
        ];
 }

    public function getNotification(int $id, string $userId)
    {
        $notification = $this->notificationRepository->findById($id);
        if ($notification && $notification->getUserId() === $userId) {
            return $notification;
        }
        return null;
    }

    public function markAsRead(int $notificationId, string $userId)
    {
        $notification = $this->getNotification($notificationId, $userId);
        if (!$notification) {
            return null;
        }
        $notification->markAsRead();
        $this->notificationRepository->save($notification);
        return $notification;
    }

    public function markAllAsRead(string $userId)
    {
        $notifications = $this->notificationRepository->findByUserId($userId, false);
        $count = 0;
        foreach ($notifications as $n) {
            if (!$n->isRead()) {
                $n->markAsRead();
                $this->notificationRepository->save($n);
                $count++;
            }
        }
        return $count;
    }

    public function deleteNotification(int $id, string $userId)
    {
        $notification = $this->getNotification($id, $userId);
        if (!$notification) {
            return false;
        }
        return $this->notificationRepository->delete($id);
    }

    public function getPreference(string $userId, string $type)
    {
        foreach ($this->preferences as $pref) {
            if ($pref->getUserId() === $userId && $pref->getType() === $type) {
                return $pref;
            }
        }
        return null;
    }

    public function setPreference(string $userId, string $type, array $channels)
    {
        $pref = $this->getPreference($userId, $type);
        if (!$pref) {
            $pref = new NotificationPreference($userId, $type);
            $this->preferences[] = $pref;
        }

        if (isset($channels['email'])) $pref->setEmailEnabled((bool)$channels['email']);
        if (isset($channels['push'])) $pref->setPushEnabled((bool)$channels['push']);
        if (isset($channels['sms'])) $pref->setSmsEnabled((bool)$channels['sms']);
        if (isset($channels['in_app'])) $pref->setInAppEnabled((bool)$channels['in_app']);

        return $pref;
    }
}
