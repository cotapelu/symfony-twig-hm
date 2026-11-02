<?php declare(strict_types=1);

namespace App\Tests\Unit\Budgeting\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Budgeting\Entity\Budget;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Budgeting\Entity\Budget
 */
#[AllowMockObjectsWithoutExpectations]
class BudgetTest extends TestCase
{
    private \DateTime $startDate;

    protected function setUp(): void
    {
        $this->startDate = new \DateTime('2026-01-01');
    }

    public function testConstructorSetsAllProperties(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);

        $this->assertNull($budget->getId());
        $this->assertEquals(123, $budget->getUserId());
        $this->assertEquals(456, $budget->getCategoryId());
        $this->assertEquals(1000.00, $budget->getAmount());
        $this->assertEquals('monthly', $budget->getPeriod());
        $this->assertEquals($this->startDate, $budget->getStartDate());
        $this->assertNull($budget->getNote());
        $this->assertTrue($budget->isActive());
    }

    public function testConstructorCalculatesEndDateForMonthly(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $expectedEnd = clone $this->startDate;
        $expectedEnd->modify('+1 month');

        $this->assertEquals($expectedEnd, $budget->getEndDate());
    }

    public function testConstructorCalculatesEndDateForWeekly(): void
    {
        $budget = new Budget(123, 456, 250.00, 'weekly', $this->startDate);
        $expectedEnd = clone $this->startDate;
        $expectedEnd->modify('+1 week');

        $this->assertEquals($expectedEnd, $budget->getEndDate());
    }

    public function testConstructorCalculatesEndDateForYearly(): void
    {
        $budget = new Budget(123, 456, 12000.00, 'yearly', $this->startDate);
        $expectedEnd = clone $this->startDate;
        $expectedEnd->modify('+1 year');

        $this->assertEquals($expectedEnd, $budget->getEndDate());
    }

    public function testConstructorClonesStartDateToPreventMutation(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        
        // Mutate original date
        $this->startDate->modify('+1 year');
        
        // Budget's start date should remain unchanged
        $this->assertEquals('2026-01-01', $budget->getStartDate()->format('Y-m-d'));
    }

    public function testSetAmountUpdatesAmount(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $result = $budget->setAmount(2000.00);

        $this->assertSame($budget, $result);
        $this->assertEquals(2000.00, $budget->getAmount());
    }

    public function testSetAmountWithZero(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $budget->setAmount(0.0);

        $this->assertEquals(0.0, $budget->getAmount());
    }

    public function testSetAmountWithNegative(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $budget->setAmount(-500.00);

        $this->assertEquals(-500.00, $budget->getAmount());
    }

    public function testSetPeriodUpdatesPeriod(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $result = $budget->setPeriod('weekly');

        $this->assertSame($budget, $result);
        $this->assertEquals('weekly', $budget->getPeriod());
    }

    public function testSetPeriodWithInvalidValue(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $budget->setPeriod('invalid_period');

        $this->assertEquals('invalid_period', $budget->getPeriod());
    }

    public function testSetNoteWithValidString(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $result = $budget->setNote('Test note');

        $this->assertSame($budget, $result);
        $this->assertEquals('Test note', $budget->getNote());
    }

    public function testSetNoteWithNull(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $budget->setNote('Initial note');
        $budget->setNote(null);

        $this->assertNull($budget->getNote());
    }

    public function testSetNoteWithEmptyString(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $budget->setNote('');

        $this->assertEquals('', $budget->getNote());
    }

    public function testSetActiveToFalse(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $result = $budget->setActive(false);

        $this->assertSame($budget, $result);
        $this->assertFalse($budget->isActive());
    }

    public function testSetActiveToTrue(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $budget->setActive(false);
        $budget->setActive(true);

        $this->assertTrue($budget->isActive());
    }

    public function testGetIdReturnsNullInitially(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $this->assertNull($budget->getId());
    }

    public function testFluentInterfaceForAllSetters(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        
        $result = $budget
            ->setAmount(2000.00)
            ->setPeriod('yearly')
            ->setNote('Fluent test')
            ->setActive(false);

        $this->assertSame($budget, $result);
        $this->assertEquals(2000.00, $budget->getAmount());
        $this->assertEquals('yearly', $budget->getPeriod());
        $this->assertEquals('Fluent test', $budget->getNote());
        $this->assertFalse($budget->isActive());
    }

    public function testGetEndDateReturnsDateTimeObject(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $endDate = $budget->getEndDate();

        $this->assertInstanceOf(\DateTime::class, $endDate);
    }

    public function testGetStartDateReturnsDateTimeObject(): void
    {
        $budget = new Budget(123, 456, 1000.00, 'monthly', $this->startDate);
        $startDate = $budget->getStartDate();

        $this->assertInstanceOf(\DateTime::class, $startDate);
    }

    public function testBudgetWithLargeAmount(): void
    {
        $budget = new Budget(123, 456, 999999999.99, 'monthly', $this->startDate);
        $this->assertEquals(999999999.99, $budget->getAmount());
    }

    public function testBudgetWithVerySmallAmount(): void
    {
        $budget = new Budget(123, 456, 0.01, 'monthly', $this->startDate);
        $this->assertEquals(0.01, $budget->getAmount());
    }
}
