<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\MediaFolder;
use App\Entity\Common\MediaFile;
use App\Repository\Common\MediaFolderRepository;
use App\Repository\Common\MediaFileRepository;
use App\Service\Common\MediaFolderService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class MediaFolderServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private MediaFolderRepository $folderRepo;
    private MediaFileRepository $fileRepo;
    private LoggerInterface $logger;
    private MediaFolderService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->folderRepo = $this->createMock(MediaFolderRepository::class);
        $this->fileRepo = $this->createMock(MediaFileRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->method('getRepository')
            ->willReturnMap([
                [MediaFolder::class, $this->folderRepo],
                [MediaFile::class, $this->fileRepo],
            ]);
    }

    public function testCreateFolderWithoutParent(): void
    {
        $folderId = 'folder-1';
        $name = 'Root Folder';
        $userId = 'user-1';

        // Capture persisted folder
        $persisted = null;
        $this->folderRepo->expects($this->never())->method('find'); // no parent
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (MediaFolder $f) use (&$persisted, $folderId, $name, $userId) {
                $persisted = $f;
                return $persisted !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Folder created', $this->arrayHasKey('folder_id'));

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->createFolder($name, $userId);

        $this->assertInstanceOf(MediaFolder::class, $result);
        $this->assertSame($name, $result->getName());
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame(null, $result->getParentId());
        $this->assertSame('/', $result->getPath());
        $this->assertNotNull($result->getId());
    }

    public function testCreateFolderWithValidParent(): void
    {
        $parentId = 'parent-1';
        $parentName = 'Parent';
        $parentPath = '/Parent';
        $childName = 'Child';
        $userId = 'user-2';

        $parent = $this->createMock(MediaFolder::class);
        $parent->method('getId')->willReturn($parentId);
        $parent->method('getName')->willReturn($parentName);
        $parent->method('getPath')->willReturn($parentPath);

        $this->folderRepo->expects($this->any())
            ->method('find')
            ->willReturn($parent);

        $persisted = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (MediaFolder $f) use (&$persisted, $childName, $userId, $parentId, $parentPath, $parentName) {
                $persisted = $f;
                $expectedPath = rtrim($parentPath, '/') . '/' . $parentName;
                return $persisted !== null
                    && $persisted->getName() === $childName
                    && $persisted->getUserId() === $userId
                    && $persisted->getParentId() === $parentId
                    && $persisted->getPath() === $expectedPath;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Folder created', $this->arrayHasKey('folder_id'));

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->createFolder($childName, $userId, $parentId);

        $this->assertInstanceOf(MediaFolder::class, $result);
    }

    public function testCreateFolderWithInvalidParent(): void
    {
        $this->folderRepo->expects($this->once())
            ->method('find')
            ->with('missing')
            ->willReturn(null);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $this->service = new MediaFolderService($this->em, $this->logger);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parent folder not found');
        $this->service->createFolder('Child', null, 'missing');
    }

    public function testGetFolder(): void
    {
        $folder = $this->createMock(MediaFolder::class);
        $this->folderRepo->expects($this->once())
            ->method('find')
            ->with('folder-id')
            ->willReturn($folder);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->getFolder('folder-id');
        $this->assertSame($folder, $result);
    }

    public function testGetRootFoldersNoUser(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())->method('where')->with('f.parentId IS NULL')->willReturnSelf();
        $qb->expects($this->never())->method('andWhere');
        $qb->expects($this->never())->method('setParameter');
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn([$this->createMock(MediaFolder::class)]);

        $this->folderRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($qb);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->getRootFolders();
        $this->assertCount(1, $result);
    }

    public function testGetRootFoldersWithUser(): void
    {
        $userId = 'user-3';
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())->method('where')->with('f.parentId IS NULL')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with('f.userId = :userId')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('userId', $userId)->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn([]);

        $this->folderRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($qb);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->getRootFolders($userId);
        $this->assertSame([], $result);
    }

    public function testGetSubfolders(): void
    {
        $parentId = 'parent-2';
        $folders = [$this->createMock(MediaFolder::class), $this->createMock(MediaFolder::class)];
        $this->folderRepo->expects($this->once())
            ->method('findBy')
            ->with(['parentId' => $parentId])
            ->willReturn($folders);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->getSubfolders($parentId);
        $this->assertSame($folders, $result);
    }

    public function testGetUserFolders(): void
    {
        $userId = 'user-4';
        $folders = [$this->createMock(MediaFolder::class)];
        $this->folderRepo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($folders);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->getUserFolders($userId);
        $this->assertSame($folders, $result);
    }

    public function testGetFolderPathMultipleLevels(): void
    {
        $root = $this->createMock(MediaFolder::class);
        $root->method('getId')->willReturn('root-id');
        $root->method('getParentId')->willReturn(null);
        $root->method('getName')->willReturn('Root');

        $child = $this->createMock(MediaFolder::class);
        $child->method('getId')->willReturn('child-id');
        $child->method('getParentId')->willReturn('root-id');
        $child->method('getName')->willReturn('Child');

        $grand = $this->createMock(MediaFolder::class);
        $grand->method('getId')->willReturn('grand-id');
        $grand->method('getParentId')->willReturn('child-id');
        $grand->method('getName')->willReturn('Grand');

        // Map getFolder calls
        $this->folderRepo->expects($this->exactly(3))
            ->method('find')
            ->willReturnMap([
                ['grand-id', $grand],
                ['child-id', $child],
                ['root-id', $root],
            ]);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->getFolderPath('grand-id');

        $this->assertSame([$root, $child, $grand], $result);
    }

    public function testGetFolderPathMissing(): void
    {
        $this->folderRepo->expects($this->once())
            ->method('find')
            ->with('missing-id')
            ->willReturn(null);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->getFolderPath('missing-id');
        $this->assertSame([], $result);
    }

    public function testGetFolderDepth(): void
    {
        $folder = $this->createMock(MediaFolder::class);
        $folder->method('getId')->willReturn('f1');
        $folder->method('getParentId')->willReturnOnConsecutiveCalls('p1', 'p2', null);
        // Chain: f1->p1->p2->root

        $p1 = $this->createMock(MediaFolder::class);
        $p1->method('getId')->willReturn('p1');
        $p1->method('getParentId')->willReturn('p2');

        $p2 = $this->createMock(MediaFolder::class);
        $p2->method('getId')->willReturn('p2');
        $p2->method('getParentId')->willReturn(null);

        $this->folderRepo->expects($this->exactly(3))
            ->method('find')
            ->willReturnMap([
                ['f1', $folder],
                ['p1', $p1],
                ['p2', $p2],
            ]);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->getFolderDepth('f1');
        $this->assertSame(2, $result);
    }

    public function testIsFolderEmptyNoContents(): void
    {
        $folderId = 'folder-empty';
        $this->fileRepo->expects($this->once())
            ->method('count')
            ->with(['folderId' => $folderId])
            ->willReturn(0);
        $this->folderRepo->expects($this->once())
            ->method('count')
            ->with(['parentId' => $folderId])
            ->willReturn(0);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->isFolderEmpty($folderId);
        $this->assertTrue($result);
    }

    public function testIsFolderEmptyWithFiles(): void
    {
        $folderId = 'folder-files';
        $this->fileRepo->expects($this->once())->method('count')->willReturn(3);
        $this->folderRepo->expects($this->once())->method('count')->willReturn(0);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->isFolderEmpty($folderId);
        $this->assertFalse($result);
    }

    public function testIsFolderEmptyWithSubfolders(): void
    {
        $folderId = 'folder-sub';
        $this->fileRepo->expects($this->once())->method('count')->willReturn(0);
        $this->folderRepo->expects($this->once())->method('count')->willReturn(2);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->isFolderEmpty($folderId);
        $this->assertFalse($result);
    }

    public function testUpdateFolderNotFound(): void
    {
        $this->folderRepo->expects($this->once())
            ->method('find')
            ->with('missing')
            ->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->updateFolder('missing');
        $this->assertNull($result);
    }

    public function testUpdateFolderNameOnly(): void
    {
        $folderId = 'folder-1';
        $newName = 'New Name';
        $folder = $this->createMock(MediaFolder::class);
        $folder->expects($this->never())->method('setParentId');
        $folder->expects($this->once())->method('setName')->with($newName);

        $this->folderRepo->expects($this->once())->method('find')->with($folderId)->willReturn($folder);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Folder updated', ['folder_id' => $folderId]);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->updateFolder($folderId, $newName);
        $this->assertSame($folder, $result);
    }

    public function testUpdateFolderParentPreventsCircular(): void
    {
        $folderId = 'folder-A';
        $newParentId = 'folder-B'; // eventually descendant of A

        $folderA = $this->createMock(MediaFolder::class);
        $folderA->method('getId')->willReturn($folderId);
        $folderA->method('getParentId')->willReturn('some-other');

        $folderB = $this->createMock(MediaFolder::class);
        $folderB->method('getId')->willReturn($newParentId);
        $folderB->method('getParentId')->willReturn($folderId); // B's parent is A -> cycle

        $this->folderRepo->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [$folderId, $folderA],
                [$newParentId, $folderB],
            ]);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot move folder: would create circular reference');
        $this->service->updateFolder($folderId, parentId: $newParentId);
    }

    public function testUpdateFolderMetadata(): void
    {
        $folderId = 'folder-2';
        $newMetadata = ['key' => 'val'];
        $folder = $this->createMock(MediaFolder::class);
        $folder->expects($this->never())->method('setName');
        $folder->expects($this->never())->method('setParentId');
        $folder->expects($this->once())->method('setMetadata')->with($newMetadata);

        $this->folderRepo->expects($this->once())->method('find')->with($folderId)->willReturn($folder);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Folder updated', ['folder_id' => $folderId]);

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->updateFolder($folderId, metadata: $newMetadata);
        $this->assertSame($folder, $result);
    }

    public function testDeleteFolderNotFound(): void
    {
        $this->folderRepo->expects($this->once())
            ->method('find')
            ->with('missing')
            ->willReturn(null);
        $this->em->expects($this->never())->method('remove');

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->deleteFolder('missing');
        $this->assertFalse($result);
    }

    public function testDeleteFolderWithContentsDeleteRecursive(): void
    {
        $folderId = 'folder-to-delete';
        $folder = $this->createMock(MediaFolder::class);
        $folder->method('getId')->willReturn($folderId);

        $this->folderRepo->expects($this->once())
            ->method('find')
            ->with($folderId)
            ->willReturn($folder);

        // Subfolders to delete
        $sub1 = $this->createMock(MediaFolder::class);
        $sub1->method('getId')->willReturn('sub1');
        $sub2 = $this->createMock(MediaFolder::class);
        $sub2->method('getId')->willReturn('sub2');

        // File repository delete queries: called for each subfolder (2) + top folder (1) = 3 times
        $qbDeleteFiles = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $qbDeleteFiles->method('delete')->willReturnSelf();
        $qbDeleteFiles->method('where')->with('f.folderId = :folderId')->willReturnSelf();
        $qbDeleteFiles->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();
        $qbDeleteFiles->method('getQuery')->willReturn($query);
        $query->method('execute')->willReturn(1);

        $this->fileRepo->expects($this->exactly(3))
            ->method('createQueryBuilder')
            ->willReturn($qbDeleteFiles);

        // Folder repository findBy: return subfolders only for the top-level folder; deeper levels return empty
        $this->folderRepo->expects($this->any())
            ->method('findBy')
            ->willReturnCallback(function (array $criteria) use ($folderId, $sub1, $sub2) {
                $parentId = $criteria['parentId'] ?? null;
                if ($parentId === $folderId) {
                    return [$sub1, $sub2];
                }
                return [];
            });

        // No createQueryBuilder on folderRepo in this path
        $this->folderRepo->expects($this->never())->method('createQueryBuilder');

        // EntityManager should remove main folder and flush; subfolders removed within recursion
        $this->em->expects($this->atLeast(1))
            ->method('remove');
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Folder deleted', $this->arrayHasKey('folder_id'));

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->deleteFolder($folderId, true);
        $this->assertTrue($result);
    }

    public function testDeleteFolderMoveContents(): void
    {
        $folderId = 'folder-move';
        $parentId = 'parent-folder';
        $folder = $this->createMock(MediaFolder::class);
        $folder->method('getId')->willReturn($folderId);
        $folder->method('getParentId')->willReturn($parentId);

        $this->folderRepo->expects($this->once())->method('find')->with($folderId)->willReturn($folder);

        // Update queries
        $qbUpdateFiles = $this->createMock(QueryBuilder::class);
        $qbUpdateFiles->expects($this->once())->method('update')->willReturnSelf();
        $qbUpdateFiles->expects($this->once())->method('set')->with('f.folderId', ':parentId')->willReturnSelf();
        $qbUpdateFiles->expects($this->once())->method('where')->with('f.folderId = :folderId')->willReturnSelf();
        $qbUpdateFiles->expects($this->exactly(2))->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();
        $queryUpdateFiles = $this->createMock(Query::class);
        $qbUpdateFiles->expects($this->once())->method('getQuery')->willReturn($queryUpdateFiles);
        $queryUpdateFiles->expects($this->once())->method('execute')->willReturn(1);

        $qbUpdateFolders = $this->createMock(QueryBuilder::class);
        $qbUpdateFolders->expects($this->once())->method('update')->willReturnSelf();
        $qbUpdateFolders->expects($this->once())->method('set')->with('f.parentId', ':parentId')->willReturnSelf();
        $qbUpdateFolders->expects($this->once())->method('where')->with('f.parentId = :folderId')->willReturnSelf();
        $qbUpdateFolders->expects($this->exactly(2))->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();
        $queryUpdateFolders = $this->createMock(Query::class);
        $qbUpdateFolders->expects($this->once())->method('getQuery')->willReturn($queryUpdateFolders);
        $queryUpdateFolders->expects($this->once())->method('execute')->willReturn(0);

        $this->fileRepo->expects($this->once())->method('createQueryBuilder')->willReturn($qbUpdateFiles);
        $this->folderRepo->expects($this->once())->method('createQueryBuilder')->willReturn($qbUpdateFolders);

        $this->em->expects($this->once())->method('remove')->with($folder);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Folder deleted', $this->arrayHasKey('folder_id'));

        $this->service = new MediaFolderService($this->em, $this->logger);
        $result = $this->service->deleteFolder($folderId, false);
        $this->assertTrue($result);
    }

    public function testWouldCreateCyclePreventsSelf(): void
    {
        // Access private method via reflection
        $method = new \ReflectionMethod(MediaFolderService::class, 'wouldCreateCycle');
        $method->setAccessible(true);

        $folderA = $this->createMock(MediaFolder::class);
        $folderA->method('getParentId')->willReturn(null);

        $folderB = $this->createMock(MediaFolder::class);
        $folderB->method('getParentId')->willReturn('folder-A');

        $this->folderRepo->expects($this->any())
            ->method('find')
            ->willReturnMap([
                ['folder-A', $folderA],
                ['folder-B', $folderB],
            ]);

        $service = new MediaFolderService($this->em, $this->logger);
        $result = $method->invoke($service, 'folder-A', 'folder-B');
        $this->assertTrue($result);
    }

    public function testWouldCreateCycleAllowsSiblingMove(): void
    {
        $method = new \ReflectionMethod(MediaFolderService::class, 'wouldCreateCycle');
        $method->setAccessible(true);

        $parent = $this->createMock(MediaFolder::class);
        $parent->method('getParentId')->willReturn(null);

        $this->folderRepo->expects($this->any())
            ->method('find')
            ->willReturnMap([
                ['parent', $parent],
                ['child-1', $this->createMock(MediaFolder::class)],
            ]);

        $service = new MediaFolderService($this->em, $this->logger);
        // simulate child-1 moving under parent (parent not in child's chain)
        $result = $method->invoke($service, 'child-1', 'parent');
        $this->assertFalse($result);
    }

    public function testBuildPathForRoot(): void
    {
        // Use reflection to call private buildPath
        $method = new \ReflectionMethod(MediaFolderService::class, 'buildPath');
        $method->setAccessible(true);

        $this->folderRepo->expects($this->never())->method('find');

        $service = new MediaFolderService($this->em, $this->logger);
        $result = $method->invoke($service, null);
        $this->assertSame('/', $result);
    }

    public function testBuildPathWithParent(): void
    {
        $method = new \ReflectionMethod(MediaFolderService::class, 'buildPath');
        $method->setAccessible(true);

        $parent = $this->createMock(MediaFolder::class);
        $parent->method('getPath')->willReturn('/Root');
        $parent->method('getName')->willReturn('Parent');

        $this->folderRepo->expects($this->once())
            ->method('find')
            ->with('parent-id')
            ->willReturn($parent);

        $service = new MediaFolderService($this->em, $this->logger);
        $result = $method->invoke($service, 'parent-id');
        $this->assertSame('/Root/Parent', $result);
    }

    public function testBuildPathParentMissing(): void
    {
        $method = new \ReflectionMethod(MediaFolderService::class, 'buildPath');
        $method->setAccessible(true);

        $this->folderRepo->expects($this->once())
            ->method('find')
            ->with('missing')
            ->willReturn(null);

        $service = new MediaFolderService($this->em, $this->logger);
        $result = $method->invoke($service, 'missing');
        $this->assertSame('/', $result);
    }
}
