<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification;

use App\Notification\Service\NotificationService;
use App\Notification\Entity\Notification;
use PHPUnit\Framework\TestCase;

class NotificationServiceTest extends TestCase
{
    private NotificationService $service;

    protected function setUp(): void
    {
        $this->service = new NotificationService();
    }

    public function testSendNotificationCreatesNotification(): void
    {
        $notif = $this->service->sendNotification(1, 'info', 'Title', 'Message', 'email');
        $this->assertInstanceOf(Notification::class, $notif);
        $this->assertSame(1, $notif->getUserId());
        $this->assertSame('info', $notif->getType());
        $this->assertTrue($notif->isSent());
    }

    public function testGetUserNotificationsReturnsOnlyUserNotifications(): void
    {
        $this->service->sendNotification(1, 't1', 'T', 'M', 'push');
        $this->service->sendNotification(2, 't2', 'T', 'M', 'email');
        $this->service->sendNotification(1, 't3', 'T', 'M', 'in_app');

        $user1 = $this->service->getUserNotifications(1);
        $this->assertCount(2, $user1);
        foreach ($user1 as $n) {
            $this->assertSame(1, $n->getUserId());
        }
    }

    public function testGetUserNotificationsUnreadOnlyFilters(): void
    {
        $n1 = $this->service->sendNotification(1, 't', 'T', 'M', 'push');
        $n2 = $this->service->sendNotification(1, 't', 'T', 'M', 'email');
        $this->service->markAsRead($n1->getId());

        $unread = $this->service->getUserNotifications(1, true);
        $this->assertCount(1, $unread);
        $this->assertFalse($unread[0]->isRead());
    }

    public function testMarkAsReadUpdatesNotification(): void
    {
        $n = $this->service->sendNotification(1, 't', 'T', 'M', 'push');
        $this->assertFalse($n->isRead());
        $updated = $this->service->markAsRead($n->getId());
        $this->assertTrue($updated->isRead());
    }

    public function testMarkAsReadReturnsNullIfNotFound(): void
    {
        $this->assertNull($this->service->markAsRead(999));
    }

    public function testMarkAllAsReadCountsCorrectly(): void
    {
        $this->service->sendNotification(1, 't1', 'T', 'M', 'push');
        $this->service->sendNotification(1, 't2', 'T', 'M', 'email');
        $n3 = $this->service->sendNotification(1, 't3', 'T', 'M', 'in_app');
        // All unread initially
        $count = $this->service->markAllAsRead(1);
        $this->assertSame(3, $count);
        // Ensure all read
        $notifs = $this->service->getUserNotifications(1);
        foreach ($notifs as $n) {
            $this->assertTrue($n->isRead());
        }
    }

    public function testDeleteNotificationRemovesIt(): void
    {
        $n = $this->service->sendNotification(1, 't', 'T', 'M', 'push');
        $id = $n->getId();
        $this->assertTrue($this->service->deleteNotification($id));
        $this->assertEmpty($this->service->getUserNotifications(1));
    }

    public function testDeleteNotificationReturnsFalseIfNotFound(): void
    {
        $this->assertFalse($this->service->deleteNotification(999));
    }

    public function testPreferenceCRUD(): void
    {
        $pref = $this->service->setPreference(1, 'email', ['email' => true, 'push' => false, 'sms' => false, 'in_app' => true]);
        $this->assertSame(1, $pref->getUserId());
        $this->assertSame('email', $pref->getType());
        $this->assertTrue($pref->isEmailEnabled());
        $this->assertFalse($pref->isSmsEnabled());

        $retrieved = $this->service->getPreference(1, 'email');
        $this->assertSame($pref->getId(), $retrieved->getId());

        $all = $this->service->getUserPreferences(1);
        $this->assertCount(1, $all);
    }

    public function testSetPreferenceUpdatesExisting(): void
    {
        $this->service->setPreference(1, 'email', ['email' => true, 'push' => false]);
        $this->service->setPreference(1, 'email', ['email' => false, 'push' => true]);

        $pref = $this->service->getPreference(1, 'email');
        $this->assertFalse($pref->isEmailEnabled());
        $this->assertTrue($pref->isPushEnabled());
    }

    public function testGetPreferenceReturnsNullIfNotFound(): void
    {
        $this->assertNull($this->service->getPreference(999, 'unknown'));
    }
}
