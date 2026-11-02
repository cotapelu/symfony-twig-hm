<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccount\Entity\BusinessCategory;
use App\BusinessAccount\Repository\BusinessCategoryRepository;
use App\BusinessAccount\Service\BusinessCategoryService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BusinessCategoryServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private BusinessCategoryRepository&MockObject $categoryRepo;
    private BusinessCategoryService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->categoryRepo = $this->createMock(BusinessCategoryRepository::class);
        $this->em->method('getRepository')->with(BusinessCategory::class)->willReturn($this->categoryRepo);
        $this->service = new BusinessCategoryService($this->em);
    }

    public function testCreateCategory(): void
    {
        $name = 'Tech';
        $slug = 'tech';
        $desc = 'Technology businesses';
        $category = new BusinessCategory();
        // We'll not actually use entity methods; just test flush called
        $this->em->expects($this->once())->method('persist')->with($this->callback(function($arg) use ($name, $slug, $desc) {
            $this->assertInstanceOf(BusinessCategory::class, $arg);
            $this->assertEquals($name, $arg->getName());
            $this->assertEquals($slug, $arg->getSlug());
            $this->assertEquals($desc, $arg->getDescription());
            $this->assertTrue($arg->isActive());
            return true;
        }));
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->createCategory($name, $slug, $desc);
        $this->assertInstanceOf(BusinessCategory::class, $result);
    }

    public function testGetCategoryById(): void
    {
        $id = 5;
        $category = $this->createMock(BusinessCategory::class);
        $this->categoryRepo->expects($this->once())->method('find')->with($id)->willReturn($category);
        $result = $this->service->getCategoryById($id);
        $this->assertSame($category, $result);
    }

    public function testGetAllCategories(): void
    {
        $categories = [new BusinessCategory(), new BusinessCategory()];
        $this->categoryRepo->expects($this->once())->method('findAll')->willReturn($categories);
        $result = $this->service->getAllCategories();
        $this->assertSame($categories, $result);
    }
}
