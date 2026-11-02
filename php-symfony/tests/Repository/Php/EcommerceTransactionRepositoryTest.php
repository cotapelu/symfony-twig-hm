<?php declare(strict_types=1);

namespace App\Tests\Repository\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Php\EcommerceTransactionRepository;
use App\Entity\Php\EcommerceTransaction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EcommerceTransactionRepositoryTest extends TestCase
{
    private EcommerceTransactionRepository&MockObject $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(EcommerceTransactionRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findBy', 'findOneBy', 'count'])
            ->getMock();
    }

    public function testFindByOrderId(): void
    {
        $orderId = 'order-123';
        $expected = $this->createMock(EcommerceTransaction::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['orderId' => $orderId])
            ->willReturn($expected);

        $result = $this->repo->findByOrderId($orderId);
        $this->assertSame($expected, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'FAILED';
        $expected = [$this->createMock(EcommerceTransaction::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($expected);

        $result = $this->repo->findByStatus($status);
        $this->assertSame($expected, $result);
    }

    public function testFindByPaymentMethod(): void
    {
        $method = 'CRYPTO';
        $expected = [$this->createMock(EcommerceTransaction::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['paymentMethod' => $method])
            ->willReturn($expected);

        $result = $this->repo->findByPaymentMethod($method);
        $this->assertSame($expected, $result);
    }

    public function testFindSuccessfulTransactions(): void
    {
        $expected = [$this->createMock(EcommerceTransaction::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'COMPLETED'])
            ->willReturn($expected);

        $result = $this->repo->findSuccessfulTransactions();
        $this->assertSame($expected, $result);
    }
}
