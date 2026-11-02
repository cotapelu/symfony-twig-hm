<?php declare(strict_types=1);

namespace App\Tests\Unit\Media\Entity;

use App\Media\Entity\MediaFile;
use PHPUnit\Framework\TestCase;

class MediaFileTest extends TestCase
{
    private MediaFile $mediaFile;

    protected function setUp(): void
    {
        $this->mediaFile = new MediaFile();
    }

    public function testConstructorSetsCreatedAt(): void
    {
        $this->assertInstanceOf(\DateTime::class, $this->mediaFile->getCreatedAt());
    }

    public function testSetAndGetOriginalName(): void
    {
        $result = $this->mediaFile->setOriginalName('test-image.jpg');
        $this->assertInstanceOf(MediaFile::class, $result);
        $this->assertEquals('test-image.jpg', $this->mediaFile->getOriginalName());
    }

    public function testSetAndGetFileName(): void
    {
        $result = $this->mediaFile->setFileName('unique_filename.jpg');
        $this->assertInstanceOf(MediaFile::class, $result);
        $this->assertEquals('unique_filename.jpg', $this->mediaFile->getFileName());
    }

    public function testSetAndGetMimeType(): void
    {
        $result = $this->mediaFile->setMimeType('image/jpeg');
        $this->assertInstanceOf(MediaFile::class, $result);
        $this->assertEquals('image/jpeg', $this->mediaFile->getMimeType());
    }

    public function testSetAndGetFileSize(): void
    {
        $result = $this->mediaFile->setFileSize(1024000);
        $this->assertInstanceOf(MediaFile::class, $result);
        $this->assertEquals(1024000, $this->mediaFile->getFileSize());
    }

    public function testSetAndGetFileType(): void
    {
        $result = $this->mediaFile->setFileType('image');
        $this->assertInstanceOf(MediaFile::class, $result);
        $this->assertEquals('image', $this->mediaFile->getFileType());
    }

    public function testSetAndGetOwnerId(): void
    {
        $result = $this->mediaFile->setOwnerId('user-123');
        $this->assertInstanceOf(MediaFile::class, $result);
        $this->assertEquals('user-123', $this->mediaFile->getOwnerId());
    }

    public function testGetIdReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->mediaFile->getId());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $dateTime = new \DateTime('2026-02-25 10:00:00');
        $result = $this->mediaFile->setUpdatedAt($dateTime);
        $this->assertInstanceOf(MediaFile::class, $result);
        $this->assertEquals($dateTime, $this->mediaFile->getUpdatedAt());
    }

    public function testUpdatedAtCanBeNull(): void
    {
        $this->assertNull($this->mediaFile->getUpdatedAt());
    }

    public function testFluentInterfaceChaining(): void
    {
        $result = $this->mediaFile
            ->setOriginalName('test.jpg')
            ->setFileName('unique.jpg')
            ->setMimeType('image/jpeg')
            ->setFileSize(5000)
            ->setFileType('image')
            ->setOwnerId('user-1');

        $this->assertInstanceOf(MediaFile::class, $result);
        $this->assertEquals('test.jpg', $this->mediaFile->getOriginalName());
        $this->assertEquals('unique.jpg', $this->mediaFile->getFileName());
        $this->assertEquals('image/jpeg', $this->mediaFile->getMimeType());
        $this->assertEquals(5000, $this->mediaFile->getFileSize());
        $this->assertEquals('image', $this->mediaFile->getFileType());
        $this->assertEquals('user-1', $this->mediaFile->getOwnerId());
    }
}
