<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\Citizen;
use App\Repository\Php\CitizenRepository;
use App\Service\Php\CitizenService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[AllowMockObjectsWithoutExpectations]
class CitizenServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private CitizenRepository&MockObject $repository;
    private CitizenService $service;

    private string $citizenId;
    private Citizen $citizen;
    private array $officerRoles = ['ROLE_OFFICER'];

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(CitizenRepository::class);
        $this->service = new CitizenService($this->em, $this->repository);

        $this->citizenId = bin2hex(random_bytes(16));
        $this->citizen = new Citizen(
            $this->citizenId,
            'John Doe',
            '1234567890'
        );
        // set optional fields via reflection? They have setters.
    }

    public function testCreateCitizenSuccess(): void
    {
        $this->service->setCurrentUserRoles($this->officerRoles);
        $name = 'Jane Doe';
        $identityNumber = 'ID987654321';
        $householdId = 'household-123';
        $metadata = ['note' => 'VIP'];

        $captured = null;
        $this->repository->expects($this->once())
            ->method('findByIdentityNumber')
            ->with($identityNumber)
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Citizen $c) use (&$captured, $name, $identityNumber, $householdId, $metadata) {
                $captured = $c;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createCitizen($name, $identityNumber, $householdId, $metadata);
        $this->assertInstanceOf(Citizen::class, $result);
        $this->assertSame($name, $result->getName());
        $this->assertSame($identityNumber, $result->getIdentityNumber());
        $this->assertSame($householdId, $result->getHouseholdId());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateCitizenValidationEmptyName(): void
    {
        $this->service->setCurrentUserRoles($this->officerRoles);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen name is required');
        $this->service->createCitizen('', 'ID123');
    }

    public function testCreateCitizenValidationEmptyIdentityNumber(): void
    {
        $this->service->setCurrentUserRoles($this->officerRoles);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identity number is required');
        $this->service->createCitizen('Name', '');
    }

    public function testCreateCitizenDuplicateIdentityNumber(): void
    {
        $this->service->setCurrentUserRoles($this->officerRoles);
        $existing = $this->createMock(Citizen::class);
        $this->repository->expects($this->once())
            ->method('findByIdentityNumber')
            ->with('ID123')
            ->willReturn($existing);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Citizen with identity number 'ID123' already exists");
        $this->service->createCitizen('Name', 'ID123');
    }

    public function testCreateCitizenAccessDeniedWhenNotOfficer(): void
    {
        $this->service->setCurrentUserRoles(['ROLE_USER']);
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Officer or admin role required');
        $this->service->createCitizen('Name', 'ID123');
    }

    public function testGetCitizenSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->citizenId)
            ->willReturn($this->citizen);

        $result = $this->service->getCitizen($this->citizenId);
        $this->assertSame($this->citizen, $result);
    }

    public function testGetCitizenNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->citizenId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Citizen not found');
        $this->service->getCitizen($this->citizenId);
    }

    public function testGetCitizenByIdentityNumber(): void
    {
        $identity = 'ID123';
        $expected = $this->createMock(Citizen::class);
        $this->repository->expects($this->once())
            ->method('findByIdentityNumber')
            ->with($identity)
            ->willReturn($expected);

        $result = $this->service->getCitizenByIdentityNumber($identity);
        $this->assertSame($expected, $result);
    }

    public function testUpdateCitizenSuccess(): void
    {
        $this->service->setCurrentUserRoles($this->officerRoles);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->citizenId)
            ->willReturn($this->citizen);
        $this->repository->expects($this->once())
            ->method('findByIdentityNumber')
            ->with('newID')
            ->willReturn(null); // no conflict

        $this->em->expects($this->once())->method('flush');

        $newName = 'Jane Updated';
        $newIdentity = 'newID';
        $newHousehold = 'hh-2';
        $newStatus = 'INACTIVE';
        $newMetadata = ['updated' => true];

        $data = [
            'name' => $newName,
            'identityNumber' => $newIdentity,
            'householdId' => $newHousehold,
            'status' => $newStatus,
            'metadata' => $newMetadata,
        ];

        $result = $this->service->updateCitizen($this->citizenId, $data);
        $this->assertSame($this->citizen, $result);
        $this->assertSame($newName, $result->getName());
        $this->assertSame($newIdentity, $result->getIdentityNumber());
        $this->assertSame($newHousehold, $result->getHouseholdId());
        $this->assertSame($newStatus, $result->getStatus());
        $this->assertSame($newMetadata, $result->getMetadata());
    }

    public function testUpdateCitizenValidationEmptyName(): void
    {
        $this->service->setCurrentUserRoles($this->officerRoles);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->citizenId)
            ->willReturn($this->citizen);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen name cannot be empty');
        $this->service->updateCitizen($this->citizenId, ['name' => '   ']);
    }

    public function testUpdateCitizenValidationEmptyIdentityNumber(): void
    {
        $this->service->setCurrentUserRoles($this->officerRoles);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->citizenId)
            ->willReturn($this->citizen);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identity number cannot be empty');
        $this->service->updateCitizen($this->citizenId, ['identityNumber' => '']);
    }

    public function testUpdateCitizenDuplicateIdentityNumber(): void
    {
        $this->service->setCurrentUserRoles($this->officerRoles);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->citizenId)
            ->willReturn($this->citizen);
        $otherCitizen = $this->createMock(Citizen::class);
        $otherCitizen->method('getId')->willReturn('other-id');
        $this->repository->expects($this->once())
            ->method('findByIdentityNumber')
            ->with('newID')
            ->willReturn($otherCitizen);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Citizen with identity number 'newID' already exists");
        $this->service->updateCitizen($this->citizenId, ['identityNumber' => 'newID']);
    }

    public function testUpdateCitizenAccessDenied(): void
    {
        $this->service->setCurrentUserRoles(['ROLE_USER']);
        $this->expectException(AccessDeniedHttpException::class);
        $this->service->updateCitizen($this->citizenId, ['name' => 'New']);
    }

    public function testDeleteCitizenSuccess(): void
    {
        $this->service->setCurrentUserRoles($this->officerRoles);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->citizenId)
            ->willReturn($this->citizen);
        $this->em->expects($this->once())->method('remove')->with($this->citizen);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteCitizen($this->citizenId);
    }

    public function testDeleteCitizenNotFound(): void
    {
        $this->service->setCurrentUserRoles($this->officerRoles);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->citizenId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteCitizen($this->citizenId);
    }

    public function testDeleteCitizenAccessDenied(): void
    {
        $this->service->setCurrentUserRoles(['ROLE_USER']);
        $this->expectException(AccessDeniedHttpException::class);
        $this->service->deleteCitizen($this->citizenId);
    }

    public function testFindByHouseholdId(): void
    {
        $householdId = 'hh-1';
        $results = [$this->citizen];
        $this->repository->expects($this->once())
            ->method('findByHouseholdId')
            ->with($householdId)
            ->willReturn($results);

        $result = $this->service->findByHouseholdId($householdId);
        $this->assertSame($results, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'ACTIVE';
        $results = [$this->citizen];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }

    public function testSearchByName(): void
    {
        $query = 'john';
        $limit = 10;
        $results = [$this->citizen];
        $this->repository->expects($this->once())
            ->method('searchByName')
            ->with($query, $limit)
            ->willReturn($results);

        $result = $this->service->searchByName($query, $limit);
        $this->assertSame($results, $result);
    }
}
