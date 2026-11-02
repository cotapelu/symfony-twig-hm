<?php declare(strict_types=1);

namespace App\Tests\Unit\Investment\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Investment\Entity\Investment;
use App\Investment\Repository\InvestmentRepository;
use App\Investment\Service\InvestmentService;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

#[AllowMockObjectsWithoutExpectations]
class InvestmentServiceTest extends TestCase
{
    private InvestmentRepository $investmentRepoMock;
    private InvestmentService $service;

    protected function setUp(): void
    {
        $this->investmentRepoMock = $this->createMock(InvestmentRepository::class);
        $this->service = new InvestmentService($this->investmentRepoMock);
    }

    // ==================== createInvestment ====================

    public function testCreateInvestmentSuccess(): void
    {
        $this->investmentRepoMock->expects($this->once())->method('save')->with($this->isInstanceOf(Investment::class));

        $investment = $this->service->createInvestment(
            userId: 'user-1',
            investmentType: 'stock',
            name: 'Tech Stocks',
            principal: 10000.0,
            purchaseDate: new \DateTime('2024-01-01')
        );

        $this->assertInstanceOf(Investment::class, $investment);
        $this->assertSame('user-1', $investment->getUserId());
        $this->assertSame('stock', $investment->getInvestmentType());
        $this->assertSame(10000.0, $investment->getPrincipal());
        $this->assertSame(10000.0, $investment->getCurrentValue());
    }

    public function testCreateInvestmentWithDefaults(): void
    {
        $this->investmentRepoMock->expects($this->once())->method('save');

        $investment = $this->service->createInvestment(
            userId: 'user-2',
            investmentType: 'bond',
            name: 'Gov Bond',
            principal: 5000.0
        );

        $this->assertSame('user-2', $investment->getUserId());
        $this->assertNotNull($investment->getPurchaseDate());
        $this->assertNull($investment->getMaturityDate());
    }

    public function testCreateInvestmentThrowsIfEmptyUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required');
        $this->service->createInvestment('', 'stock', 'Test', 1000.0);
    }

    public function testCreateInvestmentThrowsIfUserIdTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID cannot exceed 255 characters');
        $this->service->createInvestment(str_repeat('a', 256), 'stock', 'Test', 1000.0);
    }

    // ==================== getUserInvestments ====================

    public function testGetUserInvestmentsAll(): void
    {
        $investments = [$this->createInvestmentEntity(), $this->createInvestmentEntity()];
        $this->investmentRepoMock->method('findByUser')->with('user-1')->willReturn($investments);

        $result = $this->service->getUserInvestments('user-1');
        $this->assertSame($investments, $result);
    }

    public function testGetUserInvestmentsByType(): void
    {
        $investments = [$this->createInvestmentEntity(['type' => 'stock'])];
        $this->investmentRepoMock->method('findByUserAndType')->with('user-1', 'stock')->willReturn($investments);

        $result = $this->service->getUserInvestments('user-1', type: 'stock');
        $this->assertSame($investments, $result);
    }

    // ==================== getInvestment ====================

    public function testGetInvestmentSuccess(): void
    {
        $investment = $this->createInvestmentEntity(['id' => 1]);
        $this->investmentRepoMock->method('find')->with(1)->willReturn($investment);

        $result = $this->service->getInvestment(1);
        $this->assertSame($investment, $result);
    }

    // ==================== updateInvestmentValue ====================

    public function testUpdateInvestmentValueSuccess(): void
    {
        $investment = $this->createInvestmentEntity(['id' => 1, 'principal' => 1000.0, 'currentValue' => 1000.0]);
        $this->investmentRepoMock->method('find')->with(1)->willReturn($investment);
        $this->investmentRepoMock->expects($this->once())->method('save')->with($investment);

        $result = $this->service->updateInvestmentValue(1, 1500.0);

        $this->assertSame($investment, $result);
        $this->assertSame(1500.0, $investment->getCurrentValue());
        // Check return rate calculated (approx 50%? depends on implementation). We'll not check internal.
    }

    public function testUpdateInvestmentValueReturnsNullIfNotFound(): void
    {
        $this->investmentRepoMock->method('find')->with(999)->willReturn(null);
        $result = $this->service->updateInvestmentValue(999, 1500.0);
        $this->assertNull($result);
    }

    // ==================== markAsMature ====================

    public function testMarkAsMatureSuccess(): void
    {
        $investment = $this->createInvestmentEntity(['id' => 1, 'status' => 'active']);
        $this->investmentRepoMock->method('find')->with(1)->willReturn($investment);
        $this->investmentRepoMock->expects($this->once())->method('save')->with($investment);

        $result = $this->service->markAsMature(1);

        $this->assertTrue($result);
        $this->assertSame('matured', $investment->getStatus());
    }

    public function testMarkAsMatureReturnsFalseIfNotFound(): void
    {
        $this->investmentRepoMock->method('find')->with(999)->willReturn(null);
        $result = $this->service->markAsMature(999);
        $this->assertFalse($result);
    }

    // ==================== deleteInvestment ====================

    public function testDeleteInvestmentSuccess(): void
    {
        $investment = $this->createInvestmentEntity(['id' => 1]);
        $this->investmentRepoMock->method('find')->with(1)->willReturn($investment);
        $this->investmentRepoMock->expects($this->once())->method('delete')->with($investment);

        $result = $this->service->deleteInvestment(1);
        $this->assertTrue($result);
    }

    public function testDeleteInvestmentReturnsFalseIfNotFound(): void
    {
        $this->investmentRepoMock->method('find')->with(999)->willReturn(null);
        $result = $this->service->deleteInvestment(999);
        $this->assertFalse($result);
    }

    // ==================== getUserInvestmentSummary ====================

    public function testGetUserInvestmentSummary(): void
    {
        $userId = 'user-1';
        $inv1 = $this->createInvestmentEntity([
            'id' => 1,
            'type' => 'stock',
            'principal' => 10000.0,
            'currentValue' => 12000.0,
            'status' => 'active',
        ]);
        $inv2 = $this->createInvestmentEntity([
            'id' => 2,
            'type' => 'stock',
            'principal' => 5000.0,
            'currentValue' => 5500.0,
            'status' => 'active',
        ]);
        $inv3 = $this->createInvestmentEntity([
            'id' => 3,
            'type' => 'bond',
            'principal' => 20000.0,
            'currentValue' => 21000.0,
            'status' => 'matured',
        ]);

        $this->investmentRepoMock->method('findActiveByUser')->with($userId)->willReturn([$inv1, $inv2]);
        $this->investmentRepoMock->method('findMatureByUser')->with($userId)->willReturn([$inv3]);
        $this->investmentRepoMock->method('getTotalPrincipalByUser')->with($userId)->willReturn(35000.0);
        $this->investmentRepoMock->method('getTotalCurrentValueByUser')->with($userId)->willReturn(38500.0);

        $summary = $this->service->getUserInvestmentSummary($userId);

        $this->assertSame(35000.0, $summary['totalPrincipal']);
        $this->assertSame(38500.0, $summary['totalCurrentValue']);
        $this->assertSame(3500.0, $summary['totalProfit']);
        $this->assertSame(10.0, $summary['returnRate']); // (3500/35000)*100 = 10
        $this->assertSame(2, $summary['activeCount']);
        $this->assertSame(1, $summary['maturedCount']);
        $this->assertCount(1, $summary['investmentsByType']);

        // Check stock grouping (only active)
        $stockGroup = array_filter($summary['investmentsByType'], fn($t) => $t['type'] === 'stock');
        $this->assertCount(1, $stockGroup);
        $stock = reset($stockGroup);
        $this->assertSame(2, $stock['count']);
        $this->assertSame(15000.0, $stock['totalPrincipal']);
        $this->assertSame(17500.0, $stock['totalCurrentValue']);
    }

    // ==================== helper ====================

    private function createInvestmentEntity(array $data = []): Investment
    {
        $investment = new Investment();
        $investment->setUserId($data['userId'] ?? 'user-1');
        $investment->setInvestmentType($data['type'] ?? 'stock');
        $investment->setName($data['name'] ?? 'Test Investment');
        $investment->setPrincipal($data['principal'] ?? 1000.0);
        $investment->setCurrentValue($data['currentValue'] ?? $data['principal'] ?? 1000.0);
        if (isset($data['purchaseDate'])) {
            $investment->setPurchaseDate($data['purchaseDate']);
        } else {
            $investment->setPurchaseDate(new \DateTime());
        }
        if (array_key_exists('maturityDate', $data)) {
            $investment->setMaturityDate($data['maturityDate']);
        }
        if (isset($data['status'])) {
            $ref = new \ReflectionClass($investment);
            $prop = $ref->getProperty('status');
            $prop->setAccessible(true);
            $prop->setValue($investment, $data['status']);
        }
        if (isset($data['id'])) {
            $ref = new \ReflectionClass($investment);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($investment, $data['id']);
        }
        return $investment;
    }
}
