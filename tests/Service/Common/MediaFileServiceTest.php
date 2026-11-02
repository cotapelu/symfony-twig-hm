<?php declare(strict_types=1);

namespace App\Tests\Service\Common;

use App\Entity\Common\MediaFile;
use App\Service\Common\MediaFileService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MediaFileServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private MediaFileService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new MediaFileService($this->em, $this->logger);
    }

    public function testCreateMediaFileSuccess(): void
    {
        $filename = 'test.jpg';
        $mimeType = 'image/jpeg';
        $size = 102400;
        $url = 'https://example.com/uploads/test.jpg';
        $folderId = 'folder_123';
        $userId = 'user_123';
        $metadata = ['width' => 1920, 'height' => 1080];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(MediaFile::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (MediaFile $f) use (
                $filename, $mimeType, $size, $url, $folderId, $userId, $metadata
            ) {
                return $f->getFilename() === $filename
                    && $f->getMimeType() === $mimeType
                    && $f->getSize() === $size
                    && $f->getUrl() === $url
                    && $f->getFolderId() === $folderId
                    && $f->getUserId() === $userId
                    && $f->getMetadata() === $metadata;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Media file created', $this->anything());

        $result = $this->service->createMediaFile($filename, $mimeType, $size, $url, $folderId, $userId, $metadata);
        $this->assertInstanceOf(MediaFile::class, $result);
        $this->assertSame($filename, $result->getFilename());
        $this->assertSame($mimeType, $result->getMimeType());
        $this->assertSame($size, $result->getSize());
        $this->assertSame($url, $result->getUrl());
    }

    public function testCreateMediaFileMinimal(): void
    {
        $filename = 'doc.pdf';
        $mimeType = 'application/pdf';
        $size = 204800;
        $url = 'https://example.com/uploads/doc.pdf';

        $repo = $this->createMock(ObjectRepository::class);
        $this->em->method('getRepository')->willReturn($repo);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createMediaFile($filename, $mimeType, $size, $url);
        $this->assertInstanceOf(MediaFile::class, $result);
        $this->assertNull($result->getFolderId());
        $this->assertNull($result->getUserId());
        $this->assertNull($result->getMetadata());
    }

    public function testGetMediaFile(): void
    {
        $id = 'file_123';
        $file = new MediaFile($id, 'name', 'image/jpeg', 100, 'url');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($file);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(MediaFile::class)
            ->willReturn($repo);

        $result = $this->service->getMediaFile($id);
        $this->assertSame($file, $result);
    }

    public function testGetFilesByFolder(): void
    {
        $folderId = 'folder_123';
        $file1 = new MediaFile('id1', 'f1', 'image/jpeg', 100, 'url1');
        $file1->setFolderId($folderId);
        $file2 = new MediaFile('id2', 'f2', 'image/png', 200, 'url2');
        $file2->setFolderId($folderId);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['folderId' => $folderId])
            ->willReturn([$file1, $file2]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getFilesByFolder($folderId);
        $this->assertCount(2, $result);
    }

    public function testGetUserFiles(): void
    {
        $userId = 'user_123';
        $file = new MediaFile('id', 'name', 'mime', 100, 'url');
        $file->setUserId($userId);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn([$file]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getUserFiles($userId);
        $this->assertCount(1, $result);
    }

    public function testGetFilesByMimeType(): void
    {
        $mimeType = 'image/jpeg';
        $file = new MediaFile('id', 'name', $mimeType, 100, 'url');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['mimeType' => $mimeType])
            ->willReturn([$file]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getFilesByMimeType($mimeType);
        $this->assertCount(1, $result);
    }

    public function testGetImages(): void
    {
        $folderId = 'folder_123';
        $file = new MediaFile('id', 'name', 'image/jpeg', 100, 'url');

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('f.mimeType LIKE :imageType')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('imageType', 'image/%')
            ->willReturnSelf();
        if ($folderId !== null) {
            $qb->expects($this->once())
                ->method('andWhere')
                ->with('f.folderId = :folderId')
                ->willReturnSelf();
            $qb->expects($this->once())
                ->method('setParameter')
                ->with('folderId', $folderId)
                ->willReturnSelf();
        }
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$file]);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(MediaFile::class)
            ->willReturn($repo);

        $result = $this->service->getImages($folderId);
        $this->assertCount(1, $result);
    }

    public function testUpdateMediaFile(): void
    {
        $fileId = 'file_123';
        $file = new MediaFile($fileId, 'old.jpg', 'image/jpeg', 100, 'old_url');
        $newFilename = 'new.jpg';
        $newSize = 200;
        $newMetadata = ['new' => 'data'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($fileId)
            ->willReturn($file);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Media file updated', ['file_id' => $fileId]);

        $result = $this->service->updateMediaFile($fileId, $newFilename, $newSize, $newMetadata);
        $this->assertSame($file, $result);
        $this->assertSame($newFilename, $result->getFilename());
        $this->assertSame($newSize, $result->getSize());
        $this->assertSame($newMetadata, $result->getMetadata());
    }

    public function testUpdateMediaFileNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->updateMediaFile('nonexistent', 'new.jpg', 100, []);
        $this->assertNull($result);
    }

    public function testDeleteMediaFile(): void
    {
        $fileId = 'file_123';
        $file = new MediaFile($fileId, 'name', 'mime', 100, 'url');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($fileId)
            ->willReturn($file);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($file);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->deleteMediaFile($fileId);
        $this->assertTrue($result);
    }

    public function testDeleteMediaFileNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->deleteMediaFile('nonexistent');
        $this->assertFalse($result);
    }

    public function testMoveMediaFile(): void
    {
        $fileId = 'file_123';
        $newFolderId = 'folder_456';
        $file = new MediaFile($fileId, 'name', 'mime', 100, 'url');
        $file->setFolderId('old_folder');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($fileId)
            ->willReturn($file);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->moveMediaFile($fileId, $newFolderId);
        $this->assertSame($file, $result);
        $this->assertSame($newFolderId, $result->getFolderId());
    }

    public function testSearchByFilename(): void
    {
        $query = 'vacation';
        $file = new MediaFile('id', 'vacation_photo.jpg', 'image/jpeg', 100, 'url');

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('f.filename LIKE :query')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('query', '%' . $query . '%')
            ->willReturnSelf();
        $query_obj = $this->createMock(\Doctrine\ORM\Query::class);
        $query_obj->expects($this->once())
            ->method('getResult')
            ->willReturn([$file]);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query_obj);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(MediaFile::class)
            ->willReturn($repo);

        $result = $this->service->searchByFilename($query);
        $this->assertCount(1, $result);
    }

    public function testCountByUser(): void
    {
        $userId = 'user_123';
        $count = 5;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(f.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('f.userId = :userId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $count);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(MediaFile::class)
            ->willReturn($repo);

        $result = $this->service->countByUser($userId);
        $this->assertSame($count, $result);
    }
}
