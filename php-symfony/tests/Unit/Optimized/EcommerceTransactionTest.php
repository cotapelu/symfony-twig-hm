<?php

declare(strict_types=1);

namespace App\Tests\Unit\Optimized;

use PHPUnit\Framework\TestCase;
use App\Core\Entity\EcommerceTransaction;

// Load the file containing all entity classes (non-PSR-4)
require_once __DIR__.'/../../../src/Entity/Optimized/EcommerceWallet.php';

class EcommerceTransactionTest extends TestCase
{
    private function createTransaction(?string $id = null, ?string $orderId = null, ?string $type = null, ?int $amountCents = null): EcommerceTransaction
    {
        $id = $id ?? 'test-tx-id';
        $orderId = $orderId ?? 'order-1';
        $type = $type ?? EcommerceTransaction::TYPE_PAYMENT;
        $amountCents = $amountCents ?? 5000;
        return new EcommerceTransaction($id, $orderId, $type, $amountCents);
    }

    public function testConstructorInitializesProperties(): void
    {
        $tx = $this->createTransaction();

        $this->assertSame('test-tx-id', $tx->getId());
        $this->assertSame('order-1', $tx->getOrderId());
        $this->assertSame(EcommerceTransaction::TYPE_PAYMENT, $tx->getType());
        $this->assertSame(5000, $tx->getAmountCents());
        $this->assertNull($tx->getPaymentMethod());
        $this->assertSame(EcommerceTransaction::STATUS_PENDING, $tx->getStatus());
        $this->assertNull($tx->getTransactionId());
        $this->assertInstanceOf(\DateTime::class, $tx->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $tx = $this->createTransaction();

        $tx->setStatus(EcommerceTransaction::STATUS_COMPLETED);
        $this->assertSame(EcommerceTransaction::STATUS_COMPLETED, $tx->getStatus());

        $tx->setTransactionId('tx-123');
        $this->assertSame('tx-123', $tx->getTransactionId());

        $tx->setStatus(EcommerceTransaction::STATUS_FAILED);
        $this->assertSame(EcommerceTransaction::STATUS_FAILED, $tx->getStatus());
    }

    public function testTypeConstants(): void
    {
        $this->assertSame('PAYMENT', EcommerceTransaction::TYPE_PAYMENT);
        $this->assertSame('REFUND', EcommerceTransaction::TYPE_REFUND);
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('PENDING', EcommerceTransaction::STATUS_PENDING);
        $this->assertSame('COMPLETED', EcommerceTransaction::STATUS_COMPLETED);
        $this->assertSame('FAILED', EcommerceTransaction::STATUS_FAILED);
    }
}
