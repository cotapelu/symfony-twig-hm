<?php declare(strict_types=1);

namespace Tests\Unit\Media\Controller;

use App\Media\Controller\FileController;
use App\Media\Service\FileValidationService;
use App\Media\Service\FileStorageService;
use App\Media\Service\FileSanitizationService;
use App\Shared\Infrastructure\RateLimitService;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class FileControllerTest extends TestCase
{
    private RateLimitService $rateLimitMock;
    private FileValidationService $validationMock;
    private FileStorageService $storageMock;
    private FileSanitizationService $sanitizationMock;
    private HubInterface $hubMock;
    private FileController $controller;

    protected function setUp(): void
    {
        $this->rateLimitMock = $this->createMock(RateLimitService::class);
        $this->validationMock = $this->createMock(FileValidationService::class);
        $this->storageMock = $this->createMock(FileStorageService::class);
        $this->sanitizationMock = $this->createMock(FileSanitizationService::class);
        $this->hubMock = $this->createMock(HubInterface::class);

        $this->controller = new FileController(
            $this->validationMock,
            $this->storageMock,
            $this->sanitizationMock,
            $this->rateLimitMock,
            $this->hubMock,
            '/tmp/project'
        );
    }

    private function setAuthenticatedUser(string $userId = 'user1'): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn($userId);
        $this->controller->setTestUser($user);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', '/tmp/project');
        $this->controller->setContainer($container);
    }

    private function createUploadedFileMock(
        string $name,
        string $type,
        int $size,
        int $error = UPLOAD_ERR_OK,
        ?string $tmpName = null
    ): UploadedFile {
        $tmp = $tmpName ?? tempnam(sys_get_temp_dir(), 'upload');
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn($name);
        $file->method('getMimeType')->willReturn($type);
        $file->method('getSize')->willReturn($size);
        $file->method('getError')->willReturn($error);
        $file->method('getPathname')->willReturn($tmp);
        return $file;
    }

    // ==================== getFiles ====================

    public function testGetFiles_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $files = [
            ['filename' => 'file1.jpg', 'size' => 1024, 'mime_type' => 'image/jpeg', 'modified' => '2025-01-01 10:00:00'],
            ['filename' => 'file2.jpg', 'size' => 2048, 'mime_type' => 'image/jpeg', 'modified' => '2025-01-02 10:00:00'],
        ];
        $this->storageMock->expects($this->once())
            ->method('listFiles')
            ->willReturn($files);

        $request = Request::create('/api/files', 'GET');
        $response = $this->controller->getFiles($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data['files']);
        $this->assertArrayHasKey('pagination', $data);
    }

    public function testGetFiles_Unauthenticated_Returns401(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/api/files', 'GET');
        $response = $this->controller->getFiles($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetFiles_WithPagination_SlicesCorrectly(): void
    {
        $this->setAuthenticatedUser('user1');
        $files = [];
        for ($i = 1; $i <= 10; $i++) {
            $files[] = ['filename' => "file{$i}.jpg", 'size' => 1024, 'mime_type' => 'image/jpeg', 'modified' => "2025-01-{$i} 10:00:00"];
        }
        $this->storageMock->expects($this->once())
            ->method('listFiles')
            ->willReturn($files);

        $request = Request::create('/api/files', 'GET', ['page' => 2, 'perPage' => 3]);
        $response = $this->controller->getFiles($request);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data['files']);
        $this->assertSame(2, $data['pagination']['page']);
        $this->assertSame(3, $data['pagination']['per_page']);
        $this->assertSame(10, $data['pagination']['total']);
        $this->assertSame(4, $data['pagination']['total_pages']);
    }

    // ==================== upload (single) ====================

    public function testUpload_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->rateLimitMock->expects($this->once())
            ->method('checkRateLimit')
            ->with('user1', 'file_upload')
            ->willReturn(true);

        $uploadedFile = $this->createUploadedFileMock('test.jpg', 'image/jpeg', 1024);
        $request = Request::create('/api/files/upload', 'POST', [], [], ['file' => $uploadedFile]);

        // Validation pipeline
        $this->validationMock->expects($this->once())
            ->method('validateMagicNumber')
            ->with($this->stringContains('/tmp/upload'), 'image/jpeg')
            ->willReturn(true);
        $this->validationMock->expects($this->once())
            ->method('validateImageDimensions')
            ->with($this->stringContains('/tmp/upload'))
            ->willReturn([800, 600]);
        $this->validationMock->expects($this->once())
            ->method('generateSecureFilename')
            ->with('test.jpg')
            ->willReturn('secure_test.jpg');

        // Storage operations
        $this->storageMock->expects($this->once())
            ->method('moveUploadedFile')
            ->with($this->stringContains('/tmp/upload'), 'secure_test.jpg')
            ->willReturn('/uploads/communication/secure_test.jpg');

        $this->sanitizationMock->expects($this->once())
            ->method('sanitizeImageExif')
            ->with('/uploads/communication/secure_test.jpg');

        $this->hubMock->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(Update::class));

        $response = $this->controller->upload($request);
        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame('secure_test.jpg', $data['data']['filename']);
    }

    public function testUpload_NoFile_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->rateLimitMock->expects($this->once())
            ->method('checkRateLimit')
            ->with('user1', 'file_upload')
            ->willReturn(true);

        $request = Request::create('/api/files/upload', 'POST');
        $response = $this->controller->upload($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('No file uploaded', $response->getContent());
    }

    public function testUpload_RateLimited_Returns429(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->rateLimitMock->expects($this->once())
            ->method('checkRateLimit')
            ->with('user1', 'file_upload')
            ->willReturn(false);

        $uploadedFile = $this->createUploadedFileMock('test.jpg', 'image/jpeg', 1024);
        $request = Request::create('/api/files/upload', 'POST', [], [], ['file' => $uploadedFile]);

        $response = $this->controller->upload($request);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertStringContainsString('Upload rate limit exceeded', $response->getContent());
    }

    public function testUpload_InvalidMagicNumber_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(true);

        $uploadedFile = $this->createUploadedFileMock('test.jpg', 'image/jpeg', 1024);
        $request = Request::create('/api/files/upload', 'POST', [], [], ['file' => $uploadedFile]);

        $this->validationMock->expects($this->once())
            ->method('validateMagicNumber')
            ->with($this->anything(), 'image/jpeg')
            ->willReturn(false);

        $response = $this->controller->upload($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('File content mismatch', $response->getContent());
    }

    // ==================== uploadChunk ====================

    public function testUploadChunk_MissingHeaders_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $request = Request::create('/api/files/chunk', 'POST');
        $response = $this->controller->uploadChunk($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Missing required headers', $response->getContent());
    }
}
