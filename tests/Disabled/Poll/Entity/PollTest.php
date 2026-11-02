<?php declare(strict_types=1);

namespace App\Tests\Unit\Poll\Entity;

use App\Poll\Entity\Poll;
use App\Poll\Entity\PollVote;
use PHPUnit\Framework\TestCase;

class PollTest extends TestCase
{
    public function testPollCreationWithBasicOptions(): void
    {
        $poll = new Poll(
            'poll-1',
            'What is your favorite programming language?',
            ['Python', 'JavaScript', 'Go', 'Rust'],
            'user-1'
        );

        $this->assertEquals('poll-1', $poll->getId());
        $this->assertEquals('What is your favorite programming language?', $poll->getQuestion());
        $this->assertEquals(['Python', 'JavaScript', 'Go', 'Rust'], $poll->getOptions());
        $this->assertEquals('user-1', $poll->getCreatedBy());
    }

    public function testPollCreationInitializesVotesToZero(): void
    {
        $poll = new Poll(
            'poll-1',
            'Question?',
            ['Option A', 'Option B', 'Option C'],
            'user-1'
        );

        $this->assertEquals([0, 0, 0], $poll->getVotes());
    }

    public function testPollWithConversationAndMessageId(): void
    {
        $poll = new Poll(
            'poll-1',
            'Quick poll?',
            ['Yes', 'No'],
            'user-1',
            'conv-123',
            'msg-456'
        );

        $this->assertEquals('conv-123', $poll->getConversationId());
        $this->assertEquals('msg-456', $poll->getMessageId());
    }

    public function testPollWithExpirationDate(): void
    {
        $expiresAt = new \DateTime('2026-03-01 00:00:00');
        
        $poll = new Poll(
            'poll-1',
            'Question?',
            ['A', 'B'],
            'user-1',
            null,
            null,
            false,
            false,
            $expiresAt
        );

        $this->assertEquals($expiresAt, $poll->getExpiresAt());
    }

    public function testPollMultiSelectEnabled(): void
    {
        $poll = new Poll(
            'poll-1',
            'Select all that apply?',
            ['A', 'B', 'C'],
            'user-1',
            null,
            null,
            true  // isMultiSelect
        );

        $this->assertTrue($poll->isMultiSelect());
    }

    public function testPollAnonymousEnabled(): void
    {
        $poll = new Poll(
            'poll-1',
            'Anonymous poll?',
            ['Yes', 'No'],
            'user-1',
            null,
            null,
            false,
            true  // isAnonymous
        );

        $this->assertTrue($poll->isAnonymous());
    }

    public function testVoteIncrementsOptionCount(): void
    {
        $poll = new Poll(
            'poll-1',
            'Question?',
            ['Option A', 'Option B'],
            'user-1'
        );

        $poll->vote(0);
        $poll->vote(0);
        $poll->vote(1);

        $this->assertEquals([2, 1], $poll->getVotes());
    }

    public function testVoteIncrementsTotalVotes(): void
    {
        $poll = new Poll(
            'poll-1',
            'Question?',
            ['A', 'B', 'C'],
            'user-1'
        );

        $this->assertEquals(0, $poll->getTotalVotes());

        $poll->vote(0);
        $this->assertEquals(1, $poll->getTotalVotes());

        $poll->vote(1);
        $this->assertEquals(2, $poll->getTotalVotes());
    }

    public function testVoteWithInvalidIndexThrowsException(): void
    {
        $poll = new Poll(
            'poll-1',
            'Question?',
            ['A', 'B'],
            'user-1'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid option index');
        $poll->vote(5);
    }

    public function testVoteWithNegativeIndexThrowsException(): void
    {
        $poll = new Poll(
            'poll-1',
            'Question?',
            ['A', 'B'],
            'user-1'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid option index');
        $poll->vote(-1);
    }

    public function testToArrayReturnsCorrectFormat(): void
    {
        $poll = new Poll(
            'poll-1',
            'Test Question?',
            ['Yes', 'No'],
            'user-1',
            'conv-1',
            'msg-1',
            false,
            true,
            null
        );

        $poll->vote(0);

        $arr = $poll->toArray();

        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('question', $arr);
        $this->assertArrayHasKey('options', $arr);
        $this->assertArrayHasKey('votes', $arr);
        $this->assertArrayHasKey('created_by', $arr);
        $this->assertArrayHasKey('is_multi_select', $arr);
        $this->assertArrayHasKey('is_anonymous', $arr);
        $this->assertArrayHasKey('total_votes', $arr);
    }
}

class PollVoteTest extends TestCase
{
    public function testPollVoteCreation(): void
    {
        $vote = new PollVote(
            'vote-1',
            'poll-1',
            'user-1',
            0
        );

        $this->assertEquals('vote-1', $vote->getId());
        $this->assertEquals('poll-1', $vote->getPollId());
        $this->assertEquals('user-1', $vote->getUserId());
        $this->assertEquals(0, $vote->getOptionIndex());
    }

    public function testPollVoteWithMultipleOptions(): void
    {
        $vote = new PollVote(
            'vote-1',
            'poll-1',
            'user-1',
            [0, 2]  // Multi-select
        );

        $this->assertEquals([0, 2], $vote->getOptionIndex());
    }

    public function testPollVoteTimestamp(): void
    {
        $vote = new PollVote(
            'vote-1',
            'poll-1',
            'user-1',
            0
        );

        $this->assertInstanceOf(\DateTime::class, $vote->getVotedAt());
    }
}
