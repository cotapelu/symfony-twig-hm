<?php declare(strict_types=1);

namespace App\Tests\Unit\Notification\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Notification\Service\ReactionAddedGenerator;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ReactionAddedGeneratorTest extends TestCase
{
    private ReactionAddedGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ReactionAddedGenerator();
    }

    public function testCanHandleReturnsTrueForReactionAdded(): void
    {
        $this->assertTrue($this->generator->canHandle('reaction_added'));
    }

    public function testCanHandleReturnsFalseForOtherTypes(): void
    {
        $this->assertFalse($this->generator->canHandle('new_message'));
        $this->assertFalse($this->generator->canHandle(''));
    }

    public function testGenerateBodyIncludesEmojiWhenProvided(): void
    {
        $this->assertSame('Someone reacted to your message with 😀', $this->generator->generateBody(['emoji' => '😀']));
    }

    public function testGenerateBodyUsesDefaultWhenEmojiMissing(): void
    {
        $this->assertSame('Someone reacted to your message with an emoji', $this->generator->generateBody([]));
        $this->assertSame('Someone reacted to your message with an emoji', $this->generator->generateBody(['other' => 'data']));
    }
}
