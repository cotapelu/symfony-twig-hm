<?php declare(strict_types=1);

namespace App\Tests\Unit\Delivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Delivery\Entity\DeliveryPerson;
use App\Core\Entity\User;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryPersonTest extends TestCase
{
    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('person@example.com');
        $user->setPasswordHash('hashed');
        return $user;
    }

    private function createDeliveryPerson(?User $user = null, ?string $type = null): DeliveryPerson
    {
        return new DeliveryPerson(
            user: $user ?? $this->createUser(),
            type: $type ?? DeliveryPerson::TYPE_FOOD
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $dp = $this->createDeliveryPerson();
        $this->assertIsString($dp->getId());
        $this->assertNotEmpty($dp->getId());
        $this->assertInstanceOf(User::class, $dp->getUser());
        $this->assertSame(DeliveryPerson::TYPE_FOOD, $dp->getType());
        $this->assertNull($dp->getVehicleId());
        $this->assertSame(DeliveryPerson::STATUS_AVAILABLE, $dp->getStatus());
        $this->assertNull($dp->getLocation());
        $this->assertNull($dp->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $dp->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $dp->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $dp = $this->createDeliveryPerson();

        $dp->setType(DeliveryPerson::TYPE_PHARMACY);
        $this->assertSame(DeliveryPerson::TYPE_PHARMACY, $dp->getType());

        $dp->setVehicleId('vehicle-123');
        $this->assertSame('vehicle-123', $dp->getVehicleId());

        $dp->setVehicleId(null);
        $this->assertNull($dp->getVehicleId());

        $dp->setStatus(DeliveryPerson::STATUS_BUSY);
        $this->assertSame(DeliveryPerson::STATUS_BUSY, $dp->getStatus());

        $dp->setStatus(DeliveryPerson::STATUS_OFFLINE);
        $this->assertSame(DeliveryPerson::STATUS_OFFLINE, $dp->getStatus());

        $dp->setLocation(['lat' => 10.0, 'lng' => 20.0]);
        $this->assertSame(['lat' => 10.0, 'lng' => 20.0], $dp->getLocation());

        $dp->setMetadata(['capacity' => 'large']);
        $this->assertSame(['capacity' => 'large'], $dp->getMetadata());

        $newUser = $this->createUser();
        $dp->setUser($newUser);
        $this->assertSame($newUser, $dp->getUser());
    }
}
