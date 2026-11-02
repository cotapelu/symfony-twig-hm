<?php declare(strict_types=1);

namespace App\Tests\Unit\Payment\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Payment\Entity\CryptoInvoice;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CryptoInvoiceTest extends TestCase
{
    private function createInvoice(?string $id = null, ?string $userId = null, ?string $amount = null, ?string $currency = null): CryptoInvoice
    {
        return new CryptoInvoice(
            id: $id ?? 'inv-123',
            userId: $userId ?? 'user-1',
            amount: $amount ?? '10.00',
            currency: $currency ?? 'BTC'
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $invoice = $this->createInvoice();
        $this->assertSame('inv-123', $invoice->getId());
        $this->assertSame('user-1', $invoice->getUserId());
        $this->assertSame('10.00', $invoice->getAmount());
        $this->assertSame('BTC', $invoice->getCurrency());
        $this->assertSame('pending', $invoice->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $invoice->getCreatedAt());
        $this->assertNull($invoice->getPaidAt());
        $this->assertNull($invoice->getTransactionHash());
    }

    public function testGettersAndSetters(): void
    {
        $invoice = $this->createInvoice();

        $invoice->setStatus('paid');
        $this->assertSame('paid', $invoice->getStatus());

        $paidAt = new \DateTimeImmutable();
        $invoice->setPaidAt($paidAt);
        $this->assertSame($paidAt, $invoice->getPaidAt());

        $invoice->setTransactionHash('0xabc123');
        $this->assertSame('0xabc123', $invoice->getTransactionHash());
    }

    public function testCustomValuesInConstructor(): void
    {
        $invoice = new CryptoInvoice('inv-999', 'user-999', '99.99', 'ETH');
        $this->assertSame('inv-999', $invoice->getId());
        $this->assertSame('user-999', $invoice->getUserId());
        $this->assertSame('99.99', $invoice->getAmount());
        $this->assertSame('ETH', $invoice->getCurrency());
        $this->assertSame('pending', $invoice->getStatus());
    }
}
