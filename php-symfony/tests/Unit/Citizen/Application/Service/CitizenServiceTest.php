<?php

declare(strict_types=1);

namespace App\Tests\Unit\Citizen\Application\Service;

use App\Citizen\Application\Repository\CitizenRepositoryInterface;
use App\Citizen\Application\Service\CitizenService;
use App\Citizen\Domain\Entity\Citizen;
use App\Citizen\Application\DTO\CreateCitizenRequest;
use PHPUnit\Framework\TestCase;
use DateTime;
use DateTimeInterface;

class CitizenServiceTest extends TestCase
{
    private CitizenRepositoryInterface $repositoryMock;
    private CitizenService $service;

    private function createCitizen(array $data = []): Citizen
    {
        $citizen = new Citizen(
            $data['identityNumber'] ?? 'ID123',
            $data['fullName'] ?? 'Test Citizen',
            $data['dateOfBirth'] ?? null,
            $data['gender'] ?? 'Male'
        );
        if (isset($data['id'])) {
            $ref = new \ReflectionProperty(Citizen::class, 'id');
            $ref->setAccessible(true);
            $ref->setValue($citizen, $data['id']);
        }
        // Set additional fields via setters
        if (isset($data['phoneNumber'])) $citizen->setPhoneNumber($data['phoneNumber']);
        if (isset($data['email'])) $citizen->setEmail($data['email']);
        if (isset($data['permanentAddress'])) $citizen->setPermanentAddress($data['permanentAddress']);
        if (isset($data['currentAddress'])) $citizen->setCurrentAddress($data['currentAddress']);
        if (isset($data['ethnicity'])) $citizen->setEthnicity($data['ethnicity']);
        if (isset($data['religion'])) $citizen->setReligion($data['religion']);
        if (isset($data['educationLevel'])) $citizen->setEducationLevel($data['educationLevel']);
        if (isset($data['occupation'])) $citizen->setOccupation($data['occupation']);
        if (isset($data['maritalStatus'])) $citizen->setMaritalStatus($data['maritalStatus']);
        if (isset($data['specialCategories'])) $citizen->setSpecialCategories($data['specialCategories']);
        if (isset($data['notes'])) $citizen->setNotes($data['notes']);
        if (isset($data['userId'])) $citizen->setUserId($data['userId']);
        // isHouseholdHead default handled
        return $citizen;
    }

    private function createCreateCitizenRequest(array $data): CreateCitizenRequest
    {
        $req = new CreateCitizenRequest();
        foreach ($data as $prop => $value) {
            if (property_exists(CreateCitizenRequest::class, $prop)) {
                $reflection = new \ReflectionProperty(CreateCitizenRequest::class, $prop);
                $reflection->setAccessible(true);
                if ($prop === 'dateOfBirth' && is_string($value)) {
                    $value = new DateTime($value);
                }
                $reflection->setValue($req, $value);
            }
        }
        return $req;
    }

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(CitizenRepositoryInterface::class);
        $this->service = new CitizenService($this->repositoryMock);
    }

    // ==================== createCitizen ====================

    public function testCreateCitizen_Success(): void
    {
        $request = $this->createCreateCitizenRequest([
            'identityNumber' => 'ID123456',
            'fullName' => 'John Doe',
            'dateOfBirth' => '1990-01-01',
            'gender' => 'Male',
            'phoneNumber' => '+123456789',
            'email' => 'john@example.com',
            'permanentAddress' => '123 Main St',
            'currentAddress' => '456 Current Ave',
            'ethnicity' => 'Kinh',
            'religion' => 'Buddhist',
            'educationLevel' => 'Bachelor',
            'occupation' => 'Engineer',
            'maritalStatus' => 'Single',
            'isHouseholdHead' => true,
            'specialCategories' => ['elderly'],
            'notes' => 'No notes',
            'userId' => 'user-1',
        ]);

        $this->repositoryMock->method('findByIdentityNumber')->with('ID123456')->willReturn(null);
        $this->repositoryMock->expects($this->once())->method('save');

        $response = $this->service->createCitizen($request);
        $this->assertSame('John Doe', $response->fullName);
        $this->assertSame('ID123456', $response->identityNumber);
        $this->assertSame('user-1', $response->userId);
    }

    public function testCreateCitizen_DuplicateIdentity_Throws(): void
    {
        $existing = $this->createCitizen(['identityNumber' => 'ID123']);
        $request = $this->createCreateCitizenRequest(['identityNumber' => 'ID123']);
        $this->repositoryMock->method('findByIdentityNumber')->with('ID123')->willReturn($existing);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen with this identity number already exists');
        $this->service->createCitizen($request);
    }

    // ==================== getCitizen ====================

    public function testGetCitizen_Success(): void
    {
        $citizen = $this->createCitizen(['id' => 'citizen-1', 'userId' => 'user-1']);
        $this->repositoryMock->method('findById')->with('citizen-1')->willReturn($citizen);

        $response = $this->service->getCitizen('citizen-1', 'user-1');
        $this->assertNotNull($response);
        $this->assertSame('citizen-1', $response->id);
    }

    public function testGetCitizen_NotFound(): void
    {
        $this->repositoryMock->method('findById')->with('unknown')->willReturn(null);
        $response = $this->service->getCitizen('unknown', 'user-1');
        $this->assertNull($response);
    }

    public function testGetCitizen_AccessDenied(): void
    {
        $citizen = $this->createCitizen(['id' => 'citizen-1', 'userId' => 'user-2']);
        $this->repositoryMock->method('findById')->with('citizen-1')->willReturn($citizen);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');
        $this->service->getCitizen('citizen-1', 'user-1');
    }

    // ==================== searchCitizens ====================

    public function testSearchCitizens_Success(): void
    {
        $citizen1 = $this->createCitizen(['id' => 'c1']);
        $citizen2 = $this->createCitizen(['id' => 'c2']);
        $this->repositoryMock->method('search')->with('john', [])->willReturn([$citizen1, $citizen2]);

        $result = $this->service->searchCitizens('john');
        $this->assertCount(2, $result);
        $this->assertSame('c1', $result[0]->id);
    }

    public function testSearchCitizens_WithLimitOffset(): void
    {
        $citizens = [$this->createCitizen(['id' => 'c1'])];
        $this->repositoryMock->method('search')->willReturn($citizens);

        $result = $this->service->searchCitizens('john', [], 10, 0);
        $this->assertCount(1, $result);
    }

    // ==================== getCitizensByHousehold ====================

    public function testGetCitizensByHousehold(): void
    {
        $c1 = $this->createCitizen(['id' => 'c1']);
        $c2 = $this->createCitizen(['id' => 'c2']);
        $this->repositoryMock->method('findByHouseholdId')->with('hh-1', 50, 0)->willReturn([$c1, $c2]);

        $result = $this->service->getCitizensByHousehold('hh-1');
        $this->assertCount(2, $result);
    }

    // ==================== getElderlyCitizens ====================

    public function testGetElderlyCitizens(): void
    {
        $c = $this->createCitizen(['id' => 'elder']);
        $this->repositoryMock->method('findElderly')->with(60)->willReturn([$c]);

        $result = $this->service->getElderlyCitizens();
        $this->assertCount(1, $result);
    }

    public function testGetElderlyCitizens_CustomAge(): void
    {
        $c = $this->createCitizen(['id' => 'elder']);
        $this->repositoryMock->method('findElderly')->with(65)->willReturn([$c]);

        $result = $this->service->getElderlyCitizens(65);
        $this->assertCount(1, $result);
    }

    // ==================== getChildCitizens ====================

    public function testGetChildCitizens(): void
    {
        $c = $this->createCitizen(['id' => 'child']);
        $this->repositoryMock->method('findChildren')->with(16)->willReturn([$c]);

        $result = $this->service->getChildCitizens();
        $this->assertCount(1, $result);
    }

    // ==================== getCitizensBySpecialCategories ====================

    public function testGetCitizensBySpecialCategories(): void
    {
        $c = $this->createCitizen(['id' => 'spec']);
        $this->repositoryMock->method('findBySpecialCategories')->with(['disabled'])->willReturn([$c]);

        $result = $this->service->getCitizensBySpecialCategories(['disabled']);
        $this->assertCount(1, $result);
    }

    // ==================== getAllCitizens ====================

    public function testGetAllCitizens(): void
    {
        $c = $this->createCitizen(['id' => 'all']);
        $this->repositoryMock->method('findAll')->with(50, 0)->willReturn([$c]);

        $result = $this->service->getAllCitizens();
        $this->assertCount(1, $result);
    }

    public function testGetAllCitizens_WithLimitOffset(): void
    {
        $c = $this->createCitizen(['id' => 'all']);
        $this->repositoryMock->method('findAll')->with(10, 20)->willReturn([$c]);

        $result = $this->service->getAllCitizens(10, 20);
        $this->assertCount(1, $result);
    }

    // ==================== updateCitizen ====================

    public function testUpdateCitizen_Success(): void
    {
        $citizen = $this->createCitizen(['id' => 'c1', 'userId' => 'user-1']);
        $this->repositoryMock->method('findById')->with('c1')->willReturn($citizen);
        $this->repositoryMock->expects($this->once())->method('save');

        $result = $this->service->updateCitizen('c1', ['fullName' => 'Updated Name'], 'user-1');
        $this->assertNotNull($result);
        $this->assertSame('Updated Name', $result->fullName);
        $this->assertSame('c1', $result->id);
    }

    public function testUpdateCitizen_NotFound(): void
    {
        $this->repositoryMock->method('findById')->with('unknown')->willReturn(null);
        $result = $this->service->updateCitizen('unknown', ['fullName' => 'Test'], 'user-1');
        $this->assertNull($result);
    }

    public function testUpdateCitizen_AccessDenied(): void
    {
        $citizen = $this->createCitizen(['id' => 'c1', 'userId' => 'user-2']);
        $this->repositoryMock->method('findById')->with('c1')->willReturn($citizen);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');
        $this->service->updateCitizen('c1', ['fullName' => 'Test'], 'user-1');
    }

    public function testUpdateCitizen_PartialUpdatesAddress(): void
    {
        $citizen = $this->createCitizen(['id' => 'c1', 'userId' => 'user-1']);
        $this->repositoryMock->method('findById')->with('c1')->willReturn($citizen);
        $this->repositoryMock->expects($this->once())->method('save');

        $this->service->updateCitizen('c1', ['permanentAddress' => 'New Address'], 'user-1');
        $this->assertSame('New Address', $citizen->getPermanentAddress());
    }

    // ==================== deleteCitizen ====================

    public function testDeleteCitizen_Success(): void
    {
        $citizen = $this->createCitizen(['id' => 'c1', 'userId' => 'user-1']);
        $this->repositoryMock->method('findById')->with('c1')->willReturn($citizen);
        $this->repositoryMock->expects($this->once())->method('delete');

        $result = $this->service->deleteCitizen('c1', 'user-1');
        $this->assertTrue($result);
    }

    public function testDeleteCitizen_NotFound(): void
    {
        $this->repositoryMock->method('findById')->with('unknown')->willReturn(null);
        $result = $this->service->deleteCitizen('unknown', 'user-1');
        $this->assertFalse($result);
    }

    public function testDeleteCitizen_AccessDenied(): void
    {
        $citizen = $this->createCitizen(['id' => 'c1', 'userId' => 'user-2']);
        $this->repositoryMock->method('findById')->with('c1')->willReturn($citizen);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');
        $this->service->deleteCitizen('c1', 'user-1');
    }

    // ==================== getCitizenStatistics ====================

    public function testGetCitizenStatistics(): void
    {
        $this->repositoryMock->method('getStatistics')->willReturn(['total' => 100, 'male' => 50, 'female' => 50]);
        $result = $this->service->getCitizenStatistics();
        $this->assertSame(['total' => 100, 'male' => 50, 'female' => 50], $result);
    }

    // ==================== addResidentHistory ====================

    public function testAddResidentHistory_Success(): void
    {
        $citizen = $this->createMock(Citizen::class);
        $citizen->method('getId')->willReturn('c1');
        $citizen->method('getUserId')->willReturn('user-1');
        $this->repositoryMock->method('findById')->with('c1')->willReturn($citizen);
        $history = $this->createMock(\App\Citizen\Domain\Entity\ResidentHistory::class);
        $citizen->expects($this->once())->method('addResidentHistory')->with($history);
        $this->repositoryMock->expects($this->once())->method('save');

        $this->service->addResidentHistory('c1', $history, 'user-1');
        $this->assertTrue(true);
    }

    public function testAddResidentHistory_CitizenNotFound(): void
    {
        $this->repositoryMock->method('findById')->with('c1')->willReturn(null);
        $history = $this->createMock(\App\Citizen\Domain\Entity\ResidentHistory::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen not found');
        $this->service->addResidentHistory('c1', $history, 'user-1');
    }

    public function testAddResidentHistory_AccessDenied(): void
    {
        $citizen = $this->createCitizen(['id' => 'c1', 'userId' => 'user-2']);
        $this->repositoryMock->method('findById')->with('c1')->willReturn($citizen);
        $history = $this->createMock(\App\Citizen\Domain\Entity\ResidentHistory::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');
        $this->service->addResidentHistory('c1', $history, 'user-1');
    }
}
