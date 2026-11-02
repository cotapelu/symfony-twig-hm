<?php declare(strict_types=1);

namespace App\Tests\Unit\Ecommerce\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Ecommerce\Entity\EcommerceTransaction;
use App\Ecommerce\Entity\EcommerceOrder;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EcommerceTransactionTest extends TestCase
{
    private function createOrder(): EcommerceOrder
    {
        return new EcommerceOrder();
    }

    private function createTransaction(?EcommerceOrder $order = null, ?string $type = null, ?int $amountCents = null): EcommerceTransaction
    {
        return new EcommerceTransaction(
            order: $order ?? $this->createOrder(),
            type: $type ?? EcommerceTransaction::TYPE_PAYMENT,
            amountCents: $amountCents ?? 1000
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $transaction = $this->createTransaction();
        // id is not initialized (auto int)
        $this->assertSame(EcommerceTransaction::TYPE_PAYMENT, $transaction->getType());
        $this->assertSame(1000, $transaction->getAmountCents());
        $this->assertNull($transaction->getPaymentMethod());
        $this->assertSame(EcommerceTransaction::STATUS_PENDING, $transaction->getStatus());
        $this->assertNull($transaction->getTransactionId());
        $this->assertInstanceOf(\DateTime::class, $transaction->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $order = $this->createOrder();
        $transaction = new EcommerceTransaction($order, EcommerceTransaction::TYPE_REFUND, 500);

        $transaction->setOrder($order);
        $this->assertSame($order, $transaction->getOrder());

        $transaction->setType(EcommerceTransaction::TYPE_PAYMENT);
        $this->assertSame(EcommerceTransaction::TYPE_PAYMENT, $transaction->getType());

        $transaction->setAmountCents(2500);
        $this->assertSame(2500, $transaction->getAmountCents());

        $transaction->setPaymentMethod('credit_card');
        $this->assertSame('credit_card', $transaction->getPaymentMethod());

        $transaction->setPaymentMethod(null);
        $this->assertNull($transaction->getPaymentMethod());

        $transaction->setStatus(EcommerceTransaction::STATUS_COMPLETED);
        $this->assertSame(EcommerceTransaction::STATUS_COMPLETED, $transaction->getStatus());

        $transaction->setTransactionId('txn_12345');
        $this->assertSame('txn_12345', $transaction->getTransactionId());
    }
}
