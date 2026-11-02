<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\EcommerceTransaction;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EcommerceTransactionTest extends TestCase
{
    private function createTransaction(
        string $id = 'txn-id',
        string $orderId = 'order-1',
        string $customerId = 'customer-1',
        string $paymentMethod = 'CREDIT_CARD',
        int $amountCents = 5000
    ): EcommerceTransaction {
        return new EcommerceTransaction($id, $orderId, $customerId, $paymentMethod, $amountCents);
    }

    public function testConstructor(): void
    {
        $t = $this->createTransaction();
        $this->assertSame('txn-id', $t->getId());
        $this->assertSame('order-1', $t->getOrderId());
        $this->assertSame('customer-1', $t->getCustomerId());
        $this->assertSame('CREDIT_CARD', $t->getPaymentMethod());
        $this->assertSame('PENDING', $t->getStatus());
        $this->assertNull($t->getExternalTransactionId());
        $this->assertSame(5000, $t->getAmountCents());
        $this->assertSame('USD', $t->getCurrency());
        $this->assertNull($t->getPaymentDetails());
        $this->assertNull($t->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $t->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $t->getUpdatedAt());
        $this->assertTrue($t->isPending());
        $this->assertFalse($t->isProcessing());
        $this->assertFalse($t->isCompleted());
        $this->assertFalse($t->isFailed());
        $this->assertFalse($t->isRefunded());
        $this->assertTrue($t->isCreditCard());
        $this->assertFalse($t->isDebitCard());
        $this->assertFalse($t->isPayPal());
        $this->assertFalse($t->isBankTransfer());
        $this->assertFalse($t->hasExternalTransactionId());
        $this->assertFalse($t->hasPaymentDetails());
    }

    public function testSetOrderId(): void
    {
        $t = $this->createTransaction();
        $original = $t->getUpdatedAt();
        usleep(1000);
        $t->setOrderId('order-2');
        $this->assertSame('order-2', $t->getOrderId());
        $this->assertNotEquals($original, $t->getUpdatedAt());
    }

    public function testSetCustomerId(): void
    {
        $t = $this->createTransaction();
        $original = $t->getUpdatedAt();
        usleep(1000);
        $t->setCustomerId('customer-2');
        $this->assertSame('customer-2', $t->getCustomerId());
        $this->assertNotEquals($original, $t->getUpdatedAt());
    }

    public function testSetPaymentMethod(): void
    {
        $t = $this->createTransaction();
        $original = $t->getUpdatedAt();
        usleep(1000);
        $t->setPaymentMethod('PAYPAL');
        $this->assertSame('PAYPAL', $t->getPaymentMethod());
        $this->assertTrue($t->isPayPal());
        $this->assertFalse($t->isCreditCard());
        $this->assertNotEquals($original, $t->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $t = $this->createTransaction();
        $t->setStatus('PROCESSING');
        $this->assertTrue($t->isProcessing());
        $this->assertFalse($t->isPending());
        $t->setStatus('COMPLETED');
        $this->assertTrue($t->isCompleted());
        $t->setStatus('FAILED');
        $this->assertTrue($t->isFailed());
        $t->setStatus('REFUNDED');
        $this->assertTrue($t->isRefunded());
    }

    public function testSetExternalTransactionId(): void
    {
        $t = $this->createTransaction();
        $original = $t->getUpdatedAt();
        usleep(1000);
        $t->setExternalTransactionId('ext-123');
        $this->assertSame('ext-123', $t->getExternalTransactionId());
        $this->assertTrue($t->hasExternalTransactionId());
        $this->assertNotEquals($original, $t->getUpdatedAt());
        $t->setExternalTransactionId(null);
        $this->assertNull($t->getExternalTransactionId());
        $this->assertFalse($t->hasExternalTransactionId());
    }

    public function testSetAmountCents(): void
    {
        $t = $this->createTransaction();
        $original = $t->getUpdatedAt();
        usleep(1000);
        $t->setAmountCents(10000);
        $this->assertSame(10000, $t->getAmountCents());
        $this->assertNotEquals($original, $t->getUpdatedAt());
    }

    public function testSetCurrency(): void
    {
        $t = $this->createTransaction();
        $original = $t->getUpdatedAt();
        usleep(1000);
        $t->setCurrency('VND');
        $this->assertSame('VND', $t->getCurrency());
        $this->assertNotEquals($original, $t->getUpdatedAt());
    }

    public function testSetPaymentDetails(): void
    {
        $t = $this->createTransaction();
        $original = $t->getUpdatedAt();
        usleep(1000);
        $details = ['card_last4' => '4242', 'brand' => 'visa'];
        $t->setPaymentDetails($details);
        $this->assertSame($details, $t->getPaymentDetails());
        $this->assertTrue($t->hasPaymentDetails());
        $this->assertNotEquals($original, $t->getUpdatedAt());
        $t->setPaymentDetails(null);
        $this->assertNull($t->getPaymentDetails());
        $this->assertFalse($t->hasPaymentDetails());
    }

    public function testSetMetadata(): void
    {
        $t = $this->createTransaction();
        $original = $t->getUpdatedAt();
        usleep(1000);
        $meta = ['risk_score' => 0.1];
        $t->setMetadata($meta);
        $this->assertSame($meta, $t->getMetadata());
        $this->assertNotEquals($original, $t->getUpdatedAt());
        $t->setMetadata(null);
        $this->assertNull($t->getMetadata());
    }

    public function testPaymentMethodHelpers(): void
    {
        $t = $this->createTransaction();
        $t->setPaymentMethod('DEBIT_CARD');
        $this->assertTrue($t->isDebitCard());
        $this->assertFalse($t->isCreditCard());

        $t->setPaymentMethod('BANK_TRANSFER');
        $this->assertTrue($t->isBankTransfer());

        $t->setPaymentMethod('UNKNOWN');
        $this->assertFalse($t->isCreditCard());
        $this->assertFalse($t->isDebitCard());
        $this->assertFalse($t->isPayPal());
        $this->assertFalse($t->isBankTransfer());
    }

    public function testStatusHelpersAll(): void
    {
        $t = $this->createTransaction();
        $statuses = ['PROCESSING', 'COMPLETED', 'FAILED', 'REFUNDED'];
        foreach ($statuses as $s) {
            $t->setStatus($s);
            $method = 'is' . ucfirst(strtolower($s));
            $this->assertTrue($t->$method(), "Failed for status $s");
            // ensure others false
            $this->assertFalse($t->isPending(), "Should not be pending when status is $s");
        }
    }
}
