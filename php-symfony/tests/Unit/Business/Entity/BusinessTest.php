<?php declare(strict_types=1);

namespace App\Tests\Unit\Business\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Business\Entity\Business;
use App\Core\Entity\User;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BusinessTest extends TestCase
{
    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('owner@example.com');
        $user->setPasswordHash('hashed');
        return $user;
    }

    private function createBusiness(?User $owner = null, ?string $name = null): Business
    {
        return new Business(
            owner: $owner ?? $this->createUser(),
            name: $name ?? 'My Business'
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $user = $this->createUser();
        $business = $this->createBusiness($user, 'Acme Corp');

        $this->assertIsString($business->getId());
        $this->assertNotEmpty($business->getId());
        $this->assertSame($user, $business->getOwner());
        $this->assertSame('Acme Corp', $business->getName());
        $this->assertNull($business->getType());
        $this->assertNull($business->getLicense());
        $this->assertSame(Business::STATUS_PENDING, $business->getStatus());
        $this->assertFalse($business->isActive());
        $this->assertInstanceOf(\DateTime::class, $business->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $business->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $business = $this->createBusiness();

        $business->setName('New Name');
        $this->assertSame('New Name', $business->getName());

        $business->setType('retail');
        $this->assertSame('retail', $business->getType());

        $business->setLicense('LIC-12345');
        $this->assertSame('LIC-12345', $business->getLicense());

        $business->setStatus(Business::STATUS_ACTIVE);
        $this->assertSame(Business::STATUS_ACTIVE, $business->getStatus());
        $this->assertTrue($business->isActive());

        $business->setStatus(Business::STATUS_INACTIVE);
        $this->assertFalse($business->isActive());

        $business->setOwner($this->createUser());
        $this->assertInstanceOf(User::class, $business->getOwner());
    }

    public function testToArray(): void
    {
        $user = $this->createUser();
        $business = $this->createBusiness($user, 'TestBiz');
        $business->setType('service');
        $business->setLicense('LIC-001');
        $business->setStatus(Business::STATUS_ACTIVE);

        $arr = $business->toArray();
        $this->assertArrayHasKey('id', $arr);
        $this->assertSame($user->getId(), $arr['ownerId']);
        $this->assertSame('TestBiz', $arr['name']);
        $this->assertSame('service', $arr['type']);
        $this->assertSame('LIC-001', $arr['license']);
        $this->assertSame(Business::STATUS_ACTIVE, $arr['status']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['createdAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['updatedAt']);
    }
}
