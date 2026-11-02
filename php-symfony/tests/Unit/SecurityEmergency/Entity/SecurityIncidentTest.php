<?php declare(strict_types=1);

namespace App\Tests\Unit\SecurityEmergency\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\SecurityEmergency\Entity\SecurityIncident;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SecurityIncidentTest extends TestCase
{
    public function testConstructor(): void
    {
        $incident = new SecurityIncident(
            type: 'theft',
            status: 'open',
            reporterId: 'user-123',
            location: 'Main St',
            description: 'Stolen bike'
        );

        $this->assertIsString($incident->getId());
        $this->assertSame('theft', $incident->getType());
        $this->assertSame('open', $incident->getStatus());
        $this->assertSame('Main St', $incident->getLocation());
        $this->assertSame('Stolen bike', $incident->getDescription());
        $this->assertSame('user-123', $incident->getReporterId());
        $this->assertNull($incident->getAssignedOfficerId());
        $this->assertInstanceOf(\DateTime::class, $incident->getCreatedAt());
        $this->assertNull($incident->getUpdatedAt());
    }

    public function testSetters(): void
    {
        $incident = new SecurityIncident('vandalism', 'investigating', 'user-456');

        $incident->setStatus('resolved');
        $this->assertSame('resolved', $incident->getStatus());

        $incident->setLocation('Park Ave');
        $this->assertSame('Park Ave', $incident->getLocation());

        $incident->setDescription('Graffiti');
        $this->assertSame('Graffiti', $incident->getDescription());

        $incident->setAssignedOfficerId('off-789');
        $this->assertSame('off-789', $incident->getAssignedOfficerId());

        $incident->setReporterId('user-999');
        $this->assertSame('user-999', $incident->getReporterId());

        $updated = new \DateTime();
        $incident->setUpdatedAt($updated);
        $this->assertSame($updated, $incident->getUpdatedAt());
    }

    public function testToArray(): void
    {
        $created = new \DateTime('2025-01-01 10:00:00');
        $updated = new \DateTime('2025-01-02 12:00:00');
        $incident = new SecurityIncident('noise', 'closed', 'user-1', 'Residential area', 'Loud party');
        // Use reflection to set createdAt and updatedAt
        $refCreated = new \ReflectionProperty($incident, 'createdAt');
        $refCreated->setAccessible(true);
        $refCreated->setValue($incident, $created);
        $refUpdated = new \ReflectionProperty($incident, 'updatedAt');
        $refUpdated->setAccessible(true);
        $refUpdated->setValue($incident, $updated);

        $arr = $incident->toArray();

        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertSame('noise', $arr['type']);
        $this->assertSame('closed', $arr['status']);
        $this->assertSame('Residential area', $arr['location']);
        $this->assertSame('Loud party', $arr['description']);
        $this->assertSame('user-1', $arr['reporterId']);
        $this->assertSame('2025-01-01 10:00:00', $arr['createdAt']);
        $this->assertSame('2025-01-02 12:00:00', $arr['updatedAt']);
    }
}
