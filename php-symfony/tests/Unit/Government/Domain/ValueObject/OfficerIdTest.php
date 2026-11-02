<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Domain\ValueObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Domain\ValueObject\OfficerId;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OfficerIdTest extends TestCase
{
    public function testConstructorThrowsIfEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Officer ID cannot be empty');
        new OfficerId('');
    }

    public function testConstructorThrowsIfWhitespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Officer ID cannot be empty');
        new OfficerId('   ');
    }

    public function testConstructorAcceptsValid(): void
    {
        $id = new OfficerId('OFF-999');
        $this->assertSame('OFF-999', $id->getValue());
        $this->assertSame('OFF-999', (string)$id);
    }

    public function testEquals(): void
    {
        $id1 = new OfficerId('001');
        $id2 = new OfficerId('001');
        $id3 = new OfficerId('002');
        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }
}
