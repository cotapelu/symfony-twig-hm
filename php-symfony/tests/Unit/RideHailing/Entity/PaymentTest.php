<?php declare(strict_types=1);

namespace App\Tests\Unit\RideHailing\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\RideHailing\Entity\Payment;
use App\RideHailing\Entity\Ride;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PaymentTest extends TestCase
{
    private function createMockRide(): MockObject&Ride
    {
        $ride = $this->createMock(Ride::class);
        $ride->method('getId')->willReturn(1);
        return $ride;
    }

    public function testConstructorInitializesFields(): void
    {
        $payment = new Payment();
        $this->assertIsString($payment->getTransactionId());
        $this->assertStringStartsWith('PAY-', $payment->getTransactionId());
        $this->assertInstanceOf(\DateTime::class, $payment->getCreatedAt());
        $this->assertSame('VND', $payment->getCurrency());
        $this->assertSame(Payment::STATUS_PENDING, $payment->getStatus());
        $this->assertSame(Payment::METHOD_CASH, $payment->getPaymentMethod());
    }

    public function testGettersSetters(): void
    {
        $ride = $this->createMockRide();
        $payment = new Payment();

        $payment->setRide($ride);
        $this->assertSame($ride, $payment->getRide());

        $payment->setRide(null);
        $this->assertNull($payment->getRide());

        $payment->setRide($ride); // reset

        $payment->setAmount(150.00);
        $this->assertSame(150.00, $payment->getAmount());

        $payment->setCurrency('USD');
        $this->assertSame('USD', $payment->getCurrency());

        $payment->setStatus(Payment::STATUS_COMPLETED);
        $this->assertSame(Payment::STATUS_COMPLETED, $payment->getStatus());

        $payment->setPaymentMethod(Payment::METHOD_CARD);
        $this->assertSame(Payment::METHOD_CARD, $payment->getPaymentMethod());

        $payment->setPaymentProvider('Stripe');
        $this->assertSame('Stripe', $payment->getPaymentProvider());

        $payment->setProviderTransactionId('prov-123');
        $this->assertSame('prov-123', $payment->getProviderTransactionId());

        $payment->setCommissionAmount(15.00);
        $this->assertSame(15.00, $payment->getCommissionAmount());

        $payment->setDriverEarning(135.00);
        $this->assertSame(135.00, $payment->getDriverEarning());

        $metadata = '{"key":"value"}';
        $payment->setMetadata($metadata);
        $this->assertSame($metadata, $payment->getMetadata());

        $paidAt = new \DateTime('2025-01-01 10:00:00');
        $payment->setPaidAt($paidAt);
        $this->assertSame($paidAt, $payment->getPaidAt());
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('pending', Payment::STATUS_PENDING);
        $this->assertSame('completed', Payment::STATUS_COMPLETED);
        $this->assertSame('failed', Payment::STATUS_FAILED);
        $this->assertSame('refunded', Payment::STATUS_REFUNDED);
    }

    public function testMethodConstants(): void
    {
        $this->assertSame('cash', Payment::METHOD_CASH);
        $this->assertSame('wallet', Payment::METHOD_WALLET);
        $this->assertSame('card', Payment::METHOD_CARD);
        $this->assertSame('bank', Payment::METHOD_BANK);
    }

    public function testIdSetAndGet(): void
    {
        $payment = new Payment();
        $ref = new \ReflectionProperty($payment, 'id');
        $ref->setAccessible(true);
        $ref->setValue($payment, 42);
        $this->assertSame(42, $payment->getId());
    }
}
