<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\EcommerceTransaction;
use App\Repository\Php\EcommerceTransactionRepository;
use App\Service\Php\EcommerceTransactionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EcommerceTransactionServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EcommerceTransactionRepository&MockObject $repository;
    private EcommerceTransactionService $service;

    private string $txnId;
    private EcommerceTransaction $txn;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EcommerceTransactionRepository::class);
        $this->service = new EcommerceTransactionService($this->em, $this->repository);

        $this->txnId = bin2hex(random_bytes(16));
        $this->txn = new EcommerceTransaction(
            $this->txnId,
            'order-1',
            'customer-1',
            'CARD',
            5000
        );
        $this->txn->setCurrency('USD');
        $this->txn->setStatus('PENDING');
    }

    public function testCreateTransactionSuccess(): void
    {
        $orderId = 'ord-123';
        $customerId = 'cust-123';
        $paymentMethod = 'VISA';
        $amountCents = 15000;
        $externalTxnId = 'ext-999';
        $status = 'COMPLETED';
        $currency = 'VND';
        $paymentDetails = ['last4' => '1234'];
        $metadata = ['channel' => 'web'];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (EcommerceTransaction $t) use (&$captured, $orderId, $customerId, $paymentMethod, $amountCents, $externalTxnId, $status, $currency, $paymentDetails, $metadata) {
                $captured = $t;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createTransaction($orderId, $customerId, $paymentMethod, $amountCents, $externalTxnId, $status, $currency, $paymentDetails, $metadata);
        $this->assertInstanceOf(EcommerceTransaction::class, $result);
        $this->assertSame($orderId, $result->getOrderId());
        $this->assertSame($customerId, $result->getCustomerId());
        $this->assertSame($paymentMethod, $result->getPaymentMethod());
        $this->assertSame($amountCents, $result->getAmountCents());
        $this->assertSame($externalTxnId, $result->getExternalTransactionId());
        $this->assertSame($status, $result->getStatus());
        $this->assertSame($currency, $result->getCurrency());
        $this->assertSame($paymentDetails, $result->getPaymentDetails());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateTransactionValidationEmptyOrderId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order ID is required');
        $this->service->createTransaction('', 'cust', 'method', 100);
    }

    public function testCreateTransactionValidationEmptyCustomerId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer ID is required');
        $this->service->createTransaction('ord', '', 'method', 100);
    }

    public function testCreateTransactionValidationEmptyPaymentMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment method is required');
        $this->service->createTransaction('ord', 'cust', '', 100);
    }

    public function testCreateTransactionValidationAmountNotPositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');
        $this->service->createTransaction('ord', 'cust', 'method', 0);
    }

    public function testGetTransactionSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->txnId)
            ->willReturn($this->txn);

        $result = $this->service->getTransaction($this->txnId);
        $this->assertSame($this->txn, $result);
    }

    public function testGetTransactionNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->txnId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('EcommerceTransaction not found');
        $this->service->getTransaction($this->txnId);
    }

    public function testUpdateTransactionSuccess(): void
    {
        $newOrderId = 'ord-2';
        $newCustomerId = 'cust-2';
        $newPaymentMethod = 'MOMO';
        $newAmount = 20000;
        $newExternalId = 'ext-888';
        $newStatus = 'REFUNDED';
        $newCurrency = 'USD';
        $newPaymentDetails = ['bank' => 'VCB'];
        $newMetadata = ['country' => 'VN'];

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->txnId)
            ->willReturn($this->txn);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'orderId' => $newOrderId,
            'customerId' => $newCustomerId,
            'paymentMethod' => $newPaymentMethod,
            'amountCents' => $newAmount,
            'externalTransactionId' => $newExternalId,
            'status' => $newStatus,
            'currency' => $newCurrency,
            'paymentDetails' => $newPaymentDetails,
            'metadata' => $newMetadata,
        ];
        $result = $this->service->updateTransaction($this->txnId, $data);
        $this->assertSame($this->txn, $result);
        $this->assertSame($newOrderId, $result->getOrderId());
        $this->assertSame($newCustomerId, $result->getCustomerId());
        $this->assertSame($newPaymentMethod, $result->getPaymentMethod());
        $this->assertSame($newAmount, $result->getAmountCents());
        $this->assertSame($newExternalId, $result->getExternalTransactionId());
        $this->assertSame($newStatus, $result->getStatus());
        $this->assertSame($newCurrency, $result->getCurrency());
        $this->assertSame($newPaymentDetails, $result->getPaymentDetails());
        $this->assertSame($newMetadata, $result->getMetadata());
    }

    public function testUpdateTransactionValidationEmptyOrderId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->txnId)
            ->willReturn($this->txn);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order ID cannot be empty');
        $this->service->updateTransaction($this->txnId, ['orderId' => '']);
    }

    public function testUpdateTransactionValidationEmptyCustomerId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->txnId)
            ->willReturn($this->txn);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer ID cannot be empty');
        $this->service->updateTransaction($this->txnId, ['customerId' => '']);
    }

    public function testUpdateTransactionValidationEmptyPaymentMethod(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->txnId)
            ->willReturn($this->txn);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment method cannot be empty');
        $this->service->updateTransaction($this->txnId, ['paymentMethod' => '']);
    }

    public function testUpdateTransactionValidationAmountNotPositive(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->txnId)
            ->willReturn($this->txn);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');
        $this->service->updateTransaction($this->txnId, ['amountCents' => -100]);
    }

    public function testUpdateTransactionNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->txnId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateTransaction($this->txnId, []);
    }

    public function testDeleteTransactionSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->txnId)
            ->willReturn($this->txn);
        $this->em->expects($this->once())->method('remove')->with($this->txn);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteTransaction($this->txnId);
    }

    public function testDeleteTransactionNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->txnId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteTransaction($this->txnId);
    }

    public function testFindByOrderId(): void
    {
        $orderId = 'ord-123';
        $results = [$this->txn];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['orderId' => $orderId])
            ->willReturn($results);

        $result = $this->service->findByOrderId($orderId);
        $this->assertSame($results, $result);
    }

    public function testFindByCustomerId(): void
    {
        $customerId = 'cust-123';
        $results = [$this->txn];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['customerId' => $customerId])
            ->willReturn($results);

        $result = $this->service->findByCustomerId($customerId);
        $this->assertSame($results, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'FAILED';
        $results = [$this->txn];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }

    public function testFindByPaymentMethod(): void
    {
        $method = 'CASH';
        $results = [$this->txn];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['paymentMethod' => $method])
            ->willReturn($results);

        $result = $this->service->findByPaymentMethod($method);
        $this->assertSame($results, $result);
    }
}
