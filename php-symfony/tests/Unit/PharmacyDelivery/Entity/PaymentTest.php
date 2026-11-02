<?php declare(strict_types=1);

namespace App\Tests\Unit\PharmacyDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\PharmacyDelivery\Entity\Payment;
use App\PharmacyDelivery\Entity\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PaymentTest extends TestCase
{
    private function createMockOrder(int $id = 1): MockObject&Order
    {
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn($id);
        return $order;
    }

    private function createPayment(?Order $order = null): Payment
    {
        return new Payment(); // no-arg constructor sets createdAt
    }

    public function testConstructorInitializesProperties(): void
    {
        $payment = $this->createPayment();
        $this->assertSame(Payment::METHOD_CASH, $payment->getPaymentMethod());
        $this->assertSame(Payment::STATUS_PENDING, $payment->getStatus());
        $this->assertSame(0.0, $payment->getPlatformFee());
        $this->assertSame(0.0, $payment->getPharmacyEarning());
        $this->assertSame(0.0, $payment->getDriverEarning());
        $this->assertNull($payment->getTransactionId());
        $this->assertNull($payment->getPaymentResponse());
        $this->assertNull($payment->getPaidAt());
        $this->assertInstanceOf(\DateTime::class, $payment->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $payment = $this->createPayment();
        $order = $this->createMockOrder(10);

        $payment->setOrder($order);
        $this->assertSame($order, $payment->getOrder());

        $payment->setAmount(150.0);
        $this->assertSame(150.0, $payment->getAmount());

        $payment->setPaymentMethod(Payment::METHOD_CARD);
        $this->assertSame(Payment::METHOD_CARD, $payment->getPaymentMethod());

        $payment->setTransactionId('txn_12345');
        $this->assertSame('txn_12345', $payment->getTransactionId());

        $payment->setTransactionId(null);
        $this->assertNull($payment->getTransactionId());

        $payment->setStatus(Payment::STATUS_COMPLETED);
        $this->assertSame(Payment::STATUS_COMPLETED, $payment->getStatus());

        $payment->setPlatformFee(10.0);
        $this->assertSame(10.0, $payment->getPlatformFee());

        $payment->setPharmacyEarning(120.0);
        $this->assertSame(120.0, $payment->getPharmacyEarning());

        $payment->setDriverEarning(30.0);
        $this->assertSame(30.0, $payment->getDriverEarning());

        $payment->setPaymentResponse('OK');
        $this->assertSame('OK', $payment->getPaymentResponse());

        $paidAt = new \DateTime();
        $payment->setPaidAt($paidAt);
        $this->assertSame($paidAt, $payment->getPaidAt());

        $payment->setPaidAt(null);
        $this->assertNull($payment->getPaidAt());
    }
}
