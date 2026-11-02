<?php

declare(strict_types=1);

namespace App\Tests\Integration\Investment;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Investment\Entity\Investment;
use App\Investment\Repository\InvestmentRepository;

#[AllowMockObjectsWithoutExpectations]
class InvestmentIntegrationTest extends DatabaseIntegrationTestCase
{
    private InvestmentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $em = self::getEntityManager();
        $this->repository = $em->getRepository(Investment::class);
    }

    public function testCreateInvestment(): void
    {
        $investment = new Investment();
        $investment->setUserId('user-123');
        $investment->setInvestmentType('stock');
        $investment->setName('Tech Corp Shares');
        $investment->setPrincipal(10000.00);
        $investment->setCurrentValue(10500.00);
        $investment->setReturnRate(5.0);
        $investment->setCurrency('USD');
        $investment->setPurchaseDate(new \DateTime('2024-01-15'));
        $investment->setStatus('active');

        $em = self::getEntityManager();
        $em->persist($investment);
        $em->flush();

        $this->assertNotNull($investment->getId());
        $this->assertSame('Tech Corp Shares', $investment->getName());
        $this->assertSame(10000.00, $investment->getPrincipal());
        $this->assertSame(5.0, $investment->getReturnRate());
    }

    public function testFindByUser(): void
    {
        $em = self::getEntityManager();

        // Investment for user A
        $invA = new Investment();
        $invA->setUserId('user-A');
        $invA->setInvestmentType('bond');
        $invA->setName('Gov Bond');
        $invA->setPrincipal(5000);
        $invA->setCurrentValue(5200);
        $invA->setReturnRate(4.0);
        $invA->setCurrency('USD');
        $invA->setPurchaseDate(new \DateTime('2024-02-01'));
        $invA->setStatus('active');
        $em->persist($invA);

        // Investment for user B
        $invB = new Investment();
        $invB->setUserId('user-B');
        $invB->setInvestmentType('stock');
        $invB->setName('Stock XYZ');
        $invB->setPrincipal(2000);
        $invB->setCurrentValue(2100);
        $invB->setReturnRate(5.0);
        $invB->setCurrency('EUR');
        $invB->setPurchaseDate(new \DateTime('2024-03-01'));
        $invB->setStatus('active');
        $em->persist($invB);

        $em->flush();

        $userAInvestments = $this->repository->findByUser('user-A');
        $this->assertCount(1, $userAInvestments);
        $this->assertSame('user-A', $userAInvestments[0]->getUserId());
        $this->assertSame('Gov Bond', $userAInvestments[0]->getName());

        $userBInvestments = $this->repository->findByUser('user-B');
        $this->assertCount(1, $userBInvestments);
        $this->assertSame('Stock XYZ', $userBInvestments[0]->getName());
    }

    public function testFindActiveByUser(): void
    {
        $em = self::getEntityManager();

        $inv1 = new Investment();
        $inv1->setUserId('user-active');
        $inv1->setInvestmentType('savings');
        $inv1->setName('Savings Account');
        $inv1->setPrincipal(1000);
        $inv1->setCurrentValue(1100);
        $inv1->setReturnRate(2.5);
        $inv1->setCurrency('USD');
        $inv1->setPurchaseDate(new \DateTime('2024-01-01'));
        $inv1->setStatus('active');
        $em->persist($inv1);

        $inv2 = new Investment();
        $inv2->setUserId('user-active');
        $inv2->setInvestmentType('crypto');
        $inv2->setName('Bitcoin');
        $inv2->setPrincipal(500);
        $inv2->setCurrentValue(600);
        $inv2->setReturnRate(10.0);
        $inv2->setCurrency('USD');
        $inv2->setPurchaseDate(new \DateTime('2024-02-01'));
        $inv2->setStatus('matured');
        $em->persist($inv2);

        $em->flush();

        $active = $this->repository->findActiveByUser('user-active');
        $this->assertCount(1, $active);
        $this->assertSame('Savings Account', $active[0]->getName());
    }

    public function testFindByUserAndType(): void
    {
        $em = self::getEntityManager();

        $inv = new Investment();
        $inv->setUserId('user-stock');
        $inv->setInvestmentType('stock');
        $inv->setName('Apple Inc.');
        $inv->setPrincipal(1500);
        $inv->setCurrentValue(1600);
        $inv->setReturnRate(6.5);
        $inv->setCurrency('USD');
        $inv->setPurchaseDate(new \DateTime('2024-03-15'));
        $inv->setStatus('active');
        $em->persist($inv);

        $inv2 = new Investment();
        $inv2->setUserId('user-stock');
        $inv2->setInvestmentType('bond');
        $inv2->setName('Corporate Bond');
        $inv2->setPrincipal(2000);
        $inv2->setCurrentValue(2050);
        $inv2->setReturnRate(2.5);
        $inv2->setCurrency('USD');
        $inv2->setPurchaseDate(new \DateTime('2024-04-01'));
        $inv2->setStatus('active');
        $em->persist($inv2);

        $em->flush();

        $stocks = $this->repository->findByUserAndType('user-stock', 'stock');
        $this->assertCount(1, $stocks);
        $this->assertSame('Apple Inc.', $stocks[0]->getName());
    }

    public function testUpdateInvestment(): void
    {
        $em = self::getEntityManager();

        $investment = new Investment();
        $investment->setUserId('user-update');
        $investment->setInvestmentType('etf');
        $investment->setName('S&P 500 ETF');
        $investment->setPrincipal(5000);
        $investment->setCurrentValue(5100);
        $investment->setReturnRate(2.0);
        $investment->setCurrency('USD');
        $investment->setPurchaseDate(new \DateTime('2024-05-01'));
        $investment->setStatus('active');
        $em->persist($investment);
        $em->flush();

        $id = $investment->getId();

        // Update current value
        $investment->setCurrentValue(5500.00);
        $investment->setReturnRate(10.0);
        $em->flush();

        $updated = $this->repository->find($id);
        $this->assertSame(5500.00, $updated->getCurrentValue());
        $this->assertSame(10.0, $updated->getReturnRate());
    }
}
