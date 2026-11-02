<?php declare(strict_types=1);

namespace App\Tests\Repository\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Php\DeliveryPersonRepository;
use App\Entity\Php\DeliveryPerson;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryPersonRepositoryTest extends TestCase
{
    private DeliveryPersonRepository&MockObject $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(DeliveryPersonRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findBy', 'findOneBy', 'count'])
            ->getMock();
    }

    public function testFindByUserId(): void
    {
        $userId = 'user-123';
        $expected = $this->createMock(DeliveryPerson::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId])
            ->willReturn($expected);

        $result = $this->repo->findByUserId($userId);
        $this->assertSame($expected, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'ONLINE';
        $expected = [$this->createMock(DeliveryPerson::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($expected);

        $result = $this->repo->findByStatus($status);
        $this->assertSame($expected, $result);
    }

    public function testFindAvailable(): void
    {
        $expected = [$this->createMock(DeliveryPerson::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ONLINE', 'isAvailable' => true])
            ->willReturn($expected);

        $result = $this->repo->findAvailable();
        $this->assertSame($expected, $result);
    }

    public function testFindByType(): void
    {
        $type = 'MOTORBIKE';
        $expected = [$this->createMock(DeliveryPerson::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['type' => $type])
            ->willReturn($expected);

        $result = $this->repo->findByType($type);
        $this->assertSame($expected, $result);
    }
}
