<?php declare(strict_types=1);

namespace App\Tests\Unit\Delivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Delivery\Entity\DeliveryStore;
use App\Core\Entity\User;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryStoreTest extends TestCase
{
    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('owner@example.com');
        $user->setPasswordHash('hashed');
        return $user;
    }

    private function createStore(?User $owner = null, ?string $type = null, ?string $name = null, ?string $address = null, ?float $latitude = null, ?float $longitude = null): DeliveryStore
    {
        return new DeliveryStore(
            owner: $owner ?? $this->createUser(),
            type: $type ?? DeliveryStore::TYPE_RESTAURANT,
            name: $name ?? 'My Store',
            address: $address ?? '123 Main St',
            latitude: $latitude ?? 40.7128,
            longitude: $longitude ?? -74.0060
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $store = $this->createStore();
        $this->assertInstanceOf(User::class, $store->getOwner());
        $this->assertSame(DeliveryStore::TYPE_RESTAURANT, $store->getType());
        $this->assertSame('My Store', $store->getName());
        $this->assertSame('123 Main St', $store->getAddress());
        $this->assertSame(40.7128, $store->getLatitude());
        $this->assertSame(-74.0060, $store->getLongitude());
        $this->assertNull($store->getDescription());
        $this->assertNull($store->getLicense());
        $this->assertSame(DeliveryStore::STATUS_PENDING, $store->getStatus());
        $this->assertNull($store->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $store->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $store->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $store = $this->createStore();

        $store->setType(DeliveryStore::TYPE_GROCERY);
        $this->assertSame(DeliveryStore::TYPE_GROCERY, $store->getType());

        $store->setName('Grocery Store');
        $this->assertSame('Grocery Store', $store->getName());

        $store->setAddress('456 Elm St');
        $this->assertSame('456 Elm St', $store->getAddress());

        $store->setLatitude(34.0522);
        $this->assertSame(34.0522, $store->getLatitude());

        $store->setLongitude(-118.2437);
        $this->assertSame(-118.2437, $store->getLongitude());

        $store->setDescription('A grocery store');
        $this->assertSame('A grocery store', $store->getDescription());

        $store->setLicense('LIC-999');
        $this->assertSame('LIC-999', $store->getLicense());

        $store->setStatus(DeliveryStore::STATUS_ACTIVE);
        $this->assertSame(DeliveryStore::STATUS_ACTIVE, $store->getStatus());

        $store->setMetadata(['hours' => '9-5']);
        $this->assertSame(['hours' => '9-5'], $store->getMetadata());

        $newOwner = $this->createUser();
        $store->setOwner($newOwner);
        $this->assertSame($newOwner, $store->getOwner());
    }

    public function testToArray(): void
    {
        $owner = $this->createUser();
        $store = new DeliveryStore(
            owner: $owner,
            type: DeliveryStore::TYPE_PHARMACY,
            name: 'Pharmacy Plus',
            address: '789 Health Ave',
            latitude: 51.5074,
            longitude: -0.1278
        );
        // Simulate DB-assigned ID
        $ref = new \ReflectionProperty($store, 'id');
        $ref->setAccessible(true);
        $ref->setValue($store, 10);
        $store->setDescription('24h pharmacy');
        $store->setLicense('PHARM-123');
        $store->setMetadata(['delivery' => true]);

        $arr = $store->toArray();
        $this->assertSame(10, $arr['id']);
        $this->assertSame($owner->getId(), $arr['ownerId']);
        $this->assertSame(DeliveryStore::TYPE_PHARMACY, $arr['type']);
        $this->assertSame('Pharmacy Plus', $arr['name']);
        $this->assertSame('789 Health Ave', $arr['address']);
        $this->assertSame(51.5074, $arr['latitude']);
        $this->assertSame(-0.1278, $arr['longitude']);
        $this->assertSame('24h pharmacy', $arr['description']);
        $this->assertSame('PHARM-123', $arr['license']);
        $this->assertSame(DeliveryStore::STATUS_PENDING, $arr['status']);
        $this->assertSame(['delivery' => true], $arr['metadata']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['createdAt']);
    }
}
