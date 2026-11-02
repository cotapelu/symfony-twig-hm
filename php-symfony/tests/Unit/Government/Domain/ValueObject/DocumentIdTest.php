<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Domain\ValueObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Domain\ValueObject\DocumentId;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DocumentIdTest extends TestCase
{
    public function testConstructorGeneratesUuidIfNull(): void
    {
        $id = new DocumentId();
        $this->assertNotNull($id->toString());
        $this->assertSame($id->toString(), (string)$id);
    }

    public function testConstructorAcceptsGivenId(): void
    {
        $docId = new DocumentId('doc-123');
        $this->assertSame('doc-123', $docId->toString());
        $this->assertSame('doc-123', (string)$docId);
    }

    public function testEquals(): void
    {
        $id1 = new DocumentId('abc');
        $id2 = new DocumentId('abc');
        $id3 = new DocumentId('def');
        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }
}
