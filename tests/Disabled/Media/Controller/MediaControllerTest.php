<?php declare(strict_types=1);

namespace App\Tests\Unit\Media\Controller;

use App\Media\Controller\MediaController;
use PHPUnit\Framework\TestCase;

class MediaControllerTest extends TestCase
{
    private MediaController $controller;

    protected function setUp(): void
    {
        $this->controller = new MediaController();
    }

    public function testUploadFileReturns201Status(): void
    {
        $request = [
            'body' => [
                'filename' => 'test.jpg',
                'original_name' => 'Original Test.jpg',
                'mime_type' => 'image/jpeg',
                'size' => 1024,
                'storage_type' => 'local',
                'path' => '/uploads/test.jpg',
            ],
            'user_id' => 1,
        ];

        $response = $this->controller->uploadFile($request);

        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('data', $response);
    }

    public function testUploadFileWithEmptyBodyReturnsEmptyPath(): void
    {
        $request = [
            'body' => [],
            'user_id' => 0,
        ];

        $response = $this->controller->uploadFile($request);

        $this->assertEquals(201, $response['status']);
    }

    public function testGetUserFilesReturns200Status(): void
    {
        $request = ['user_id' => 1, 'query' => []];

        $response = $this->controller->getUserFiles($request);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response);
    }

    public function testGetUserFilesWithFolderId(): void
    {
        $request = ['user_id' => 1, 'query' => ['folder_id' => 5]];

        $response = $this->controller->getUserFiles($request);

        $this->assertEquals(200, $response['status']);
    }

    public function testGetFileReturns200WhenFound(): void
    {
        $uploadRequest = [
            'body' => [
                'filename' => 'test.jpg',
                'original_name' => 'Test',
                'mime_type' => 'image/jpeg',
                'size' => 1024,
                'storage_type' => 'local',
                'path' => '/path',
            ],
            'user_id' => 1,
        ];
        $uploadResponse = $this->controller->uploadFile($uploadRequest);
        $fileId = $uploadResponse['data']->getId();

        $request = ['params' => ['id' => $fileId]];
        $response = $this->controller->getFile($request);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals($fileId, $response['data']->getId());
    }

    public function testGetFileReturns404WhenNotFound(): void
    {
        $request = ['params' => ['id' => 9999]];

        $response = $this->controller->getFile($request);

        $this->assertEquals(404, $response['status']);
        $this->assertArrayHasKey('error', $response);
    }

    public function testDeleteFileReturns204WhenSuccessful(): void
    {
        $uploadRequest = [
            'body' => [
                'filename' => 'to_delete.jpg',
                'original_name' => 'To Delete',
                'mime_type' => 'image/jpeg',
                'size' => 1024,
                'storage_type' => 'local',
                'path' => '/path',
            ],
            'user_id' => 1,
        ];
        $uploadResponse = $this->controller->uploadFile($uploadRequest);
        $fileId = $uploadResponse['data']->getId();

        $request = ['params' => ['id' => $fileId]];
        $response = $this->controller->deleteFile($request);

        $this->assertEquals(204, $response['status']);
    }

    public function testDeleteFileReturns404WhenNotFound(): void
    {
        $request = ['params' => ['id' => 9999]];

        $response = $this->controller->deleteFile($request);

        $this->assertEquals(404, $response['status']);
    }

    public function testMoveFileReturns200WhenSuccessful(): void
    {
        $uploadRequest = [
            'body' => [
                'filename' => 'test.jpg',
                'original_name' => 'Test',
                'mime_type' => 'image/jpeg',
                'size' => 1024,
                'storage_type' => 'local',
                'path' => '/path',
            ],
            'user_id' => 1,
        ];
        $uploadResponse = $this->controller->uploadFile($uploadRequest);
        $fileId = $uploadResponse['data']->getId();

        $folderRequest = [
            'body' => ['name' => 'Folder'],
            'user_id' => 1,
        ];
        $folderResponse = $this->controller->createFolder($folderRequest);
        $folderId = $folderResponse['data']->getId();

        $request = [
            'params' => ['id' => $fileId],
            'body' => ['folder_id' => $folderId],
        ];
        $response = $this->controller->moveFile($request);

        $this->assertEquals(200, $response['status']);
    }

    public function testMoveFileReturns404WhenFileNotFound(): void
    {
        $request = [
            'params' => ['id' => 9999],
            'body' => ['folder_id' => 1],
        ];

        $response = $this->controller->moveFile($request);

        $this->assertEquals(404, $response['status']);
    }

    public function testCreateFolderReturns201Status(): void
    {
        $request = [
            'body' => ['name' => 'My Photos'],
            'user_id' => 1,
        ];

        $response = $this->controller->createFolder($request);

        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('data', $response);
    }

    public function testCreateFolderWithParentId(): void
    {
        $parentRequest = [
            'body' => ['name' => 'Parent Folder'],
            'user_id' => 1,
        ];
        $parentResponse = $this->controller->createFolder($parentRequest);
        $parentId = $parentResponse['data']->getId();

        $request = [
            'body' => ['name' => 'Child Folder', 'parent_id' => $parentId],
            'user_id' => 1,
        ];

        $response = $this->controller->createFolder($request);

        $this->assertEquals(201, $response['status']);
        $this->assertEquals($parentId, $response['data']->getParentId());
    }

    public function testGetUserFoldersReturns200Status(): void
    {
        $request = ['user_id' => 1, 'query' => []];

        $response = $this->controller->getUserFolders($request);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response);
    }

    public function testGetUserFoldersWithParentId(): void
    {
        $request = ['user_id' => 1, 'query' => ['parent_id' => 5]];

        $response = $this->controller->getUserFolders($request);

        $this->assertEquals(200, $response['status']);
    }

    public function testGetFolderReturns200WhenFound(): void
    {
        $createRequest = [
            'body' => ['name' => 'Test Folder'],
            'user_id' => 1,
        ];
        $createResponse = $this->controller->createFolder($createRequest);
        $folderId = $createResponse['data']->getId();

        $request = ['params' => ['id' => $folderId]];
        $response = $this->controller->getFolder($request);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals($folderId, $response['data']->getId());
    }

    public function testGetFolderReturns404WhenNotFound(): void
    {
        $request = ['params' => ['id' => 9999]];

        $response = $this->controller->getFolder($request);

        $this->assertEquals(404, $response['status']);
    }

    public function testDeleteFolderReturns204WhenSuccessful(): void
    {
        $createRequest = [
            'body' => ['name' => 'To Delete'],
            'user_id' => 1,
        ];
        $createResponse = $this->controller->createFolder($createRequest);
        $folderId = $createResponse['data']->getId();

        $request = ['params' => ['id' => $folderId]];
        $response = $this->controller->deleteFolder($request);

        $this->assertEquals(204, $response['status']);
    }

    public function testDeleteFolderReturns404WhenNotFound(): void
    {
        $request = ['params' => ['id' => 9999]];

        $response = $this->controller->deleteFolder($request);

        $this->assertEquals(404, $response['status']);
    }
}