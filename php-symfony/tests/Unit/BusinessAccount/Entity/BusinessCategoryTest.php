<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccount\Entity\BusinessCategory;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BusinessCategoryTest extends TestCase
{
    private function createCategory(array $overrides = []): BusinessCategory
    {
        $cat = new BusinessCategory();
        $cat->setName('General');
        $cat->setSlug('general');
        foreach ($overrides as $prop => $value) {
            $setter = 'set' . $prop;
            if (method_exists($cat, $setter)) {
                $cat->$setter($value);
            }
        }
        return $cat;
    }

    public function testConstructorInitializesCreatedAt(): void
    {
        $cat = $this->createCategory([]);
        $this->assertInstanceOf(\DateTime::class, $cat->getCreatedAt());
    }

    public function testDefaultValues(): void
    {
        $cat = $this->createCategory([]);
        $this->assertSame(0, $cat->getDisplayOrder());
        $this->assertTrue($cat->isActive());
        $this->assertNull($cat->getIconUrl());
    }

    public function testGettersAndSetters(): void
    {
        $cat = $this->createCategory([]);

        $cat->setName('Tech');
        $this->assertSame('Tech', $cat->getName());

        $cat->setSlug('tech');
        $this->assertSame('tech', $cat->getSlug());

        $cat->setIconUrl('icon.png');
        $this->assertSame('icon.png', $cat->getIconUrl());

        $cat->setDisplayOrder(5);
        $this->assertSame(5, $cat->getDisplayOrder());

        $cat->setActive(false);
        $this->assertFalse($cat->isActive());
    }
}
