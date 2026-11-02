<?php declare(strict_types=1);

namespace App\Tests\Unit\Notification\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Notification\Service\NewMessageGenerator;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NewMessageGeneratorTest extends TestCase
{
    private NewMessageGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new NewMessageGenerator();
    }

    public function testCanHandleReturnsTrueForNewMessage(): void
    {
        $this->assertTrue($this->generator->canHandle('new_message'));
    }

    public function testCanHandleReturnsFalseForOtherTypes(): void
    {
        $this->assertFalse($this->generator->canHandle('other_type'));
        $this->assertFalse($this->generator->canHandle(''));
    }

    public function testGenerateBodyReturnsMessagePreviewWhenProvided(): void
    {
        $this->assertSame('Hello there', $this->generator->generateBody(['message_preview' => 'Hello there']));
    }

    public function testGenerateBodyReturnsDefaultWhenMissing(): void
    {
        $this->assertSame('You have a new message', $this->generator->generateBody([]));
        $this->assertSame('You have a new message', $this->generator->generateBody(['other' => 'data']));
    }

    public function testGenerateBodyIgnoresExtraKeys(): void
    {
        $result = $this->generator->generateBody(['message_preview' => 'Hi', 'extra' => 'ignore']);
        $this->assertSame('Hi', $result);
    }
}
