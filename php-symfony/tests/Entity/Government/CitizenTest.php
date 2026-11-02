<?php declare(strict_types=1);

namespace App\Tests\Entity\Government;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Government\Citizen;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CitizenTest extends TestCase
{
    private function createCitizen(): Citizen
    {
        return new Citizen();
    }

    public function testConstructor(): void
    {
        $c = $this->createCitizen();
        $this->assertNull($c->getId());
        $this->assertNull($c->getUserId());
        $this->assertNull($c->getCitizenId());
        $this->assertNull($c->getFullName());
        $this->assertNull($c->getDateOfBirth());
        $this->assertNull($c->getGender());
        $this->assertNull($c->getAddress());
        $this->assertNull($c->getPhone());
        $this->assertNull($c->getEmail());
        $this->assertSame('active', $c->getStatus());
        $this->assertInstanceOf(\DateTime::class, $c->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $c->getUpdatedAt());
    }

    public function testSetUserId(): void
    {
        $c = $this->createCitizen();
        $c->setUserId('user-123');
        $this->assertSame('user-123', $c->getUserId());
        $c->setUserId(null);
        $this->assertNull($c->getUserId());
    }

    public function testSetCitizenId(): void
    {
        $c = $this->createCitizen();
        $c->setCitizenId('CID-456');
        $this->assertSame('CID-456', $c->getCitizenId());
    }

    public function testSetFullName(): void
    {
        $c = $this->createCitizen();
        $c->setFullName('John Doe');
        $this->assertSame('John Doe', $c->getFullName());
    }

    public function testSetDateOfBirth(): void
    {
        $c = $this->createCitizen();
        $dob = new \DateTime('1990-05-15');
        $c->setDateOfBirth($dob);
        $this->assertSame($dob, $c->getDateOfBirth());
        $c->setDateOfBirth(null);
        $this->assertNull($c->getDateOfBirth());
    }

    public function testSetGender(): void
    {
        $c = $this->createCitizen();
        $c->setGender('male');
        $this->assertSame('male', $c->getGender());
        $c->setGender(null);
        $this->assertNull($c->getGender());
    }

    public function testSetAddress(): void
    {
        $c = $this->createCitizen();
        $c->setAddress('123 Main St');
        $this->assertSame('123 Main St', $c->getAddress());
        $c->setAddress(null);
        $this->assertNull($c->getAddress());
    }

    public function testSetPhone(): void
    {
        $c = $this->createCitizen();
        $c->setPhone('0123456789');
        $this->assertSame('0123456789', $c->getPhone());
        $c->setPhone(null);
        $this->assertNull($c->getPhone());
    }

    public function testSetEmail(): void
    {
        $c = $this->createCitizen();
        $c->setEmail('john@example.com');
        $this->assertSame('john@example.com', $c->getEmail());
        $c->setEmail(null);
        $this->assertNull($c->getEmail());
    }

    public function testSetStatus(): void
    {
        $c = $this->createCitizen();
        $c->setStatus('inactive');
        $this->assertSame('inactive', $c->getStatus());
    }

    public function testSetCreatedAt(): void
    {
        $c = $this->createCitizen();
        $now = new \DateTime();
        $c->setCreatedAt($now);
        $this->assertSame($now, $c->getCreatedAt());
    }

    public function testSetUpdatedAt(): void
    {
        $c = $this->createCitizen();
        $now = new \DateTime();
        $c->setUpdatedAt($now);
        $this->assertSame($now, $c->getUpdatedAt());
    }
}
