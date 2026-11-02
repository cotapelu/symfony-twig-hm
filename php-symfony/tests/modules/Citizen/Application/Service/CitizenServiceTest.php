<?php

declare(strict_types=1);

namespace App\Tests\Modules\Citizen\Application\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Citizen\Application\DTO\CreateCitizenRequest;
use App\Citizen\Application\DTO\CitizenResponse;
use App\Citizen\Application\Service\CitizenService;
use App\Citizen\Domain\Entity\Citizen;
use App\Citizen\Application\Repository\CitizenRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CitizenServiceTest extends TestCase
{
    private CitizenRepositoryInterface&MockObject $citizenRepository;
    private CitizenService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->citizenRepository = $this->createMock(CitizenRepositoryInterface::class);
        $this->service = new CitizenService($this->citizenRepository);
    }

    private function createValidCreateRequest(array $overrides = []): CreateCitizenRequest
    {
        $request = new CreateCitizenRequest();
        $request->identityNumber = $overrides['identityNumber'] ?? '123456789';
        $request->fullName = $overrides['fullName'] ?? 'John Doe';
        $request->dateOfBirth = $overrides['dateOfBirth'] ?? new \DateTime('1990-01-01');
        $request->gender = $overrides['gender'] ?? 'male';
        $request->phoneNumber = $overrides['phoneNumber'] ?? '0123456789';
        $request->email = $overrides['email'] ?? 'john@example.com';
        $request->permanentAddress = $overrides['permanentAddress'] ?? '123 Main St';
        $request->currentAddress = $overrides['currentAddress'] ?? '456 Current Ave';
        $request->ethnicity = $overrides['ethnicity'] ?? 'Kinh';
        $request->religion = $overrides['religion'] ?? 'None';
        $request->educationLevel = $overrides['educationLevel'] ?? 'Bachelor';
        $request->occupation = $overrides['occupation'] ?? 'Engineer';
        $request->maritalStatus = $overrides['maritalStatus'] ?? 'single';
        $request->isHouseholdHead = $overrides['isHouseholdHead'] ?? false;
        $request->specialCategories = $overrides['specialCategories'] ?? [];
        $request->notes = $overrides['notes'] ?? null;
        $request->userId = $overrides['userId'] ?? 'user-123';

        return $request;
    }

    public function testCreateCitizenThrowsWhenDuplicateIdentityNumber(): void
    {
        $existingCitizen = new Citizen('123456789', 'Existing', new \DateTime('1980-01-01'), 'male');
        $existingCitizen->setId('citizen-1');

        $this->citizenRepository->expects($this->once())
            ->method('findByIdentityNumber')
            ->with('123456789')
            ->willReturn($existingCitizen);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen with this identity number already exists');

        $request = $this->createValidCreateRequest();
        $this->service->createCitizen($request);
    }

    public function testCreateCitizenSuccess(): void
    {
        $this->citizenRepository->expects($this->once())
            ->method('findByIdentityNumber')
            ->with('123456789')
            ->willReturn(null);

        $this->citizenRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Citizen $citizen) {
                return $citizen->getIdentityNumber() === '123456789'
                    && $citizen->getFullName() === 'John Doe'
                    && $citizen->getGender() === 'male'
                    && $citizen->getPhoneNumber() === '0123456789'
                    && $citizen->getEmail() === 'john@example.com'
                    && $citizen->getUserId() === 'user-123';
            }));

        $request = $this->createValidCreateRequest();
        $response = $this->service->createCitizen($request);

        $this->assertInstanceOf(CitizenResponse::class, $response);
        $this->assertSame('123456789', $response->identityNumber);
        $this->assertSame('John Doe', $response->fullName);
    }

    public function testGetCitizenReturnsNullWhenNotFound(): void
    {
        $this->citizenRepository->expects($this->once())
            ->method('findById')
            ->with('citizen-999')
            ->willReturn(null);

        $result = $this->service->getCitizen('citizen-999', 'user-123');

        $this->assertNull($result);
    }

    public function testGetCitizenReturnsResponseWhenFound(): void
    {
        $citizen = new Citizen('123456789', 'John Doe', new \DateTime('1990-01-01'), 'male');
        $citizen->setId('citizen-1');
        $citizen->setUserId('user-123');
        $citizen->setPhoneNumber('0123456789');
        $citizen->setEmail('john@example.com');

        $this->citizenRepository->expects($this->once())
            ->method('findById')
            ->with('citizen-1')
            ->willReturn($citizen);

        $result = $this->service->getCitizen('citizen-1', 'user-123');

        $this->assertInstanceOf(CitizenResponse::class, $result);
        $this->assertSame('citizen-1', $result->id);
        $this->assertSame('John Doe', $result->fullName);
    }

    public function testGetCitizenThrowsWhenAccessDenied(): void
    {
        $citizen = new Citizen('123456789', 'John Doe', new \DateTime('1990-01-01'), 'male');
        $citizen->setId('citizen-1');
        $citizen->setUserId('other-user');

        $this->citizenRepository->expects($this->once())
            ->method('findById')
            ->with('citizen-1')
            ->willReturn($citizen);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');

        $this->service->getCitizen('citizen-1', 'user-123');
    }

    public function testSearchCitizensAppliesLimitAndOffset(): void
    {
        $citizens = [
            $this->createCitizenMock(1, 'John'),
            $this->createCitizenMock(2, 'Jane'),
            $this->createCitizenMock(3, 'Bob'),
        ];

        $this->citizenRepository->expects($this->once())
            ->method('search')
            ->with('john', [])
            ->willReturn($citizens);

        $result = $this->service->searchCitizens('john', [], 1, 1); // limit=1, offset=1

        $this->assertCount(1, $result);
        $this->assertInstanceOf(CitizenResponse::class, $result[0]);
        $this->assertSame('Jane', $result[0]->fullName);
    }

    public function testGetElderlyCitizensCallsRepository(): void
    {
        $citizens = [
            $this->createCitizenMock(1, 'Elderly 1', 80),
            $this->createCitizenMock(2, 'Elderly 2', 75),
        ];

        $this->citizenRepository->expects($this->once())
            ->method('findElderly')
            ->with(60)
            ->willReturn($citizens);

        $result = $this->service->getElderlyCitizens(60);

        $this->assertCount(2, $result);
        foreach ($result as $citizenResponse) {
            $this->assertInstanceOf(CitizenResponse::class, $citizenResponse);
        }
    }

    private function createCitizenMock(int $id, string $name, ?int $age = null): Citizen
    {
        $citizen = new Citizen('ID' . $id, $name, new \DateTime(sprintf('-%d years', $age ?? 30)), 'male');
        $citizen->setId('citizen-' . $id);
        return $citizen;
    }

    // ==================== UPDATE CITIZEN TESTS ====================

    public function testUpdateCitizenReturnsNullWhenNotFound(): void
    {
        $this->citizenRepository->expects($this->once())
            ->method('findById')
            ->with('citizen-999')
            ->willReturn(null);

        $result = $this->service->updateCitizen('citizen-999', ['fullName' => 'New Name'], 'user-123');

        $this->assertNull($result);
    }

    public function testUpdateCitizenSuccessfullyUpdates(): void
    {
        $citizen = new Citizen('123456789', 'Original Name', new \DateTime('1990-01-01'), 'male');
        $citizen->setId('citizen-1');
        $citizen->setUserId('user-123');
        $citizen->setPhoneNumber('0123456789');

        $this->citizenRepository->expects($this->once())
            ->method('findById')
            ->with('citizen-1')
            ->willReturn($citizen);

        $this->citizenRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Citizen $c) {
                return $c->getFullName() === 'Updated Name';
            }));

        $result = $this->service->updateCitizen('citizen-1', ['fullName' => 'Updated Name'], 'user-123');

        $this->assertInstanceOf(CitizenResponse::class, $result);
    }

    public function testUpdateCitizenThrowsWhenAccessDenied(): void
    {
        $citizen = new Citizen('123456789', 'Original Name', new \DateTime('1990-01-01'), 'male');
        $citizen->setId('citizen-1');
        $citizen->setUserId('other-user');

        $this->citizenRepository->expects($this->once())
            ->method('findById')
            ->with('citizen-1')
            ->willReturn($citizen);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');

        $this->service->updateCitizen('citizen-1', ['fullName' => 'Updated Name'], 'user-123');
    }

    // ==================== DELETE CITIZEN TESTS ====================

    public function testDeleteCitizenReturnsFalseWhenNotFound(): void
    {
        $this->citizenRepository->expects($this->once())
            ->method('findById')
            ->with('citizen-999')
            ->willReturn(null);

        $result = $this->service->deleteCitizen('citizen-999', 'user-123');

        $this->assertFalse($result);
    }

    public function testDeleteCitizenSuccessfullyDeletes(): void
    {
        $citizen = new Citizen('123456789', 'John Doe', new \DateTime('1990-01-01'), 'male');
        $citizen->setId('citizen-1');
        $citizen->setUserId('user-123');

        $this->citizenRepository->expects($this->once())
            ->method('findById')
            ->with('citizen-1')
            ->willReturn($citizen);

        $this->citizenRepository->expects($this->once())
            ->method('delete')
            ->with($citizen);

        $result = $this->service->deleteCitizen('citizen-1', 'user-123');

        $this->assertTrue($result);
    }

    public function testDeleteCitizenThrowsWhenAccessDenied(): void
    {
        $citizen = new Citizen('123456789', 'John Doe', new \DateTime('1990-01-01'), 'male');
        $citizen->setId('citizen-1');
        $citizen->setUserId('other-user');

        $this->citizenRepository->expects($this->once())
            ->method('findById')
            ->with('citizen-1')
            ->willReturn($citizen);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');

        $this->service->deleteCitizen('citizen-1', 'user-123');
    }

    // ==================== GET ALL CITIZENS TEST ====================

    public function testGetAllCitizensReturnsArray(): void
    {
        $citizens = [
            $this->createCitizenMock(1, 'Citizen 1'),
            $this->createCitizenMock(2, 'Citizen 2'),
        ];

        $this->citizenRepository->expects($this->once())
            ->method('findAll')
            ->with(50, 0)
            ->willReturn($citizens);

        $result = $this->service->getAllCitizens(50, 0);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(CitizenResponse::class, $result[0]);
    }
}
