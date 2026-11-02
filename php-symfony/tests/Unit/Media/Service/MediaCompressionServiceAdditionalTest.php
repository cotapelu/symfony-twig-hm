<?php

declare(strict_types=1);

namespace App\Tests\Unit\Media\Service;

use PHPUnit\Framework\TestCase;
use App\Media\Service\MediaCompressionService;

/**
 * Additional tests for MediaCompressionService to cover remaining branches.
 * Focus: unsupported image format, non-image/video media info.
 */
class MediaCompressionServiceAdditionalTest extends TestCase
{
    private string $uploadDir;
    private MediaCompressionService $service;

    protected function setUp(): void
    {
        $this->uploadDir = \sys_get_temp_dir() . '/media_test_' . uniqid();
        \mkdir($this->uploadDir, 0755, true);
        $this->service = new MediaCompressionService($this->uploadDir);
    }

    protected function tearDown(): void
    {
        $this->recursiveRemoveDir($this->uploadDir);
    }

    private function recursiveRemoveDir(string $dir): void
    {
        if (!\is_dir($dir)) {
            return;
        }
        $files = \array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            \is_dir($path) ? $this->recursiveRemoveDir($path) : \unlink($path);
        }
        \rmdir($dir);
    }

    private function createTempImage(int $sizeKB = 100, string $format = 'jpg'): string
    {
        $path = \tempnam(sys_get_temp_dir(), 'img_');
        $pathWithExt = $path . '.' . $format;
        rename($path, $pathWithExt);
        if ($sizeKB > 100) {
            $this->padFileSize($pathWithExt, $sizeKB * 1024);
        }
        return $pathWithExt;
    }

    private function padFileSize(string $filepath, int $targetSize): void
    {
        $current = \filesize($filepath);
        if ($current >= $targetSize) {
            return;
        }
        $pad = $targetSize - $current;
        $f = \fopen($filepath, 'ab');
        \fwrite($f, str_repeat('0', $pad));
        \fclose($f);
    }

    public function testCreateSourceImage_ThrowsExceptionForUnsupportedFormat(): void
    {
        $imagePath = $this->createTempImage(100, 'jpg'); // valid image path to pass exist check
        $method = new \ReflectionMethod($this->service, 'createSourceImage');
        $method->setAccessible(true);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unsupported image format');
        $method->invoke($this->service, 'image/unknown', $imagePath);
    }

    public function testGetMediaInfo_WithNonImageNonVideo_ReturnsBasicInfoOnly(): void
    {
        // Create a temporary text file
        $path = \tempnam(sys_get_temp_dir(), 'txt_');
        file_put_contents($path, 'plain text');
        $info = $this->service->getMediaInfo($path);
        $this->assertArrayHasKey('size', $info);
        $this->assertArrayHasKey('mime_type', $info);
        $this->assertArrayHasKey('extension', $info);
        $this->assertArrayNotHasKey('width', $info);
        $this->assertArrayNotHasKey('height', $info);
        $this->assertArrayNotHasKey('duration', $info);
        unlink($path);
    }
}
