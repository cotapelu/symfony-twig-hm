<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure;

use App\Shared\Infrastructure\ValidationService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ValidationServiceTest extends TestCase
{
    private ValidationService $service;

    protected function setUp(): void
    {
        $this->service = new ValidationService();
    }

    public function testValidateMessageContentWithEmptyString(): void
    {
        $result = $this->service->validateMessageContent('');
        $this->assertCount(1, $result);
        $this->assertContains('Message content cannot be empty', $result);
    }

    public function testValidateMessageContentWithWhitespaceOnly(): void
    {
        $result = $this->service->validateMessageContent('   ');
        $this->assertCount(1, $result);
        $this->assertContains('Message content cannot be empty', $result);
    }

    public function testValidateMessageContentTooLong(): void
    {
        $longContent = str_repeat('a', 1001);
        $result = $this->service->validateMessageContent($longContent);
        $this->assertCount(1, $result);
        $this->assertContains('Message content cannot exceed 1000 characters', $result);
    }

    public function testValidateMessageContentWithMaliciousScript(): void
    {
        $result = $this->service->validateMessageContent('<script>alert("xss")</script>');
        $this->assertCount(1, $result);
        $this->assertContains('Message contains inappropriate content', $result);
    }

    public function testValidateMessageContentWithJavaScriptProtocol(): void
    {
        $result = $this->service->validateMessageContent('javascript:alert(1)');
        $this->assertCount(1, $result);
        $this->assertContains('Message contains inappropriate content', $result);
    }

    public function testValidateMessageContentWithOnEvent(): void
    {
        $result = $this->service->validateMessageContent('<div onclick="evil()">test</div>');
        $this->assertCount(1, $result);
        $this->assertContains('Message contains inappropriate content', $result);
    }

    public function testValidateMessageContentWithEval(): void
    {
        $result = $this->service->validateMessageContent('some text eval(\'bad\') more');
        $this->assertCount(1, $result);
        $this->assertContains('Message contains inappropriate content', $result);
    }

    public function testValidateMessageContentSuccess(): void
    {
        $result = $this->service->validateMessageContent('Hello, world!');
        $this->assertEmpty($result);
    }

    public function testValidateConversationIdEmpty(): void
    {
        $result = $this->service->validateConversationId('');
        // Empty string triggers both required and format errors
        $this->assertCount(2, $result);
        $this->assertContains('Conversation ID is required', $result);
        $this->assertContains('Invalid conversation ID format', $result);
    }

    public function testValidateConversationIdInvalidFormat(): void
    {
        $result = $this->service->validateConversationId('invalid@id!');
        $this->assertCount(1, $result);
        $this->assertContains('Invalid conversation ID format', $result);
    }

    public function testValidateConversationIdValid(): void
    {
        $result = $this->service->validateConversationId('conv_123-abc');
        $this->assertEmpty($result);
    }

    public function testValidateUserCredentialsEmptyEmail(): void
    {
        $result = $this->service->validateUserCredentials('', 'password123');
        $this->assertCount(1, $result);
        $this->assertContains('Valid email is required', $result);
    }

    public function testValidateUserCredentialsInvalidEmail(): void
    {
        $result = $this->service->validateUserCredentials('notanemail', 'password123');
        $this->assertCount(1, $result);
        $this->assertContains('Valid email is required', $result);
    }

    public function testValidateUserCredentialsShortPassword(): void
    {
        $result = $this->service->validateUserCredentials('user@example.com', 'short');
        $this->assertCount(1, $result);
        $this->assertContains('Password must be at least 8 characters long', $result);
    }

    public function testValidateUserCredentialsValid(): void
    {
        $result = $this->service->validateUserCredentials('user@example.com', 'longenoughpassword');
        $this->assertEmpty($result);
    }

    public function testValidateGroupDataEmptyTitle(): void
    {
        $result = $this->service->validateGroupData('', ['user1']);
        $this->assertCount(1, $result);
        $this->assertContains('Group title is required', $result);
    }

    public function testValidateGroupDataTitleTooLong(): void
    {
        $title = str_repeat('a', 101);
        $result = $this->service->validateGroupData($title, ['user1']);
        $this->assertCount(1, $result);
        $this->assertContains('Group title cannot exceed 100 characters', $result);
    }

    public function testValidateGroupDataNoParticipants(): void
    {
        $result = $this->service->validateGroupData('Group', []);
        $this->assertCount(1, $result);
        $this->assertContains('Group must have at least one participant', $result);
    }

    public function testValidateGroupDataTooManyParticipants(): void
    {
        $participants = range(1, 1001); // 1001 IDs
        $result = $this->service->validateGroupData('Group', $participants);
        $this->assertCount(1, $result);
        $this->assertContains('Group cannot have more than 1000 participants', $result);
    }

    public function testValidateGroupDataSuccess(): void
    {
        $result = $this->service->validateGroupData('My Group', ['user1', 'user2']);
        $this->assertEmpty($result);
    }

    public function testValidateFileUploadWithEmptyArray(): void
    {
        // Empty file array should trigger validation errors from factory
        $result = $this->service->validateFileUpload([]);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result); // Should have errors
    }

    public function testValidateFileUploadWithInvalidFile(): void
    {
        // Missing required keys
        $result = $this->service->validateFileUpload(['name' => 'test.txt']);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result); // Should have errors
    }
}
