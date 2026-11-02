<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\MediaFile;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MediaFileTest extends TestCase
{
    private function createFile(
        string $id = 'file-id',
        string $filename = 'file.txt',
        string $mimeType = 'text/plain',
        int $size = 123,
        string $url = '/files/file.txt'
    ): MediaFile {
        return new MediaFile($id, $filename, $mimeType, $size, $url);
    }

    public function testConstructor(): void
    {
        $f = $this->createFile();
        $this->assertSame('file-id', $f->getId());
        $this->assertNull($f->getFolderId());
        $this->assertNull($f->getUserId());
        $this->assertSame('file.txt', $f->getFilename());
        $this->assertSame('text/plain', $f->getMimeType());
        $this->assertSame(123, $f->getSize());
        $this->assertSame('/files/file.txt', $f->getUrl());
        $this->assertNull($f->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $f->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $f->getUpdatedAt());
    }

    public function testSetFolderId(): void
    {
        $f = $this->createFile();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setFolderId('folder-1');
        $this->assertSame('folder-1', $f->getFolderId());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
        $f->setFolderId(null);
        $this->assertNull($f->getFolderId());
    }

    public function testSetUserId(): void
    {
        $f = $this->createFile();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setUserId('user-1');
        $this->assertSame('user-1', $f->getUserId());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
        $f->setUserId(null);
        $this->assertNull($f->getUserId());
    }

    public function testSetFilename(): void
    {
        $f = $this->createFile();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setFilename('newfile.txt');
        $this->assertSame('newfile.txt', $f->getFilename());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
    }

    public function testSetMimeType(): void
    {
        $f = $this->createFile();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setMimeType('image/png');
        $this->assertSame('image/png', $f->getMimeType());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
    }

    public function testSetSize(): void
    {
        $f = $this->createFile();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setSize(2048);
        $this->assertSame(2048, $f->getSize());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
    }

    public function testSetUrl(): void
    {
        $f = $this->createFile();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setUrl('/new/path/file.jpg');
        $this->assertSame('/new/path/file.jpg', $f->getUrl());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
    }

    public function testSetMetadata(): void
    {
        $f = $this->createFile();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $meta = ['width' => 800, 'height' => 600, 'exif' => ['GPS' => '...']];
        $f->setMetadata($meta);
        $this->assertSame($meta, $f->getMetadata());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
        $f->setMetadata(null);
        $this->assertNull($f->getMetadata());
    }
}
