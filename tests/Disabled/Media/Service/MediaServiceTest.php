<?php declare(strict_types=1);

namespace App\Tests\Unit\Media\Service;

use App\Media\Entity\MediaFile;
use App\Media\Entity\MediaFolder;
use App\Media\Service\MediaService;
use PHPUnit\Framework\TestCase;

class MediaServiceTest extends TestCase
{
    private MediaService $service;

    protected function setUp(): void
    {
        $this->service = new MediaService();
    }

    public function testUploadFileCreatesMediaFile(): void
    {
        $file = $this->service->uploadFile(
            userId: 1,
            filename: 'test.jpg',
            originalName: 'Original Test.jpg',
            mimeType: 'image/jpeg',
            size: 1024,
            storageType: 'local',
            path: '/uploads/test.jpg'
        );

        $this->assertInstanceOf(MediaFile::class, $file);
        $this->assertEquals(1, $file->getUserId());
        $this->assertEquals('test.jpg', $file->getFilename());
        $this->assertEquals('Original Test.jpg', $file->getOriginalName());
        $this->assertEquals('image/jpeg', $file->getMimeType());
        $this->assertEquals(1024, $file->getSize());
        $this->assertEquals('local', $file->getStorageType());
        $this->assertEquals('/uploads/test.jpg', $file->getPath());
    }

    public function testUploadFileGeneratesId(): void
    {
        $file = $this->service->uploadFile(1, 'test.jpg', 'Test', 'image/jpeg', 1024, 'local', '/path');

        $this->assertNotNull($file->getId());
    }

    public function testGetFileReturnsFileWhenExists(): void
    {
        $created = $this->service->uploadFile(1, 'test.jpg', 'Test', 'image/jpeg', 1024, 'local', '/path');

        $retrieved = $this->service->getFile($created->getId());

        $this->assertNotNull($retrieved);
        $this->assertEquals($created->getId(), $retrieved->getId());
    }

    public function testGetFileReturnsNullWhenNotFound(): void
    {
        $result = $this->service->getFile(9999);

        $this->assertNull($result);
    }

    public function testGetUserFilesReturnsUserFiles(): void
    {
        $this->service->uploadFile(1, 'file1.jpg', 'File 1', 'image/jpeg', 1024, 'local', '/path1');
        $this->service->uploadFile(2, 'file2.jpg', 'File 2', 'image/jpeg', 2048, 'local', '/path2');
        $this->service->uploadFile(1, 'file3.jpg', 'File 3', 'image/jpeg', 512, 'local', '/path3');

        $user1Files = $this->service->getUserFiles(1);

        $this->assertCount(2, $user1Files);
        foreach ($user1Files as $file) {
            $this->assertEquals(1, $file->getUserId());
        }
    }

    public function testGetUserFilesFiltersByFolder(): void
    {
        $this->service->uploadFile(1, 'file1.jpg', 'File 1', 'image/jpeg', 1024, 'local', '/path1');
        $folder = $this->service->createFolder(1, 'My Folder');
        $fileInFolder = $this->service->uploadFile(1, 'file2.jpg', 'File 2', 'image/jpeg', 1024, 'local', '/path2');
        $this->service->moveFile($fileInFolder->getId(), $folder->getId());

        $filesNoFolder = $this->service->getUserFiles(1, null);
        $filesInFolder = $this->service->getUserFiles(1, $folder->getId());

        $this->assertCount(1, $filesNoFolder);
        $this->assertCount(1, $filesInFolder);
    }

    public function testDeleteFileReturnsTrueWhenFileExists(): void
    {
        $file = $this->service->uploadFile(1, 'test.jpg', 'Test', 'image/jpeg', 1024, 'local', '/path');

        $deleted = $this->service->deleteFile($file->getId());

        $this->assertTrue($deleted);
        $this->assertNull($this->service->getFile($file->getId()));
    }

    public function testDeleteFileReturnsFalseWhenNotFound(): void
    {
        $result = $this->service->deleteFile(9999);

        $this->assertFalse($result);
    }

    public function testCreateFolderCreatesMediaFolder(): void
    {
        $folder = $this->service->createFolder(
            userId: 1,
            name: 'My Photos',
            parentId: null
        );

        $this->assertInstanceOf(MediaFolder::class, $folder);
        $this->assertEquals(1, $folder->getUserId());
        $this->assertEquals('My Photos', $folder->getName());
        $this->assertNull($folder->getParentId());
    }

    public function testCreateFolderWithParentId(): void
    {
        $parent = $this->service->createFolder(1, 'Parent Folder');
        $child = $this->service->createFolder(1, 'Child Folder', $parent->getId());

        $this->assertEquals($parent->getId(), $child->getParentId());
    }

    public function testCreateFolderGeneratesId(): void
    {
        $folder = $this->service->createFolder(1, 'Test Folder');

        $this->assertNotNull($folder->getId());
    }

    public function testGetFolderReturnsFolderWhenExists(): void
    {
        $created = $this->service->createFolder(1, 'Test Folder');

        $retrieved = $this->service->getFolder($created->getId());

        $this->assertNotNull($retrieved);
        $this->assertEquals($created->getId(), $retrieved->getId());
    }

    public function testGetFolderReturnsNullWhenNotFound(): void
    {
        $result = $this->service->getFolder(9999);

        $this->assertNull($result);
    }

    public function testGetUserFoldersReturnsUserFolders(): void
    {
        $this->service->createFolder(1, 'Folder 1');
        $this->service->createFolder(2, 'Folder 2');
        $this->service->createFolder(1, 'Folder 3');

        $user1Folders = $this->service->getUserFolders(1);

        $this->assertCount(2, $user1Folders);
        foreach ($user1Folders as $folder) {
            $this->assertEquals(1, $folder->getUserId());
        }
    }

    public function testGetUserFoldersFiltersByParent(): void
    {
        $parent = $this->service->createFolder(1, 'Parent');
        $child1 = $this->service->createFolder(1, 'Child 1', $parent->getId());
        $child2 = $this->service->createFolder(1, 'Child 2', $parent->getId());

        $rootFolders = $this->service->getUserFolders(1, null);
        $parentChildren = $this->service->getUserFolders(1, $parent->getId());

        $this->assertCount(1, $rootFolders);
        $this->assertCount(2, $parentChildren);
    }

    public function testDeleteFolderReturnsTrueWhenFolderExists(): void
    {
        $folder = $this->service->createFolder(1, 'To Delete');

        $deleted = $this->service->deleteFolder($folder->getId());

        $this->assertTrue($deleted);
        $this->assertNull($this->service->getFolder($folder->getId()));
    }

    public function testDeleteFolderReturnsFalseWhenNotFound(): void
    {
        $result = $this->service->deleteFolder(9999);

        $this->assertFalse($result);
    }

    public function testMoveFileToFolder(): void
    {
        $file = $this->service->uploadFile(1, 'test.jpg', 'Test', 'image/jpeg', 1024, 'local', '/path');
        $folder = $this->service->createFolder(1, 'Folder');

        $moved = $this->service->moveFile($file->getId(), $folder->getId());

        $this->assertNotNull($moved);
        $this->assertEquals($folder->getId(), $moved->getFolderId());
    }

    public function testMoveFileToNullRemovesFromFolder(): void
    {
        $folder = $this->service->createFolder(1, 'Folder');
        $file = $this->service->uploadFile(1, 'test.jpg', 'Test', 'image/jpeg', 1024, 'local', '/path');
        $this->service->moveFile($file->getId(), $folder->getId());

        $moved = $this->service->moveFile($file->getId(), null);

        $this->assertNotNull($moved);
        $this->assertNull($moved->getFolderId());
    }

    public function testMoveFileReturnsNullWhenFileNotFound(): void
    {
        $result = $this->service->moveFile(9999, 1);

        $this->assertNull($result);
    }
}