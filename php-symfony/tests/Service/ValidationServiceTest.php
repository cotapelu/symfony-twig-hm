<?php

declare(strict_types=1);

namespace App\Tests\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Infrastructure\ValidationService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ValidationServiceTest extends TestCase
{
    private ValidationService $validationService;

    protected function setUp(): void
    {
        $this->validationService = new ValidationService();
    }

    public function testValidateMessageContentValid(): void
    {
        $errors = $this->validationService->validateMessageContent('Hello World');
        $this->assertEmpty($errors);
    }

    public function testValidateMessageContentEmpty(): void
    {
        $errors = $this->validationService->validateMessageContent('');
        $this->assertContains('Message content cannot be empty', $errors);
    }

    public function testValidateMessageContentTooLong(): void
    {
        $longContent = str_repeat('a', 1001);
        $errors = $this->validationService->validateMessageContent($longContent);
        $this->assertContains('Message content cannot exceed 1000 characters', $errors);
    }

    public function testValidateMessageContentMalicious(): void
    {
        $errors = $this->validationService->validateMessageContent('<script>alert("xss")</script>');
        $this->assertContains('Message contains inappropriate content', $errors);
    }

    public function testValidateConversationIdValid(): void
    {
        $errors = $this->validationService->validateConversationId('conv_123');
        $this->assertEmpty($errors);
    }

    public function testValidateConversationIdEmpty(): void
    {
        $errors = $this->validationService->validateConversationId('');
        $this->assertContains('Conversation ID is required', $errors);
    }

    public function testValidateConversationIdInvalidFormat(): void
    {
        $errors = $this->validationService->validateConversationId('conv@123');
        $this->assertContains('Invalid conversation ID format', $errors);
    }

    public function testValidateFileUploadValid(): void
    {
        $file = [
            'name' => 'test.jpg',
            'size' => 1024,
            'type' => 'image/jpeg'
        ];
        $errors = $this->validationService->validateFileUpload($file);
        $this->assertEmpty($errors);
    }

    public function testValidateFileUploadTooLarge(): void
    {
        $file = [
            'name' => 'test.jpg',
            'size' => 15 * 1024 * 1024, // 15MB
            'type' => 'image/jpeg'
        ];
        $errors = $this->validationService->validateFileUpload($file);
        $this->assertContains('Image file size cannot exceed 10MB', $errors);
    }

    public function testValidateFileUploadInvalidType(): void
    {
        $file = [
            'name' => 'test.exe',
            'size' => 1024,
            'type' => 'application/x-executable'
        ];
        $errors = $this->validationService->validateFileUpload($file);
        $this->assertContains('File type not supported: application/x-executable', $errors);
    }

    public function testValidateFileUploadMaliciousFilename(): void
    {
        $file = [
            'name' => '../../../etc/passwd',
            'size' => 1024,
            'type' => 'text/plain'
        ];
        $errors = $this->validationService->validateFileUpload($file);
        // This should be handled by DocumentFileValidator now
        $this->assertContains('Document file appears to be potentially dangerous', $errors);
    }

    public function testValidateUserCredentialsValid(): void
    {
        $errors = $this->validationService->validateUserCredentials('test@example.com', 'password123');
        $this->assertEmpty($errors);
    }

    public function testValidateUserCredentialsInvalidEmail(): void
    {
        $errors = $this->validationService->validateUserCredentials('invalid-email', 'password123');
        $this->assertContains('Valid email is required', $errors);
    }

    public function testValidateUserCredentialsShortPassword(): void
    {
        $errors = $this->validationService->validateUserCredentials('test@example.com', 'short');
        $this->assertContains('Password must be at least 8 characters long', $errors);
    }

    public function testValidateGroupDataValid(): void
    {
        $errors = $this->validationService->validateGroupData('My Group', ['user1', 'user2']);
        $this->assertEmpty($errors);
    }

    public function testValidateGroupDataEmptyTitle(): void
    {
        $errors = $this->validationService->validateGroupData('', ['user1']);
        $this->assertContains('Group title is required', $errors);
    }

    public function testValidateGroupDataNoParticipants(): void
    {
        $errors = $this->validationService->validateGroupData('My Group', []);
        $this->assertContains('Group must have at least one participant', $errors);
    }
}