<?php declare(strict_types=1);

namespace App\Tests\Unit\SmartIncidents\Domain\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\SmartIncidents\Domain\Entity\SmartIncident;
use App\SmartIncidents\Domain\Entity\SmartIncidentAttachment;
use App\SmartIncidents\Domain\Entity\SmartIncidentMessage;
use App\SmartIncidents\Domain\Entity\SmartIncidentActivity;
use App\SmartIncidents\Domain\Service\SmartIncidentsService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

#[AllowMockObjectsWithoutExpectations]
class SmartIncidentsServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private SmartIncidentsService $service;

    // Helper to create a basic SmartIncident with some defaults
    private function createIncident(array $data = []): SmartIncident
    {
        $incident = new SmartIncident();
        $incident->setTitle($data['title'] ?? 'Test Incident');
        if (isset($data['description'])) {
            $incident->setDescription($data['description']);
        }
        $incident->setIncidentType($data['incident_type'] ?? 'safety');
        if (isset($data['severity'])) {
            $incident->setSeverity($data['severity']);
        } else {
            $incident->setSeverity('medium');
        }
        if (!empty($data['reporter_name'])) {
            $incident->setReporterName($data['reporter_name']);
        }
        if (!empty($data['reporter_phone'])) {
            $incident->setReporterPhone($data['reporter_phone']);
        }
        if (!empty($data['reporter_anonymous'])) {
            $incident->setReporterAnonymous(true);
        }
        if (!empty($data['location_address']) || !empty($data['latitude']) || !empty($data['longitude'])) {
            $incident->setLocation(
                $data['location_address'] ?? null,
                $data['latitude'] ?? null,
                $data['longitude'] ?? null
            );
        }
        if (!empty($data['ward_code'])) {
            $incident->setWardCode($data['ward_code']);
        }
        // Dates will be set by the service via entity lifecycle; we can ignore for tests.
        return $incident;
    }

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new SmartIncidentsService($this->em);
    }

    // ==================== createIncident ====================

    public function testCreateIncidentSuccess(): void
    {
        $data = [
            'title' => 'Water leak',
            'incident_type' => 'infrastructure',
            'severity' => 'high',
            'reporter_name' => 'Alice',
            'location_address' => '123 Main St',
            'ward_code' => 'W1',
        ];

        // Capture persist calls to verify activity
        $persisted = [];
        $this->em->expects($this->exactly(2))
            ->method('persist')
            ->with($this->callback(function ($obj) use (&$persisted) {
                $persisted[] = $obj;
                return true;
            }));
        $this->em->expects($this->exactly(2))->method('flush');

        $incident = $this->service->createIncident($data);

        $this->assertInstanceOf(SmartIncident::class, $incident);
        $this->assertSame('Water leak', $incident->getTitle());
        $this->assertSame('infrastructure', $incident->getIncidentType());
        $this->assertSame('high', $incident->getSeverity());
        $this->assertSame('Alice', $incident->getReporterName());
        $this->assertSame('W1', $incident->getWardCode());

        // Verify activity log was created
        $this->assertCount(2, $persisted);
        $this->assertInstanceOf(SmartIncidentActivity::class, $persisted[1]);
        $activity = $persisted[1];
        $this->assertSame($incident, $activity->getIncident());
        $this->assertSame('created', $activity->getActivityType());
    }

    // createIncident validation tests
    public function testCreateIncidentThrowsIfTitleEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Title is required');
        $this->service->createIncident(['title' => '', 'incident_type' => 'safety']);
    }

    public function testCreateIncidentThrowsIfTitleTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Title too long');
        $this->service->createIncident(['title' => str_repeat('a', 256), 'incident_type' => 'safety']);
    }

    public function testCreateIncidentThrowsIfMissingIncidentType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Incident type is required');
        $this->service->createIncident(['title' => 'Test']);
    }

    public function testCreateIncidentThrowsIfInvalidIncidentType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid incident type');
        $this->service->createIncident(['title' => 'Test', 'incident_type' => 'invalid']);
    }

    public function testCreateIncidentThrowsIfInvalidSeverity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid severity');
        $this->service->createIncident(['title' => 'Test', 'incident_type' => 'safety', 'severity' => 'extreme']);
    }

    public function testCreateIncidentThrowsIfPhoneTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone number too long');
        $this->service->createIncident(['title' => 'Test', 'incident_type' => 'safety', 'reporter_phone' => str_repeat('1', 21)]);
    }

    public function testCreateIncidentThrowsIfInvalidLatitude(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid latitude');
        $this->service->createIncident(['title' => 'Test', 'incident_type' => 'safety', 'latitude' => 100]);
    }

    public function testCreateIncidentThrowsIfInvalidLongitude(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid longitude');
        $this->service->createIncident(['title' => 'Test', 'incident_type' => 'safety', 'longitude' => 200]);
    }

    public function testCreateIncidentThrowsIfAddressTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Address too long');
        $this->service->createIncident(['title' => 'Test', 'incident_type' => 'safety', 'location_address' => str_repeat('a', 501)]);
    }

    // ==================== getIncident ====================

    public function testGetIncidentSuccess(): void
    {
        $incident = new SmartIncident();
        // Set incidentNumber
        $ref = new \ReflectionProperty($incident, 'incidentNumber');
        $ref->setAccessible(true);
        $ref->setValue($incident, 'INC123');

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->with(['incidentNumber' => 'INC123'])->willReturn($incident);

        $this->em->method('getRepository')->with(SmartIncident::class)->willReturn($repo);

        $result = $this->service->getIncident('INC123');
        $this->assertSame($incident, $result);
    }

    public function testGetIncidentReturnsNullIfNotFound(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn(null);
        $this->em->method('getRepository')->with(SmartIncident::class)->willReturn($repo);

        $result = $this->service->getIncident('UNKNOWN');
        $this->assertNull($result);
    }

    // ==================== getIncidentById ====================

    public function testGetIncidentByIdSuccess(): void
    {
        $incident = new SmartIncident();
        $this->em->expects($this->once())
            ->method('find')
            ->with(SmartIncident::class, 5)
            ->willReturn($incident);

        $result = $this->service->getIncidentById(5);
        $this->assertSame($incident, $result);
    }

    public function testGetIncidentByIdReturnsNullIfNotFound(): void
    {
        $this->em->expects($this->once())->method('find')->with(SmartIncident::class, 999)->willReturn(null);
        $result = $this->service->getIncidentById(999);
        $this->assertNull($result);
    }

    // ==================== getAllIncidents ====================

    public function testGetAllIncidents(): void
    {
        $incident1 = new SmartIncident();
        $incident2 = new SmartIncident();

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())->method('createQueryBuilder')->with('i')->willReturn($qb);

        $this->em->method('getRepository')->with(SmartIncident::class)->willReturn($repo);

        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([$incident1, $incident2]);

        $result = $this->service->getAllIncidents();
        $this->assertSame([$incident1, $incident2], $result);
    }

    public function testGetAllIncidentsWithFilters(): void
    {
        $incident = new SmartIncident();

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())->method('createQueryBuilder')->with('i')->willReturn($qb);
        $this->em->method('getRepository')->with(SmartIncident::class)->willReturn($repo);

        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([$incident]);

        $filters = ['status' => 'open', 'limit' => 10];
        $result = $this->service->getAllIncidents($filters);

        $this->assertSame([$incident], $result);
    }

    // ==================== updateIncidentStatus ====================

    public function testUpdateIncidentStatusSuccess(): void
    {
        $incident = new SmartIncident();
        $incident->setStatus('reported');

        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->exactly(2))->method('flush');

        $result = $this->service->updateIncidentStatus($incident, 'in_progress', 'admin-1');
        $this->assertSame($incident, $result);
        $this->assertSame('in_progress', $incident->getStatus());
    }

    public function testUpdateIncidentStatusThrowsInvalidStatus(): void
    {
        $incident = new SmartIncident();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status');
        $this->service->updateIncidentStatus($incident, 'invalid', 'admin-1');
    }

    // ==================== assignIncident ====================

    public function testAssignIncidentSuccess(): void
    {
        $incident = new SmartIncident();
        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->exactly(2))->method('flush');

        $result = $this->service->assignIncident($incident, 'officer-123', 'team-alpha', 'admin-1');
        $this->assertSame($incident, $result);
        $this->assertSame('officer-123', $incident->getAssignedOfficerId());
        $this->assertSame('team-alpha', $incident->getAssignedTeam());
    }

    // ==================== resolveIncident ====================

    public function testResolveIncidentSuccess(): void
    {
        $incident = new SmartIncident();
        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->exactly(2))->method('flush');

        $result = $this->service->resolveIncident($incident, 'Fixed pipe', 'officer-1');
        $this->assertSame($incident, $result);
        $this->assertNotNull($incident->getResolvedAt());
        $this->assertSame('Fixed pipe', $incident->getResolutionSummary());
    }

    // ==================== addAttachment ====================

    public function testAddAttachmentSuccess(): void
    {
        $incident = new SmartIncident();
        $data = [
            'file_type' => 'image',
            'file_name' => 'photo.jpg',
            'file_url' => 'https://example.com/photo.jpg',
            'file_size' => 1024,
        ];

        $this->em->expects($this->once())->method('persist')->with($this->callback(function ($obj) use ($incident, $data) {
            return $obj instanceof SmartIncidentAttachment && $obj->getIncident() === $incident && $obj->getFileName() === $data['file_name'];
        }));
        $this->em->expects($this->once())->method('flush');

        $attachment = $this->service->addAttachment($incident, $data);
        $this->assertInstanceOf(SmartIncidentAttachment::class, $attachment);
        $this->assertSame('image', $attachment->getFileType());
        $this->assertSame('photo.jpg', $attachment->getFileName());
        $this->assertSame(1024, $attachment->getFileSize());
    }

    // Attachment validation tests
    public function testAddAttachmentThrowsIfInvalidFileType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file type');
        $incident = new SmartIncident();
        $this->service->addAttachment($incident, ['file_type' => 'exe', 'file_name' => 'a', 'file_url' => 'u', 'file_size' => 1]);
    }

    public function testAddAttachmentThrowsIfMissingFileName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File name is required');
        $incident = new SmartIncident();
        $this->service->addAttachment($incident, ['file_type' => 'image', 'file_url' => 'u', 'file_size' => 1]);
    }

    public function testAddAttachmentThrowsIfMissingFileUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File URL is required');
        $incident = new SmartIncident();
        $this->service->addAttachment($incident, ['file_type' => 'image', 'file_name' => 'a', 'file_size' => 1]);
    }

    public function testAddAttachmentThrowsIfInvalidFileSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File size is required');
        $incident = new SmartIncident();
        $this->service->addAttachment($incident, ['file_type' => 'image', 'file_name' => 'a', 'file_url' => 'u']);
    }

    public function testAddAttachmentThrowsIfFileSizeNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File size exceeds maximum');
        $incident = new SmartIncident();
        $this->service->addAttachment($incident, ['file_type' => 'image', 'file_name' => 'a', 'file_url' => 'u', 'file_size' => -1]);
    }

    public function testAddAttachmentThrowsIfFileSizeTooLarge(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File size exceeds maximum');
        $incident = new SmartIncident();
        $this->service->addAttachment($incident, ['file_type' => 'image', 'file_name' => 'a', 'file_url' => 'u', 'file_size' => 20*1024*1024]);
    }

    public function testAddAttachmentThrowsIfInvalidMimeType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid MIME type');
        $incident = new SmartIncident();
        $this->service->addAttachment($incident, ['file_type' => 'image', 'file_name' => 'a', 'file_url' => 'u', 'file_size' => 1, 'mime_type' => 'application/x-msdownload']);
    }

    // ==================== addMessage ====================

    public function testAddMessageSuccess(): void
    {
        $incident = new SmartIncident();
        $data = [
            'sender_type' => 'officer',
            'sender_id' => 'off-1',
            'sender_name' => 'Officer One',
            'content' => 'Investigating',
        ];

        $this->em->expects($this->once())->method('persist')->with($this->callback(function ($obj) use ($incident, $data) {
            return $obj instanceof SmartIncidentMessage && $obj->getIncident() === $incident && $obj->getContent() === $data['content'];
        }));
        $this->em->expects($this->once())->method('flush');

        $message = $this->service->addMessage($incident, $data);
        $this->assertInstanceOf(SmartIncidentMessage::class, $message);
        $this->assertSame('officer', $message->getSender());
        $this->assertSame('off-1', $message->getSenderId());
        $this->assertSame('Officer One', $message->getSenderName());
        $this->assertSame('Investigating', $message->getContent());
    }

    // Message validation tests
    public function testAddMessageThrowsIfMissingSenderType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sender type is required');
        $incident = new SmartIncident();
        $this->service->addMessage($incident, ['sender_id' => '1', 'sender_name' => 'A', 'content' => 'Hi']);
    }

    public function testAddMessageThrowsIfMissingSenderId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sender ID is required');
        $incident = new SmartIncident();
        $this->service->addMessage($incident, ['sender_type' => 'citizen', 'sender_name' => 'A', 'content' => 'Hi']);
    }

    public function testAddMessageThrowsIfMissingSenderName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sender name is required');
        $incident = new SmartIncident();
        $this->service->addMessage($incident, ['sender_type' => 'citizen', 'sender_id' => '1', 'content' => 'Hi']);
    }

    public function testAddMessageThrowsIfMissingContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message content is required');
        $incident = new SmartIncident();
        $this->service->addMessage($incident, ['sender_type' => 'citizen', 'sender_id' => '1', 'sender_name' => 'A']);
    }

    public function testAddMessageThrowsIfContentTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message too long');
        $incident = new SmartIncident();
        $this->service->addMessage($incident, [
            'sender_type' => 'citizen',
            'sender_id' => '1',
            'sender_name' => 'A',
            'content' => str_repeat('a', 2001)
        ]);
    }

    // ==================== getMessages ====================

    public function testGetMessages(): void
    {
        $incident = new SmartIncident();
        $msg = new SmartIncidentMessage($incident, 'citizen', 'Hello');
        $msg->setSenderId('c1');
        $msg->setSenderName('Citizen');

        // Use reflection to add to the messages collection
        $ref = new \ReflectionProperty($incident, 'messages');
        $ref->setAccessible(true);
        $collection = $ref->getValue($incident);
        if (!$collection) {
            $collection = new ArrayCollection();
            $ref->setValue($incident, $collection);
        }
        $collection->add($msg);

        $result = $this->service->getMessages($incident);
        $this->assertTrue(in_array($msg, $result, true), 'Message should be in result array');
    }

    // ==================== getActivityLog ====================

    public function testGetActivityLog(): void
    {
        $incident = new SmartIncident();
        $activity = new SmartIncidentActivity($incident, 'status_changed', 'Status changed', 'admin', 'Admin');

        $ref = new \ReflectionProperty($incident, 'activityLog');
        $ref->setAccessible(true);
        $collection = $ref->getValue($incident) ?: new ArrayCollection();
        $collection->add($activity);
        $ref->setValue($incident, $collection);

        $result = $this->service->getActivityLog($incident);
        $this->assertTrue(in_array($activity, $result, true), 'Activity should be in result array');
    }

    // ==================== incrementViewCount & upvoteIncident ====================

    public function testIncrementViewCount(): void
    {
        $incident = new SmartIncident();
        $ref = new \ReflectionProperty($incident, 'viewCount');
        $ref->setAccessible(true);
        $ref->setValue($incident, 0);

        $this->em->expects($this->once())->method('persist')->with($incident);
        $this->em->expects($this->once())->method('flush');

        $this->service->incrementViewCount($incident);
        $this->assertSame(1, $ref->getValue($incident));
    }

    public function testUpvoteIncident(): void
    {
        $incident = new SmartIncident();
        $ref = new \ReflectionProperty($incident, 'upvoteCount');
        $ref->setAccessible(true);
        $ref->setValue($incident, 0);

        $this->em->expects($this->once())->method('persist')->with($incident);
        $this->em->expects($this->once())->method('flush');

        $this->service->upvoteIncident($incident);
        $this->assertSame(1, $ref->getValue($incident));
    }
}
