<?php declare(strict_types=1);

namespace App\Tests\Unit\Notification\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Notification\Service\ParticipantRemovedGenerator;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ParticipantRemovedGeneratorTest extends TestCase
{
    private ParticipantRemovedGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ParticipantRemovedGenerator();
    }

    public function testCanHandleReturnsTrueForParticipantRemoved(): void
    {
        $this->assertTrue($this->generator->canHandle('participant_removed'));
    }

    public function testCanHandleReturnsFalseForOtherTypes(): void
    {
        $this->assertFalse($this->generator->canHandle('new_message'));
        $this->assertFalse($this->generator->canHandle('reaction_added'));
    }

    public function testGenerateBodyReturnsConstantMessage(): void
    {
        $this->assertSame('You were removed from a conversation', $this->generator->generateBody([]));
        $this->assertSame('You were removed from a conversation', $this->generator->generateBody(['any' => 'data']));
    }
}
