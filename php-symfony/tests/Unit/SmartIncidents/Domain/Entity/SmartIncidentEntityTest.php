<?php

declare(strict_types=1);

namespace App\Tests\Unit\SmartIncidents\Domain\Entity;

use App\SmartIncidents\Domain\Entity\SmartIncident;
use App\SmartIncidents\Domain\Entity\SmartIncidentActivity;
use App\SmartIncidents\Domain\Entity\SmartIncidentAttachment;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SmartIncidentEntityTest extends TestCase
{
    private function makeIncident(): SmartIncident
    {
        $incident = new SmartIncident();
        $incident->setTitle('Tranh cai giao thong')
                ->setDescription('Xe container dam vao hang rao')
                ->setIncidentType('traffic')
                ->setSeverity('high');
        return $incident;
    }

    // ================= constructor / identity =================
    public function testConstructorInitializesState(): void
    {
        $incident = new SmartIncident();

        $this->assertSame('reported', $incident->getStatus());
        $this->assertSame('medium', $incident->getSeverity());
        // format: 'BAO' . Ymd . '-' . 6 hex chars (no dash after BAO)
        $this->assertStringStartsWith('BAO' . date('Ymd') . '-', $incident->getIncidentNumber());
        $this->assertInstanceOf(DateTimeImmutable::class, $incident->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $incident->getReportedAt());
        $this->assertNull($incident->getResolvedAt());
        $this->assertNull($incident->getClosedAt());
        $this->assertTrue($incident->isOpen());
        $this->assertFalse($incident->isResolved());
        $this->assertCount(0, $incident->getAttachments());
        $this->assertCount(0, $incident->getMessages());
    }

    // ================= setters / getters =================
    public function testSettersUpdateFields(): void
    {
        $incident = new SmartIncident();
        $result = $incident->setTitle('Chay nha')
            ->setDescription('Chay nha o khu B')
            ->setIncidentType('fire')
            ->setSeverity('critical')
            ->setReporterName('Nguyen Van B')
            ->setReporterPhone('0909123456')
            ->setReporterAnonymous(true)
            ->setWardCode('W02')
            ->setAssignedOfficerId('OFF-9')
            ->setAssignedTeam('Team A')
            ->setLocation('123 Le Loi', 10.7769, 106.7009);

        $this->assertSame($incident, $result); // fluent
        $this->assertSame('Chay nha', $incident->getTitle());
        $this->assertSame('Chay nha o khu B', $incident->getDescription());
        $this->assertSame('fire', $incident->getIncidentType());
        $this->assertSame('critical', $incident->getSeverity());
        $this->assertSame('Nguyen Van B', $incident->getReporterName());
        $this->assertSame('0909123456', $incident->getReporterPhone());
        $this->assertTrue($incident->isReporterAnonymous());
        $this->assertSame('W02', $incident->getWardCode());
        $this->assertSame('OFF-9', $incident->getAssignedOfficerId());
        $this->assertSame('Team A', $incident->getAssignedTeam());
        $this->assertSame('123 Le Loi', $incident->getLocationAddress());
        $this->assertEqualsWithDelta(10.7769, $incident->getLatitude(), 0.0001);
        $this->assertEqualsWithDelta(106.7009, $incident->getLongitude(), 0.0001);
    }

    public function testSettersAcceptNull(): void
    {
        $incident = $this->makeIncident();
        $incident->setDescription(null)
            ->setReporterName(null)
            ->setReporterPhone(null)
            ->setAssignedOfficerId(null)
            ->setAssignedTeam(null);

        $this->assertNull($incident->getDescription());
        $this->assertNull($incident->getReporterName());
        $this->assertNull($incident->getReporterPhone());
        $this->assertNull($incident->getAssignedOfficerId());
        $this->assertNull($incident->getAssignedTeam());
    }

    public function testSetLocationWithoutCoordinates(): void
    {
        $incident = $this->makeIncident()->setLocation('Dia diem moi');

        $this->assertSame('Dia diem moi', $incident->getLocationAddress());
        $this->assertNull($incident->getLatitude());
        $this->assertNull($incident->getLongitude());
    }

    // ================= state machine =================
    public function testAssignToSetsOfficerAndStatus(): void
    {
        $incident = $this->makeIncident()->assignTo('OFF-1', 'Team X');

        $this->assertSame('assigned', $incident->getStatus());
        $this->assertSame('OFF-1', $incident->getAssignedOfficerId());
        $this->assertSame('Team X', $incident->getAssignedTeam());
        $this->assertTrue($incident->isOpen());
    }

    public function testAssignToTeamOnly(): void
    {
        $incident = $this->makeIncident()->assignTo(null, 'Team Y');

        $this->assertSame('assigned', $incident->getStatus());
        $this->assertNull($incident->getAssignedOfficerId());
        $this->assertSame('Team Y', $incident->getAssignedTeam());
    }

    public function testResolveSetsSummaryAndTimestamp(): void
    {
        $incident = $this->makeIncident()->resolve('Da xu xong');

        $this->assertSame('resolved', $incident->getStatus());
        $this->assertSame('Da xu xong', $incident->getResolutionSummary());
        $this->assertInstanceOf(DateTimeImmutable::class, $incident->getResolvedAt());
        $this->assertFalse($incident->isOpen());
        $this->assertTrue($incident->isResolved());
    }

    public function testCloseSetsClosedAt(): void
    {
        $incident = $this->makeIncident()->close();

        $this->assertSame('closed', $incident->getStatus());
        $this->assertInstanceOf(DateTimeImmutable::class, $incident->getClosedAt());
        $this->assertTrue($incident->isResolved());
        $this->assertFalse($incident->isOpen());
    }

    public function testEscalateFromOpenState(): void
    {
        $incident = $this->makeIncident()->escalate();

        $this->assertSame('escalated', $incident->getStatus());
        $this->assertFalse($incident->isOpen());
        $this->assertFalse($incident->isResolved());
    }

    public function testIsOpenForInProgressAndAwaitingInfo(): void
    {
        $incident = $this->makeIncident();
        $incident->setStatus('in_progress');
        $this->assertTrue($incident->isOpen());

        $incident->setStatus('awaiting_info');
        $this->assertTrue($incident->isOpen());
    }

    // ================= collections =================
    public function testAddAttachmentLinksBothDirections(): void
    {
        $incident = $this->makeIncident();
        $attachment = new SmartIncidentAttachment($incident, 'image/png', 'photo.png', 'https://cdn/x.png', 2048);

        $result = $incident->addAttachment($attachment);

        $this->assertSame($incident, $result);
        $this->assertCount(1, $incident->getAttachments());
        $this->assertSame($incident, $attachment->getIncident());
        $this->assertNotNull($incident->getUpdatedAt());
    }

    public function testRemoveAttachment(): void
    {
        $incident = $this->makeIncident();
        $attachment = new SmartIncidentAttachment($incident, 'image/png', 'p.png', 'u', 10);
        $incident->addAttachment($attachment);

        $incident->removeAttachment($attachment);

        $this->assertCount(0, $incident->getAttachments());
    }

    public function testDefaultSetIsWritable(): void
    {
        $incident = new SmartIncident();

        $this->assertCount(0, $incident->getActivityLog());
    }

    public function testViewAndUpvoteCounters(): void
    {
        $incident = $this->makeIncident();
        $this->assertSame(0, $incident->getViewCount());
        $this->assertSame(0, $incident->getUpvoteCount());

        $incident->incrementViewCount()->incrementUpvote()->incrementUpvote();

        $this->assertSame(1, $incident->getViewCount());
        $this->assertSame(2, $incident->getUpvoteCount());
    }

    public function testSetMetadataRoundTrip(): void
    {
        $incident = $this->makeIncident()->setMetadata(['source' => 'app', 'priority' => 5]);

        $this->assertSame(['source' => 'app', 'priority' => 5], $incident->getMetadata());
    }

    public function testToArraySerializesCoreFields(): void
    {
        $incident = $this->makeIncident();
        $incident->setWardCode('W01')->setSeverity('high');

        $arr = $incident->toArray();

        // toArray is the API payload shape (snake_case keys)
        $this->assertIsArray($arr);
        $this->assertSame('reported', $arr['status']);
        $this->assertSame('high', $arr['severity']);
        $this->assertSame('W01', $arr['ward_code']);
        $this->assertSame('Tranh cai giao thong', $arr['title']);
        $this->assertSame(0, $arr['view_count']);
    }
}
