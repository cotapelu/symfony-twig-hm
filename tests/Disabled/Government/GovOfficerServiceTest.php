<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government;

use PHPUnit\Framework\TestCase;
use App\Service\Php\GovOfficerService;
use App\Entity\Php\GovOfficer;
use App\Repository\Php\GovOfficerRepository;
use Doctrine\ORM\EntityManagerInterface;

class GovOfficerServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private GovOfficerRepository $repository;
    private GovOfficerService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(GovOfficerRepository::class);
        $this->service = new GovOfficerService($this->em, $this->repository);
    }

    public function testCreateGovOfficer_Success(): void
    {
        $userId = 'user-123';
        $department = 'Police';
        $position = 'Officer';
        $metadata = ['badge' => '12345'];

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (GovOfficer $o) use ($userId, $department, $position, $metadata) {
                return $o->getUserId() === $userId
                    && $o->getDepartment() === $department
                    && $o->getPosition() === $position
                    && $o->getMetadata() === $metadata;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->createGovOfficer($userId, $department, $position, $metadata);

        $this->assertInstanceOf(GovOfficer::class, $result);
        $this->assertEquals($userId, $result->getUserId());
        $this->assertEquals($department, $result->getDepartment());
        $this->assertEquals('ACTIVE', $result->getStatus()); // default
    }

    public function testCreateGovOfficer_EmptyUserId_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required');

        $this->service->createGovOfficer('', 'Dept', 'Pos');
    }

    public function testCreateGovOfficer_EmptyDepartment_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Department is required');

        $this->service->createGovOfficer('user123', '', 'Pos');
    }

    public function testCreateGovOfficer_EmptyPosition_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Position is required');

        $this->service->createGovOfficer('user123', 'Dept', '');
    }

    public function testGetGovOfficer_Exists(): void
    {
        $officerId = 'officer-123';
        $officer = new GovOfficer($officerId, 'user-123', 'Police', 'Sergeant');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($officerId)
            ->willReturn($officer);

        $result = $this->service->getGovOfficer($officerId);

        $this->assertSame($officer, $result);
    }

    public function testGetGovOfficer_NotFound_ThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GovOfficer not found');

        $this->repository->expects($this->once())
            ->method('find')
            ->with('non-existent')
            ->willReturn(null);

        $this->service->getGovOfficer('non-existent');
    }

    public function testUpdateGovOfficer_Success(): void
    {
        $officerId = 'officer-123';
        $existing = new GovOfficer($officerId, 'user-123', 'Police', 'Officer');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($officerId)
            ->willReturn($existing);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->updateGovOfficer($officerId, [
            'department' => 'Fire',
            'position' => 'Chief',
            'status' => 'INACTIVE',
        ]);

        $this->assertEquals('Fire', $result->getDepartment());
        $this->assertEquals('Chief', $result->getPosition());
        $this->assertEquals('INACTIVE', $result->getStatus());
    }

    public function testUpdateGovOfficer_EmptyDepartment_ThrowsException(): void
    {
        $officer = new GovOfficer('id', 'user', 'Police', 'Officer');
        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn($officer);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Department cannot be empty');

        $this->service->updateGovOfficer('id', ['department' => '']);
    }

    public function testUpdateGovOfficer_NotFound_ThrowsException(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with('missing')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GovOfficer not found');

        $this->service->updateGovOfficer('missing', ['department' => 'New']);
    }

    public function testDeleteGovOfficer_Success(): void
    {
        $officer = new GovOfficer('id', 'user', 'Dept', 'Pos');

        $this->repository->expects($this->once())
            ->method('find')
            ->with('id')
            ->willReturn($officer);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($officer);
        $this->em->expects($this->once())
            ->method('flush');

        $this->service->deleteGovOfficer('id');
    }

    public function testFindByUserId_ReturnsArray(): void
    {
        $officers = [
            new GovOfficer('id1', 'user-123', 'Dept1', 'Pos1'),
            new GovOfficer('id2', 'user-123', 'Dept2', 'Pos2'),
        ];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => 'user-123'])
            ->willReturn($officers);

        $result = $this->service->findByUserId('user-123');

        $this->assertCount(2, $result);
    }

    public function testFindByDepartment_ReturnsArray(): void
    {
        $officers = [new GovOfficer('id1', 'user1', 'Police', 'Pos')];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['department' => 'Police'])
            ->willReturn($officers);

        $result = $this->service->findByDepartment('Police');

        $this->assertCount(1, $result);
    }

    public function testUpdateGovOfficer_OnlyStatus(): void
    {
        $officer = new GovOfficer('id', 'user', 'Dept', 'Pos');
        $this->repository->expects($this->once())->method('find')->willReturn($officer);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateGovOfficer('id', ['status' => 'INACTIVE']);

        $this->assertEquals('INACTIVE', $result->getStatus());
    }

    public function testUpdateGovOfficer_EmptyStatus_ThrowsException(): void
    {
        $officer = new GovOfficer('id', 'user', 'Dept', 'Pos');
        $this->repository->expects($this->once())->method('find')->willReturn($officer);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status cannot be empty');

        $this->service->updateGovOfficer('id', ['status' => '']);
    }
}
