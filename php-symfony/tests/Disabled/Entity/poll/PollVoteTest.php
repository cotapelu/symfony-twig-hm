<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\poll;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Poll\Entity\PollVote;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PollVote entity.
 * 
 * @covers \App\Poll\Entity\PollVote
 */
#[AllowMockObjectsWithoutExpectations]
class PollVoteTest extends TestCase
{
    private PollVote $vote;

    protected function setUp(): void
    {
        $this->vote = new PollVote(10, 5, [0, 1]);
        $this->vote->setId(100);
    }

    public function testConstructor(): void
    {
        $this->assertEquals(10, $this->vote->getPollId());
        $this->assertEquals(5, $this->vote->getUserId());
        $this->assertEquals([0, 1], $this->vote->getSelectedOptions());
        $this->assertInstanceOf(\DateTime::class, $this->vote->getVotedAt());
        $this->assertEquals(100, $this->vote->getId());
    }

    public function testGetters(): void
    {
        $this->vote = new PollVote(1, 2, [0]);
        $this->vote->setId(50);
        $this->assertEquals(1, $this->vote->getPollId());
        $this->assertEquals(2, $this->vote->getUserId());
        $this->assertEquals([0], $this->vote->getSelectedOptions());
        $this->assertEquals(50, $this->vote->getId());
    }

    public function testSetters(): void
    {
        // PollVote only has setId; other properties are immutable after construction
        $this->vote->setId(200);
        $this->assertEquals(200, $this->vote->getId());
    }

    public function testImmutabilityOfSelectedOptions(): void
    {
        $this->vote->getSelectedOptions()[0] = 99; // shouldn't affect internal
        $this->assertEquals([0,1], $this->vote->getSelectedOptions());
    }
}
