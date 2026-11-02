<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\BusinessCategory;
use App\BusinessAccount\Repository\BusinessCategoryRepository;
use App\BusinessAccount\Service\BusinessProfileService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AllowMockObjectsWithoutExpectations]
class BusinessProfileServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private BusinessCategoryRepository&MockObject $categoryRepo;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private BusinessProfileService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->categoryRepo = $this->createMock(BusinessCategoryRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->em->method('getRepository')->with('App\BusinessAccount\Entity\BusinessCategory')->willReturn($this->categoryRepo);
        $this->service = new BusinessProfileService($this->em, $this->passwordHasher);
    }

    public function testCreateBusiness(): void
    {
        $data = [
            'name' => 'My Biz',
            'email' => 'owner@example.com',
            'phone' => '123-456-7890',
            'description' => 'Best biz',
            'address' => '123 Main St',
            'latitude' => 10.0,
            'longitude' => 20.0,
            'website' => 'https://example.com',
            'instagram' => '@mybiz',
            'facebook' => 'mybiz',
            'category' => 5,
        ];
        $category = $this->createMock(BusinessCategory::class);
        $category->method('getName')->willReturn('Retail');
        $this->categoryRepo->expects($this->once())->method('find')->with(5)->willReturn($category);

        $this->em->expects($this->once())->method('persist')->with($this->callback(function($arg) use ($data) {
            $this->assertInstanceOf(Business::class, $arg);
            $this->assertSame($data['name'], $arg->getName());
            $this->assertSame($data['email'], $arg->getEmail());
            $this->assertSame($data['phone'], $arg->getPhone());
            $this->assertSame($data['description'], $arg->getDescription());
            $this->assertSame($data['address'], $arg->getAddress());
            $this->assertSame($data['latitude'], $arg->getLatitude());
            $this->assertSame($data['longitude'], $arg->getLongitude());
            $this->assertSame($data['website'], $arg->getWebsite());
            $this->assertSame($data['instagram'], $arg->getInstagram());
            $this->assertSame($data['facebook'], $arg->getFacebook());
            $this->assertSame('Retail', $arg->getCategory());
            $this->assertSame('pending', $arg->getStatus());
            $this->assertFalse($arg->isIsVerified());
            return true;
        }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createBusiness($data);
        $this->assertInstanceOf(Business::class, $result);
    }

    public function testCreateBusinessWithCategoryObject(): void
    {
        $category = new BusinessCategory();
        // set name via reflection or setter
        $ref = new \ReflectionProperty($category, 'name');
        $ref->setAccessible(true);
        $ref->setValue($category, 'Food');
        $data = [
            'name' => 'Restaurant',
            'email' => 'resto@example.com',
            'category' => $category,
        ];
        $this->categoryRepo->expects($this->never())->method('find');
        $this->em->expects($this->once())->method('persist')->with($this->callback(fn($arg) => $arg->getCategory() === 'Food'));
        $this->em->expects($this->once())->method('flush');
        $this->service->createBusiness($data);
    }

    public function testCreateBusinessWithPassword(): void
    {
        $data = [
            'name' => 'Biz A',
            'email' => 'a@example.com',
            'password' => 'secret123',
        ];
        $hashed = 'hashed_pw';
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($this->callback(fn($arg) => $arg instanceof Business && $arg->getName() === 'Biz A'), 'secret123')
            ->willReturn($hashed);
        $this->em->expects($this->once())->method('persist')->with($this->callback(fn($arg) => $arg->getPassword() === $hashed));
        $this->em->expects($this->once())->method('flush');
        $this->service->createBusiness($data);
    }

    public function testCreateBusinessWithoutPassword(): void
    {
        $data = [
            'name' => 'Biz B',
            'email' => 'b@example.com',
        ];
        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->em->expects($this->once())->method('persist')->with($this->callback(fn($arg) => $arg->getPassword() === null));
        $this->em->expects($this->once())->method('flush');
        $this->service->createBusiness($data);
    }

    public function testUpdateBusiness(): void
    {
        $business = new Business();
        // Set some initial state via reflection for fields being updated
        $refName = new \ReflectionProperty($business, 'name');
        $refName->setAccessible(true);
        $refName->setValue($business, 'Old Name');
        $refDesc = new \ReflectionProperty($business, 'description');
        $refDesc->setAccessible(true);
        $refDesc->setValue($business, 'Old desc');
        // etc.

        $updateData = [
            'name' => 'New Name',
            'description' => 'New desc',
            'logo' => 'https://example.com/logo.png',
            'coverImage' => 'https://example.com/cover.jpg',
        ];
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->updateBusiness($business, $updateData);
        $this->assertSame($business, $result);
        $this->assertSame('New Name', $business->getName());
        $this->assertSame('New desc', $business->getDescription());
        $this->assertSame('https://example.com/logo.png', $business->getLogo());
        $this->assertSame('https://example.com/cover.jpg', $business->getCoverImage());
    }
}
