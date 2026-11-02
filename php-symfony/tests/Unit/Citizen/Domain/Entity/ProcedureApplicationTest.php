<?php

declare(strict_types=1);

namespace App\Tests\Unit\Citizen\Domain\Entity;

use PHPUnit\Framework\TestCase;
use App\Citizen\Domain\Entity\ProcedureApplication;
use App\Citizen\Domain\Entity\Citizen;
use App\Government\Domain\Entity\AdministrativeProcedure;
use DateTime;

class ProcedureApplicationTest extends TestCase
{
    private Citizen $citizen;
    private AdministrativeProcedure $procedure;
    private ProcedureApplication $app;

    protected function setUp(): void
    {
        $this->citizen = $this->createMock(Citizen::class);
        $this->citizen->method('getId')->willReturn('citizen-123');
        $this->procedure = $this->createMock(AdministrativeProcedure::class);
        $this->procedure->method('getId')->willReturn('proc-456');
        $this->procedure->method('getProcessingTimeDays')->willReturn(5);
        $this->app = new ProcedureApplication($this->citizen, $this->procedure);
    }

    public function testConstructorInitializesDefaults(): void
    {
        $this->assertNotEmpty($this->app->getId());
        $this->assertSame($this->citizen, $this->app->getCitizen());
        $this->assertSame($this->procedure, $this->app->getProcedure());
        $this->assertSame(ProcedureApplication::STATUS_DRAFT, $this->app->getStatus());
        $this->assertSame([], $this->app->getApplicationData());
        $this->assertSame([], $this->app->getAttachedDocuments());
        $this->assertNull($this->app->getSubmissionDate());
        $this->assertNull($this->app->getExpectedCompletionDate());
        $this->assertNull($this->app->getActualCompletionDate());
        $this->assertNull($this->app->getRejectionReason());
        $this->assertNull($this->app->getProcessedBy());
        $this->assertNull($this->app->getNotes());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->app->getStatusHistory());
        $this->assertCount(0, $this->app->getStatusHistory());
    }

    public function testGetEventName(): void
    {
        $this->assertSame('procedure_application.updated', $this->app->getEventName());
    }

    public function testGetEventDataContainsIdsAndStatus(): void
    {
        $data = $this->app->getEventData();
        $this->assertArrayHasKey('applicationId', $data);
        $this->assertArrayHasKey('citizenId', $data);
        $this->assertArrayHasKey('procedureId', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertSame($this->app->getId(), $data['applicationId']);
        $this->assertSame('citizen-123', $data['citizenId']);
        $this->assertSame('proc-456', $data['procedureId']);
        $this->assertSame(ProcedureApplication::STATUS_DRAFT, $data['status']);
    }

    public function testGetEventTimestampReturnsDateTime(): void
    {
        $ts = $this->app->getEventTimestamp();
        $this->assertInstanceOf(\DateTimeInterface::class, $ts);
    }

    // Setters and fluent interface

    public function testSetCitizen(): void
    {
        $newCitizen = $this->createMock(Citizen::class);
        $newCitizen->method('getId')->willReturn('citizen-999');
        $result = $this->app->setCitizen($newCitizen);
        $this->assertSame($this->app, $result);
        $this->assertSame($newCitizen, $this->app->getCitizen());
    }

    public function testSetProcedure(): void
    {
        $newProc = $this->createMock(AdministrativeProcedure::class);
        $newProc->method('getId')->willReturn('proc-888');
        $result = $this->app->setProcedure($newProc);
        $this->assertSame($this->app, $result);
        $this->assertSame($newProc, $this->app->getProcedure());
    }

    public function testSetStatus(): void
    {
        $result = $this->app->setStatus(ProcedureApplication::STATUS_SUBMITTED);
        $this->assertSame($this->app, $result);
        $this->assertSame(ProcedureApplication::STATUS_SUBMITTED, $this->app->getStatus());
    }

    public function testSetApplicationData(): void
    {
        $data = ['field1' => 'value1', 'field2' => 123];
        $result = $this->app->setApplicationData($data);
        $this->assertSame($this->app, $result);
        $this->assertSame($data, $this->app->getApplicationData());
    }

    public function testSetAttachedDocuments(): void
    {
        $docs = ['doc1.pdf', 'doc2.pdf'];
        $result = $this->app->setAttachedDocuments($docs);
        $this->assertSame($this->app, $result);
        $this->assertSame($docs, $this->app->getAttachedDocuments());
    }

    public function testSetSubmissionDate(): void
    {
        $date = new DateTime('2024-01-15');
        $result = $this->app->setSubmissionDate($date);
        $this->assertSame($this->app, $result);
        $this->assertSame($date, $this->app->getSubmissionDate());
    }

    public function testSetExpectedCompletionDate(): void
    {
        $date = new DateTime('2024-03-01');
        $result = $this->app->setExpectedCompletionDate($date);
        $this->assertSame($this->app, $result);
        $this->assertSame($date, $this->app->getExpectedCompletionDate());
    }

    public function testSetActualCompletionDate(): void
    {
        $date = new DateTime('2024-02-20');
        $result = $this->app->setActualCompletionDate($date);
        $this->assertSame($this->app, $result);
        $this->assertSame($date, $this->app->getActualCompletionDate());
    }

    public function testSetRejectionReason(): void
    {
        $reason = 'Missing documents';
        $result = $this->app->setRejectionReason($reason);
        $this->assertSame($this->app, $result);
        $this->assertSame($reason, $this->app->getRejectionReason());
    }

    public function testSetProcessedBy(): void
    {
        $officerId = 'officer-789';
        $result = $this->app->setProcessedBy($officerId);
        $this->assertSame($this->app, $result);
        $this->assertSame($officerId, $this->app->getProcessedBy());
    }

    public function testSetNotes(): void
    {
        $notes = 'Some notes';
        $result = $this->app->setNotes($notes);
        $this->assertSame($this->app, $result);
        $this->assertSame($notes, $this->app->getNotes());
    }

    // Status history

    public function testAddStatusHistory(): void
    {
        $history = $this->createMock(\App\Citizen\Domain\Entity\ApplicationStatusHistory::class);
        $history->expects($this->once())->method('setApplication')->with($this->app);
        $result = $this->app->addStatusHistory($history);
        $this->assertSame($this->app, $result);
        $this->assertTrue($this->app->getStatusHistory()->contains($history));
    }

    public function testRemoveStatusHistory(): void
    {
        $history = $this->createMock(\App\Citizen\Domain\Entity\ApplicationStatusHistory::class);
        $this->app->addStatusHistory($history);
        $this->assertCount(1, $this->app->getStatusHistory());
        $history->expects($this->once())->method('setApplication')->with($this->app);
        $result = $this->app->removeStatusHistory($history);
        $this->assertSame($this->app, $result);
        $this->assertCount(0, $this->app->getStatusHistory());
    }

    // Domain actions

    public function testSubmit(): void
    {
        $this->app->submit();
        $this->assertSame(ProcedureApplication::STATUS_SUBMITTED, $this->app->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->app->getSubmissionDate());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->app->getExpectedCompletionDate());
    }

    public function testApprove(): void
    {
        $this->app->approve('officer-123');
        $this->assertSame(ProcedureApplication::STATUS_APPROVED, $this->app->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->app->getActualCompletionDate());
        $this->assertSame('officer-123', $this->app->getProcessedBy());
    }

    public function testReject(): void
    {
        $this->app->reject('Missing info', 'officer-456');
        $this->assertSame(ProcedureApplication::STATUS_REJECTED, $this->app->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->app->getActualCompletionDate());
        $this->assertSame('Missing info', $this->app->getRejectionReason());
        $this->assertSame('officer-456', $this->app->getProcessedBy());
    }
}