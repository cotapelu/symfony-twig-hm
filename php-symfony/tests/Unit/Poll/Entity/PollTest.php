<?php declare(strict_types=1);

namespace App\Tests\Unit\Poll\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Poll\Entity\Poll;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PollTest extends TestCase
{
    public function testConstructor(): void
    {
        $poll = new Poll(
            creatorId: 1,
            question: 'Favorite color?',
            options: ['Red', 'Blue'],
            description: 'Pick one',
            allowMultiple: false,
            expiresAt: new DateTimeImmutable('2025-12-31')
        );

        $this->assertNull($poll->getId()); // id not generated automatically for new entity (can be set later)
        $this->assertSame(1, $poll->getCreatorId());
        $this->assertSame('Favorite color?', $poll->getQuestion());
        $this->assertSame(['Red', 'Blue'], array_column($poll->getOptions(), 'text'));
        $this->assertSame('Pick one', $poll->getDescription());
        $this->assertFalse($poll->getAllowMultiple());
        $this->assertInstanceOf(\DateTimeImmutable::class, $poll->getExpiresAt());
        $this->assertSame(0, $poll->getTotalVotes());
    }

    public function testAddOption(): void
    {
        $poll = new Poll(creatorId: 1, question: 'Test', options: []);
        $poll->addOption('Option A');
        $poll->addOption('Option B');
        $opts = $poll->getOptions();
        $this->assertCount(2, $opts);
        $this->assertSame('Option A', $opts[0]['text']);
        $this->assertSame(0, $opts[0]['votes']);
        $this->assertSame('Option B', $opts[1]['text']);
    }

    public function testIncrementVotesAndVote(): void
    {
        $poll = new Poll(creatorId: 1, question: 'Q', options: ['A', 'B']);
        $poll->incrementVotes(0);
        $poll->incrementVotes(0);
        $poll->incrementVotes(1);
        $votes = $poll->getVotes();
        $this->assertSame([2, 1], $votes);
        $this->assertSame(3, $poll->getTotalVotes());

        // vote() is an alias
        $poll->vote(0);
        $this->assertSame(3, $poll->getVotes()[0]);
        $this->assertSame(4, $poll->getTotalVotes());
    }

    public function testToArray(): void
    {
        $poll = new Poll(
            creatorId: 5,
            question: 'Q?',
            options: ['Opt1', 'Opt2'],
            description: null,
            allowMultiple: true,
            isAnonymous: true,
            conversationId: 'conv-123',
            messageId: 'msg-456'
        );
        $poll->incrementVotes(0);
        $poll->incrementVotes(0);
        $poll->incrementVotes(1);

        $arr = $poll->toArray();

        $this->assertSame('Q?', $arr['question']);
        $this->assertCount(2, $arr['options']);
        $this->assertSame('Opt1', $arr['options'][0]['text']);
        $this->assertSame(2, $arr['options'][0]['votes']);
        $this->assertSame(66.67, $arr['options'][0]['percentage']); // 2/3*100=66.666... rounded
        $this->assertSame(33.33, $arr['options'][1]['percentage']);
        $this->assertSame(5, $arr['created_by']);
        $this->assertSame(3, $arr['total_votes']);
        $this->assertSame([2,1], $arr['votes']);
        $this->assertTrue($arr['is_multi_select']);
        $this->assertTrue($arr['is_anonymous']);
        $this->assertSame('conv-123', $arr['conversation_id']);
        $this->assertSame('msg-456', $arr['message_id']);
        $this->assertNull($arr['description']);
        $this->assertSame('active', $arr['status']);
        $this->assertNull($arr['expires_at']);
    }

    public function testSetMethods(): void
    {
        $poll = new Poll(creatorId: 1, question: 'Q');
        $poll->setId(42);
        $this->assertSame(42, $poll->getId());

        $poll->setDescription('Desc');
        $this->assertSame('Desc', $poll->getDescription());

        $poll->setStatus('closed');
        $this->assertSame('closed', $poll->getStatus());

        $date = new DateTimeImmutable('2026-01-01');
        $poll->setExpiresAt($date);
        $this->assertSame($date, $poll->getExpiresAt());
    }
}
