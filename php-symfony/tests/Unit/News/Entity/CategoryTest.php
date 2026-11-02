<?php declare(strict_types=1);

namespace App\Tests\Unit\News\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\News\Entity\Category;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CategoryTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $cat = new Category();
        $this->assertSame(0, $cat->getArticleCount());
    }

    public function testSettersAndGetters(): void
    {
        $cat = new Category();
        $cat->setName('Technology');
        $this->assertSame('Technology', $cat->getName());

        $cat->setSlug('technology');
        $this->assertSame('technology', $cat->getSlug());

        $cat->setArticleCount(3);
        $this->assertSame(3, $cat->getArticleCount());
    }

    public function testSetIdAfterPersist(): void
    {
        $cat = new Category();
        // id is uninitialized until set (by Doctrine)
        $cat->setId(5);
        $this->assertSame(5, $cat->getId());
    }
}
