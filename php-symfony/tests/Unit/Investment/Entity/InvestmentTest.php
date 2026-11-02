<?php declare(strict_types=1);

namespace App\Tests\Unit\Investment\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Investment\Entity\Investment;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class InvestmentTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $inv = new Investment();
        $this->assertSame(0.00, $inv->getCurrentValue());
        $this->assertSame(0.00, $inv->getReturnRate());
        $this->assertSame('VND', $inv->getCurrency());
        $this->assertSame('active', $inv->getStatus());
        $this->assertInstanceOf(\DateTime::class, $inv->getCreatedAt());
    }

    public function testSetUserId(): void
    {
        $inv = new Investment();
        $inv->setUserId('user123');
        $this->assertSame('user123', $inv->getUserId());
    }

    public function testSetUserIdTrimsAndEscapes(): void
    {
        $inv = new Investment();
        $inv->setUserId('  user <test>  ');
        // htmlspecialchars converts < to &lt; etc
        $this->assertSame('user &lt;test&gt;', $inv->getUserId());
    }

    public function testSetUserIdInvalid(): void
    {
        $inv = new Investment();
        $this->expectException(\InvalidArgumentException::class);
        $inv->setUserId(''); // empty after trim
    }

    public function testSetInvestmentType(): void
    {
        $inv = new Investment();
        $inv->setInvestmentType('stock');
        $this->assertSame('stock', $inv->getInvestmentType());
    }

    public function testSetInvestmentTypeInvalid(): void
    {
        $inv = new Investment();
        $this->expectException(\InvalidArgumentException::class);
        $inv->setInvestmentType('unknown');
    }

    public function testSetName(): void
    {
        $inv = new Investment();
        $inv->setName('My Investment');
        $this->assertSame('My Investment', $inv->getName());
    }

    public function testSetNameTrimsAndEscapes(): void
    {
        $inv = new Investment();
        $inv->setName('  Test & Demo  ');
        $this->assertSame('Test &amp; Demo', $inv->getName());
    }

    public function testSetNameInvalid(): void
    {
        $inv = new Investment();
        $this->expectException(\InvalidArgumentException::class);
        $inv->setName(''); // empty after trim
    }

    public function testSetPrincipal(): void
    {
        $inv = new Investment();
        $inv->setPrincipal(1000.00);
        $this->assertSame(1000.00, $inv->getPrincipal());
    }

    public function testSetPrincipalInvalid(): void
    {
        $inv = new Investment();
        $this->expectException(\InvalidArgumentException::class);
        $inv->setPrincipal(0);
    }

    public function testSetCurrentValue(): void
    {
        $inv = new Investment();
        $inv->setCurrentValue(1500.00);
        $this->assertSame(1500.00, $inv->getCurrentValue());
    }

    public function testSetCurrentValueInvalid(): void
    {
        $inv = new Investment();
        $this->expectException(\InvalidArgumentException::class);
        $inv->setCurrentValue(-100);
    }

    public function testSetReturnRate(): void
    {
        $inv = new Investment();
        $inv->setReturnRate(5.5);
        $this->assertSame(5.5, $inv->getReturnRate());
    }

    public function testCalculateReturnRate(): void
    {
        $inv = new Investment();
        $inv->setPrincipal(1000);
        $inv->setCurrentValue(1100);
        $inv->calculateReturnRate();
        $this->assertSame(10.0, $inv->getReturnRate());
    }

    // Note: Principal must be >0 per entity validation, so zero principal scenario is not reachable via public API.
    // The calculateReturnRate method safely handles principal > 0 only.


    public function testSetCurrency(): void
    {
        $inv = new Investment();
        $inv->setCurrency('usd');
        $this->assertSame('USD', $inv->getCurrency());
    }

    public function testSetCurrencyInvalid(): void
    {
        $inv = new Investment();
        $this->expectException(\InvalidArgumentException::class);
        $inv->setCurrency('US'); // 2 letters
    }

    public function testSetPurchaseDate(): void
    {
        $inv = new Investment();
        $date = new \DateTime('2025-01-01');
        $inv->setPurchaseDate($date);
        $this->assertSame($date, $inv->getPurchaseDate());
    }

    public function testSetPurchaseDateInFutureThrows(): void
    {
        $inv = new Investment();
        $future = (new \DateTime())->modify('+1 day');
        $this->expectException(\InvalidArgumentException::class);
        $inv->setPurchaseDate($future);
    }

    public function testSetMaturityDate(): void
    {
        $inv = new Investment();
        $purchase = new \DateTime('2025-01-01');
        $inv->setPurchaseDate($purchase);
        $maturity = new \DateTime('2025-12-31');
        $inv->setMaturityDate($maturity);
        $this->assertSame($maturity, $inv->getMaturityDate());
    }

    public function testSetMaturityDateBeforePurchaseThrows(): void
    {
        $inv = new Investment();
        $purchase = new \DateTime('2025-01-01');
        $inv->setPurchaseDate($purchase);
        $early = new \DateTime('2024-12-01');
        $this->expectException(\InvalidArgumentException::class);
        $inv->setMaturityDate($early);
    }

    public function testSetMaturityDateNullable(): void
    {
        $inv = new Investment();
        $purchase = new \DateTime('2025-01-01');
        $inv->setPurchaseDate($purchase);
        $inv->setMaturityDate(null);
        $this->assertNull($inv->getMaturityDate());
    }

    public function testSetStatus(): void
    {
        $inv = new Investment();
        $inv->setStatus('matured');
        $this->assertSame('matured', $inv->getStatus());
    }

    public function testSetStatusInvalid(): void
    {
        $inv = new Investment();
        $this->expectException(\InvalidArgumentException::class);
        $inv->setStatus('unknown');
    }

    public function testIdGetterAndSetter(): void
    {
        $inv = new Investment();
        $ref = new \ReflectionProperty($inv, 'id');
        $ref->setAccessible(true);
        $ref->setValue($inv, 5);
        $this->assertSame(5, $inv->getId());
    }
}
