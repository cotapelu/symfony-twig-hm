<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Citizen\Entity\Citizen;
use App\Government\Entity\AdministrativeProcedure;
use App\Government\Entity\ProcedureApplication;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProcedureApplicationTest extends TestCase
{
    private function createCitizen(): Citizen
    {
        return new Citizen(
            identityNumber: '123456789',
            fullName: 'John Doe',
            dateOfBirth: new \DateTime('1990-01-01'),
            gender: 'M'
        );
    }

    private function createProcedure(): AdministrativeProcedure
    {
        $proc = new AdministrativeProcedure();
        $proc->setProcedureCode('PROC-001');
        $proc->setName('Business License');
        return $proc;
    }

    private function setProcedureApplicationId(ProcedureApplication $app, int $id): void
    {
        $ref = new \ReflectionProperty($app, 'id');
        $ref->setAccessible(true);
        $ref->setValue($app, $id);
    }

    public function testConstructorInitializesProperties(): void
    {
        $citizen = $this->createCitizen();
        $procedure = $this->createProcedure();

        $app = new ProcedureApplication($citizen, $procedure);
        $this->assertSame($citizen, $app->getCitizen());
        $this->assertSame($procedure, $app->getProcedure());
        $this->assertSame(ProcedureApplication::STATUS_PENDING, $app->getStatus());
        $this->assertTrue($app->isPending());
        $this->assertFalse($app->isApproved());
        $this->assertFalse($app->isRejected());
        $this->assertFalse($app->isProcessing());
        $this->assertFalse($app->isCompleted());
        $this->assertNull($app->getData());
        $this->assertInstanceOf(\DateTime::class, $app->getSubmittedAt());
        $this->assertInstanceOf(\DateTime::class, $app->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $citizen = $this->createCitizen();
        $procedure = $this->createProcedure();

        $app = new ProcedureApplication($citizen, $procedure);

        $app->setStatus(ProcedureApplication::STATUS_APPROVED);
        $this->assertSame(ProcedureApplication::STATUS_APPROVED, $app->getStatus());
        $this->assertTrue($app->isApproved());
        $this->assertFalse($app->isPending());

        $app->setStatus(ProcedureApplication::STATUS_REJECTED);
        $this->assertTrue($app->isRejected());

        $app->setStatus(ProcedureApplication::STATUS_PROCESSING);
        $this->assertTrue($app->isProcessing());

        $app->setStatus(ProcedureApplication::STATUS_COMPLETED);
        $this->assertTrue($app->isCompleted());

        $data = ['field1' => 'value1'];
        $app->setData($data);
        $this->assertSame($data, $app->getData());
    }

    public function testSettersForCitizenAndProcedure(): void
    {
        $citizen1 = $this->createCitizen();
        $procedure1 = $this->createProcedure();
        $app = new ProcedureApplication($citizen1, $procedure1);
        $this->assertSame($citizen1, $app->getCitizen());

        $citizen2 = $this->createCitizen();
        $app->setCitizen($citizen2);
        $this->assertSame($citizen2, $app->getCitizen());

        $procedure2 = $this->createProcedure();
        $app->setProcedure($procedure2);
        $this->assertSame($procedure2, $app->getProcedure());
    }

    public function testToArrayFormat(): void
    {
        $citizen = $this->createCitizen();
        $procedure = $this->createProcedure();
        $app = new ProcedureApplication($citizen, $procedure);
        // Simulate persisted ID
        $this->setProcedureApplicationId($app, 1);
        $app->setStatus(ProcedureApplication::STATUS_PROCESSING);
        $data = ['notes' => 'in progress'];
        $app->setData($data);

        $arr = $app->toArray();
        $this->assertArrayHasKey('id', $arr);
        $this->assertSame($citizen->getId(), $arr['citizenId']);
        $this->assertNull($arr['procedureId']); // procedure id not set
        $this->assertSame(ProcedureApplication::STATUS_PROCESSING, $arr['status']);
        $this->assertSame($data, $arr['data']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['submittedAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['updatedAt']);
    }
}
