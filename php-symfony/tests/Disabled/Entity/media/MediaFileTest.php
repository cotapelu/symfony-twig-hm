<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\media;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Media\Entity\MediaFile;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MediaFile entity.
 * 
 * @covers \App\Media\Entity\MediaFile
 */
#[AllowMockObjectsWithoutExpectations]
class MediaFileTest extends TestCase
{
    private MediaFile $file;

    protected function setUp(): void
    {
        $this->file = new MediaFile(
            userId: 1,
            filename: 'image.jpg',
            originalName: 'vacation.jpg',
            mimeType: 'image/jpeg',
            size: 1024000,
            storageType: 'local',
            path: '/uploads/2024/01/image.jpg'
        );
        $this->file->setId(100);
    }

    public function testConstructor(): void
    {
        $this->assertEquals(1, $this->file->getUserId());
        $this->assertEquals('image.jpg', $this->file->getFilename());
        $this->assertEquals('vacation.jpg', $this->file->getOriginalName());
        $this->assertEquals('image/jpeg', $this->file->getMimeType());
        $this->assertEquals(1024000, $this->file->getSize());
        $this->assertEquals('local', $this->file->getStorageType());
        $this->assertEquals('/uploads/2024/01/image.jpg', $this->file->getPath());
        $this->assertNull($this->file->getThumbnailPath());
        $this->assertNull($this->file->getFolderId());
        $this->assertInstanceOf(\DateTime::class, $this->file->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $this->file->getUpdatedAt());
        $this->assertEquals(100, $this->file->getId());
    }

    public function testSetThumbnailPath(): void
    {
        $this->file->setThumbnailPath('/thumbs/image.jpg');
        $this->assertEquals('/thumbs/image.jpg', $this->file->getThumbnailPath());
        $this->file->setThumbnailPath(null);
        $this->assertNull($this->file->getThumbnailPath());
    }

    public function testSetFolderId(): void
    {
        $this->file->setFolderId(5);
        $this->assertEquals(5, $this->file->getFolderId());
        $this->file->setFolderId(null);
        $this->assertNull($this->file->getFolderId());
    }

    public function testSetPath(): void
    {
        $this->file->setPath('/new/path/file.jpg');
        $this->assertEquals('/new/path/file.jpg', $this->file->getPath());
    }

    public function testSettersReturnSelf(): void
    {
        $result = $this->file->setThumbnailPath('t.jpg')->setFolderId(1)->setPath('p.jpg');
        $this->assertSame($this->file, $result);
    }
}