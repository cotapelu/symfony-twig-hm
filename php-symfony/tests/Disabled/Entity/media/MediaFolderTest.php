<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\media;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Media\Entity\MediaFolder;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MediaFolder entity.
 * 
 * @covers \App\Media\Entity\MediaFolder
 */
#[AllowMockObjectsWithoutExpectations]
class MediaFolderTest extends TestCase
{
    private MediaFolder $folder;

    protected function setUp(): void
    {
        $this->folder = new MediaFolder(
            userId: 1,
            name: 'Vacation Photos',
            parentId: null
        );
        $this->folder->setId(10);
    }

    public function testConstructor(): void
    {
        $this->assertEquals(1, $this->folder->getUserId());
        $this->assertEquals('Vacation Photos', $this->folder->getName());
        $this->assertNull($this->folder->getParentId());
        $this->assertInstanceOf(\DateTime::class, $this->folder->getCreatedAt());
        $this->assertEquals(10, $this->folder->getId());
    }

    public function testConstructorWithParent(): void
    {
        $subfolder = new MediaFolder(1, '2024', 10);
        $subfolder->setId(20);
        $this->assertEquals(10, $subfolder->getParentId());
    }

    public function testSetName(): void
    {
        $this->folder->setName('Updated Name');
        $this->assertEquals('Updated Name', $this->folder->getName());
    }

    public function testSetParentId(): void
    {
        $this->folder->setParentId(5);
        $this->assertEquals(5, $this->folder->getParentId());
        $this->folder->setParentId(null);
        $this->assertNull($this->folder->getParentId());
    }

    public function testSettersReturnSelf(): void
    {
        $result = $this->folder->setName('N')->setParentId(5);
        $this->assertSame($this->folder, $result);
    }
}