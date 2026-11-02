<?php declare(strict_types=1);

namespace App\Tests\Unit\Notification\Service;

use App\Notification\Entity\Notification;
use App\Notification\Repository\NotificationRepositoryInterface;
use App\Notification\Service\NotificationService;
use PHPUnit\Framework\TestCase;

class NotificationServiceTest extends TestCase
{
    private NotificationService $service;
    private NotificationRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(NotificationRepositoryInterface::class);
        $this->service = new NotificationService($this->repository);
    }

    public function testSendNotification(): void
    {
        $n = new Notification(123, 'order', 'Order Update', 'Your order shipped', 'push');
        $n->setId(1);
        $this->repository->method('save')->willReturn($n);
        
        $result = $this->service->sendNotification(123, 'order', 'Order Update', 'Your order shipped', 'push');
        
        $this->assertEquals(123, $result->getUserId());
        $this->assertTrue($result->isSent());
    }

    public function testGetUserNotifications(): void
    {
        $n1 = new Notification(123, 'order', 'Test1', 'Msg1', 'push');
        $n1->setId(1);
        $n2 = new Notification(123, 'promo', 'Test2', 'Msg2', 'email');
        $n2->setId(2);
        $this->repository->method('findByUserId')->willReturn([$n1, $n2]);
        
        $result = $this->service->getUserNotifications(123);
        
        $this->assertCount(2, $result);
    }

    public function testMarkAsRead(): void
    {
        $n = new Notification(123, 'order', 'Test', 'Msg', 'push');
        $n->setId(1);
        $this->repository->method('findById')->willReturn($n);
        $this->repository->method('save')->willReturn($n);
        
        $result = $this->service->markAsRead(1);
        
        $this->assertTrue($result->isRead());
    }

    public function testDeleteNotification(): void
    {
        $this->repository->method('delete')->willReturn(true);
        
        $result = $this->service->deleteNotification(1);
        
        $this->assertTrue($result);
    }
}
