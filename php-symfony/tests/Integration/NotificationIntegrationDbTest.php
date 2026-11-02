<?php

declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Notification\Entity\Notification;

#[AllowMockObjectsWithoutExpectations]
class NotificationIntegrationDbTest extends DatabaseIntegrationTestCase
{
    private function generateUserId(): string
    {
        return 'user_' . random_int(1, 100000);
    }

    public function testCreateAndRetrieveNotification(): void
    {
        $userId = $this->generateUserId();
        $notification = new Notification(
            userId: $userId,
            type: 'order.update',
            title: 'Order Update',
            message: 'Your order has shipped',
            channel: 'push'
        );
        $notification->setData(['orderId' => 123]);

        $em = self::getEntityManager();
        $em->persist($notification);
        $em->flush();

        $this->assertNotNull($notification->getId());

        $repo = $em->getRepository(Notification::class);
        $found = $repo->find($notification->getId());

        $this->assertNotNull($found);
        $this->assertSame($userId, $found->getUserId());
        $this->assertSame('order.update', $found->getType());
        $this->assertSame('Order Update', $found->getTitle());
        $this->assertSame('Your order has shipped', $found->getMessage());
        $this->assertSame(['orderId' => 123], $found->getData());
        $this->assertSame('push', $found->getChannel());
        $this->assertFalse($found->isRead());
        $this->assertFalse($found->isSent());
    }

    public function testMarkAsReadAndSent(): void
    {
        $userId = $this->generateUserId();
        $notification = new Notification(
            userId: $userId,
            type: 'alert',
            title: 'Alert',
            message: 'Something happened',
            channel: 'in-app'
        );

        $em = self::getEntityManager();
        $em->persist($notification);
        $em->flush();

        $notification->markAsRead();
        $this->assertTrue($notification->isRead());
        $this->assertInstanceOf(\DateTimeImmutable::class, $notification->getReadAt());

        $notification->markAsSent();
        $this->assertTrue($notification->isSent());
        $this->assertInstanceOf(\DateTimeImmutable::class, $notification->getSentAt());

        $em->flush();

        $found = $em->getRepository(Notification::class)->find($notification->getId());
        $this->assertTrue($found->isRead());
        $this->assertTrue($found->isSent());
    }

    public function testFindByUser(): void
    {
        $userId = $this->generateUserId();
        $n1 = new Notification(
            userId: $userId,
            type: 'type1',
            title: 'Title 1',
            message: 'Message 1',
            channel: 'push'
        );
        $n2 = new Notification(
            userId: $userId,
            type: 'type2',
            title: 'Title 2',
            message: 'Message 2',
            channel: 'email'
        );

        $em = self::getEntityManager();
        $em->persist($n1);
        $em->persist($n2);
        $em->flush();

        $repo = $em->getRepository(Notification::class);
        $byUser = $repo->findBy(['userId' => $userId]);

        $this->assertCount(2, $byUser);
        $titles = array_map(fn($n) => $n->getTitle(), $byUser);
        $this->assertContains('Title 1', $titles);
        $this->assertContains('Title 2', $titles);
    }

    public function testDeleteNotification(): void
    {
        $userId = $this->generateUserId();
        $notification = new Notification(
            userId: $userId,
            type: 'to.delete',
            title: 'To Delete',
            message: 'Delete me',
            channel: 'sms'
        );

        $em = self::getEntityManager();
        $em->persist($notification);
        $em->flush();

        $id = $notification->getId();

        $em->remove($notification);
        $em->flush();

        $deleted = $em->getRepository(Notification::class)->find($id);
        $this->assertNull($deleted);
    }
}
