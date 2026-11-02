<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Entity\GovOfficer;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GovOfficerTest extends TestCase
{
    public function testConstructorInitializesProperties(): void
    {
        $officer = new GovOfficer('Health Dept', 'Manager');
        $this->assertIsString($officer->getId());
        $this->assertNotEmpty($officer->getId());
        $this->assertSame('Health Dept', $officer->getDepartment());
        $this->assertSame('Manager', $officer->getPosition());
        $this->assertSame(GovOfficer::STATUS_ACTIVE, $officer->getStatus());
        $this->assertTrue($officer->isActive());
        $this->assertInstanceOf(\DateTime::class, $officer->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $officer->getUpdatedAt());
        $this->assertNull($officer->getUser());
    }

    public function testGettersAndSetters(): void
    {
        $officer = new GovOfficer('Dept', 'Pos');

        $officer->setDepartment('New Dept');
        $this->assertSame('New Dept', $officer->getDepartment());

        $officer->setPosition('New Pos');
        $this->assertSame('New Pos', $officer->getPosition());

        $officer->setStatus(GovOfficer::STATUS_INACTIVE);
        $this->assertSame(GovOfficer::STATUS_INACTIVE, $officer->getStatus());
        $this->assertFalse($officer->isActive());

        $officer->setUser(null);
        $this->assertNull($officer->getUser());
    }

    public function testToArray(): void
    {
        $officer = new GovOfficer('DeptX', 'PosX');
        $arr = $officer->toArray();
        $this->assertSame('DeptX', $arr['department']);
        $this->assertSame('PosX', $arr['position']);
        $this->assertSame(GovOfficer::STATUS_ACTIVE, $arr['status']);
        $this->assertNull($arr['userId']);
        $this->assertIsString($arr['id']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['createdAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['updatedAt']);
    }
}
