<?php declare(strict_types=1);

namespace App\Tests\Service\Media;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Media\Entity\MediaFile;
use App\Media\Entity\MediaFolder;
use App\Media\Repository\MediaFileRepository;
use App\Media\Repository\MediaFolderRepository;
use App\Media\Service\MediaService;
use Doctrine\DBAL\LockMode;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MediaService using in-memory repository implementations.
 * 
 * @covers \App\Media\Service\MediaService
 */
#[AllowMockObjectsWithoutExpectations]
class MediaServiceTest extends TestCase
{
    private MediaService $service;
    private InMemoryMediaFileRepository $fileRepo;
    private InMemoryMediaFolderRepository $folderRepo;

    protected function setUp(): void
    {
        $this->fileRepo = new InMemoryMediaFileRepository();
        $this->folderRepo = new InMemoryMediaFolderRepository();
        $this->service = new MediaService($this->fileRepo, $this->folderRepo);
    }

    // ========== File operations ==========
    public function testUploadFile(): void
    {
        $file = $this->service->uploadFile(1, 'img.jpg', 'original.jpg', 'image/jpeg', 1024, 'local', '/path/img.jpg');

        $this->assertInstanceOf(MediaFile::class, $file);
        $this->assertEquals(1, $file->getUserId());
        $this->assertEquals('img.jpg', $file->getFilename());
        $this->assertEquals('image/jpeg', $file->getMimeType());
        $this->assertEquals(1024, $file->getSize());
        $this->assertEquals('local', $file->getStorageType());
        $this->assertGreaterThan(0, $file->getId());
    }

    public function testGetFileFound(): void
    {
        $created = $this->service->uploadFile(1, 'f.jpg', 'o.jpg', 'image/jpeg', 100, 's3', '/p');
        $retrieved = $this->service->getFile($created->getId());
        $this->assertSame($created, $retrieved);
    }

    public function testGetFileNotFound(): void
    {
        $this->assertNull($this->service->getFile(999));
    }

    public function testGetUserFiles(): void
    {
        $this->service->uploadFile(1, 'f1.jpg', 'o.jpg', 'image/jpeg', 100, 's3', '/p1'); // user 1
        $this->service->uploadFile(1, 'f2.jpg', 'o.jpg', 'image/jpeg', 100, 's3', '/p2'); // user 1
        $this->service->uploadFile(2, 'f3.jpg', 'o.jpg', 'image/jpeg', 100, 's3', '/p3'); // user 2

        $user1Files = $this->service->getUserFiles(1);
        $this->assertCount(2, $user1Files);
        foreach ($user1Files as $f) {
            $this->assertEquals(1, $f->getUserId());
        }
    }

    public function testGetUserFilesByFolder(): void
    {
        $f1 = $this->service->uploadFile(1, 'f1.jpg', 'o.jpg', 'image/jpeg', 100, 's3', '/p1');
        $f2 = $this->service->uploadFile(1, 'f2.jpg', 'o.jpg', 'image/jpeg', 100, 's3', '/p2');
        $f1->setFolderId(10);
        $f2->setFolderId(20);

        $folder10 = $this->service->getUserFiles(1, 10);
        $this->assertCount(1, $folder10);
        $this->assertEquals($f1->getId(), $folder10[0]->getId());
    }

    public function testDeleteFile(): void
    {
        $file = $this->service->uploadFile(1, 'f.jpg', 'o.jpg', 'image/jpeg', 100, 's3', '/p');
        $this->assertTrue($this->service->deleteFile($file->getId()));
        $this->assertNull($this->service->getFile($file->getId()));
    }

    public function testDeleteFileNotFound(): void
    {
        $this->assertFalse($this->service->deleteFile(999));
    }

    public function testMoveFile(): void
    {
        $file = $this->service->uploadFile(1, 'f.jpg', 'o.jpg', 'image/jpeg', 100, 's3', '/p');
        $moved = $this->service->moveFile($file->getId(), 10);
        $this->assertNotNull($moved);
        $this->assertEquals(10, $moved->getFolderId());
    }

    public function testMoveFileToRoot(): void
    {
        $file = $this->service->uploadFile(1, 'f.jpg', 'o.jpg', 'image/jpeg', 100, 's3', '/p');
        $file->setFolderId(10);
        $moved = $this->service->moveFile($file->getId(), null);
        $this->assertNull($moved->getFolderId());
    }

    // ========== Folder operations ==========
    public function testCreateFolder(): void
    {
        $folder = $this->service->createFolder(1, 'Photos', null);

        $this->assertInstanceOf(MediaFolder::class, $folder);
        $this->assertEquals(1, $folder->getUserId());
        $this->assertEquals('Photos', $folder->getName());
        $this->assertNull($folder->getParentId());
        $this->assertGreaterThan(0, $folder->getId());
    }

    public function testCreateSubfolder(): void
    {
        $parent = $this->service->createFolder(1, 'Parent', null);
        $child = $this->service->createFolder(1, 'Child', $parent->getId());

        $this->assertEquals($parent->getId(), $child->getParentId());
    }

    public function testGetFolderFound(): void
    {
        $created = $this->service->createFolder(1, 'F', null);
        $retrieved = $this->service->getFolder($created->getId());
        $this->assertSame($created, $retrieved);
    }

    public function testGetFolderNotFound(): void
    {
        $this->assertNull($this->service->getFolder(999));
    }

    public function testGetUserFolders(): void
    {
        $this->service->createFolder(1, 'F1', null);
        $this->service->createFolder(1, 'F2', null);
        $this->service->createFolder(2, 'F3', null);

        $user1 = $this->service->getUserFolders(1);
        $this->assertCount(2, $user1);
    }

    public function testGetUserSubfolders(): void
    {
        $p1 = $this->service->createFolder(1, 'P1', null);
        $this->service->createFolder(1, 'C1', $p1->getId());
        $this->service->createFolder(1, 'C2', $p1->getId());
        $p2 = $this->service->createFolder(2, 'P2', null);
        $this->service->createFolder(2, 'C3', $p2->getId());

        $subs = $this->service->getUserFolders(1, $p1->getId());
        $this->assertCount(2, $subs);
    }

    public function testDeleteFolder(): void
    {
        $folder = $this->service->createFolder(1, 'F', null);
        $this->assertTrue($this->service->deleteFolder($folder->getId()));
        $this->assertNull($this->service->getFolder($folder->getId()));
    }

    public function testDeleteFolderNotFound(): void
    {
        $this->assertFalse($this->service->deleteFolder(999));
    }

    // ========== Integration tests ==========
    public function testFullWorkflow(): void
    {
        // Create folders
        $photos = $this->service->createFolder(1, 'Photos', null);
        $vacation = $this->service->createFolder(1, 'Vacation', $photos->getId());

        // Upload files
        $f1 = $this->service->uploadFile(1, 'img1.jpg', 'orig.jpg', 'image/jpeg', 100, 's3', '/p1');
        $f1->setFolderId($vacation->getId());
        $f2 = $this->service->uploadFile(1, 'img2.jpg', 'orig.jpg', 'image/jpeg', 100, 's3', '/p2');
        $f2->setFolderId($vacation->getId());

        // Get files in vacation folder
        $vacationFiles = $this->service->getUserFiles(1, $vacation->getId());
        $this->assertCount(2, $vacationFiles);

        // Move file to photos
        $this->service->moveFile($f1->getId(), $photos->getId());
        $this->assertEquals($photos->getId(), $this->service->getFile($f1->getId())->getFolderId());

        // Delete vacation folder (files remain - folderId is just a reference)
        $this->service->deleteFolder($vacation->getId());
        $this->assertNull($this->service->getFolder($vacation->getId()));

        // Files still exist - folder deletion does not cascade delete files
        $remaining = $this->service->getUserFiles(1, $vacation->getId());
        $this->assertCount(1, $remaining); // f2 still has folderId=$vacation->getId()
    }
}

// ========== In-Memory Repository Implementations ==========

class InMemoryMediaFileRepository extends MediaFileRepository
{
    /** @var array<int, MediaFile> */
    private array $files = [];
    private int $nextId = 1;

    public function __construct()
    {
        // Skip parent constructor (requires ManagerRegistry)
    }

    public function save(MediaFile $file): void
    {
        if ($file->getId() === null) {
            $file->setId($this->nextId++);
        }
        $this->files[$file->getId()] = $file;
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
    {
        return $this->files[$id] ?? null;
    }

    public function delete(MediaFile $file): void
    {
        if ($file->getId() !== null) {
            unset($this->files[$file->getId()]);
        }
    }

    public function findByUser(int $userId, ?int $folderId = null, int $limit = 50, int $offset = 0): array
    {
        $results = array_values(array_filter($this->files, function (MediaFile $f) use ($userId, $folderId): bool {
            if ($f->getUserId() !== $userId) {
                return false;
            }
            if ($folderId !== null && $f->getFolderId() !== $folderId) {
                return false;
            }
            return true;
        }));

        // Simple offset/limit slicing
        $results = array_slice($results, $offset, $limit);

        return $results;
    }
}

class InMemoryMediaFolderRepository extends MediaFolderRepository
{
    /** @var array<int, MediaFolder> */
    private array $folders = [];
    private int $nextId = 1;

    public function __construct()
    {
        // Skip parent constructor (requires ManagerRegistry)
    }

    public function save(MediaFolder $folder): void
    {
        if ($folder->getId() === null) {
            $folder->setId($this->nextId++);
        }
        $this->folders[$folder->getId()] = $folder;
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
    {
        return $this->folders[$id] ?? null;
    }

    public function delete(MediaFolder $folder): void
    {
        if ($folder->getId() !== null) {
            unset($this->folders[$folder->getId()]);
        }
    }

    public function findByUser(int $userId, ?int $parentId = null): array
    {
        return array_values(array_filter($this->folders, function (MediaFolder $f) use ($userId, $parentId): bool {
            if ($f->getUserId() !== $userId) {
                return false;
            }
            if ($parentId !== null && $f->getParentId() !== $parentId) {
                return false;
            }
            return true;
        }));
    }
}
