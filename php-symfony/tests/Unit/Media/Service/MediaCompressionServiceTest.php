<?php

declare(strict_types=1);

namespace App\Tests\Unit\Media\Service;

use PHPUnit\Framework\TestCase;
use App\Media\Service\MediaCompressionService;

/**
 * Unit tests for MediaCompressionService
 * Covers: image compression, thumbnail generation, media info extraction
 */
class MediaCompressionServiceTest extends TestCase
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
        // Ensure correct extension for mime detection
        $pathWithExt = $path . '.' . $format;
        rename($path, $pathWithExt);
        // Pad size if needed
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

    private function createTempVideo(): string
    {
        $path = \tempnam(sys_get_temp_dir(), 'vid_');
        $pathWithExt = $path . '.mp4';
        rename($path, $pathWithExt);
        \file_put_contents($pathWithExt, "fake video content");
        return $pathWithExt;
    }

    public function testCompressImage_ReturnsUncompressedResultWhenSmall(): void
    {
        $imagePath = $this->createTempImage(50); // 50KB < 5MB
        $result = $this->service->compressImage($imagePath);

        $this->assertSame($imagePath, $result['path']);
        $this->assertSame(0, $result['compression_ratio']);
        $this->assertArrayHasKey('original_size', $result);
        $this->assertArrayHasKey('compressed_size', $result);
        $this->assertArrayHasKey('format', $result);
        \unlink($imagePath);
    }

    public function testCompressImage_CompressesLargeImage(): void
    {
        $imagePath = $this->createTempImage(6000); // 6MB > 5MB
        $result = $this->service->compressImage($imagePath);

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('original_size', $result);
        $this->assertArrayHasKey('compressed_size', $result);
        $this->assertArrayHasKey('compression_ratio', $result);
        $this->assertSame('jpeg', $result['format']);
        $this->assertFileExists($result['path']);
        // Cleanup
        @\unlink($result['path']);
        \unlink($imagePath);
    }

    public function testCompressImage_ThrowsExceptionWhenFileNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Image file not found');
        $this->service->compressImage('/nonexistent/path.jpg');
    }

    public function testCompressImage_ThrowsExceptionWhenInvalidImage(): void
    {
        $path = \tempnam(sys_get_temp_dir(), 'bad_');
        \file_put_contents($path, 'not an image');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid image file');
        $this->service->compressImage($path);
        \unlink($path);
    }

    public function testGenerateThumbnail_ImageTypeCreatesResizedThumbnail(): void
    {
        $imagePath = $this->createTempImage(200, 'jpg');
        $thumbnailPath = $this->service->generateThumbnail($imagePath, 'image');

        $this->assertFileExists($thumbnailPath);
        $this->assertStringEndsWith('_thumb.jpg', $thumbnailPath);
        \unlink($imagePath);
        @\unlink($thumbnailPath);
    }

    public function testGenerateThumbnail_VideoTypeUsesFfmpeg(): void
    {
        $videoPath = $this->createTempVideo();
        // We'll let exec run but ffmpeg will fail; service should fallback
        $thumbnailPath = $this->service->generateThumbnail($videoPath, 'video');

        $this->assertFileExists($thumbnailPath);
        $this->assertStringEndsWith('_thumb.mp4', $thumbnailPath);
        \unlink($videoPath);
        @\unlink($thumbnailPath);
    }

    public function testGenerateThumbnail_CreatesThumbnailDirectoryIfMissing(): void
    {
        $this->recursiveRemoveDir($this->uploadDir . '/thumbnails');
        $imagePath = $this->createTempImage(100, 'jpg');
        $thumbnailPath = $this->service->generateThumbnail($imagePath, 'image');
        $this->assertFileExists($thumbnailPath);
        \unlink($imagePath);
        @\unlink($thumbnailPath);
    }

    public function testGetMediaInfo_ReturnsInfoForImage(): void
    {
        $imagePath = $this->createTempImage(100, 'jpg');
        $info = $this->service->getMediaInfo($imagePath);

        $this->assertArrayHasKey('size', $info);
        $this->assertArrayHasKey('mime_type', $info);
        $this->assertArrayHasKey('width', $info);
        $this->assertArrayHasKey('height', $info);
        $this->assertArrayHasKey('format', $info);
        $this->assertSame('jpeg', $info['format']);
        \unlink($imagePath);
    }

    public function testGetMediaInfo_ReturnsInfoForVideo(): void
    {
        $videoPath = $this->createTempVideo();
        $info = $this->service->getMediaInfo($videoPath);

        $this->assertArrayHasKey('size', $info);
        $this->assertArrayHasKey('mime_type', $info);
        // Duration may be 0 for fake video (ffprobe fails)
        $this->assertArrayHasKey('duration', $info);
        $this->assertArrayHasKey('format', $info);
        \unlink($videoPath);
    }

    public function testGetMediaInfo_ThrowsExceptionWhenFileNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Media file not found');
        $this->service->getMediaInfo('/nonexistent/file.mp4');
    }

    public function testResizeImage_CreatesThumbnailWithCorrectDimensions(): void
    {
        $imagePath = $this->createTempImage(100, 'jpg');
        // Use reflection to call private resizeImage
        $method = new \ReflectionMethod($this->service, 'resizeImage');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $imagePath);

        $this->assertFileExists($result);
        \unlink($imagePath);
        @\unlink($result);
        // Dimensions are tested via calculateNewDimensions test
    }

    public function testEnsureDirectoryExists_CreatesDirectory(): void
    {
        $testDir = $this->uploadDir . '/test_ensure/' . uniqid();
        $this->assertFalse(\is_dir($testDir));
        
        $method = new \ReflectionMethod($this->service, 'ensureDirectoryExists');
        $method->setAccessible(true);
        $method->invoke($this->service, $testDir);
        
        $this->assertTrue(\is_dir($testDir));
        \rmdir($testDir);
    }

    public function testGetImageFormat_ReturnsCorrectFormat(): void
    {
        $method = new \ReflectionMethod($this->service, 'getImageFormat');
        $method->setAccessible(true);
        $this->assertSame('jpeg', $method->invoke($this->service, 'image/jpeg'));
        $this->assertSame('png', $method->invoke($this->service, 'image/png'));
        $this->assertSame('gif', $method->invoke($this->service, 'image/gif'));
        $this->assertSame('webp', $method->invoke($this->service, 'image/webp'));
        $this->assertSame('unknown', $method->invoke($this->service, 'application/octet-stream'));
    }

    public function testBuildCompressedResult_CalculatesRatioCorrectly(): void
    {
        $method = new \ReflectionMethod($this->service, 'buildCompressedResult');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, 1000, $this->uploadDir . '/dummy.jpg');
        
        $this->assertArrayHasKey('compression_ratio', $result);
        $this->assertIsFloat($result['compression_ratio']);
    }

    public function testBuildUncompressedResult_ReturnsZeroRatio(): void
    {
        $method = new \ReflectionMethod($this->service, 'buildUncompressedResult');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, 1000, 'image/jpeg', '/dummy.jpg');
        
        $this->assertSame(0, $result['compression_ratio']);
        $this->assertSame('/dummy.jpg', $result['path']);
        $this->assertSame(1000, $result['compressed_size']);
    }

    public function testCalculateNewDimensions_MaintainsAspectRatio(): void
    {
        $method = new \ReflectionMethod($this->service, 'calculateNewDimensions');
        $method->setAccessible(true);
        [$w, $h] = $method->invoke($this->service, 400, 300);
        
        $this->assertSame(200, $w);
        $this->assertSame(150, $h); // 300 * (200/400) = 150
    }

    public function testCreateThumbnail_ReturnsValidImageResource(): void
    {
        // Create a mock source image object with width/height
        $src = (object) ['width' => 100, 'height' => 100];
        $method = new \ReflectionMethod($this->service, 'createThumbnail');
        $method->setAccessible(true);
        $thumb = $method->invoke($this->service, $src, 100, 100, 50, 50);
        
        $this->assertIsObject($thumb);
        $this->assertTrue(isset($thumb->width));
        $this->assertTrue(isset($thumb->height));
        $this->assertSame(50, $thumb->width);
        $this->assertSame(50, $thumb->height);
        // No need to destroy; our mock imagedestroy handles cleanup
    }
}

namespace App\Media\Service;
function imagecreatetruecolor($w, $h) {
    return (object) ['width' => $w, 'height' => $h];
}
function imagedestroy($resource) {}
function imagejpeg($resource, $path, $quality = null) {
    $dir = \dirname($path);
    if (!\is_dir($dir)) \mkdir($dir, 0755, true);
    \file_put_contents($path, 'jpeg');
}
function imagepng($resource, $path, $quality = null) {
    $dir = \dirname($path);
    if (!\is_dir($dir)) \mkdir($dir, 0755, true);
    \file_put_contents($path, 'png');
}
function imagegif($resource, $path) {
    $dir = \dirname($path);
    if (!\is_dir($dir)) \mkdir($dir, 0755, true);
    \file_put_contents($path, 'gif');
}
function getimagesize($path) {
    if (str_contains($path, 'bad_')) {
        return false;
    }
    return [100, 100, 3, 'mime' => 'image/jpeg'];
}
function imagesx($resource) {
    return $resource->width ?? 100;
}
function imagesy($resource) {
    return $resource->height ?? 100;
}
function imagecreatefromjpeg($path) {
    return (object)['width'=>100, 'height'=>100];
}
function imagecreatefrompng($path) {
    return (object)['width'=>100, 'height'=>100];
}
function imagecreatefromgif($path) {
    return (object)['width'=>100, 'height'=>100];
}
function imagecreatefromwebp($path) {
    return (object)['width'=>100, 'height'=>100];
}
function imagecolorallocate($img, $r, $g, $b) { return 1; }
function imagefill($img, $x, $y, $color) {}
function imagecopyresampled($dst, $src, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
    return true;
}


function exec($command, &$output = null, &$returnCode = null) {
    if (str_contains($command, 'ffprobe')) {
        $returnCode = 0;
        $output = ['120'];
    } else {
        $returnCode = 1;
        $output = ['ffmpeg error'];
    }
}

function mime_content_type($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $map = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
    ];
    return $map[$ext] ?? 'application/octet-stream';
}
