<?php declare(strict_types=1);

namespace App\Tests\Unit\Media\Service;

use App\Media\Service\FileValidationService;
use PHPUnit\Framework\TestCase;

class FileValidationServiceTest extends TestCase
{
    private FileValidationService $service;

    protected function setUp(): void
    {
        $this->service = new FileValidationService();
    }

    public function testValidateFileSizeWithinLimit(): void
    {
        $this->service->validateFileSize(5000000); // 5MB
        $this->assertTrue(true); // No exception thrown
    }

    public function testValidateFileSizeExceedsLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File size exceeds limit');
        $this->service->validateFileSize(20000000); // 20MB
    }

    public function testValidateFileSizeWithCustomMaxSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File size exceeds limit');
        $this->service->validateFileSize(2000000, 1000000); // 2MB with 1MB limit
    }

    public function testValidateFileSizeAtExactLimit(): void
    {
        $this->service->validateFileSize(10485760); // 10MB exactly
        $this->assertTrue(true);
    }

    public function testValidateFilenameWithValidName(): void
    {
        $this->service->validateFilename('valid_file.jpg');
        $this->assertTrue(true);
    }

    public function testValidateFilenameWithSpaces(): void
    {
        $this->service->validateFilename('valid file name.jpg');
        $this->assertTrue(true);
    }

    public function testValidateFilenameWithSpecialCharsThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filename contains invalid characters');
        $this->service->validateFilename('file@#$.jpg');
    }

    public function testSanitizeFilenameRemovesDangerousChars(): void
    {
        $result = $this->service->sanitizeFilename('file<>:"/\\|?*.jpg');
        $this->assertStringNotContainsString('<', $result);
    }

    public function testSanitizeFilenameRemovesPathTraversal(): void
    {
        $result = $this->service->sanitizeFilename('../etc/passwd');
        $this->assertEquals('__etc_passwd', $result);
    }

    public function testSanitizeFilenameRemovesPhpExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dangerous filename pattern detected');
        $this->service->sanitizeFilename('malicious.php');
    }

    public function testSanitizeFilenameRemovesExeExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dangerous filename pattern detected');
        $this->service->sanitizeFilename('malicious.exe');
    }

    public function testSanitizeFilenameRemovesScriptTags(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dangerous filename pattern detected');
        $this->service->sanitizeFilename('file<script>.jpg');
    }

    public function testGenerateSecureFilenameCreatesUniqueName(): void
    {
        $result1 = $this->service->generateSecureFilename('original.jpg');
        $result2 = $this->service->generateSecureFilename('original.jpg');

        $this->assertStringStartsWith('file_', $result1);
        $this->assertStringEndsWith('.jpg', $result1);
        $this->assertNotEquals($result1, $result2); // Should be unique
    }

    public function testGenerateSecureFilenameSanitizesBasename(): void
    {
        $result = $this->service->generateSecureFilename('file@#$.jpg');
        $this->assertStringNotContainsString('@', $result);
        $this->assertStringEndsWith('.jpg', $result);
    }

    public function testValidateMagicNumberWithNonExistentFile(): void
    {
        $result = $this->service->validateMagicNumber('/non/existent/file.jpg', 'image/jpeg');
        $this->assertFalse($result);
    }

    public function testValidateMagicNumberWithJpegFile(): void
    {
        // Create temp JPEG file with proper magic number
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        // JPEG magic number: FF D8 FF
        file_put_contents($tempFile, "\xFF\xD8\xFF\xE0\x00\x10JFIF");

        $result = $this->service->validateMagicNumber($tempFile, 'image/jpeg');
        unlink($tempFile);

        $this->assertTrue($result);
    }

    public function testValidateMagicNumberWithPngFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        // PNG magic number: 89 50 4E 47
        file_put_contents($tempFile, "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A");

        $result = $this->service->validateMagicNumber($tempFile, 'image/png');
        unlink($tempFile);

        $this->assertTrue($result);
    }

    public function testValidateMagicNumberWithGifFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        // GIF magic number: 47 49 46 38
        file_put_contents($tempFile, "GIF89a");

        $result = $this->service->validateMagicNumber($tempFile, 'image/gif');
        unlink($tempFile);

        $this->assertTrue($result);
    }

    public function testValidateMagicNumberWithPdfFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        // PDF magic number: 25 50 44 46
        file_put_contents($tempFile, "%PDF-1.4");

        $result = $this->service->validateMagicNumber($tempFile, 'application/pdf');
        unlink($tempFile);

        $this->assertTrue($result);
    }

    public function testValidateMagicNumberMismatchReturnsFalse(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        // Write JPEG magic but claim it's PNG
        file_put_contents($tempFile, "\xFF\xD8\xFF\xE0\x00\x10JFIF");

        $result = $this->service->validateMagicNumber($tempFile, 'image/png');
        unlink($tempFile);

        $this->assertFalse($result);
    }

    public function testValidateImageDimensionsWithNonExistentFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File does not exist');
        $this->service->validateImageDimensions('/non/existent/image.jpg');
    }

    public function testValidateImageDimensionsWithValidImage(): void
    {
        // Create a minimal valid 1x1 PNG
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tempFile, $pngData);

        $result = $this->service->validateImageDimensions($tempFile);
        unlink($tempFile);

        $this->assertArrayHasKey('width', $result);
        $this->assertArrayHasKey('height', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('mime', $result);
        $this->assertEquals(1, $result['width']);
        $this->assertEquals(1, $result['height']);
    }

    public function testValidateImageDimensionsExceedsMaxSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Image dimensions exceed maximum allowed size');
        $this->service->validateImageDimensions(__FILE__); // Not an image, will throw
    }
}
