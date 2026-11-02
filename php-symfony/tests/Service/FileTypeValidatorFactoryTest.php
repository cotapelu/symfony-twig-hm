<?php

declare(strict_types=1);

namespace App\Tests\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Media\Validator\FileTypeValidatorFactory;
use App\Media\Validator\ImageFileValidator;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FileTypeValidatorFactoryTest extends TestCase
{
    private FileTypeValidatorFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new FileTypeValidatorFactory();
    }

    public function testFactoryRegistersDefaultValidators(): void
    {
        $validators = $this->factory->getAllValidators();
        $this->assertCount(3, $validators); // Image, Document, and Video validators
    }

    public function testCanValidateSupportedTypes(): void
    {
        $this->assertTrue($this->factory->isSupported('image/jpeg'));
        $this->assertTrue($this->factory->isSupported('application/pdf'));
        $this->assertTrue($this->factory->isSupported('video/mp4')); // Now supported
        $this->assertFalse($this->factory->isSupported('audio/mp3')); // Not supported
    }

    public function testGetValidatorReturnsCorrectValidator(): void
    {
        $validator = $this->factory->getValidator('image/jpeg');
        $this->assertInstanceOf(ImageFileValidator::class, $validator);
        $this->assertTrue($validator->canValidate('image/jpeg'));
    }

    public function testValidateFileWithValidImage(): void
    {
        $file = [
            'name' => 'test.jpg',
            'size' => 1024,
            'type' => 'image/jpeg'
        ];

        $errors = $this->factory->validateFile($file);
        $this->assertEmpty($errors);
    }

    public function testValidateFileWithInvalidType(): void
    {
        $file = [
            'name' => 'test.unknown',
            'size' => 1024,
            'type' => 'application/unknown'
        ];

        $errors = $this->factory->validateFile($file);
        $this->assertContains('File type not supported: application/unknown', $errors);
    }

    public function testCanAddCustomValidator(): void
    {
        // Create a mock validator for video files
        $videoValidator = new class implements \App\Media\Validator\FileTypeValidatorInterface {
            public function canValidate(string $mimeType): bool {
                return str_starts_with($mimeType, 'video/');
            }
            public function validate(array $file): array {
                return [];
            }
            public function getTypeName(): string {
                return 'video';
            }
        };

        $this->factory->registerValidator($videoValidator);

        $this->assertTrue($this->factory->isSupported('video/mp4'));
        $this->assertCount(4, $this->factory->getAllValidators()); // Now 4 validators (including the new one)
    }
}