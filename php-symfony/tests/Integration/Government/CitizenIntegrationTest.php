<?php

declare(strict_types=1);

namespace App\Tests\Integration\Government;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Government\Application\Service\CitizenService;
use App\Government\Domain\Entity\Citizen;
use App\Government\Infrastructure\Repository\CitizenRepository;
use App\Government\Domain\ValueObject\CitizenId;
use App\Government\Contracts\DTO\CreateCitizenRequest;

#[AllowMockObjectsWithoutExpectations]
class CitizenIntegrationTest extends DatabaseIntegrationTestCase
{
    private CitizenService $service;
    private CitizenRepository $citizenRepo;
    private \Doctrine\ORM\EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = self::getEntityManager();
        $this->citizenRepo = new CitizenRepository($this->em);
        $this->service = new CitizenService($this->citizenRepo);
    }

    public function testCreateCitizenSuccess(): void
    {
        $request = new CreateCitizenRequest(
            fullName: 'John Doe',
            identityNumber: 'ID123456789',
            province: 'Hanoi',
            district: 'Ba Dinh',
            ward: 'Phuc Yen',
            street: '123 Main St'
        );

        $result = $this->service->createCitizen($request);

        $this->assertInstanceOf(\App\Government\Contracts\DTO\CitizenResponse::class, $result);
        $this->assertNotNull($result->id);
        $this->assertSame('John Doe', $result->fullName);
        $this->assertSame('ID123456789', $result->identityNumber);
        $this->assertSame('Hanoi', $result->registeredAddress['province'] ?? null);
        $this->assertSame('Ba Dinh', $result->registeredAddress['district'] ?? null);
        $this->assertSame('Phuc Yen', $result->registeredAddress['ward'] ?? null);
        $this->assertSame('123 Main St', $result->registeredAddress['street'] ?? null);

        // Verify persisted by fetching entity using repository's findById
        $saved = $this->citizenRepo->findById(new CitizenId($result->id));
        $this->assertNotNull($saved);
        $this->assertSame('John Doe', $saved->getFullName());
        $this->assertSame('ID123456789', $saved->getIdentityNumber());
        $this->assertSame('Hanoi', $saved->getRegisteredAddress()->getProvince());
        $this->assertSame('Ba Dinh', $saved->getRegisteredAddress()->getDistrict());
        $this->assertSame('Phuc Yen', $saved->getRegisteredAddress()->getWard());
        $this->assertSame('123 Main St', $saved->getRegisteredAddress()->getStreet());
    }

    public function testCreateCitizenFailsWhenIdentityNumberDuplicate(): void
    {
        $identity = 'DUP123456';

        $req1 = new CreateCitizenRequest(
            fullName: 'Alice',
            identityNumber: $identity,
            province: 'Hanoi',
            district: 'Ba Dinh',
            ward: 'Phuc Yen',
            street: '123 Main St'
        );
        $this->service->createCitizen($req1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen with identity number ' . $identity . ' already exists');

        $req2 = new CreateCitizenRequest(
            fullName: 'Bob',
            identityNumber: $identity,
            province: 'Hanoi',
            district: 'Ba Dinh',
            ward: 'Phuc Yen',
            street: '456 Main St'
        );
        $this->service->createCitizen($req2);
    }

    public function testUpdateCitizenChangesOptionalFields(): void
    {
        $request = new CreateCitizenRequest(
            fullName: 'Charlie',
            identityNumber: 'IDUPDATE',
            province: 'Hanoi',
            district: 'Ba Dinh',
            ward: 'Phuc Yen',
            street: '789 Main St'
        );
        $response = $this->service->createCitizen($request);

        // Fetch the entity using findById
        $citizen = $this->citizenRepo->findById(new CitizenId($response->id));
        $this->assertNotNull($citizen, 'Citizen not found after creation');

        // Update optional fields
        $citizen->setEmail('charlie@example.com');
        $citizen->setPhone('0912345678');
        $citizen->setDateOfBirth(new \DateTimeImmutable('1990-01-01'));
        $this->em->flush();

        // Reload entity from DB to verify updates
        $this->em->refresh($citizen);
        $this->assertSame('charlie@example.com', $citizen->getEmail());
        $this->assertSame('0912345678', $citizen->getPhone());
        $this->assertNotNull($citizen->getDateOfBirth());
    }

    public function testFindByIdentityNumberReturnsCitizen(): void
    {
        $request = new CreateCitizenRequest(
            fullName: 'Diana',
            identityNumber: 'IDFINDME',
            province: 'Hanoi',
            district: 'Ba Dinh',
            ward: 'Phuc Yen',
            street: '999 Main St'
        );
        $createdResponse = $this->service->createCitizen($request);

        $found = $this->citizenRepo->findByIdentityNumber('IDFINDME');
        $this->assertNotNull($found);
        $this->assertSame($createdResponse->id, $found->getId()->getValue());
    }
}
