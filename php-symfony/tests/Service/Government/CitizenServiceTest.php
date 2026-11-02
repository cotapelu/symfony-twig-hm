<?php

declare(strict_types=1);

namespace App\Tests\Service\Government;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\TestCase;
use App\Government\Application\Service\CitizenService;
use App\Government\Contracts\DTO\CreateCitizenRequest;
use App\Government\Contracts\DTO\UpdateCitizenRequest;
use App\Government\Domain\Entity\Citizen;
use App\Government\Domain\Repository\CitizenRepositoryInterface;
use App\Government\Domain\ValueObject\CitizenId;
use App\Government\Domain\ValueObject\Address;

#[AllowMockObjectsWithoutExpectations]
class CitizenServiceTest extends TestCase
{
    private CitizenService $citizenService;
    private $citizenRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->citizenRepository = $this->createMock(CitizenRepositoryInterface::class);
        $this->citizenService = new CitizenService($this->citizenRepository);
    }

    public function testCreateCitizenWithValidData(): void
    {
        $request = new CreateCitizenRequest(
            fullName: 'Nguyen Van A',
            identityNumber: '012345678901',
            province: 'Ha Noi',
            district: 'Ba Dinh',
            ward: 'Phuc Xa',
            street: 'Pho Hue',
            apartmentNumber: '123',
            email: 'nguyenvana@example.com',
            phone: '0987654321',
            dateOfBirth: '1990-01-01',
            gender: 'male',
            ethnicity: 'Kinh',
            nationality: 'Vietnamese',
            occupation: 'Engineer',
            educationLevel: 'University',
            maritalStatus: 'married'
        );

        $this->citizenRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Citizen::class));

        $result = $this->citizenService->createCitizen($request);

        $this->assertNotNull($result);
        $this->assertEquals('Nguyen Van A', $result->fullName);
        $this->assertEquals('012345678901', $result->identityNumber);
    }

    public function testCreateCitizenWithMinimalData(): void
    {
        $request = new CreateCitizenRequest(
            fullName: 'Tran Thi B',
            identityNumber: '098765432109',
            province: 'Ho Chi Minh',
            district: 'District 1',
            ward: 'Ben Nghe',
            street: 'Le Loi',
            apartmentNumber: '456'
        );

        $this->citizenRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Citizen::class));

        $result = $this->citizenService->createCitizen($request);

        $this->assertNotNull($result);
        $this->assertEquals('Tran Thi B', $result->fullName);
    }

    public function testGetCitizenById(): void
    {
        $citizenId = 'citizen-123';
        $citizen = $this->createMock(Citizen::class);
        $citizen->method('getId')->willReturn(new CitizenId($citizenId));
        $citizen->method('getFullName')->willReturn('Nguyen Van A');
        $citizen->method('getIdentityNumber')->willReturn('012345678901');
        $citizen->method('getRegisteredAddress')->willReturn(new Address('Ha Noi', 'Ba Dinh', 'Phuc Xa', 'Pho Hue', '123'));
        $citizen->method('getEmail')->willReturn('test@example.com');
        $citizen->method('getPhone')->willReturn('0987654321');
        $citizen->method('getDateOfBirth')->willReturn(new \DateTimeImmutable('1990-01-01'));
        $citizen->method('getGender')->willReturn('male');
        $citizen->method('getEthnicity')->willReturn('Kinh');
        $citizen->method('getNationality')->willReturn('Vietnamese');
        $citizen->method('getOccupation')->willReturn('Engineer');
        $citizen->method('getEducationLevel')->willReturn('University');
        $citizen->method('getMaritalStatus')->willReturn('married');
        $citizen->method('getHouseholdId')->willReturn(null);
        $citizen->method('isActive')->willReturn(true);
        $citizen->method('isDeceased')->willReturn(false);
        $citizen->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        $citizen->method('getUpdatedAt')->willReturn(new \DateTimeImmutable());

        $this->citizenRepository->method('findById')
            ->with($this->isInstanceOf(CitizenId::class))
            ->willReturn($citizen);

        $result = $this->citizenService->getCitizenById($citizenId);

        $this->assertNotNull($result);
        $this->assertEquals('Nguyen Van A', $result->fullName);
    }

    public function testGetNonExistentCitizen(): void
    {
        $this->citizenRepository->method('findById')
            ->willReturn(null);

        $result = $this->citizenService->getCitizenById('non-existent-id');

        $this->assertNull($result);
    }

    public function testListCitizens(): void
    {
        $citizen1 = $this->createMock(Citizen::class);
        $citizen1->method('getId')->willReturn(new CitizenId('citizen-1'));
        $citizen1->method('getFullName')->willReturn('Nguyen Van A');
        $citizen1->method('getIdentityNumber')->willReturn('012345678901');
        $citizen1->method('getRegisteredAddress')->willReturn(new Address('Ha Noi', 'Ba Dinh', 'Phuc Xa', 'Pho Hue', '123'));
        $citizen1->method('getEmail')->willReturn('a@example.com');
        $citizen1->method('getPhone')->willReturn('0987654321');
        $citizen1->method('getDateOfBirth')->willReturn(new \DateTimeImmutable('1990-01-01'));
        $citizen1->method('getGender')->willReturn('male');
        $citizen1->method('getEthnicity')->willReturn('Kinh');
        $citizen1->method('getNationality')->willReturn('Vietnamese');
        $citizen1->method('getOccupation')->willReturn('Engineer');
        $citizen1->method('getEducationLevel')->willReturn('University');
        $citizen1->method('getMaritalStatus')->willReturn('married');
        $citizen1->method('getHouseholdId')->willReturn(null);
        $citizen1->method('isActive')->willReturn(true);
        $citizen1->method('isDeceased')->willReturn(false);
        $citizen1->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        $citizen1->method('getUpdatedAt')->willReturn(new \DateTimeImmutable());

        $citizen2 = $this->createMock(Citizen::class);
        $citizen2->method('getId')->willReturn(new CitizenId('citizen-2'));
        $citizen2->method('getFullName')->willReturn('Tran Thi B');
        $citizen2->method('getIdentityNumber')->willReturn('098765432109');
        $citizen2->method('getRegisteredAddress')->willReturn(new Address('HCM', 'District 1', 'Ben Nghe', 'Le Loi', '456'));
        $citizen2->method('getEmail')->willReturn('b@example.com');
        $citizen2->method('getPhone')->willReturn('0123456789');
        $citizen2->method('getDateOfBirth')->willReturn(new \DateTimeImmutable('1995-05-05'));
        $citizen2->method('getGender')->willReturn('female');
        $citizen2->method('getEthnicity')->willReturn('Kinh');
        $citizen2->method('getNationality')->willReturn('Vietnamese');
        $citizen2->method('getOccupation')->willReturn('Teacher');
        $citizen2->method('getEducationLevel')->willReturn('University');
        $citizen2->method('getMaritalStatus')->willReturn('single');
        $citizen2->method('getHouseholdId')->willReturn(null);
        $citizen2->method('isActive')->willReturn(true);
        $citizen2->method('isDeceased')->willReturn(false);
        $citizen2->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        $citizen2->method('getUpdatedAt')->willReturn(new \DateTimeImmutable());

        $this->citizenRepository->method('findAll')
            ->willReturn([$citizen1, $citizen2]);

        $result = $this->citizenService->listCitizens();

        $this->assertCount(2, $result);
        $this->assertEquals('Nguyen Van A', $result[0]->fullName);
        $this->assertEquals('Tran Thi B', $result[1]->fullName);
    }

    public function testUpdateCitizen(): void
    {
        $citizenId = 'citizen-123';
        $citizen = $this->createMock(Citizen::class);
        $citizen->method('getId')->willReturn(new CitizenId($citizenId));
        $citizen->method('getFullName')->willReturn('Nguyen Van A Updated');
        $citizen->method('getIdentityNumber')->willReturn('012345678901');
        $citizen->method('getRegisteredAddress')->willReturn(new Address('Ha Noi', 'Ba Dinh', 'Phuc Xa', 'Pho Hue', '123'));
        $citizen->method('getEmail')->willReturn('updated@example.com');
        $citizen->method('getPhone')->willReturn('0987654321');
        $citizen->method('getDateOfBirth')->willReturn(new \DateTimeImmutable('1990-01-01'));
        $citizen->method('getGender')->willReturn('male');
        $citizen->method('getEthnicity')->willReturn('Kinh');
        $citizen->method('getNationality')->willReturn('Vietnamese');
        $citizen->method('getOccupation')->willReturn('Senior Engineer');
        $citizen->method('getEducationLevel')->willReturn('University');
        $citizen->method('getMaritalStatus')->willReturn('married');
        $citizen->method('getHouseholdId')->willReturn(null);
        $citizen->method('isActive')->willReturn(true);
        $citizen->method('isDeceased')->willReturn(false);
        $citizen->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        $citizen->method('getUpdatedAt')->willReturn(new \DateTimeImmutable());

        $this->citizenRepository->method('findById')
            ->willReturn($citizen);

        $this->citizenRepository->expects($this->once())
            ->method('save')
            ->with($citizen);

        $request = new UpdateCitizenRequest(
            fullName: 'Nguyen Van A Updated',
            email: 'updated@example.com',
            occupation: 'Senior Engineer'
        );

        $result = $this->citizenService->updateCitizen($citizenId, $request);

        $this->assertNotNull($result);
    }

    public function testUpdateNonExistentCitizen(): void
    {
        $this->citizenRepository->method('findById')
            ->willReturn(null);

        $request = new UpdateCitizenRequest(fullName: 'Updated Name');

        $result = $this->citizenService->updateCitizen('non-existent', $request);

        $this->assertNull($result);
    }

    public function testDeactivateCitizen(): void
    {
        $citizenId = 'citizen-123';
        $citizen = $this->createMock(Citizen::class);

        $this->citizenRepository->method('findById')
            ->willReturn($citizen);

        $citizen->expects($this->once())
            ->method('deactivate');

        $this->citizenRepository->expects($this->once())
            ->method('save')
            ->with($citizen);

        $result = $this->citizenService->deactivateCitizen($citizenId);

        $this->assertTrue($result);
    }

    public function testDeactivateNonExistentCitizen(): void
    {
        $this->citizenRepository->method('findById')
            ->willReturn(null);

        $result = $this->citizenService->deactivateCitizen('non-existent');

        $this->assertFalse($result);
    }

    public function testActivateCitizen(): void
    {
        $citizenId = 'citizen-123';
        $citizen = $this->createMock(Citizen::class);

        $this->citizenRepository->method('findById')
            ->willReturn($citizen);

        $citizen->expects($this->once())
            ->method('activate');

        $this->citizenRepository->expects($this->once())
            ->method('save')
            ->with($citizen);

        $result = $this->citizenService->activateCitizen($citizenId);

        $this->assertTrue($result);
    }

    public function testGetStatistics(): void
    {
        $this->citizenRepository->method('count')
            ->willReturnCallback(function ($filters) {
                if (isset($filters['isActive'])) {
                    return 80;
                }
                if (isset($filters['isDeceased'])) {
                    return 5;
                }
                return 100;
            });

        $result = $this->citizenService->getStatistics();

        $this->assertEquals(100, $result['total']);
        $this->assertEquals(80, $result['active']);
        $this->assertEquals(5, $result['deceased']);
        $this->assertEquals(20, $result['inactive']);
    }

    public function testSearchCitizens(): void
    {
        $citizen = $this->createMock(Citizen::class);
        $citizen->method('getId')->willReturn(new CitizenId('citizen-1'));
        $citizen->method('getFullName')->willReturn('Nguyen Van A');
        $citizen->method('getIdentityNumber')->willReturn('012345678901');
        $citizen->method('getRegisteredAddress')->willReturn(new Address('Ha Noi', 'Ba Dinh', 'Phuc Xa', 'Pho Hue', '123'));
        $citizen->method('getEmail')->willReturn('a@example.com');
        $citizen->method('getPhone')->willReturn('0987654321');
        $citizen->method('getDateOfBirth')->willReturn(new \DateTimeImmutable('1990-01-01'));
        $citizen->method('getGender')->willReturn('male');
        $citizen->method('getEthnicity')->willReturn('Kinh');
        $citizen->method('getNationality')->willReturn('Vietnamese');
        $citizen->method('getOccupation')->willReturn('Engineer');
        $citizen->method('getEducationLevel')->willReturn('University');
        $citizen->method('getMaritalStatus')->willReturn('married');
        $citizen->method('getHouseholdId')->willReturn(null);
        $citizen->method('isActive')->willReturn(true);
        $citizen->method('isDeceased')->willReturn(false);
        $citizen->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        $citizen->method('getUpdatedAt')->willReturn(new \DateTimeImmutable());

        $this->citizenRepository->method('search')
            ->with('Nguyen')
            ->willReturn([$citizen]);

        $result = $this->citizenService->searchCitizens('Nguyen');

        $this->assertCount(1, $result);
        $this->assertEquals('Nguyen Van A', $result[0]->fullName);
    }
}
