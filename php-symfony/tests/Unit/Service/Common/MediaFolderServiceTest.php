<?php declare(strict_types=1);

namespace App\Tests\Unit\Service\Common;

use App\Entity\Common\MediaFolder;
use App\Entity\Common\MediaFile;
use App\Service\Common\MediaFolderService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MediaFolderServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $emMock;
    private EntityRepository&MockObject $folderRepoMock;
    private EntityRepository&MockObject $fileRepoMock;
    private QueryBuilder&MockObject $folderQbMock;
    private QueryBuilder&MockObject $fileQbMock;
    private Query&MockObject $queryMock;
    private LoggerInterface&MockObject $loggerMock;
    private MediaFolderService $service;

    protected function setUp(): void
    {
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->folderRepoMock = $this->createMock(EntityRepository::class);
        $this->fileRepoMock = $this->createMock(EntityRepository::class);
        $this->folderQbMock = $this->createMock(QueryBuilder::class);
        $this->fileQbMock = $this->createMock(QueryBuilder::class);
        $this->queryMock = $this->createMock(Query::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->emMock->method('getRepository')
            ->willReturnCallback(function($class) {
                return $class === MediaFolder::class || $class === 'App\\Entity\\Common\\MediaFolder'
                    ? $this->folderRepoMock
                    : $this->fileRepoMock;
            });

        $this->service = new MediaFolderService($this->emMock, $this->loggerMock);
    }

    private function createFolder(array $data = []): MediaFolder
    {
        $id = $data['id'] ?? $this->randomId();
        $name = $data['name'] ?? 'Folder';
        $userId = $data['userId'] ?? null;
        $parentId = $data['parentId'] ?? null;
        $path = $data['path'] ?? ($parentId ? '/parent/'.$name : '/'.$name);
        $folder = new MediaFolder($id, $name);
        $ref = new \ReflectionClass($folder);
        $prop = $ref->getProperty('userId');
        $prop->setAccessible(true);
        $prop->setValue($folder, $userId);
        $prop = $ref->getProperty('parentId');
        $prop->setAccessible(true);
        $prop->setValue($folder, $parentId);
        $prop = $ref->getProperty('path');
        $prop->setAccessible(true);
        $prop->setValue($folder, $path);
        // Also set updatedAt via reflection if needed? Not used in tests.
        return $folder;
    }

    private function randomId(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', random_int(0,0xffff), random_int(0,0xffff), random_int(0,0xffff), random_int(0,0x0fff)|0x4000, random_int(0,0x3fff)|0x8000, random_int(0,0xffff), random_int(0,0xffff), random_int(0,0xffff));
    }

    // ==================== createFolder ====================

    public function testCreateFolder_Success(): void
    {
        $name = 'MyFolder';
        $userId = 'user_123';
        $parentId = 'parent_456';
        $metadata = ['key' => 'value'];

        $parentFolder = $this->createFolder(['id'=>$parentId, 'name'=>'Parent']);
        // getFolder($parentId) called twice (validation + buildPath)
        $this->folderRepoMock->method('find')->willReturnCallback(function($id) use ($parentFolder, $parentId) {
            return $id === $parentId ? $parentFolder : null;
        });

        $this->emMock->expects($this->once())->method('persist')->with($this->callback(function(MediaFolder $f) use ($name,$userId,$parentId,$metadata) {
            return $f->getName() === $name && $f->getUserId() === $userId && $f->getParentId() === $parentId && $f->getMetadata() === $metadata;
        }));
        $this->emMock->expects($this->once())->method('flush');

        $this->loggerMock->expects($this->once())->method('info')->with('Folder created', $this->arrayHasKey('folder_id'));

        $result = $this->service->createFolder($name, $userId, $parentId, $metadata);
        $this->assertInstanceOf(MediaFolder::class, $result);
        $this->assertSame($name, $result->getName());
    }

    public function testCreateFolder_WithNullParent(): void
    {
        $this->folderRepoMock->expects($this->never())->method('find');
        $this->emMock->expects($this->once())->method('persist');
        $this->emMock->expects($this->once())->method('flush');
        $result = $this->service->createFolder('RootFolder', 'user_1', null);
        $this->assertInstanceOf(MediaFolder::class, $result);
        $this->assertNull($result->getParentId());
    }

    public function testCreateFolder_ThrowsWhenParentNotFound(): void
    {
        $this->folderRepoMock->method('find')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parent folder not found');
        $this->service->createFolder('Child', 'user_1', 'unknown');
    }

    // ==================== getFolder ====================

    public function testGetFolder_Found(): void
    {
        $folder = $this->createFolder(['id'=>'folder_1']);
        $this->folderRepoMock->expects($this->once())->method('find')->with('folder_1')->willReturn($folder);
        $result = $this->service->getFolder('folder_1');
        $this->assertSame($folder, $result);
    }

    public function testGetFolder_NotFound(): void
    {
        $this->folderRepoMock->expects($this->once())->method('find')->with('unknown')->willReturn(null);
        $result = $this->service->getFolder('unknown');
        $this->assertNull($result);
    }

    // ==================== getRootFolders ====================

    public function testGetRootFolders_WithoutUser(): void
    {
        $folders = [$this->createFolder(['parentId'=>null]), $this->createFolder(['parentId'=>null])];
        $this->folderQbMock->method('where')->with('f.parentId IS NULL')->willReturnSelf();
        $this->folderQbMock->expects($this->never())->method('andWhere');
        $this->folderQbMock->method('getQuery')->willReturn($this->queryMock);
        $this->queryMock->method('getResult')->willReturn($folders);
        $this->folderRepoMock->method('createQueryBuilder')->with('f')->willReturn($this->folderQbMock);

        $result = $this->service->getRootFolders(null);
        $this->assertCount(2, $result);
    }

    public function testGetRootFolders_WithUser(): void
    {
        $userId = 'user_123';
        $folders = [$this->createFolder(['userId'=>$userId, 'parentId'=>null])];
        $this->folderQbMock->method('where')->with('f.parentId IS NULL')->willReturnSelf();
        $this->folderQbMock->method('andWhere')->with('f.userId = :userId')->willReturnSelf();
        $this->folderQbMock->method('setParameter')->with('userId', $userId)->willReturnSelf();
        $this->folderQbMock->method('getQuery')->willReturn($this->queryMock);
        $this->queryMock->method('getResult')->willReturn($folders);
        $this->folderRepoMock->method('createQueryBuilder')->with('f')->willReturn($this->folderQbMock);

        $result = $this->service->getRootFolders($userId);
        $this->assertCount(1, $result);
    }

    // ==================== getSubfolders ====================

    public function testGetSubfolders(): void
    {
        $parentId = 'parent_1';
        $subfolders = [$this->createFolder(['parentId'=>$parentId])];
        $this->folderRepoMock->expects($this->once())->method('findBy')->with(['parentId'=>$parentId])->willReturn($subfolders);
        $result = $this->service->getSubfolders($parentId);
        $this->assertCount(1, $result);
    }

    // ==================== getUserFolders ====================

    public function testGetUserFolders(): void
    {
        $userId = 'user_1';
        $folders = [$this->createFolder(['userId'=>$userId])];
        $this->folderRepoMock->expects($this->once())->method('findBy')->with(['userId'=>$userId])->willReturn($folders);
        $result = $this->service->getUserFolders($userId);
        $this->assertCount(1, $result);
    }

    // ==================== getFolderPath ====================

    public function testGetFolderPath_Root(): void
    {
        $root = $this->createFolder(['id'=>'root','parentId'=>null, 'name'=>'Root', 'path'=>'/']);
        $this->folderRepoMock->method('find')->willReturnCallback(fn($id) => $id === 'root' ? $root : null);
        $path = $this->service->getFolderPath('root');
        $this->assertCount(1, $path);
        $this->assertSame($root, $path[0]);
    }

    public function testGetFolderPath_Nested(): void
    {
        $root = $this->createFolder(['id'=>'1','parentId'=>null, 'name'=>'Root', 'path'=>'/']);
        $child = $this->createFolder(['id'=>'2','parentId'=>'1', 'name'=>'Child', 'path'=>'/Root']);
        $grand = $this->createFolder(['id'=>'3','parentId'=>'2', 'name'=>'Grand', 'path'=>'/Root/Child']);
        $this->folderRepoMock->method('find')->willReturnCallback(function($id) use ($root,$child,$grand) {
            return match($id) { '1'=>$root, '2'=>$child, '3'=>$grand, default=>null };
        });
        $path = $this->service->getFolderPath('3');
        $this->assertCount(3, $path);
        $this->assertSame($root, $path[0]);
        $this->assertSame($child, $path[1]);
        $this->assertSame($grand, $path[2]);
    }

    public function testGetFolderPath_BreaksOnMissing(): void
    {
        $root = $this->createFolder(['id'=>'1','parentId'=>null, 'name'=>'Root']);
        $this->folderRepoMock->method('find')->willReturnCallback(fn($id) => $id === '1' ? $root : null);
        $path = $this->service->getFolderPath('2');
        $this->assertEmpty($path);
    }

    // ==================== updateFolder ====================

    public function testUpdateFolder_NotFound(): void
    {
        $this->folderRepoMock->method('find')->willReturnCallback(fn($id) => $id === 'unknown' ? null : null);
        $result = $this->service->updateFolder('unknown', 'NewName');
        $this->assertNull($result);
    }

    public function testUpdateFolder_ChangeName(): void
    {
        $folder = $this->createFolder(['id'=>'f1','name'=>'Old']);
        $this->folderRepoMock->method('find')->willReturnCallback(fn($id) => $id === 'f1' ? $folder : null);
        $this->emMock->expects($this->once())->method('flush');
        $this->loggerMock->expects($this->once())->method('info')->with('Folder updated', ['folder_id'=>'f1']);
        $result = $this->service->updateFolder('f1', 'NewName');
        $this->assertSame($folder, $result);
        $this->assertSame('NewName', $folder->getName());
    }

    public function testUpdateFolder_ChangeParent_CyclePrevented(): void
    {
        // Setup: parent (ancestor) has child (descendant)
        $parent = $this->createFolder(['id'=>'parent','parentId'=>null, 'name'=>'Parent', 'path'=>'/Parent']);
        $child = $this->createFolder(['id'=>'child','parentId'=>'parent', 'name'=>'Child', 'path'=>'/Parent']);
        // find returns based on ID
        $this->folderRepoMock->method('find')->willReturnCallback(function($id) use ($parent, $child) {
            return match($id) { 'parent' => $parent, 'child' => $child, default => null };
        });

        // Attempt to move parent under its child => cycle
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot move folder: would create circular reference');
        $this->service->updateFolder('parent', null, 'child');
    }

    public function testUpdateFolder_ChangeParent_Success(): void
    {
        $folder = $this->createFolder(['id'=>'f1','parentId'=>null, 'name'=>'Folder', 'path'=>'/']);
        $newParent = $this->createFolder(['id'=>'p1','parentId'=>null, 'name'=>'Other', 'path'=>'/Other']);
        $this->folderRepoMock->method('find')->willReturnCallback(fn($id) => match($id){'f1'=>$folder,'p1'=>$newParent, default=>null});
        $this->emMock->expects($this->once())->method('flush');
        $this->loggerMock->expects($this->once())->method('info')->with('Folder updated', ['folder_id'=>'f1']);

        $result = $this->service->updateFolder('f1', null, 'p1');
        $this->assertSame($folder, $result);
        $this->assertSame('p1', $folder->getParentId());
        // buildPath('p1') = rtrim($newParent->getPath(),'/') . '/' . $newParent->getName() = '/Other/Other'
        $this->assertSame('/Other/Other', $folder->getPath());
    }

    // ==================== deleteFolder ====================

    public function testDeleteFolder_NotFound(): void
    {
        $this->folderRepoMock->method('find')->willReturn(null);
        $result = $this->service->deleteFolder('unknown');
        $this->assertFalse($result);
    }

    public function testDeleteFolder_WithDeleteContents(): void
    {
        $folder = $this->createFolder(['id'=>'f1']);
        $this->folderRepoMock->method('find')->willReturnCallback(fn($id) => $id === 'f1' ? $folder : null);

        // Files deletion: fileRepo->createQueryBuilder() once, leading to delete query execute
        $fileDeleteQb = $this->createMock(QueryBuilder::class);
        $fileDeleteQuery = $this->createMock(Query::class);
        $this->fileRepoMock->expects($this->once())->method('createQueryBuilder')->with('f')->willReturn($fileDeleteQb);
        $fileDeleteQb->method('delete')->willReturnSelf();
        $fileDeleteQb->method('where')->with('f.folderId = :folderId')->willReturnSelf();
        $fileDeleteQb->method('setParameter')->with('folderId','f1')->willReturnSelf();
        $fileDeleteQb->method('getQuery')->willReturn($fileDeleteQuery);
        $fileDeleteQuery->expects($this->once())->method('execute');

        // Subfolders deletion: folderRepo->findBy returns empty
        $this->folderRepoMock->expects($this->once())->method('findBy')->with(['parentId'=>'f1'])->willReturn([]);

        // Remove and flush
        $this->emMock->expects($this->once())->method('remove')->with($folder);
        $this->emMock->expects($this->once())->method('flush');
        $this->loggerMock->expects($this->once())->method('info')->with('Folder deleted', ['folder_id'=>'f1','delete_contents'=>true]);

        $result = $this->service->deleteFolder('f1', true);
        $this->assertTrue($result);
    }

    public function testDeleteFolder_WithoutDeleteContents_MovesFilesAndSubfolders(): void
    {
        $folder = $this->createFolder(['id'=>'f1','parentId'=>'p1']);
        $this->folderRepoMock->method('find')->willReturnCallback(fn($id) => $id === 'f1' ? $folder : null);

        // Move files to parent: fileRepo->createQueryBuilder() -> update query
        $fileUpdateQb = $this->createMock(QueryBuilder::class);
        $fileUpdateQuery = $this->createMock(Query::class);
        $this->fileRepoMock->expects($this->once())->method('createQueryBuilder')->with('f')->willReturn($fileUpdateQb);
        $fileUpdateQb->method('update')->willReturnSelf();
        $fileUpdateQb->method('set')->with('f.folderId', ':parentId')->willReturnSelf();
        $fileUpdateQb->method('where')->with('f.folderId = :folderId')->willReturnSelf();
        $fileUpdateQb->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();
        $fileUpdateQb->method('getQuery')->willReturn($fileUpdateQuery);
        $fileUpdateQuery->expects($this->once())->method('execute');

        // Move subfolders to parent: folderRepo->createQueryBuilder() -> update query
        $folderUpdateQb = $this->createMock(QueryBuilder::class);
        $folderUpdateQuery = $this->createMock(Query::class);
        $this->folderRepoMock->expects($this->once())->method('createQueryBuilder')->with('f')->willReturn($folderUpdateQb);
        $folderUpdateQb->method('update')->willReturnSelf();
        $folderUpdateQb->method('set')->with('f.parentId', ':parentId')->willReturnSelf();
        $folderUpdateQb->method('where')->with('f.parentId = :folderId')->willReturnSelf();
        $folderUpdateQb->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();
        $folderUpdateQb->method('getQuery')->willReturn($folderUpdateQuery);
        $folderUpdateQuery->expects($this->once())->method('execute');

        // Remove folder
        $this->emMock->expects($this->once())->method('remove')->with($folder);
        $this->emMock->expects($this->once())->method('flush');
        $this->loggerMock->expects($this->once())->method('info')->with('Folder deleted', ['folder_id'=>'f1','delete_contents'=>false]);

        $result = $this->service->deleteFolder('f1', false);
        $this->assertTrue($result);
    }

    // ==================== getFolderDepth ====================

    public function testGetFolderDepth_Root(): void
    {
        $root = $this->createFolder(['id'=>'root','parentId'=>null]);
        $this->folderRepoMock->method('find')->willReturnCallback(fn($id)=> $id==='root'?$root:null);
        $depth = $this->service->getFolderDepth('root');
        $this->assertSame(0, $depth);
    }

    public function testGetFolderDepth_Nested(): void
    {
        $depth3 = $this->createFolder(['id'=>'3','parentId'=>'2']);
        $depth2 = $this->createFolder(['id'=>'2','parentId'=>'1']);
        $depth1 = $this->createFolder(['id'=>'1','parentId'=>null]);
        $this->folderRepoMock->method('find')->willReturnCallback(fn($id)=> match($id){'1'=>$depth1,'2'=>$depth2,'3'=>$depth3, default=>null});
        $this->assertSame(2, $this->service->getFolderDepth('3'));
    }

    // ==================== isFolderEmpty ====================

    public function testIsFolderEmpty_True(): void
    {
        $folderId = 'f1';
        $this->fileRepoMock->method('count')->with(['folderId'=>$folderId])->willReturn(0);
        $this->folderRepoMock->method('count')->with(['parentId'=>$folderId])->willReturn(0);
        $result = $this->service->isFolderEmpty($folderId);
        $this->assertTrue($result);
    }

    public function testIsFolderEmpty_False_HasFiles(): void
    {
        $folderId = 'f1';
        $this->fileRepoMock->method('count')->with(['folderId'=>$folderId])->willReturn(5);
        $this->folderRepoMock->method('count')->with(['parentId'=>$folderId])->willReturn(0);
        $result = $this->service->isFolderEmpty($folderId);
        $this->assertFalse($result);
    }

    public function testIsFolderEmpty_False_HasSubfolders(): void
    {
        $folderId = 'f1';
        $this->fileRepoMock->method('count')->with(['folderId'=>$folderId])->willReturn(0);
        $this->folderRepoMock->method('count')->with(['parentId'=>$folderId])->willReturn(3);
        $result = $this->service->isFolderEmpty($folderId);
        $this->assertFalse($result);
    }

    // ==================== buildPath (private) ====================

    public function testBuildPath_Root(): void
    {
        $method = new \ReflectionMethod($this->service, 'buildPath');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, null);
        $this->assertSame('/', $result);
    }

    public function testBuildPath_WithParent(): void
    {
        $parent = $this->createFolder(['id'=>'p1','parentId'=>null,'name'=>'Parent','path'=>'/']);
        $this->folderRepoMock->method('find')->with('p1')->willReturn($parent);
        $method = new \ReflectionMethod($this->service, 'buildPath');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, 'p1');
        $this->assertSame('/Parent', $result);
    }

    // ==================== generateId ====================

    public function testGenerateId_Format(): void
    {
        $method = new \ReflectionMethod($this->service, 'generateId');
        $method->setAccessible(true);
        $id = $method->invoke($this->service);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $id);
    }
}
