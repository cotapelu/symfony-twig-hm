<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\DeliveryVehicle;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryVehicleTest extends TestCase
{
    private function createVehicle(
        string $id = 'vehicle-id',
        string $type = 'MOTORCYCLE',
        string $model = 'Honda',
        int $capacity = 30
    ): DeliveryVehicle {
        return new DeliveryVehicle($id, $type, $model, $capacity);
    }

    public function testConstructor(): void
    {
        $v = $this->createVehicle();
        $this->assertSame('vehicle-id', $v->getId());
        $this->assertSame('MOTORCYCLE', $v->getType());
        $this->assertSame('Honda', $v->getModel());
        $this->assertNull($v->getPlateNumber());
        $this->assertSame(30, $v->getCapacity());
        $this->assertSame('ACTIVE', $v->getStatus());
        $this->assertNull($v->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $v->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $v->getUpdatedAt());
    }

    public function testSetType(): void
    {
        $v = $this->createVehicle();
        $original = $v->getUpdatedAt();
        usleep(1000);
        $v->setType('CAR');
        $this->assertSame('CAR', $v->getType());
        $this->assertNotEquals($original, $v->getUpdatedAt());
    }

    public function testSetModel(): void
    {
        $v = $this->createVehicle();
        $original = $v->getUpdatedAt();
        usleep(1000);
        $v->setModel('Yamaha');
        $this->assertSame('Yamaha', $v->getModel());
        $this->assertNotEquals($original, $v->getUpdatedAt());
    }

    public function testSetPlateNumber(): void
    {
        $v = $this->createVehicle();
        $original = $v->getUpdatedAt();
        usleep(1000);
        $v->setPlateNumber('29A-12345');
        $this->assertSame('29A-12345', $v->getPlateNumber());
        $this->assertNotEquals($original, $v->getUpdatedAt());
        $v->setPlateNumber(null);
        $this->assertNull($v->getPlateNumber());
    }

    public function testSetCapacity(): void
    {
        $v = $this->createVehicle();
        $original = $v->getUpdatedAt();
        usleep(1000);
        $v->setCapacity(100);
        $this->assertSame(100, $v->getCapacity());
        $this->assertNotEquals($original, $v->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $v = $this->createVehicle();
        $original = $v->getUpdatedAt();
        usleep(1000);
        $v->setStatus('INACTIVE');
        $this->assertSame('INACTIVE', $v->getStatus());
        $this->assertNotEquals($original, $v->getUpdatedAt());
    }

    public function testSetMetadata(): void
    {
        $v = $this->createVehicle();
        $original = $v->getUpdatedAt();
        usleep(1000);
        $meta = ['color' => 'red', 'year' => 2022];
        $v->setMetadata($meta);
        $this->assertSame($meta, $v->getMetadata());
        $this->assertNotEquals($original, $v->getUpdatedAt());
        $v->setMetadata(null);
        $this->assertNull($v->getMetadata());
    }
}
