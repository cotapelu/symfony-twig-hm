<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\Household;
use App\Repository\Php\HouseholdRepository;
use App\Service\Php\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class HouseholdServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private HouseholdRepository&MockObject $repository;
    private HouseholdService $service;

    private string $householdId;
    private Household $household;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(HouseholdRepository::class);
        $this->service = new HouseholdService($this->em, $this->repository);

        $this->householdId = bin2hex(random_bytes(16));
        $this->household = new Household(
            $this->householdId,
            '123 Main St'
        );
        // set optional via setters: wardId, headId
    }

    public function testCreateHouseholdSuccess(): void
    {
        $address = '456 Elm St';
        $wardId = 'ward-1';
        $headId = 'citizen-head';

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Household $hh) use (&$captured, $address, $wardId, $headId) {
                $captured = $hh;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createHousehold($address, $wardId, $headId);
        $this->assertInstanceOf(Household::class, $result);
        $this->assertSame($address, $result->getAddress());
        $this->assertSame($wardId, $result->getWardId());
        $this->assertSame($headId, $result->getHeadId());
        $this->assertSame($captured, $result);
    }

    public function testCreateHouseholdValidationEmptyAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Address is required');
        $this->service->createHousehold('');
    }

    public function testGetHouseholdSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->householdId)
            ->willReturn($this->household);

        $result = $this->service->getHousehold($this->householdId);
        $this->assertSame($this->household, $result);
    }

    public function testGetHouseholdNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->householdId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Household not found');
        $this->service->getHousehold($this->householdId);
    }

    public function testUpdateHouseholdSuccess(): void
    {
        $newAddress = 'New Address';
        $newWard = 'ward-2';
        $newHead = 'citizen-new';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->householdId)
            ->willReturn($this->household);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'address' => $newAddress,
            'wardId' => $newWard,
            'headId' => $newHead,
        ];
        $result = $this->service->updateHousehold($this->householdId, $data);
        $this->assertSame($this->household, $result);
        $this->assertSame($newAddress, $result->getAddress());
        $this->assertSame($newWard, $result->getWardId());
        $this->assertSame($newHead, $result->getHeadId());
    }

    public function testUpdateHouseholdValidationEmptyAddress(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->householdId)
            ->willReturn($this->household);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Address is required');
        $this->service->updateHousehold($this->householdId, ['address' => '   ']);
    }

    public function testUpdateHouseholdNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->householdId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateHousehold($this->householdId, []);
    }

    public function testDeleteHouseholdSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->householdId)
            ->willReturn($this->household);
        $this->em->expects($this->once())->method('remove')->with($this->household);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteHousehold($this->householdId);
    }

    public function testDeleteHouseholdNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->householdId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteHousehold($this->householdId);
    }

    public function testFindByWardId(): void
    {
        $wardId = 'ward-test';
        $results = [$this->household];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['wardId' => $wardId])
            ->willReturn($results);

        $result = $this->service->findByWardId($wardId);
        $this->assertSame($results, $result);
    }
}
