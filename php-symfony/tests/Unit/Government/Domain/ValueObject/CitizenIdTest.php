<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Domain\ValueObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Domain\ValueObject\CitizenId;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CitizenIdTest extends TestCase
{
    public function testConstructorThrowsIfEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen ID cannot be empty');
        new CitizenId('');
    }

    public function testConstructorTrimsAndThrowsIfWhitespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen ID cannot be empty');
        new CitizenId('   ');
    }

    public function testConstructorAcceptsValid(): void
    {
        $id = new CitizenId('CID-12345');
        $this->assertSame('CID-12345', $id->getValue());
        $this->assertSame('CID-12345', (string)$id);
    }

    public function testEquals(): void
    {
        $id1 = new CitizenId('ABC');
        $id2 = new CitizenId('ABC');
        $id3 = new CitizenId('XYZ');
        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }
}
