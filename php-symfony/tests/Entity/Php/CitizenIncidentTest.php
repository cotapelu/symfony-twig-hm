<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\CitizenIncident;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CitizenIncidentTest extends TestCase
{
    private function createIncident(
        string $id = 'incident-id',
        string $citizenId = 'citizen-1',
        string $type = 'NOISE'
    ): CitizenIncident {
        return new CitizenIncident($id, $citizenId, $type);
    }

    public function testConstructor(): void
    {
        $i = $this->createIncident();
        $this->assertSame('incident-id', $i->getId());
        $this->assertSame('citizen-1', $i->getCitizenId());
        $this->assertSame('NOISE', $i->getType());
        $this->assertSame('PENDING', $i->getStatus());
        $this->assertNull($i->getData());
        $this->assertInstanceOf(\DateTime::class, $i->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $i->getUpdatedAt());
        $this->assertTrue($i->isPending());
        $this->assertFalse($i->isInvestigating());
        $this->assertFalse($i->isResolved());
        $this->assertFalse($i->isClosed());
        $this->assertFalse($i->isCancelled());
    }

    public function testSetCitizenId(): void
    {
        $i = $this->createIncident();
        $original = $i->getUpdatedAt();
        usleep(1000);
        $i->setCitizenId('citizen-2');
        $this->assertSame('citizen-2', $i->getCitizenId());
        $this->assertNotEquals($original, $i->getUpdatedAt());
    }

    public function testSetType(): void
    {
        $i = $this->createIncident();
        $original = $i->getUpdatedAt();
        usleep(1000);
        $i->setType('TRAFFIC');
        $this->assertSame('TRAFFIC', $i->getType());
        $this->assertNotEquals($original, $i->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $i = $this->createIncident();
        $i->setStatus('INVESTIGATING');
        $this->assertTrue($i->isInvestigating());
        $this->assertFalse($i->isPending());

        $i->setStatus('RESOLVED');
        $this->assertTrue($i->isResolved());
        $i->setStatus('CLOSED');
        $this->assertTrue($i->isClosed());
        $i->setStatus('CANCELLED');
        $this->assertTrue($i->isCancelled());
    }

    public function testSetData(): void
    {
        $i = $this->createIncident();
        $original = $i->getUpdatedAt();
        usleep(1000);
        $data = ['details' => 'Loud music after 10pm'];
        $i->setData($data);
        $this->assertSame($data, $i->getData());
        $this->assertNotEquals($original, $i->getUpdatedAt());
        $i->setData(null);
        $this->assertNull($i->getData());
    }
}
