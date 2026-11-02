<?php declare(strict_types=1);

namespace App\Tests\Unit\FoodDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\FoodDelivery\Entity\Payment;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PaymentTest extends TestCase
{
    private function createPayment(): Payment
    {
        return new Payment();
    }

    public function testConstructorInitializesProperties(): void
    {
        $payment = $this->createPayment();
        $this->assertNull($payment->getOrder());
        $this->assertIsString($payment->getTransactionId());
        $this->assertStringStartsWith('FD-PAY-', $payment->getTransactionId());
        $this->assertInstanceOf(\DateTime::class, $payment->getCreatedAt());
        // Defaults
        $this->assertSame('VND', $payment->getCurrency());
        $this->assertSame(Payment::STATUS_PENDING, $payment->getStatus());
        $this->assertSame(Payment::METHOD_CASH, $payment->getPaymentMethod());
        $this->assertNull($payment->getPaymentProvider());
        $this->assertNull($payment->getProviderTransactionId());
        $this->assertNull($payment->getPlatformFee());
        $this->assertNull($payment->getRestaurantEarning());
        $this->assertNull($payment->getDeliveryFee());
        $this->assertNull($payment->getDriverEarning());
        $this->assertNull($payment->getMetadata());
        $this->assertNull($payment->getPaidAt());
    }

    public function testSettersAndGetters(): void
    {
        $payment = $this->createPayment();

        // We cannot change transactionId (no setter) unless we use reflection; skip.

        $payment->setAmount(99.99);
        $this->assertSame(99.99, $payment->getAmount());

        $payment->setCurrency('USD');
        $this->assertSame('USD', $payment->getCurrency());

        $payment->setStatus(Payment::STATUS_COMPLETED);
        $this->assertSame(Payment::STATUS_COMPLETED, $payment->getStatus());

        $payment->setPaymentMethod(Payment::METHOD_CARD);
        $this->assertSame(Payment::METHOD_CARD, $payment->getPaymentMethod());

        $payment->setPaymentProvider('Stripe');
        $this->assertSame('Stripe', $payment->getPaymentProvider());

        $payment->setProviderTransactionId('txn_123');
        $this->assertSame('txn_123', $payment->getProviderTransactionId());

        $payment->setPlatformFee(2.5);
        $this->assertSame(2.5, $payment->getPlatformFee());

        $payment->setRestaurantEarning(80.0);
        $this->assertSame(80.0, $payment->getRestaurantEarning());

        $payment->setDeliveryFee(5.0);
        $this->assertSame(5.0, $payment->getDeliveryFee());

        $payment->setDriverEarning(10.0);
        $this->assertSame(10.0, $payment->getDriverEarning());

        $meta = json_encode(['fee' => 2.5]);
        $payment->setMetadata($meta);
        $this->assertSame($meta, $payment->getMetadata());

        $paidAt = new \DateTime();
        $payment->setPaidAt($paidAt);
        $this->assertSame($paidAt, $payment->getPaidAt());

        // setOrder(null) is no-op, already null. Ensure returns $this.
        $result = $payment->setOrder(null);
        $this->assertSame($payment, $result);
    }
}
