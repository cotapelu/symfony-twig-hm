<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Entity\CitizenIncident;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CitizenIncidentTest extends TestCase
{
    public function testConstructorInitializesProperties(): void
    {
        $incident = new CitizenIncident(type: CitizenIncident::TYPE_ANTT);
        $this->assertIsString($incident->getId());
        $this->assertNotEmpty($incident->getId());
        $this->assertSame(CitizenIncident::TYPE_ANTT, $incident->getType());
        $this->assertSame(CitizenIncident::STATUS_OPEN, $incident->getStatus());
        $this->assertTrue($incident->isOpen());
        $this->assertFalse($incident->isInProgress());
        $this->assertFalse($incident->isResolved());
        $this->assertFalse($incident->isClosed());
        $this->assertNull($incident->getCitizen());
        $this->assertNull($incident->getData());
        $this->assertInstanceOf(\DateTime::class, $incident->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $incident->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $incident = new CitizenIncident(type: CitizenIncident::TYPE_SECURITY);

        $incident->setType(CitizenIncident::TYPE_MEDICAL);
        $this->assertSame(CitizenIncident::TYPE_MEDICAL, $incident->getType());

        $incident->setStatus(CitizenIncident::STATUS_IN_PROGRESS);
        $this->assertSame(CitizenIncident::STATUS_IN_PROGRESS, $incident->getStatus());
        $this->assertFalse($incident->isOpen());
        $this->assertTrue($incident->isInProgress());
        $this->assertFalse($incident->isResolved());
        $this->assertFalse($incident->isClosed());

        $incident->setStatus(CitizenIncident::STATUS_RESOLVED);
        $this->assertTrue($incident->isResolved());

        $incident->setStatus(CitizenIncident::STATUS_CLOSED);
        $this->assertTrue($incident->isClosed());

        $data = ['location' => '123 Main St', 'details' => 'Noise complaint'];
        $incident->setData($data);
        $this->assertSame($data, $incident->getData());

        $incident->setData(null);
        $this->assertNull($incident->getData());
    }

    public function testToArray(): void
    {
        $incident = new CitizenIncident(type: CitizenIncident::TYPE_OTHER);
        $arr = $incident->toArray();
        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('type', $arr);
        $this->assertArrayHasKey('status', $arr);
        $this->assertArrayHasKey('data', $arr);
        $this->assertArrayHasKey('createdAt', $arr);
        $this->assertArrayHasKey('updatedAt', $arr);
        $this->assertNull($arr['citizenId']);
        $this->assertSame(CitizenIncident::TYPE_OTHER, $arr['type']);
        $this->assertSame(CitizenIncident::STATUS_OPEN, $arr['status']);
    }
}
