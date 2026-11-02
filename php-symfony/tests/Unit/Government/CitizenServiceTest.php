<?php declare(strict_types=1);

namespace App\Tests\Unit\Government;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Application\Service\CitizenService;
use App\Government\Contracts\DTO\CreateCitizenRequest;
use App\Government\Contracts\DTO\CitizenResponse;
use App\Government\Domain\Repository\CitizenRepositoryInterface;
use App\Government\Domain\Entity\Citizen;
use App\Government\Domain\ValueObject\CitizenId;
use App\Government\Domain\ValueObject\Address;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[AllowMockObjectsWithoutExpectations]
class CitizenServiceTest extends TestCase
{
    private CitizenRepositoryInterface $citizenRepo;
    private CitizenService $service;

    protected function setUp(): void
    {
        $this->citizenRepo = $this->createMock(CitizenRepositoryInterface::class);
        $this->service = new CitizenService($this->citizenRepo);
    }

    private function createValidRequest(array $overrides = []): CreateCitizenRequest
    {
        return new CreateCitizenRequest(
            fullName: $overrides['fullName'] ?? 'John Doe',
            identityNumber: $overrides['identityNumber'] ?? '123456789',
            province: $overrides['province'] ?? 'Hà Nội',
            district: $overrides['district'] ?? 'Ba Đình',
            ward: $overrides['ward'] ?? 'Phường C',
            street: $overrides['street'] ?? '123 Đường ABC',
            apartmentNumber: $overrides['apartmentNumber'] ?? null,
            email: $overrides['email'] ?? null,
            phone: $overrides['phone'] ?? null,
            dateOfBirth: $overrides['dateOfBirth'] ?? null,
            gender: $overrides['gender'] ?? 'male',
            ethnicity: $overrides['ethnicity'] ?? null,
            nationality: $overrides['nationality'] ?? 'Vietnam',
            occupation: $overrides['occupation'] ?? null,
            educationLevel: $overrides['educationLevel'] ?? null,
            maritalStatus: $overrides['maritalStatus'] ?? null,
            householdId: $overrides['householdId'] ?? null
        );
    }

    private function createCitizenEntity(string $id, string $identityNumber, string $name = 'Test Citizen'): Citizen
    {
        $citizenId = new CitizenId($id);
        $address = new Address('Province', 'District', 'Ward', 'Street', null);
        $citizen = new Citizen($citizenId, $name, $identityNumber, $address);
        // Set required fields not set by constructor
        $citizen->setGender('male');
        $citizen->setNationality('Vietnam');
        $citizen->setReligion('none');
        $citizen->setEthnicity('Kinh');
        return $citizen;
    }

    public function testCreateCitizenThrowsIfDuplicateIdentityNumber(): void
    {
        $identityNumber = '123456789';
        $existingCitizen = $this->createCitizenEntity(Uuid::uuid4()->toString(), $identityNumber);

        $this->citizenRepo->expects($this->once())
            ->method('findByIdentityNumber')
            ->with($identityNumber)
            ->willReturn($existingCitizen);

        $request = $this->createValidRequest(['identityNumber' => $identityNumber]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Citizen with identity number $identityNumber already exists");

        $this->service->createCitizen($request);
    }

    public function testCreateCitizenSavesNewCitizen(): void
    {
        $identityNumber = '987654321';
        $uuid = Uuid::uuid4()->toString();

        $this->citizenRepo->expects($this->once())
            ->method('findByIdentityNumber')
            ->with($identityNumber)
            ->willReturn(null);

        $this->citizenRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Citizen $citizen) use ($identityNumber) {
                $this->assertEquals($identityNumber, $citizen->getIdentityNumber());
                return $citizen;
            });

        $request = $this->createValidRequest([
            'identityNumber' => $identityNumber,
            'fullName' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '0987654321',
        ]);

        $result = $this->service->createCitizen($request);

        $this->assertInstanceOf(CitizenResponse::class, $result);
        $this->assertEquals($identityNumber, $result->identityNumber);
        $this->assertEquals('Jane Doe', $result->fullName);
    }

    public function testGetCitizenByIdReturnsResponseWhenFound(): void
    {
        $id = Uuid::uuid4()->toString();
        $identityNumber = '123456789';
        $citizen = $this->createCitizenEntity($id, $identityNumber, 'Found Citizen');

        $this->citizenRepo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($citizen);

        $result = $this->service->getCitizenById($id);

        $this->assertInstanceOf(CitizenResponse::class, $result);
        $this->assertEquals($id, $result->id);
        $this->assertEquals($identityNumber, $result->identityNumber);
    }

    public function testGetCitizenByIdReturnsNullWhenNotFound(): void
    {
        $id = Uuid::uuid4()->toString();

        $this->citizenRepo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn(null);

        $result = $this->service->getCitizenById($id);

        $this->assertNull($result);
    }

    public function testListCitizensReturnsPaginatedResults(): void
    {
        $filters = ['province' => 'Hanoi'];
        $page = 1;
        $perPage = 20;

        $citizens = [
            $this->createCitizenEntity(Uuid::uuid4()->toString(), '111'),
            $this->createCitizenEntity(Uuid::uuid4()->toString(), '222'),
        ];

        $this->citizenRepo->expects($this->once())
            ->method('findAll')
            ->with($filters, $page, $perPage)
            ->willReturn($citizens);

        $result = $this->service->listCitizens($filters, $page, $perPage);

        $this->assertCount(2, $result);
        $this->assertEquals('111', $result[0]->identityNumber);
    }
}
