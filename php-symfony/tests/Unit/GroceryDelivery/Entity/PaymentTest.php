<?php declare(strict_types=1);

namespace App\Tests\Unit\GroceryDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\GroceryDelivery\Entity\Payment;
use App\GroceryDelivery\Entity\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PaymentTest extends TestCase
{
    private function createMockOrder(): MockObject&Order
    {
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(1);
        $order->method('getOrderNumber')->willReturn('ORD-123');
        return $order;
    }

    public function testConstructorGeneratesTransactionIdAndCreatedAt(): void
    {
        $payment = new Payment();
        $this->assertIsString($payment->getTransactionId());
        $this->assertStringStartsWith('GD-PAY-', $payment->getTransactionId());
        $this->assertInstanceOf(\DateTime::class, $payment->getCreatedAt());
    }

    public function testGettersSetters(): void
    {
        $order = $this->createMockOrder();
        $payment = new Payment();

        $payment->setOrder($order);
        $this->assertSame($order, $payment->getOrder());

        $payment->setOrder(null);
        $this->assertNull($payment->getOrder());

        $payment->setOrder($order); // reset

        $payment->setAmount(99.99);
        $this->assertSame(99.99, $payment->getAmount());

        $payment->setCurrency('USD');
        $this->assertSame('USD', $payment->getCurrency());

        $payment->setStatus(Payment::STATUS_COMPLETED);
        $this->assertSame(Payment::STATUS_COMPLETED, $payment->getStatus());

        $payment->setPaymentMethod(Payment::METHOD_CARD);
        $this->assertSame(Payment::METHOD_CARD, $payment->getPaymentMethod());

        $payment->setPlatformFee(2.50);
        $this->assertSame(2.50, $payment->getPlatformFee());

        $payment->setStoreEarning(70.00);
        $this->assertSame(70.00, $payment->getStoreEarning());

        $payment->setDriverEarning(20.00);
        $this->assertSame(20.00, $payment->getDriverEarning());

        $paidAt = new \DateTime('2025-01-01 10:00:00');
        $payment->setPaidAt($paidAt);
        $this->assertSame($paidAt, $payment->getPaidAt());

        $this->assertTrue($payment->isCompleted());
        $payment->setStatus(Payment::STATUS_PENDING);
        $this->assertFalse($payment->isCompleted());
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('pending', Payment::STATUS_PENDING);
        $this->assertSame('completed', Payment::STATUS_COMPLETED);
        $this->assertSame('failed', Payment::STATUS_FAILED);
    }

    public function testMethodConstants(): void
    {
        $this->assertSame('cash', Payment::METHOD_CASH);
        $this->assertSame('wallet', Payment::METHOD_WALLET);
        $this->assertSame('card', Payment::METHOD_CARD);
    }

    public function testIdSetAndGet(): void
    {
        $payment = new Payment();
        $ref = new \ReflectionProperty($payment, 'id');
        $ref->setAccessible(true);
        $ref->setValue($payment, 123);
        $this->assertSame(123, $payment->getId());
    }
}
