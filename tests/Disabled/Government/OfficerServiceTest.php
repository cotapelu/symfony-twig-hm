<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government;

use PHPUnit\Framework\TestCase;
use App\Government\Application\Service\OfficerService;
use App\Government\Contracts\DTO\CreateOfficerRequest;
use App\Government\Domain\Entity\Officer;
use App\Government\Domain\Entity\Ward;
use App\Government\Domain\Repository\OfficerRepositoryInterface;
use App\Government\Domain\Repository\WardRepositoryInterface;
use App\Government\Domain\ValueObject\OfficerId;
use App\Government\Domain\ValueObject\Position;
use App\Government\Domain\ValueObject\Department;

class OfficerServiceTest extends TestCase
{
    private OfficerRepositoryInterface $officerRepository;
    private WardRepositoryInterface $wardRepository;
    private OfficerService $officerService;

    protected function setUp(): void
    {
        $this->officerRepository = $this->createMock(OfficerRepositoryInterface::class);
        $this->wardRepository = $this->createMock(WardRepositoryInterface::class);
        $this->officerService = new OfficerService(
            $this->officerRepository,
            $this->wardRepository
        );
    }

    /**
     * Create a fully configured Officer mock for response mapping
     */
    private function createOfficerMock(string $id, string $fullName): Officer
    {
        $officer = $this->createMock(Officer::class);
        $officer->method('getId')->willReturn(new OfficerId($id));
        $officer->method('getFullName')->willReturn($fullName);
        $officer->method('getEmail')->willReturn('officer@example.com');
        $officer->method('getPhone')->willReturn('0901234567');
        $officer->method('getDateOfBirth')->willReturn(null);
        $officer->method('getIdentityNumber')->willReturn('012345678901');
        $officer->method('getPosition')->willReturn(new Position('OF001', 'Officer', 2));
        $officer->method('getDepartment')->willReturn(new Department('GOV', 'Government', null));
        $officer->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2024-01-01'));
        $officer->method('getUpdatedAt')->willReturn(null);
        $officer->method('isActive')->willReturn(true);
        return $officer;
    }

    public function testCreateOfficer_Success(): void
    {
        $request = new CreateOfficerRequest(
            fullName: 'Officer Nguyen',
            positionCode: 'OF001',
            positionName: 'Senior Officer',
            positionLevel: 3,
            departmentCode: 'GOV',
            departmentName: 'Government'
        );

        $this->officerRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Officer::class));

        $response = $this->officerService->createOfficer($request);

        $this->assertEquals('Officer Nguyen', $response->fullName);
    }

    public function testCreateOfficer_WithOptionalFields(): void
    {
        $request = new CreateOfficerRequest(
            fullName: 'Officer Tran',
            positionCode: 'OF002',
            positionName: 'Officer',
            positionLevel: 2,
            departmentCode: 'SEC',
            departmentName: 'Security',
            email: 'officer@example.com',
            phone: '0901234567',
            identityNumber: '012345678901'
        );

        $this->officerRepository->expects($this->once())->method('save');

        $response = $this->officerService->createOfficer($request);

        $this->assertEquals('Officer Tran', $response->fullName);
    }

    public function testGetOfficerById_Exists(): void
    {
        $officerId = '550e8400-e29b-41d4-a716-446655440000';
        $officer = $this->createOfficerMock($officerId, 'Officer Nguyen');

        $this->officerRepository->expects($this->once())
            ->method('findById')
            ->willReturn($officer);

        $response = $this->officerService->getOfficerById($officerId);

        $this->assertNotNull($response);
        $this->assertEquals('Officer Nguyen', $response->fullName);
    }

    public function testGetOfficerById_NotExists(): void
    {
        $this->officerRepository->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $response = $this->officerService->getOfficerById('non-existent');

        $this->assertNull($response);
    }

    public function testListOfficers_WithFilters(): void
    {
        $officer1 = $this->createOfficerMock('officer-001', 'Officer A');
        $officer2 = $this->createOfficerMock('officer-002', 'Officer B');

        $this->officerRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$officer1, $officer2]);

        $result = $this->officerService->listOfficers();

        $this->assertCount(2, $result);
    }

    public function testSearchOfficers(): void
    {
        $officer = $this->createOfficerMock('officer-001', 'Officer Nguyen');

        $this->officerRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$officer]);

        $result = $this->officerService->searchOfficers('Nguyen');

        $this->assertCount(1, $result);
    }

    public function testDeactivateOfficer(): void
    {
        $officerId = '550e8400-e29b-41d4-a716-446655440000';
        $officer = $this->createMock(Officer::class);

        $this->officerRepository->expects($this->once())
            ->method('findById')
            ->willReturn($officer);

        $officer->expects($this->once())->method('deactivate');
        $this->officerRepository->expects($this->once())->method('save');

        $this->officerService->deactivateOfficer($officerId);
    }

    public function testActivateOfficer(): void
    {
        $officerId = '550e8400-e29b-41d4-a716-446655440000';
        $officer = $this->createMock(Officer::class);

        $this->officerRepository->expects($this->once())
            ->method('findById')
            ->willReturn($officer);

        $officer->expects($this->once())->method('activate');
        $this->officerRepository->expects($this->once())->method('save');

        $this->officerService->activateOfficer($officerId);
    }

    public function testAssignOfficerToWard_Success(): void
    {
        $officerId = '550e8400-e29b-41d4-a716-446655440000';
        $wardId = 'ward-001';

        $officer = $this->createMock(Officer::class);
        $ward = $this->createMock(Ward::class);

        $this->officerRepository->expects($this->once())
            ->method('findById')
            ->willReturn($officer);

        $this->wardRepository->expects($this->once())
            ->method('findById')
            ->with($wardId)
            ->willReturn($ward);

        $this->wardRepository->expects($this->once())
            ->method('countByAssignedOfficer')
            ->with($officerId)
            ->willReturn(2);

        $ward->expects($this->once())
            ->method('setAssignedOfficerId')
            ->with($officerId);

        $this->wardRepository->expects($this->once())->method('save');

        $this->officerService->assignOfficerToWard($officerId, $wardId);
    }

    public function testAssignOfficerToWard_ExceedsLimit_ShouldThrowException(): void
    {
        $officerId = '550e8400-e29b-41d4-a716-446655440000';
        $wardId = 'ward-001';

        $officer = $this->createMock(Officer::class);
        $ward = $this->createMock(Ward::class);

        $this->officerRepository->expects($this->once())
            ->method('findById')
            ->willReturn($officer);

        $this->wardRepository->expects($this->once())
            ->method('findById')
            ->willReturn($ward);

        $this->wardRepository->expects($this->once())
            ->method('countByAssignedOfficer')
            ->with($officerId)
            ->willReturn(3);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Officer cannot be assigned to more than 3 wards');

        $this->officerService->assignOfficerToWard($officerId, $wardId);
    }

    public function testRemoveOfficerFromWard(): void
    {
        $officerId = '550e8400-e29b-41d4-a716-446655440000';
        $wardId = 'ward-001';

        $ward = $this->createMock(Ward::class);

        $this->wardRepository->expects($this->once())
            ->method('findById')
            ->with($wardId)
            ->willReturn($ward);

        $ward->expects($this->once())
            ->method('getAssignedOfficerId')
            ->willReturn($officerId);

        $ward->expects($this->once())
            ->method('setAssignedOfficerId')
            ->with(null);

        $this->wardRepository->expects($this->once())->method('save');

        $this->officerService->removeOfficerFromWard($officerId, $wardId);
    }

    public function testGetOfficerWards(): void
    {
        $officerId = '550e8400-e29b-41d4-a716-446655440000';
        $ward1 = $this->createMock(Ward::class);
        $ward2 = $this->createMock(Ward::class);

        $this->wardRepository->expects($this->once())
            ->method('findByAssignedOfficer')
            ->with($officerId)
            ->willReturn([$ward1, $ward2]);

        $result = $this->officerService->getOfficerWards($officerId);

        $this->assertCount(2, $result);
    }
}
