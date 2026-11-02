<?php

declare(strict_types=1);

namespace Tests\Unit\Message\Service;

use App\Message\Service\AIService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AIServicePrivateTest extends TestCase
{
    private AIService $service;

    protected function setUp(): void
    {
        // Instantiate without invoking constructor via reflection
        $reflection = new \ReflectionClass(AIService::class);
        $this->service = $reflection->newInstanceWithoutConstructor();
    }

    private function invokePrivate(string $method, array $args = [])
    {
        $ref = new ReflectionMethod($this->service, $method);
        $ref->setAccessible(true);
        return $ref->invoke($this->service, ...$args);
    }

    // ==================== buildSmartRepliesPrompt ====================

    public function testBuildSmartRepliesPrompt_ShortPrompt(): void
    {
        $result = $this->invokePrivate('buildSmartRepliesPrompt', ['Hi there', []]);
        $this->assertStringStartsWith('Generate 3 smart reply suggestions for this message: "Hi there"', $result);
        $this->assertStringContainsString('Keep each reply under 100 characters.', $result);
        $this->assertLessThanOrEqual(4000, strlen($result));
    }

    public function testBuildSmartRepliesPrompt_WithContext(): void
    {
        $context = ['Previous msg 1', 'Previous msg 2'];
        $result = $this->invokePrivate('buildSmartRepliesPrompt', ['Hello', $context]);
        $this->assertStringContainsString("Conversation context:\nPrevious msg 1\nPrevious msg 2", $result);
    }

    public function testBuildSmartRepliesPrompt_LongPrompt_Truncated(): void
    {
        // Create a message and context that together exceed 4000 chars
        $longMsg = str_repeat('A', 3000);
        $longContext = [str_repeat('B', 1000), str_repeat('C', 1000)];
        $result = $this->invokePrivate('buildSmartRepliesPrompt', [$longMsg, $longContext]);
        // After >4000, it truncates to 4000 chars and appends '...' => 4003
        $this->assertLessThanOrEqual(4003, strlen($result));
        $this->assertTrue(str_ends_with($result, '...'), 'Prompt should end with ellipsis');
    }

    public function testBuildSmartRepliesPrompt_ContextTrimmedToLastThree(): void
    {
        $manyContext = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6'];
        $result = $this->invokePrivate('buildSmartRepliesPrompt', ['Msg', $manyContext]);
        // Should take last 3 of slice -5 then last 3, so c4,c5,c6? Actually array_slice($conversationContext, -5) then -3 for context. With [-5] gives last 5: c2-c6; then slice -3 gives last 3 of that: c4,c5,c6. So expected c4,c5,c6.
        $this->assertStringContainsString("c4", $result);
        $this->assertStringContainsString("c5", $result);
        $this->assertStringContainsString("c6", $result);
        $this->assertStringNotContainsString("c1", $result);
    }

    // ==================== processSmartRepliesResponse ====================

    public function testProcessSmartRepliesResponse_ValidChoices_ReturnsTrimmedReplies(): void
    {
        $response = [
            'choices' => [
                ['message' => ['content' => 'Reply 1']],
                ['message' => ['content' => 'Reply 2']],
                ['message' => ['content' => 'Reply 3']],
            ]
        ];
        $result = $this->invokePrivate('processSmartRepliesResponse', [$response]);
        $this->assertCount(3, $result);
        $this->assertSame('Reply 1', $result[0]);
        $this->assertSame('Reply 2', $result[1]);
        $this->assertSame('Reply 3', $result[2]);
    }

    public function testProcessSmartRepliesResponse_EmptyChoices_FallbackToMock(): void
    {
        $response = ['choices' => []];
        $result = $this->invokePrivate('processSmartRepliesResponse', [$response]);
        $this->assertIsArray($result);
        $this->assertCount(3, $result); // mock returns exactly 3
    }

    public function testProcessSmartRepliesResponse_MissingContent_FilteredOut(): void
    {
        $response = [
            'choices' => [
                ['message' => ['content' => null]],
                ['message' => ['content' => '']],
                ['message' => []], // no content
                ['message' => ['content' => 'Valid']],
            ]
        ];
        $result = $this->invokePrivate('processSmartRepliesResponse', [$response]);
        // Only 'Valid' passes filters; since at least one valid, mock not invoked
        $this->assertCount(1, $result);
        $this->assertSame('Valid', $result[0]);
    }

    public function testProcessSmartRepliesResponse_LongReplies_Over100Chars_FilteredOut(): void
    {
        $response = [
            'choices' => [
                ['message' => ['content' => str_repeat('a', 101)]],
                ['message' => ['content' => 'Short']],
            ]
        ];
        $result = $this->invokePrivate('processSmartRepliesResponse', [$response]);
        $this->assertContains('Short', $result);
        $this->assertNotContains(str_repeat('a', 101), $result);
        // Only 'Short' passes filter; mock not invoked because one valid exists
        $this->assertCount(1, $result);
    }

    public function testProcessSmartRepliesResponse_MoreThanThreeChoices_Truncated(): void
    {
        $response = [
            'choices' => [
                ['message' => ['content' => 'One']],
                ['message' => ['content' => 'Two']],
                ['message' => ['content' => 'Three']],
                ['message' => ['content' => 'Four']],
            ]
        ];
        $result = $this->invokePrivate('processSmartRepliesResponse', [$response]);
        $this->assertCount(3, $result);
        $this->assertNotContains('Four', $result);
    }

    // ==================== getMockSmartReplies ====================

    public function testGetMockSmartReplies_ReturnsThreeReplies(): void
    {
        $result = $this->invokePrivate('getMockSmartReplies');
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        foreach ($result as $reply) {
            $this->assertIsString($reply);
            $this->assertNotEmpty($reply);
            $this->assertLessThanOrEqual(100, strlen($reply));
        }
    }


}
