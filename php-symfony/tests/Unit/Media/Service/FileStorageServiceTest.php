<?php

namespace App\Tests\Unit\Media\Service;

use App\Media\Service\FileStorageService;
use PHPUnit\Framework\TestCase;

class FileStorageServiceTest extends TestCase
{
    private string $tempDir;
    private string $projectDir;
    private FileStorageService $service;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/filestorage_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->projectDir = sys_get_temp_dir() . '/project_test_' . uniqid();
        mkdir($this->projectDir, 0755, true);
        mkdir($this->projectDir . '/var', 0755, true);

        $this->service = new FileStorageService($this->tempDir, $this->projectDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        $this->removeDirectory($this->projectDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }

    public function testConstructorCreatesUploadDirIfNotExists(): void
    {
        $newDir = sys_get_temp_dir() . '/new_upload_' . uniqid();
        $this->assertFalse(is_dir($newDir));

        new FileStorageService($newDir, $this->projectDir);

        $this->assertTrue(is_dir($newDir));
        $this->removeDirectory($newDir);
    }

    public function testConstructorCreatesHtaccessFile(): void
    {
        $htaccess = $this->tempDir . '/.htaccess';
        $this->assertFileExists($htaccess);
        $content = file_get_contents($htaccess);
        $this->assertStringContainsString('Deny from all', $content);
        $this->assertStringContainsString('php', $content);
    }

    public function testGetSecureHtaccessContentReturnsExpectedContent(): void
    {
        $method = new \ReflectionMethod($this->service, 'getSecureHtaccessContent');
        $method->setAccessible(true);
        $content = $method->invoke($this->service);

        $this->assertIsString($content);
        $this->assertStringContainsString('<FilesMatch', $content);
        $this->assertStringContainsString('php', $content);
        $this->assertStringContainsString('jpg', $content);
    }

    public function testMoveUploadedFileMovesFileSuccessfully(): void
    {
        // Skip because move_uploaded_file requires HTTP upload context
        $this->markTestSkipped('Requires real uploaded file; test in integration environment');
    }

    public function testMoveUploadedFileRejectsPathTraversalInFilename(): void
    {
        $source = $this->tempDir . '/source2.txt';
        file_put_contents($source, 'test');
        $maliciousFilename = '../../../etc/passwd';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target filename cannot contain directory separators');
        $this->service->moveUploadedFile($source, $maliciousFilename);
    }

    public function testMoveUploadedFileRejectsDirectoryInFilename(): void
    {
        $source = $this->tempDir . '/source3.txt';
        file_put_contents($source, 'test');
        $badFilename = 'subdir/file.txt';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target filename cannot contain directory separators');
        $this->service->moveUploadedFile($source, $badFilename);
    }

    public function testDeleteFileRemovesFile(): void
    {
        $filename = 'to_delete.txt';
        $filepath = $this->tempDir . '/' . $filename;
        file_put_contents($filepath, 'content');

        $this->assertFileExists($filepath);
        $result = $this->service->deleteFile($filename);
        $this->assertTrue($result);
        $this->assertFileDoesNotExist($filepath);
    }

    public function testDeleteFileThrowsExceptionIfFileNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file path');
        $this->service->deleteFile('missing.txt');
    }

    public function testReadFileReturnsContent(): void
    {
        $filename = 'read.txt';
        $filepath = $this->tempDir . '/' . $filename;
        file_put_contents($filepath, 'file data');

        $content = $this->service->readFile($filename);
        $this->assertSame('file data', $content);
    }

    public function testReadFileThrowsExceptionIfNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file path');
        $this->service->readFile('missing.txt');
    }

    public function testGetFileInfoReturnsCorrectInfo(): void
    {
        $filename = 'info_test.json';
        $filepath = $this->tempDir . '/' . $filename;
        file_put_contents($filepath, '{"key":"value"}');

        $info = $this->service->getFileInfo($filename);

        $this->assertSame($filepath, $info['path']);
        $this->assertSame($filename, $info['filename']);
        $this->assertSame('json', $info['extension']);
        $this->assertArrayHasKey('size', $info);
        $this->assertArrayHasKey('mime_type', $info);
        $this->assertArrayHasKey('modified', $info);
    }

    public function testAssembleFileFromChunksSuccess(): void
    {
        $uploadId = 'upload_' . uniqid();
        $chunkDir = $this->projectDir . '/var/chunks/' . $uploadId;
        mkdir($chunkDir, 0755, true);
        $targetFilename = 'assembled.bin';
        $totalChunks = 3;

        for ($i = 0; $i < $totalChunks; $i++) {
            file_put_contents("$chunkDir/chunk_$i", "chunk_$i data ");
        }

        $result = $this->service->assembleFileFromChunks($uploadId, $targetFilename, $totalChunks);

        $this->assertSame($this->tempDir . '/' . $targetFilename, $result);
        $this->assertFileExists($result);
        $this->assertSame('chunk_0 data chunk_1 data chunk_2 data ', file_get_contents($result));
    }

    public function testAssembleFileFromChunksMissingChunkThrowsException(): void
    {
        $uploadId = 'upload_missing_' . uniqid();
        $chunkDir = $this->projectDir . '/var/chunks/' . $uploadId;
        mkdir($chunkDir, 0755, true);
        file_put_contents("$chunkDir/chunk_0", 'data');
        // chunk 1 is missing

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Chunk 1 not found');
        $this->service->assembleFileFromChunks($uploadId, 'out.bin', 2);
    }

    public function testListFilesWithPrefix(): void
    {
        // Create files
        file_put_contents($this->tempDir . '/prefix_file1.txt', '1');
        file_put_contents($this->tempDir . '/prefix_file2.txt', '2');
        file_put_contents($this->tempDir . '/other_file.txt', '3');

        $files = $this->service->listFiles('prefix_');
        $this->assertCount(2, $files);
        $names = array_map(fn($f) => $f['filename'], $files);
        $this->assertContains('prefix_file1.txt', $names);
        $this->assertContains('prefix_file2.txt', $names);
    }

    public function testReadFileThrowsExceptionWhenPathTraversal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file path');
        $this->service->readFile('../../../etc/passwd');
    }

    public function testGetFileInfoThrowsExceptionWhenFileNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file path');
        $this->service->getFileInfo('nonexistent.txt');
    }
}
