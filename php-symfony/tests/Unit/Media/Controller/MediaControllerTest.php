<?php

declare(strict_types=1);

namespace App\Tests\Unit\Media\Controller;

use PHPUnit\Framework\TestCase;
use App\Media\Controller\MediaController;
use App\Media\Service\MediaService;
use App\Media\Entity\MediaFile;
use App\Media\Entity\MediaFolder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\PostAuthenticationToken;

class MediaControllerTest extends TestCase
{
    private MediaService $service;
    private MediaController $controller;

    protected function setUp(): void
    {
        $this->service = $this->createMock(MediaService::class);
        $this->controller = new MediaController($this->service);
        $this->setupContainer();
    }

    private function setupContainer(): void
    {
        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->set('serializer', new \Symfony\Component\Serializer\Serializer([], [new \Symfony\Component\Serializer\Encoder\JsonEncoder()]));
        $this->controller->setContainer($container);
    }

    private function setAuthenticatedUser(int $userId): void
    {
        $user = new class($userId) implements \Symfony\Component\Security\Core\User\UserInterface {
            private int $id;
            public function __construct(int $id) { $this->id = $id; }
            public function getUserIdentifier(): string { return (string)$this->id; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function eraseCredentials() {}
        };
        $this->controller->setTestUser($user);
    }

    private function jsonRequest(array $data = []): Request
    {
        return Request::create('/api/media/test', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    private function createMediaFile(array $override = []): MediaFile
    {
        $file = $this->createMock(MediaFile::class);
        $file->method('getId')->willReturn($override['id'] ?? 1);
        $file->method('getUserId')->willReturn($override['user_id'] ?? 1);
        $file->method('getFilename')->willReturn($override['filename'] ?? 'file.jpg');
        $file->method('getOriginalName')->willReturn($override['original_name'] ?? 'original.jpg');
        $file->method('getMimeType')->willReturn($override['mime_type'] ?? 'image/jpeg');
        $file->method('getSize')->willReturn($override['size'] ?? 1000);
        $file->method('getStorageType')->willReturn($override['storage_type'] ?? 'local');
        $file->method('getPath')->willReturn($override['path'] ?? '/uploads/file.jpg');
        $file->method('toArray')->willReturn([
            'id' => $override['id'] ?? 1,
            'user_id' => $override['user_id'] ?? 1,
            'filename' => $override['filename'] ?? 'file.jpg',
            'original_name' => $override['original_name'] ?? 'original.jpg',
            'mime_type' => $override['mime_type'] ?? 'image/jpeg',
            'size' => $override['size'] ?? 1000,
            'storage_type' => $override['storage_type'] ?? 'local',
            'path' => $override['path'] ?? '/uploads/file.jpg',
        ]);
        return $file;
    }

    private function createMediaFolder(array $override = []): MediaFolder
    {
        $folder = $this->createMock(MediaFolder::class);
        $folder->method('getId')->willReturn($override['id'] ?? 1);
        $folder->method('getUserId')->willReturn($override['user_id'] ?? 1);
        $folder->method('getName')->willReturn($override['name'] ?? 'Folder');
        $folder->method('getParentId')->willReturn($override['parent_id'] ?? null);
        $folder->method('toArray')->willReturn([
            'id' => $override['id'] ?? 1,
            'user_id' => $override['user_id'] ?? 1,
            'name' => $override['name'] ?? 'Folder',
            'parent_id' => $override['parent_id'] ?? null,
        ]);
        return $folder;
    }

    // ==================== uploadFile ====================

    public function testUploadFile_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $file = $this->createMediaFile();
        $this->service->method('uploadFile')->with(1, 'file.jpg', 'original.jpg', 'image/jpeg', 1000, 'local', '')->willReturn($file);

        $request = $this->jsonRequest([
            'filename' => 'file.jpg',
            'original_name' => 'original.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1000,
            'storage_type' => 'local',
            'path' => ''
        ]);
        $response = $this->controller->uploadFile($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testUploadFile_Unauthenticated(): void
    {
        $request = $this->jsonRequest([
            'filename' => 'file.jpg',
            'original_name' => 'original.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1000
        ]);
        $response = $this->controller->uploadFile($request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Authentication required', $data['error']);
    }

    public function testUploadFile_InvalidJson(): void
    {
        $this->setAuthenticatedUser(1);
        $request = Request::create('/api/media/upload', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->uploadFile($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUploadFile_MissingRequiredFields(): void
    {
        $this->setAuthenticatedUser(1);
        $request = $this->jsonRequest(['filename' => 'file.jpg']); // missing others
        $response = $this->controller->uploadFile($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUploadFile_FilenameTooLong(): void
    {
        $this->setAuthenticatedUser(1);
        $longName = str_repeat('a', 256);
        $request = $this->jsonRequest([
            'filename' => $longName,
            'original_name' => 'orig.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1000
        ]);
        $response = $this->controller->uploadFile($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Filename too long', $response->getContent());
    }

    public function testUploadFile_InvalidSize(): void
    {
        $this->setAuthenticatedUser(1);
        $request = $this->jsonRequest([
            'filename' => 'file.jpg',
            'original_name' => 'orig.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 0
        ]);
        $response = $this->controller->uploadFile($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== getUserFiles ====================

    public function testGetUserFiles_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $file1 = $this->createMediaFile();
        $file2 = $this->createMediaFile(['id' => 2]);
        $this->service->method('getUserFiles')->with(1, null)->willReturn([$file1, $file2]);

        $request = new Request();
        $response = $this->controller->getUserFiles($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetUserFiles_Unauthenticated(): void
    {
        $request = new Request();
        $response = $this->controller->getUserFiles($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== getFile ====================

    public function testGetFile_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $file = $this->createMediaFile(['id' => 42, 'user_id' => 1]);
        $this->service->method('getFile')->with(42)->willReturn($file);
        $request = new Request();
        $response = $this->controller->getFile($request, 42);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetFile_InvalidId(): void
    {
        $this->setAuthenticatedUser(1);
        $request = new Request();
        $response = $this->controller->getFile($request, 0);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetFile_NotFound(): void
    {
        $this->setAuthenticatedUser(1);
        $this->service->method('getFile')->with(99)->willReturn(null);
        $request = new Request();
        $response = $this->controller->getFile($request, 99);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetFile_AccessDenied(): void
    {
        $this->setAuthenticatedUser(1);
        $file = $this->createMediaFile(['id' => 42, 'user_id' => 2]);
        $this->service->method('getFile')->with(42)->willReturn($file);
        $request = new Request();
        $response = $this->controller->getFile($request, 42);
        $this->assertSame(403, $response->getStatusCode());
    }

    // ==================== deleteFile ====================

    public function testDeleteFile_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $file = $this->createMediaFile(['id' => 42, 'user_id' => 1]);
        $this->service->method('getFile')->with(42)->willReturn($file);
        $this->service->method('deleteFile')->with(42)->willReturn(true);
        $request = new Request();
        $response = $this->controller->deleteFile($request, 42);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testDeleteFile_NotFound(): void
    {
        $this->setAuthenticatedUser(1);
        $this->service->method('deleteFile')->with(42)->willReturn(false);
        $request = new Request();
        $response = $this->controller->deleteFile($request, 42);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDeleteFile_AccessDenied(): void
    {
        $this->setAuthenticatedUser(1);
        $file = $this->createMediaFile(['id' => 42, 'user_id' => 2]);
        $this->service->method('getFile')->with(42)->willReturn($file);
        $request = new Request();
        $response = $this->controller->deleteFile($request, 42);
        $this->assertSame(403, $response->getStatusCode());
    }

    // ==================== moveFile ====================

    public function testMoveFile_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $file = $this->createMediaFile(['id' => 42, 'user_id' => 1]);
        $this->service->method('getFile')->with(42)->willReturn($file);
        $this->service->method('moveFile')->with(42, 5)->willReturn($file);
        $request = $this->jsonRequest(['folder_id' => 5]);
        $response = $this->controller->moveFile($request, 42);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMoveFile_NotFound(): void
    {
        $this->setAuthenticatedUser(1);
        $this->service->method('getFile')->with(42)->willReturn(null);
        $request = $this->jsonRequest(['folder_id' => 5]);
        $response = $this->controller->moveFile($request, 42);
        $this->assertSame(404, $response->getStatusCode());
    }

    // ==================== createFolder ====================

    public function testCreateFolder_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $folder = $this->createMediaFolder(['id' => 10, 'user_id' => 1, 'name' => 'My Folder']);
        $this->service->method('createFolder')->with(1, 'My Folder', null)->willReturn($folder);

        $request = $this->jsonRequest(['name' => 'My Folder']);
        $response = $this->controller->createFolder($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateFolder_Unauthenticated(): void
    {
        $request = $this->jsonRequest(['name' => 'Folder']);
        $response = $this->controller->createFolder($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateFolder_MissingName(): void
    {
        $this->setAuthenticatedUser(1);
        $request = $this->jsonRequest([]);
        $response = $this->controller->createFolder($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== getUserFolders ====================

    public function testGetUserFolders_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $folder = $this->createMediaFolder();
        $this->service->method('getUserFolders')->with(1, null)->willReturn([$folder]);

        $request = new Request();
        $response = $this->controller->getUserFolders($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== getFolder ====================

    public function testGetFolder_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $folder = $this->createMediaFolder(['id' => 5, 'user_id' => 1]);
        $this->service->method('getFolder')->with(5)->willReturn($folder);
        $request = new Request();
        $response = $this->controller->getFolder($request, 5);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetFolder_AccessDenied(): void
    {
        $this->setAuthenticatedUser(1);
        $folder = $this->createMediaFolder(['id' => 5, 'user_id' => 2]);
        $this->service->method('getFolder')->with(5)->willReturn($folder);
        $request = new Request();
        $response = $this->controller->getFolder($request, 5);
        $this->assertSame(403, $response->getStatusCode());
    }

    // ==================== deleteFolder ====================

    public function testDeleteFolder_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $folder = $this->createMediaFolder(['id' => 5, 'user_id' => 1]);
        $this->service->method('getFolder')->with(5)->willReturn($folder);
        $this->service->method('deleteFolder')->with(5)->willReturn(true);
        $request = new Request();
        $response = $this->controller->deleteFolder($request, 5);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testDeleteFolder_NotFound(): void
    {
        $this->setAuthenticatedUser(1);
        $this->service->method('getFolder')->with(5)->willReturn(null);
        $request = new Request();
        $response = $this->controller->deleteFolder($request, 5);
        $this->assertSame(404, $response->getStatusCode());
    }
}
