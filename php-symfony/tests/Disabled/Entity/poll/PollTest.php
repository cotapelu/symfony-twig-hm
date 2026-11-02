<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\poll;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Poll\Entity\Poll;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Poll entity.
 * 
 * @covers \App\Poll\Entity\Poll
 */
#[AllowMockObjectsWithoutExpectations]
class PollTest extends TestCase
{
    private Poll $poll;

    protected function setUp(): void
    {
        $this->poll = new Poll(1, 'Test poll?');
        $this->poll->setId(1);
    }

    public function testConstructor(): void
    {
        $this->assertEquals(1, $this->poll->getCreatorId());
        $this->assertEquals('Test poll?', $this->poll->getQuestion());
        $this->assertEquals([], $this->poll->getOptions());
        $this->assertNull($this->poll->getDescription());
        $this->assertEquals('active', $this->poll->getStatus());
        $this->assertFalse($this->poll->allowMultiple());
        $this->assertNull($this->poll->getExpiresAt());
        $this->assertEquals(0, $this->poll->getTotalVotes());
        $this->assertInstanceOf(\DateTime::class, $this->poll->getCreatedAt());
        $this->assertEquals(1, $this->poll->getId());
    }

    public function testAddOption(): void
    {
        $this->poll->addOption('Option A');
        $this->poll->addOption('Option B');
        $opts = $this->poll->getOptions();
        $this->assertCount(2, $opts);
        $this->assertEquals('Option A', $opts[0]['text']);
        $this->assertEquals(0, $opts[0]['votes']);
    }

    public function testSetQuestion(): void
    {
        $this->poll->setQuestion('New?');
        $this->assertEquals('New?', $this->poll->getQuestion());
    }

    public function testSetOptions(): void
    {
        $this->poll->setOptions([['text' => 'A', 'votes' => 5], ['text' => 'B', 'votes' => 3]]);
        $this->assertEquals(5, $this->poll->getOptions()[0]['votes']);
    }

    public function testSetDescription(): void
    {
        $this->poll->setDescription('Desc');
        $this->assertEquals('Desc', $this->poll->getDescription());
        $this->poll->setDescription(null);
        $this->assertNull($this->poll->getDescription());
    }

    public function testSetStatus(): void
    {
        $this->poll->setStatus('closed');
        $this->assertEquals('closed', $this->poll->getStatus());
    }

    public function testSetAllowMultiple(): void
    {
        $this->poll->setAllowMultiple(true);
        $this->assertTrue($this->poll->allowMultiple());
    }

    public function testSetExpiresAt(): void
    {
        $date = new \DateTime('2025-12-31');
        $this->poll->setExpiresAt($date);
        $this->assertEquals($date, $this->poll->getExpiresAt());
        $this->poll->setExpiresAt(null);
        $this->assertNull($this->poll->getExpiresAt());
    }

    public function testIncrementVotes(): void
    {
        $this->poll->addOption('A');
        $this->poll->addOption('B');
        $this->poll->incrementVotes(0);
        $this->poll->incrementVotes(0);
        $this->poll->incrementVotes(1);
        $this->assertEquals(2, $this->poll->getOptions()[0]['votes']);
        $this->assertEquals(1, $this->poll->getOptions()[1]['votes']);
        $this->assertEquals(3, $this->poll->getTotalVotes());
    }

    public function testIncrementVotesInvalidIndex(): void
    {
        $this->poll->addOption('A');
        $this->poll->incrementVotes(5); // invalid
        $this->assertEquals(0, $this->poll->getTotalVotes());
    }

    public function testSettersReturnSelf(): void
    {
        $result = $this->poll->setQuestion('Q')->setDescription('D')->setStatus('closed');
        $this->assertSame($this->poll, $result);
    }
}
