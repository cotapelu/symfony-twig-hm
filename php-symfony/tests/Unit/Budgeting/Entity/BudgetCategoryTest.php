<?php declare(strict_types=1);

namespace App\Tests\Unit\Budgeting\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Budgeting\Entity\BudgetCategory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Budgeting\Entity\BudgetCategory
 */
#[AllowMockObjectsWithoutExpectations]
class BudgetCategoryTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);

        $this->assertNull($category->getId());
        $this->assertEquals('Food', $category->getName());
        $this->assertEquals('expense', $category->getType());
        $this->assertEquals('#FF0000', $category->getColor());
        $this->assertEquals(123, $category->getUserId());
        $this->assertNull($category->getDescription());
        $this->assertNull($category->getParentId());
        $this->assertFalse($category->isDefault());
    }

    public function testConstructorWithIncomeType(): void
    {
        $category = new BudgetCategory('Salary', 'income', '#00FF00', 123);

        $this->assertEquals('Salary', $category->getName());
        $this->assertEquals('income', $category->getType());
    }

    public function testConstructorWithSavingType(): void
    {
        $category = new BudgetCategory('Emergency Fund', 'saving', '#0000FF', 123);

        $this->assertEquals('Emergency Fund', $category->getName());
        $this->assertEquals('saving', $category->getType());
    }

    public function testSetNameUpdatesName(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $result = $category->setName('Groceries');

        $this->assertSame($category, $result);
        $this->assertEquals('Groceries', $category->getName());
    }

    public function testSetNameWithEmptyString(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $category->setName('');

        $this->assertEquals('', $category->getName());
    }

    public function testSetNameWithLongName(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $longName = str_repeat('A', 255);
        $category->setName($longName);

        $this->assertEquals($longName, $category->getName());
        $this->assertEquals(255, strlen($category->getName()));
    }

    public function testSetTypeUpdatesType(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $result = $category->setType('income');

        $this->assertSame($category, $result);
        $this->assertEquals('income', $category->getType());
    }

    public function testSetTypeWithSaving(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $category->setType('saving');

        $this->assertEquals('saving', $category->getType());
    }

    public function testSetTypeWithInvalidValue(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $category->setType('invalid_type');

        $this->assertEquals('invalid_type', $category->getType());
    }

    public function testSetDescriptionWithValidString(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $result = $category->setDescription('Monthly grocery budget');

        $this->assertSame($category, $result);
        $this->assertEquals('Monthly grocery budget', $category->getDescription());
    }

    public function testSetDescriptionWithNull(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $category->setDescription('Initial description');
        $result = $category->setDescription(null);

        $this->assertSame($category, $result);
        $this->assertNull($category->getDescription());
    }

    public function testSetDescriptionWithEmptyString(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $category->setDescription('');

        $this->assertEquals('', $category->getDescription());
    }

    public function testSetColorUpdatesColor(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $result = $category->setColor('#00FF00');

        $this->assertSame($category, $result);
        $this->assertEquals('#00FF00', $category->getColor());
    }

    public function testSetColorWithRGB(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $category->setColor('rgb(255, 0, 0)');

        $this->assertEquals('rgb(255, 0, 0)', $category->getColor());
    }

    public function testSetColorWithNamedColor(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $category->setColor('red');

        $this->assertEquals('red', $category->getColor());
    }

    public function testSetParentIdWithValidId(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $result = $category->setParentId(999);

        $this->assertSame($category, $result);
        $this->assertEquals(999, $category->getParentId());
    }

    public function testSetParentIdWithNull(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $category->setParentId(999);
        $category->setParentId(null);

        $this->assertNull($category->getParentId());
    }

    public function testSetParentIdWithZero(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $category->setParentId(0);

        $this->assertEquals(0, $category->getParentId());
    }

    public function testSetDefaultToTrue(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $result = $category->setDefault(true);

        $this->assertSame($category, $result);
        $this->assertTrue($category->isDefault());
    }

    public function testSetDefaultToFalse(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $category->setDefault(true);
        $category->setDefault(false);

        $this->assertFalse($category->isDefault());
    }

    public function testFluentInterfaceForAllSetters(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        
        $result = $category
            ->setName('Groceries')
            ->setType('saving')
            ->setDescription('Test description')
            ->setColor('#0000FF')
            ->setParentId(999)
            ->setDefault(true);

        $this->assertSame($category, $result);
        $this->assertEquals('Groceries', $category->getName());
        $this->assertEquals('saving', $category->getType());
        $this->assertEquals('Test description', $category->getDescription());
        $this->assertEquals('#0000FF', $category->getColor());
        $this->assertEquals(999, $category->getParentId());
        $this->assertTrue($category->isDefault());
    }

    public function testGetIdReturnsNullInitially(): void
    {
        $category = new BudgetCategory('Food', 'expense', '#FF0000', 123);
        $this->assertNull($category->getId());
    }

    public function testCategoryWithSpecialCharactersInName(): void
    {
        $category = new BudgetCategory('Food & Drinks', 'expense', '#FF0000', 123);
        $this->assertEquals('Food & Drinks', $category->getName());
    }

    public function testCategoryWithUnicodeName(): void
    {
        $category = new BudgetCategory('Ăn uống', 'expense', '#FF0000', 123);
        $this->assertEquals('Ăn uống', $category->getName());
    }

    public function testCategoryWithEmojiColor(): void
        {
        $category = new BudgetCategory('Fun', 'expense', '😀', 123);
        $this->assertEquals('😀', $category->getColor());
    }
}
