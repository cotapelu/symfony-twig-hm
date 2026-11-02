<?php declare(strict_types=1);

namespace App\Tests\Service\Common;

use App\Entity\Common\MediaFolder;
use App\Service\Common\MediaFolderService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MediaFolderServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private MediaFolderService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new MediaFolderService($this->em, $this->logger);
    }

    public function testCreateFolderRoot(): void
    {
        $name = 'Root Folder';
        $userId = 'user_123';
        $metadata = ['color' => 'blue'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(MediaFolder::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (MediaFolder $f) use ($name, $userId, $metadata) {
                return $f->getName() === $name
                    && $f->getUserId() === $userId
                    && $f->getParentId() === null
                    && $f->getPath() === '/'
                    && $f->getMetadata() === $metadata;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Folder created', $this->anything());

        $result = $this->service->createFolder($name, $userId, null, $metadata);
        $this->assertInstanceOf(MediaFolder::class, $result);
        $this->assertSame($name, $result->getName());
        $this->assertNull($result->getParentId());
        $this->assertSame('/', $result->getPath());
    }

    public function testCreateFolderWithParent(): void
    {
        $parentId = 'parent_123';
        $parent = new MediaFolder($parentId, 'Parent');
        $parent->setPath('/parent');

        $name = 'Sub Folder';
        $userId = 'user_123';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($parentId)
            ->willReturn($parent);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(MediaFolder::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (MediaFolder $f) use ($name, $parentId) {
                return $f->getName() === $name
                    && $f->getParentId() === $parentId
                    && $f->getPath() === '/parent/';
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->createFolder($name, $userId, $parentId);
        $this->assertInstanceOf(MediaFolder::class, $result);
        $this->assertSame($name, $result->getName());
        $this->assertSame($parentId, $result->getParentId());
        $this->assertSame('/parent/', $result->getPath());
    }

    public function testCreateFolderInvalidParent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parent folder not found');
        $this->service->createFolder('Sub', 'user', 'nonexistent');
    }

    public function testGetFolder(): void
    {
        $id = 'folder_123';
        $folder = new MediaFolder($id, 'Folder');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($folder);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(MediaFolder::class)
            ->willReturn($repo);

        $result = $this->service->getFolder($id);
        $this->assertSame($folder, $result);
    }

    public function testGetRootFolders(): void
    {
        $folder1 = new MediaFolder('id1', 'Folder1');
        $folder1->setParentId(null);
        $folder2 = new MediaFolder('id2', 'Folder2');
        $folder2->setParentId(null);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('f.parentId IS NULL')
            ->willReturnSelf();
        $qb->expects($this->never())
            ->method('andWhere');
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$folder1, $folder2]);
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
            ->with(MediaFolder::class)
            ->willReturn($repo);

        $result = $this->service->getRootFolders();
        $this->assertCount(2, $result);
    }

    public function testGetRootFoldersWithUser(): void
    {
        $userId = 'user_123';
        $folder = new MediaFolder('id', 'Folder');

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('f.parentId IS NULL')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('f.userId = :userId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$folder]);
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
            ->willReturn($repo);

        $result = $this->service->getRootFolders($userId);
        $this->assertCount(1, $result);
    }

    public function testGetSubfolders(): void
    {
        $parentId = 'parent_123';
        $sub1 = new MediaFolder('id1', 'Sub1');
        $sub1->setParentId($parentId);
        $sub2 = new MediaFolder('id2', 'Sub2');
        $sub2->setParentId($parentId);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['parentId' => $parentId])
            ->willReturn([$sub1, $sub2]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getSubfolders($parentId);
        $this->assertCount(2, $result);
    }

    public function testGetUserFolders(): void
    {
        $userId = 'user_123';
        $folder = new MediaFolder('id', 'Folder');
        $folder->setUserId($userId);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn([$folder]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getUserFolders($userId);
        $this->assertCount(1, $result);
    }

    public function testRenameFolder(): void
    {
        $folderId = 'folder_123';
        $folder = new MediaFolder($folderId, 'Old Name');
        $newName = 'New Name';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($folderId)
            ->willReturn($folder);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Folder renamed', $this->anything());

        $result = $this->service->renameFolder($folderId, $newName);
        $this->assertSame($folder, $result);
        $this->assertSame($newName, $result->getName());
    }

    public function testRenameFolderNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->renameFolder('nonexistent', 'New');
        $this->assertNull($result);
    }

    public function testDeleteFolder(): void
    {
        $folderId = 'folder_123';
        $folder = new MediaFolder($folderId, 'Folder');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($folderId)
            ->willReturn($folder);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($folder);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->deleteFolder($folderId);
        $this->assertTrue($result);
    }

    public function testDeleteFolderNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->deleteFolder('nonexistent');
        $this->assertFalse($result);
    }

    public function testCountByUser(): void
    {
        $userId = 'user_123';
        $count = 3;

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
            ->with(MediaFolder::class)
            ->willReturn($repo);

        $result = $this->service->countByUser($userId);
        $this->assertSame($count, $result);
    }

    public function testGetFullPathRoot(): void
    {
        // Test private method via reflection: buildPath for root returns '/'
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildPath');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, null);
        $this->assertSame('/', $result);
    }

    public function testGetFullPathNested(): void
    {
        // Simulate: parent path = '/a/b/'
        $parent = new MediaFolder('parent', 'Parent');
        $reflectionParent = new \ReflectionClass(MediaFolder::class);
        $pathProp = $reflectionParent->getProperty('path');
        $pathProp->setAccessible(true);
        $pathProp->set($parent, '/a/b/');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildPath');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $parent->getId());
        $this->assertSame('/a/b/', $result);
    }
}
