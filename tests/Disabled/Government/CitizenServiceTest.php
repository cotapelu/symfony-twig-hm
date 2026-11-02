<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government;

use PHPUnit\Framework\TestCase;
use App\Service\Php\CitizenService;
use App\Entity\Php\Citizen;
use App\Repository\Php\CitizenRepository;
use Doctrine\ORM\EntityManagerInterface;

class CitizenServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private CitizenRepository $repository;
    private CitizenService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(CitizenRepository::class);
        $this->service = new CitizenService($this->em, $this->repository);
    }

    public function testCreateCitizen_Success(): void
    {
        $this->repository->expects($this->once())
            ->method('findByIdentityNumber')
            ->with('012345678901')
            ->willReturn(null);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createCitizen(
            'Nguyen Van A',
            '012345678901',
            'household-123',
            ['notes' => 'New citizen']
        );

        $this->assertInstanceOf(Citizen::class, $result);
        $this->assertEquals('Nguyen Van A', $result->getName());
        $this->assertEquals('012345678901', $result->getIdentityNumber());
        $this->assertEquals('household-123', $result->getHouseholdId());
        $this->assertEquals('ACTIVE', $result->getStatus());
    }

    public function testCreateCitizen_DuplicateIdentity_ThrowsException(): void
    {
        $existing = $this->createMock(Citizen::class);
        $this->repository->expects($this->once())
            ->method('findByIdentityNumber')
            ->with('EXISTING')
            ->willReturn($existing);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Citizen with identity number 'EXISTING' already exists");

        $this->service->createCitizen('Name', 'EXISTING');
    }

    public function testCreateCitizen_EmptyName_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createCitizen('', '123456');
    }

    public function testCreateCitizen_EmptyIdentity_ThrowsException(): void
    {
        $this->repository->expects($this->never())->method('findByIdentityNumber');
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createCitizen('Name', '');
    }

    public function testGetCitizen_Exists(): void
    {
        $citizen = new Citizen('id-1', 'Nguyen Van A', '012345678901');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($citizen);
        $result = $this->service->getCitizen('id-1');
        $this->assertSame($citizen, $result);
    }

    public function testGetCitizen_NotFound_ThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getCitizen('missing');
    }

    public function testGetCitizenByIdentityNumber_ReturnsNullWhenNotFound(): void
    {
        $this->repository->expects($this->once())->method('findByIdentityNumber')->with('NOTEXIST')->willReturn(null);
        $result = $this->service->getCitizenByIdentityNumber('NOTEXIST');
        $this->assertNull($result);
    }

    public function testGetCitizenByIdentityNumber_ReturnsCitizen(): void
    {
        $citizen = new Citizen('id-1', 'Nguyen Van A', '012345678901');
        $this->repository->expects($this->once())->method('findByIdentityNumber')->with('012345678901')->willReturn($citizen);
        $result = $this->service->getCitizenByIdentityNumber('012345678901');
        $this->assertSame($citizen, $result);
    }

    public function testUpdateCitizen_Success(): void
    {
        $citizen = new Citizen('id-1', 'Old Name', 'OLD_ID');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($citizen);
        $this->repository->expects($this->once())
            ->method('findByIdentityNumber')
            ->with('NEW_ID')
            ->willReturn(null);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateCitizen('id-1', [
            'name' => 'New Name',
            'identityNumber' => 'NEW_ID',
            'householdId' => 'hh-123',
        ]);

        $this->assertEquals('New Name', $result->getName());
        $this->assertEquals('NEW_ID', $result->getIdentityNumber());
        $this->assertEquals('hh-123', $result->getHouseholdId());
    }

    public function testUpdateCitizen_DuplicateIdentity_ThrowsException(): void
    {
        $citizen = new Citizen('id-1', 'Name', 'OLD_ID');
        $conflicting = new Citizen('id-2', 'Other', 'EXISTING');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($citizen);
        $this->repository->expects($this->once())
            ->method('findByIdentityNumber')
            ->with('EXISTING')
            ->willReturn($conflicting);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateCitizen('id-1', ['identityNumber' => 'EXISTING']);
    }

    public function testUpdateCitizen_NotFound_ThrowsException(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateCitizen('missing', ['name' => 'New']);
    }

    public function testDeleteCitizen_Success(): void
    {
        $citizen = new Citizen('id-1', 'Name', 'ID');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($citizen);
        $this->em->expects($this->once())->method('remove')->with($citizen);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteCitizen('id-1');
    }

    public function testFindByHouseholdId_ReturnsArray(): void
    {
        $citizens = [new Citizen('id-1', 'Name', 'ID', 'hh-123')];
        $this->repository->expects($this->once())->method('findByHouseholdId')->with('hh-123')->willReturn($citizens);
        $result = $this->service->findByHouseholdId('hh-123');
        $this->assertCount(1, $result);
    }

    public function testFindByStatus_ReturnsArray(): void
    {
        $citizens = [new Citizen('id-1', 'Name', 'ID')];
        $this->repository->expects($this->once())->method('findBy')->with(['status' => 'ACTIVE'])->willReturn($citizens);
        $result = $this->service->findByStatus('ACTIVE');
        $this->assertCount(1, $result);
    }

    public function testSearchByName_ReturnsArray(): void
    {
        $citizens = [new Citizen('id-1', 'Nguyen Van A', 'ID')];
        $this->repository->expects($this->once())->method('searchByName')->with('Nguyen', 20)->willReturn($citizens);
        $result = $this->service->searchByName('Nguyen');
        $this->assertCount(1, $result);
    }

    public function testUpdate_OnlyMetadata(): void
    {
        $citizen = new Citizen('id-1', 'Name', 'ID');
        $this->repository->expects($this->once())->method('find')->willReturn($citizen);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateCitizen('id-1', ['metadata' => ['tags' => ['vip']]]);

        $this->assertEquals(['tags' => ['vip']], $result->getMetadata());
    }
}
