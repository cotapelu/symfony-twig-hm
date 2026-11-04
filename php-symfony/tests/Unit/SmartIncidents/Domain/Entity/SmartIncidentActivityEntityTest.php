<?php

declare(strict_types=1);

namespace App\Tests\Unit\SmartIncidents\Domain\Entity;

use App\SmartIncidents\Domain\Entity\SmartIncident;
use App\SmartIncidents\Domain\Entity\SmartIncidentActivity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SmartIncidentActivityEntityTest extends TestCase
{
    private function makeActivity(): SmartIncidentActivity
    {
        return new SmartIncidentActivity(
            new SmartIncident(),
            'status_changed',
            'Trang thai chuyen thanh assigned',
            'OFF-1',
            'Nguyen Van A'
        );
    }

    public function testConstructorStoresFields(): void
    {
        $activity = $this->makeActivity();

        $this->assertSame('status_changed', $activity->getActivityType());
        $this->assertSame('Trang thai chuyen thanh assigned', $activity->getDescription());
        $this->assertSame('OFF-1', $activity->getPerformedBy());
        $this->assertSame('Nguyen Van A', $activity->getPerformedByName());
        $this->assertInstanceOf(DateTimeImmutable::class, $activity->getCreatedAt());
        $this->assertSame([], $activity->getMetadata()); // null metadataJson -> []
    }

    public function testSetIncident(): void
    {
        $activity = $this->makeActivity();
        $newIncident = new SmartIncident();

        $result = $activity->setIncident($newIncident);

        $this->assertSame($activity, $result);
        $this->assertSame($newIncident, $activity->getIncident());
    }

    public function testSetMetadataEncodesJson(): void
    {
        $activity = $this->makeActivity();

        $activity->setMetadata(['oldStatus' => 'reported', 'newStatus' => 'assigned']);

        $this->assertSame(
            ['oldStatus' => 'reported', 'newStatus' => 'assigned'],
            $activity->getMetadata()
        );
    }

    public function testSetMetadataToEmpty(): void
    {
        $activity = $this->makeActivity()->setMetadata(['a' => 1]);

        $activity->setMetadata([]);

        $this->assertSame([], $activity->getMetadata());
    }

    public function testToArraySerializesKeyFields(): void
    {
        $activity = $this->makeActivity();
        $activity->setMetadata(['channel' => 'mobile']);

        $arr = $activity->toArray();

        $this->assertIsArray($arr);
        $this->assertSame('status_changed', $arr['activityType']);
        $this->assertSame('OFF-1', $arr['performedBy']);
        $this->assertSame('Nguyen Van A', $arr['performedByName']);
        $this->assertSame(['channel' => 'mobile'], $arr['metadata']);
    }

    // ================= static factories =================
    public function testCreateCreatedActivity(): void
    {
        $incident = new SmartIncident();
        $activity = SmartIncidentActivity::createCreatedActivity($incident, 'CIT-1', 'Cong dan A');

        $this->assertSame('created', $activity->getActivityType());
        $this->assertSame('Incident reported', $activity->getDescription());
        $this->assertSame('CIT-1', $activity->getPerformedBy());
        $this->assertSame($incident, $activity->getIncident());
    }

    public function testCreateStatusChangedActivity(): void
    {
        $activity = SmartIncidentActivity::createStatusChangedActivity(
            new SmartIncident(), 'reported', 'assigned', 'OFF-1', 'Can bo A'
        );

        $this->assertSame('status_changed', $activity->getActivityType());
        $this->assertSame('Status changed from reported to assigned', $activity->getDescription());
    }

    public function testCreateAssignedActivity_WithAndWithoutTeam(): void
    {
        $withoutTeam = SmartIncidentActivity::createAssignedActivity(
            new SmartIncident(), 'OFF-1', null, 'OFF-1', 'Can bo A'
        );
        $this->assertSame('Assigned to officer OFF-1', $withoutTeam->getDescription());

        $withTeam = SmartIncidentActivity::createAssignedActivity(
            new SmartIncident(), 'OFF-1', 'Team X', 'OFF-1', 'Can bo A'
        );
        $this->assertSame('Assigned to officer OFF-1 in team Team X', $withTeam->getDescription());
    }

    public function testCreateResolvedActivity(): void
    {
        $activity = SmartIncidentActivity::createResolvedActivity(
            new SmartIncident(), 'Da xu ly xong', 'OFF-1', 'Can bo A'
        );

        $this->assertSame('resolved', $activity->getActivityType());
        $this->assertSame('Incident resolved: Da xu ly xong', $activity->getDescription());
    }

    public function testCreateEscalatedActivity(): void
    {
        $activity = SmartIncidentActivity::createEscalatedActivity(
            new SmartIncident(), 'Can xu ly cap cao', 'OFF-1', 'Can bo A'
        );

        $this->assertSame('escalated', $activity->getActivityType());
        $this->assertSame('Incident escalated: Can xu ly cap cao', $activity->getDescription());
    }

    public function testCreateMessageSentActivity(): void
    {
        $activity = SmartIncidentActivity::createMessageSentActivity(
            new SmartIncident(), 'CIT-1', 'Cong dan A'
        );

        $this->assertSame('message_sent', $activity->getActivityType());
        $this->assertSame('Message sent by Cong dan A', $activity->getDescription());
    }

    public function testCreateAttachmentAddedActivity(): void
    {
        $activity = SmartIncidentActivity::createAttachmentAddedActivity(
            new SmartIncident(), 42, 'image/jpeg', 'CIT-1', 'Cong dan A'
        );

        $this->assertSame('attachment_added', $activity->getActivityType());
    }
}
