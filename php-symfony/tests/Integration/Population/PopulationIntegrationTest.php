<?php

declare(strict_types=1);

namespace App\Tests\Integration\Population;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Population\Entity\PopulationRecord;
use App\Population\Repository\PopulationRecordRepository;

#[AllowMockObjectsWithoutExpectations]
class PopulationIntegrationTest extends DatabaseIntegrationTestCase
{
    private PopulationRecordRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $em = self::getEntityManager();
        $this->repository = $em->getRepository(PopulationRecord::class);
    }

    public function testCreatePopulationRecord(): void
    {
        $record = new PopulationRecord();
        $record->setName('John Doe');
        $record->setBirthDate(new \DateTime('1990-05-15'));
        $record->setGender('male');
        $record->setAddress('123 Main St');
        $record->setHouseholdId('HH-001');
        $record->setStatus('active');
        $record->setType('citizen');
        $record->setCitizenId('CID-12345');

        $em = self::getEntityManager();
        $em->persist($record);
        $em->flush();

        $this->assertNotNull($record->getId());
        $this->assertSame('John Doe', $record->getName());
    }

    public function testFindByCitizenId(): void
    {
        $record = new PopulationRecord();
        $record->setName('Jane Smith');
        $record->setBirthDate(new \DateTime('1985-08-20'));
        $record->setGender('female');
        $record->setAddress('456 Oak Ave');
        $record->setHouseholdId('HH-002');
        $record->setStatus('active');
        $record->setType('citizen');
        $record->setCitizenId('CID-67890');

        $em = self::getEntityManager();
        $em->persist($record);
        $em->flush();

        $found = $this->repository->findOneBy(['citizenId' => 'CID-67890']);
        $this->assertNotNull($found);
        $this->assertSame('Jane Smith', $found->getName());
    }

    public function testFindPaginatedReturnsRecords(): void
    {
        // Create 5 records
        for ($i = 0; $i < 5; $i++) {
            $record = new PopulationRecord();
            $record->setName("Person $i");
            $record->setBirthDate(new \DateTime('2000-01-01'));
            $record->setGender('male');
            $record->setAddress("Address $i");
            $record->setHouseholdId('HH-' . $i);
            $record->setStatus('active');
            $record->setType('citizen');
            $record->setCitizenId('CID-' . $i);
            self::getEntityManager()->persist($record);
        }
        self::getEntityManager()->flush();

        $result = $this->repository->findPaginated(1, 10);
        $this->assertCount(5, $result);
    }

    public function testUpdatePopulationRecord(): void
    {
        $record = new PopulationRecord();
        $record->setName('Original Name');
        $record->setBirthDate(new \DateTime());
        $record->setGender('male');
        $record->setAddress('Original Address');
        $record->setHouseholdId('HH-999');
        $record->setStatus('active');
        $record->setType('citizen');
        $record->setCitizenId('CID-UPDATE');

        $em = self::getEntityManager();
        $em->persist($record);
        $em->flush();

        $recordId = $record->getId();
        $record->setName('Updated Name');
        $em->flush();

        $updated = $this->repository->find($recordId);
        $this->assertSame('Updated Name', $updated->getName());
    }

    public function testDeletePopulationRecord(): void
    {
        $record = new PopulationRecord();
        $record->setName('ToDelete');
        $record->setBirthDate(new \DateTime());
        $record->setGender('female');
        $record->setAddress('Delete Address');
        $record->setHouseholdId('HH-DEL');
        $record->setStatus('inactive');
        $record->setType('citizen');
        $record->setCitizenId('CID-DEL');

        $em = self::getEntityManager();
        $em->persist($record);
        $em->flush();

        $id = $record->getId();
        $em->remove($record);
        $em->flush();

        $deleted = $this->repository->find($id);
        $this->assertNull($deleted);
    }
}
