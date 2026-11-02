<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\MediaFolder;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MediaFolderTest extends TestCase
{
    private function createFolder(
        string $id = 'folder-id',
        string $name = 'Folder'
    ): MediaFolder {
        return new MediaFolder($id, $name);
    }

    public function testConstructor(): void
    {
        $f = $this->createFolder();
        $this->assertSame('folder-id', $f->getId());
        $this->assertSame('Folder', $f->getName());
        $this->assertNull($f->getUserId());
        $this->assertNull($f->getParentId());
        $this->assertNull($f->getPath());
        $this->assertNull($f->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $f->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $f->getUpdatedAt());
    }

    public function testSetUserId(): void
    {
        $f = $this->createFolder();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setUserId('user-2');
        $this->assertSame('user-2', $f->getUserId());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
        $f->setUserId(null);
        $this->assertNull($f->getUserId());
    }

    public function testSetParentId(): void
    {
        $f = $this->createFolder();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setParentId('parent-2');
        $this->assertSame('parent-2', $f->getParentId());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
        $f->setParentId(null);
        $this->assertNull($f->getParentId());
    }

    public function testSetName(): void
    {
        $f = $this->createFolder();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setName('New Name');
        $this->assertSame('New Name', $f->getName());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
    }

    public function testSetPath(): void
    {
        $f = $this->createFolder();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setPath('/uploads/folder');
        $this->assertSame('/uploads/folder', $f->getPath());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
        $f->setPath(null);
        $this->assertNull($f->getPath());
    }

    public function testSetMetadata(): void
    {
        $f = $this->createFolder();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $meta = ['size' => 100, 'private' => true];
        $f->setMetadata($meta);
        $this->assertSame($meta, $f->getMetadata());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
        $f->setMetadata(null);
        $this->assertNull($f->getMetadata());
    }
}
