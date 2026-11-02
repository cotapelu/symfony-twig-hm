<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\MediaFile;
use App\Repository\Common\MediaFileRepository;
use App\Service\Common\MediaFileService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class MediaFileServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private MediaFileRepository $fileRepo;
    private LoggerInterface $logger;
    private MediaFileService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->fileRepo = $this->createMock(MediaFileRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->method('getRepository')
            ->willReturnMap([
                [MediaFile::class, $this->fileRepo],
            ]);
    }

    public function testCreateMediaFile(): void
    {
        $filename = 'test.jpg';
        $mimeType = 'image/jpeg';
        $size = 1234;
        $url = 'https://example.com/test.jpg';
        $folderId = 'folder-1';
        $userId = 'user-1';
        $metadata = ['key' => 'val'];

        // Capture persisted entity
        $persisted = null;
        $this->fileRepo->expects($this->never())->method('find'); // not used
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (MediaFile $f) use (&$persisted, $filename, $mimeType, $size, $url, $folderId, $userId, $metadata) {
                $persisted = $f;
                return $persisted !== null
                    && $persisted->getFilename() === $filename
                    && $persisted->getMimeType() === $mimeType
                    && $persisted->getSize() === $size
                    && $persisted->getUrl() === $url
                    && $persisted->getFolderId() === $folderId
                    && $persisted->getUserId() === $userId
                    && $persisted->getMetadata() === $metadata;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Media file created', $this->arrayHasKey('file_id'));

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->createMediaFile($filename, $mimeType, $size, $url, $folderId, $userId, $metadata);

        $this->assertInstanceOf(MediaFile::class, $result);
        $this->assertSame($filename, $result->getFilename());
        $this->assertNotNull($result->getId());
    }

    public function testGetMediaFile(): void
    {
        $fileId = 'file-1';
        $file = $this->createMock(MediaFile::class);
        $this->fileRepo->expects($this->once())
            ->method('find')
            ->with($fileId)
            ->willReturn($file);

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->getMediaFile($fileId);
        $this->assertSame($file, $result);
    }

    public function testGetFilesByFolder(): void
    {
        $folderId = 'folder-2';
        $files = [$this->createMock(MediaFile::class), $this->createMock(MediaFile::class)];
        $this->fileRepo->expects($this->once())
            ->method('findBy')
            ->with(['folderId' => $folderId])
            ->willReturn($files);

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->getFilesByFolder($folderId);
        $this->assertSame($files, $result);
    }

    public function testGetUserFiles(): void
    {
        $userId = 'user-3';
        $files = [$this->createMock(MediaFile::class)];
        $this->fileRepo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($files);

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->getUserFiles($userId);
        $this->assertSame($files, $result);
    }

    public function testGetFilesByMimeType(): void
    {
        $mimeType = 'image/png';
        $files = [$this->createMock(MediaFile::class)];
        $this->fileRepo->expects($this->once())
            ->method('findBy')
            ->with(['mimeType' => $mimeType])
            ->willReturn($files);

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->getFilesByMimeType($mimeType);
        $this->assertSame($files, $result);
    }

    public function testGetImages(): void
    {
        $folderId = 'folder-4';
        $userId = 'user-4';
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())->method('where')->with('f.mimeType LIKE :imageType')->willReturnSelf();
        $qb->expects($this->exactly(3))
            ->method('setParameter')
            ->with($this->anything(), $this->anything(), $this->anything())
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('andWhere')
            ->with($this->anything())
            ->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn([$this->createMock(MediaFile::class)]);

        $this->fileRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($qb);

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->getImages($folderId, $userId);
        $this->assertCount(1, $result);
    }

    public function testGetImagesWithoutFilters(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())->method('where')->with('f.mimeType LIKE :imageType')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('imageType', 'image/%', null)->willReturnSelf();
        $qb->expects($this->never())->method('andWhere'); // no folderId or userId
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn([]);

        $this->fileRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($qb);

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->getImages();
        $this->assertSame([], $result);
    }

    public function testUpdateMediaFileSuccess(): void
    {
        $fileId = 'file-2';
        $newFilename = 'newname.jpg';
        $newMetadata = ['updated' => true];
        $file = $this->createMock(MediaFile::class);
        $file->expects($this->once())->method('setFilename')->with($newFilename);
        $file->expects($this->once())->method('setMetadata')->with($newMetadata);

        $this->fileRepo->expects($this->once())
            ->method('find')
            ->with($fileId)
            ->willReturn($file);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Media file updated', ['file_id' => $fileId]);

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->updateMediaFile($fileId, $newFilename, $newMetadata);
        $this->assertSame($file, $result);
    }

    public function testUpdateMediaFileNotFound(): void
    {
        $this->fileRepo->expects($this->once())
            ->method('find')
            ->with('missing')
            ->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->updateMediaFile('missing');
        $this->assertNull($result);
    }

    public function testDeleteMediaFileSuccess(): void
    {
        $fileId = 'file-3';
        $filename = 'delete.jpg';
        $file = $this->createMock(MediaFile::class);
        $file->method('getFilename')->willReturn($filename);

        $this->fileRepo->expects($this->once())
            ->method('find')
            ->with($fileId)
            ->willReturn($file);
        $this->em->expects($this->once())->method('remove')->with($file);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Media file deleted', $this->arrayHasKey('file_id'));

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->deleteMediaFile($fileId);
        $this->assertTrue($result);
    }

    public function testDeleteMediaFileNotFound(): void
    {
        $this->fileRepo->expects($this->once())
            ->method('find')
            ->with('missing')
            ->willReturn(null);
        $this->em->expects($this->never())->method('remove');

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->deleteMediaFile('missing');
        $this->assertFalse($result);
    }

    public function testDeleteFolderFiles(): void
    {
        $folderId = 'folder-delete';
        $files = [
            $this->createMock(MediaFile::class),
            $this->createMock(MediaFile::class),
        ];

        // getFilesByFolder calls repository findBy
        $this->fileRepo->expects($this->once())
            ->method('findBy')
            ->with(['folderId' => $folderId])
            ->willReturn($files);

        // remove each file (2 calls)
        $this->em->expects($this->exactly(2))
            ->method('remove')
            ->with($this->isInstanceOf(MediaFile::class));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Folder files deleted', $this->arrayHasKey('count'));

        $this->service = new MediaFileService($this->em, $this->logger);
        $count = $this->service->deleteFolderFiles($folderId);
        $this->assertSame(2, $count);
    }

    public function testGetUserTotalSize(): void
    {
        $userId = 'user-4';
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())->method('select')->with('SUM(f.size)')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('f.userId = :userId')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('userId', $userId, null)->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn('12345');

        $this->fileRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($qb);

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->getUserTotalSize($userId);
        $this->assertSame(12345, $result);
    }

    public function testGetUserTotalSizeNull(): void
    {
        $userId = 'user-empty';
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())->method('select')->with('SUM(f.size)')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('f.userId = :userId')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('userId', $userId, null)->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn(null);

        $this->fileRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($qb);

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->getUserTotalSize($userId);
        $this->assertSame(0, $result);
    }

    public function testCountUserFiles(): void
    {
        $userId = 'user-5';
        $this->fileRepo->expects($this->once())
            ->method('count')
            ->with(['userId' => $userId])
            ->willReturn(5);

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->countUserFiles($userId);
        $this->assertSame(5, $result);
    }

    public function testGetFilesByFolderWithNull(): void
    {
        $this->fileRepo->expects($this->once())
            ->method('findBy')
            ->with(['folderId' => null])
            ->willReturn([]);

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->getFilesByFolder(null);
        $this->assertSame([], $result);
    }

    public function testGetImagesOnlyOneFolder(): void
    {
        $folderId = 'folder-only';
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())->method('where')->with('f.mimeType LIKE :imageType')->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->with($this->anything(), $this->anything(), $this->anything())
            ->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with('f.folderId = :folderId')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn([]);

        $this->fileRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($qb);

        $this->service = new MediaFileService($this->em, $this->logger);
        $result = $this->service->getImages($folderId);
        $this->assertSame([], $result);
    }
}
