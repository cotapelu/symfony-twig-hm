<?php declare(strict_types=1);

namespace App\Tests\Unit\Payment\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Payment\Service\CryptoPaymentService;
use App\Payment\Entity\CryptoInvoice;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CryptoPaymentServiceTest extends TestCase
{
    private CryptoPaymentService $service;

    protected function setUp(): void
    {
        $this->service = new CryptoPaymentService('test-api-key', 'https://api.example.com');
    }

    public function testCreateInvoiceReturnsInvoiceWithCorrectData(): void
    {
        $userId = 'user-123';
        $amount = '99.99';
        $currency = 'BTC';

        $invoice = $this->service->createInvoice($userId, $amount, $currency);

        $this->assertInstanceOf(CryptoInvoice::class, $invoice);
        $this->assertSame($userId, $invoice->getUserId());
        $this->assertSame($amount, $invoice->getAmount());
        $this->assertSame($currency, $invoice->getCurrency());
        $this->assertSame('pending', $invoice->getStatus());
        $this->assertNotNull($invoice->getCreatedAt());
        $this->assertIsString($invoice->getId());
        $this->assertStringStartsWith('ch_', $invoice->getId());
        // Ensure charge ID is 16 hex chars after prefix (8 bytes -> 16 hex)
        $this->assertMatchesRegularExpression('/^ch_[0-9a-f]{16}$/', $invoice->getId());
    }

    public function testCreateInvoiceGeneratesUniqueIds(): void
    {
        $invoice1 = $this->service->createInvoice('u1', '1.00', 'ETH');
        $invoice2 = $this->service->createInvoice('u2', '2.00', 'BTC');
        $this->assertNotEquals($invoice1->getId(), $invoice2->getId());
    }

    public function testVerifyWebhookReturnsTrue(): void
    {
        $result = $this->service->verifyWebhook('payload', 'signature');
        $this->assertTrue($result);
    }
}
