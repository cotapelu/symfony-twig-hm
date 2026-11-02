<?php declare(strict_types=1);

namespace App\Tests\Unit\Realtime\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Service\TypingIndicatorService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TypingIndicatorServiceTest extends TestCase
{
    private TypingIndicatorService $service;

    protected function setUp(): void
    {
        $this->service = new TypingIndicatorService();
    }

    public function testStartTypingAddsUserToTypingList(): void
    {
        $this->service->startTyping('conv1', 'user1');
        $ref = new \ReflectionProperty($this->service, 'typingUsers');
        $ref->setAccessible(true);
        $typingUsers = $ref->getValue($this->service);
        $this->assertArrayHasKey('conv1', $typingUsers);
        $this->assertArrayHasKey('user1', $typingUsers['conv1']);
    }

    public function testStartTypingDoesNotOverwriteExisting(): void
    {
        $this->service->startTyping('conv1', 'user1');
        $this->service->startTyping('conv1', 'user1'); // call again
        $ref = new \ReflectionProperty($this->service, 'typingUsers');
        $ref->setAccessible(true);
        $typingUsers = $ref->getValue($this->service);
        $this->assertCount(1, $typingUsers['conv1']);
    }

    public function testStopTypingRemovesUser(): void
    {
        $this->service->startTyping('conv1', 'user1');
        $this->service->stopTyping('conv1', 'user1');
        $ref = new \ReflectionProperty($this->service, 'typingUsers');
        $ref->setAccessible(true);
        $typingUsers = $ref->getValue($this->service);
        $this->assertArrayNotHasKey('user1', $typingUsers['conv1'] ?? []);
    }

    public function testStopTypingCleansUpEmptyConversation(): void
    {
        $this->service->startTyping('conv1', 'user1');
        $this->service->stopTyping('conv1', 'user1');
        $ref = new \ReflectionProperty($this->service, 'typingUsers');
        $ref->setAccessible(true);
        $typingUsers = $ref->getValue($this->service);
        $this->assertArrayNotHasKey('conv1', $typingUsers);
    }

    public function testGetTypingUsersReturnsEmptyArrayForUnknownConversation(): void
    {
        $result = $this->service->getTypingUsers('unknown');
        $this->assertEmpty($result);
    }

    public function testGetTypingUsersReturnsTypingUsers(): void
    {
        $this->service->startTyping('conv1', 'user1');
        $this->service->startTyping('conv1', 'user2');
        $result = $this->service->getTypingUsers('conv1');
        $this->assertCount(2, $result);
        $this->assertContains('user1', $result);
        $this->assertContains('user2', $result);
    }

    public function testGetTypingUsersExcludesStaleTypingBeyondTimeout(): void
    {
        // Simulate user1 typed long ago; need to manipulate time via reflection
        $this->service->startTyping('conv1', 'user1');
        $this->service->startTyping('conv1', 'user2');

        // Simulate user1's timestamp being old (more than 10 seconds)
        $ref = new \ReflectionProperty($this->service, 'typingUsers');
        $ref->setAccessible(true);
        $typingUsers = $ref->getValue($this->service);
        $typingUsers['conv1']['user1'] = time() - 11;
        $ref->setValue($this->service, $typingUsers);

        $result = $this->service->getTypingUsers('conv1');
        $this->assertCount(1, $result);
        $this->assertNotContains('user1', $result);
    }

    public function testGetTypingUserIdsReturnsOnlyUserIds(): void
    {
        $this->service->startTyping('conv1', 'user1');
        $this->service->startTyping('conv1', 'user2');
        $result = $this->service->getTypingUsers('conv1');
        $this->assertEqualsCanonicalizing(['user1', 'user2'], $result);
    }
}
