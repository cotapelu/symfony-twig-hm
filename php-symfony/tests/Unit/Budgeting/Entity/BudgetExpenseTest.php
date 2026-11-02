<?php declare(strict_types=1);

namespace App\Tests\Unit\Budgeting\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Budgeting\Entity\BudgetExpense;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Budgeting\Entity\BudgetExpense
 */
#[AllowMockObjectsWithoutExpectations]
class BudgetExpenseTest extends TestCase
{
    private \DateTime $expenseDate;

    protected function setUp(): void
    {
        $this->expenseDate = new \DateTime('2026-02-15');
    }

    public function testConstructorSetsAllProperties(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);

        $this->assertNull($expense->getId());
        $this->assertEquals(123, $expense->getUserId());
        $this->assertEquals(456, $expense->getBudgetId());
        $this->assertEquals(789, $expense->getCategoryId());
        $this->assertEquals(150.00, $expense->getAmount());
        $this->assertEquals($this->expenseDate, $expense->getExpenseDate());
        $this->assertNull($expense->getDescription());
        $this->assertNull($expense->getMerchant());
        $this->assertNull($expense->getTransactionId());
    }

    public function testConstructorClonesExpenseDateToPreventMutation(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        
        // Mutate original date
        $this->expenseDate->modify('+1 year');
        
        // Expense's date should remain unchanged
        $this->assertEquals('2026-02-15', $expense->getExpenseDate()->format('Y-m-d'));
    }

    public function testSetDescriptionWithValidString(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $result = $expense->setDescription('Grocery shopping');

        $this->assertSame($expense, $result);
        $this->assertEquals('Grocery shopping', $expense->getDescription());
    }

    public function testSetDescriptionWithNull(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $expense->setDescription('Initial description');
        $result = $expense->setDescription(null);

        $this->assertSame($expense, $result);
        $this->assertNull($expense->getDescription());
    }

    public function testSetDescriptionWithEmptyString(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $expense->setDescription('');

        $this->assertEquals('', $expense->getDescription());
    }

    public function testSetDescriptionWithLongText(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $longDesc = str_repeat('A', 500);
        $expense->setDescription($longDesc);

        $this->assertEquals($longDesc, $expense->getDescription());
        $this->assertEquals(500, strlen($expense->getDescription()));
    }

    public function testSetMerchantWithValidString(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $result = $expense->setMerchant('Walmart');

        $this->assertSame($expense, $result);
        $this->assertEquals('Walmart', $expense->getMerchant());
    }

    public function testSetMerchantWithNull(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $expense->setMerchant('Initial merchant');
        $expense->setMerchant(null);

        $this->assertNull($expense->getMerchant());
    }

    public function testSetMerchantWithEmptyString(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $expense->setMerchant('');

        $this->assertEquals('', $expense->getMerchant());
    }

    public function testSetMerchantWithOnlineStore(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $expense->setMerchant('Amazon.com');

        $this->assertEquals('Amazon.com', $expense->getMerchant());
    }

    public function testSetTransactionIdWithValidId(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $result = $expense->setTransactionId(9999);

        $this->assertSame($expense, $result);
        $this->assertEquals(9999, $expense->getTransactionId());
    }

    public function testSetTransactionIdWithNull(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $expense->setTransactionId(9999);
        $result = $expense->setTransactionId(null);

        $this->assertSame($expense, $result);
        $this->assertNull($expense->getTransactionId());
    }

    public function testSetTransactionIdWithZero(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $expense->setTransactionId(0);

        $this->assertEquals(0, $expense->getTransactionId());
    }

    public function testFluentInterfaceForAllSetters(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        
        $result = $expense
            ->setDescription('Test expense')
            ->setMerchant('Test Store')
            ->setTransactionId(12345);

        $this->assertSame($expense, $result);
        $this->assertEquals('Test expense', $expense->getDescription());
        $this->assertEquals('Test Store', $expense->getMerchant());
        $this->assertEquals(12345, $expense->getTransactionId());
    }

    public function testGetIdReturnsNullInitially(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $this->assertNull($expense->getId());
    }

    public function testGetExpenseDateReturnsDateTimeObject(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $date = $expense->getExpenseDate();

        $this->assertInstanceOf(\DateTime::class, $date);
    }

    public function testExpenseWithLargeAmount(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 999999.99, $this->expenseDate);
        $this->assertEquals(999999.99, $expense->getAmount());
    }

    public function testExpenseWithVerySmallAmount(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 0.01, $this->expenseDate);
        $this->assertEquals(0.01, $expense->getAmount());
    }

    public function testExpenseWithZeroAmount(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 0.00, $this->expenseDate);
        $this->assertEquals(0.00, $expense->getAmount());
    }

    public function testExpenseWithNegativeAmount(): void
    {
        $expense = new BudgetExpense(123, 456, 789, -50.00, $this->expenseDate);
        $this->assertEquals(-50.00, $expense->getAmount());
    }

    public function testExpenseWithSpecialCharactersInDescription(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $expense->setDescription('Lunch at McDonald\'s & drinks');

        $this->assertEquals('Lunch at McDonald\'s & drinks', $expense->getDescription());
    }

    public function testExpenseWithUnicodeDescription(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $expense->setDescription('Mua sắm tại siêu thị');

        $this->assertEquals('Mua sắm tại siêu thị', $expense->getDescription());
    }

    public function testExpenseWithEmojiInDescription(): void
    {
        $expense = new BudgetExpense(123, 456, 789, 150.00, $this->expenseDate);
        $expense->setDescription('Coffee ☕ and cake 🍰');

        $this->assertEquals('Coffee ☕ and cake 🍰', $expense->getDescription());
    }

    public function testExpenseWithFutureDate(): void
    {
        $futureDate = new \DateTime('2027-01-01');
        $expense = new BudgetExpense(123, 456, 789, 150.00, $futureDate);
        
        $this->assertEquals('2027-01-01', $expense->getExpenseDate()->format('Y-m-d'));
    }

    public function testExpenseWithPastDate(): void
    {
        $pastDate = new \DateTime('2020-01-01');
        $expense = new BudgetExpense(123, 456, 789, 150.00, $pastDate);
        
        $this->assertEquals('2020-01-01', $expense->getExpenseDate()->format('Y-m-d'));
    }
}
