<?php

declare(strict_types=1);

namespace App\Tests\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Poll\Entity\Poll;
use App\Poll\Entity\PollVote;
use App\Poll\Service\PollService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PollService.
 * 
 * @covers \App\Poll\Service\PollService
 */
#[AllowMockObjectsWithoutExpectations]
class PollServiceTest extends TestCase
{
    private PollService $service;

    protected function setUp(): void
    {
        $this->service = new PollService();
    }

    public function testCreatePoll(): void
    {
        $poll = $this->service->createPoll(1, 'Favorite color?', ['Red', 'Blue'], 'Choose one');

        $this->assertInstanceOf(Poll::class, $poll);
        $this->assertEquals(1, $poll->getCreatorId());
        $this->assertEquals('Favorite color?', $poll->getQuestion());
        $this->assertEquals(['Red', 'Blue'], array_column($poll->getOptions(), 'text'));
        $this->assertEquals('Choose one', $poll->getDescription());
        $this->assertEquals('active', $poll->getStatus());
        $this->assertFalse($poll->allowMultiple());
    }

    public function testGetPollFound(): void
    {
        $poll = $this->service->createPoll(1, 'Q', ['A']);
        $retrieved = $this->service->getPoll($poll->getId());
        $this->assertSame($poll, $retrieved);
    }

    public function testGetPollNotFound(): void
    {
        $this->assertNull($this->service->getPoll(999));
    }

    public function testGetActivePolls(): void
    {
        $p1 = $this->service->createPoll(1, 'A', ['1']);
        $p2 = $this->service->createPoll(2, 'B', ['2']);
        $p2->setStatus('closed');
        $active = $this->service->getActivePolls();
        $this->assertCount(1, $active);
        $this->assertEquals($p1, $active[0]);
    }

    public function testGetCreatorPolls(): void
    {
        $this->service->createPoll(1, 'Q1', ['A']);
        $this->service->createPoll(2, 'Q2', ['B']);
        $this->service->createPoll(1, 'Q3', ['C']);

        $creator1 = $this->service->getCreatorPolls(1);
        $this->assertCount(2, $creator1);
    }

    public function testUpdatePoll(): void
    {
        $poll = $this->service->createPoll(1, 'Orig', ['A']);
        $updated = $this->service->updatePoll($poll->getId(), [
            'question' => 'Updated Q',
            'description' => 'New desc',
            'status' => 'closed',
            'allow_multiple' => true,
            'expires_at' => '2100-12-31'
        ], 1);

        $this->assertEquals('Updated Q', $updated->getQuestion());
        $this->assertEquals('New desc', $updated->getDescription());
        $this->assertEquals('closed', $updated->getStatus());
        $this->assertTrue($updated->allowMultiple());
        $this->assertNotNull($updated->getExpiresAt());
    }

    public function testUpdatePollNotFound(): void
    {
        $this->assertNull($this->service->updatePoll(999, ['question' => 'x'], 1));
    }

    public function testDeletePoll(): void
    {
        $poll = $this->service->createPoll(1, 'Q', ['A']);
        $this->assertTrue($this->service->deletePoll($poll->getId(), 1));
        $this->assertNull($this->service->getPoll($poll->getId()));
    }

    public function testDeletePollNotFound(): void
    {
        $this->assertFalse($this->service->deletePoll(999, 1));
    }

    public function testVote(): void
    {
        $poll = $this->service->createPoll(1, 'Q', ['A', 'B']);
        $vote = $this->service->vote($poll->getId(), '2', [0]);

        $this->assertInstanceOf(PollVote::class, $vote);
        $this->assertEquals($poll->getId(), $vote->getPollId());
        $this->assertEquals('2', $vote->getUserId());
        $this->assertEquals([0], $vote->getSelectedOptions());

        // Check vote count
        $this->assertEquals(1, $poll->getTotalVotes());
        $this->assertEquals(1, $poll->getOptions()[0]['votes']);
    }

    public function testVoteFailsIfPollInactive(): void
    {
        $poll = $this->service->createPoll(1, 'Q', ['A']);
        $poll->setStatus('closed');
        $this->assertNull($this->service->vote($poll->getId(), '2', [0]));
    }

    public function testVoteFailsIfAlreadyVoted(): void
    {
        $poll = $this->service->createPoll(1, 'Q', ['A']);
        $this->service->vote($poll->getId(), '2', [0]);
        $this->assertNull($this->service->vote($poll->getId(), '2', [1]));
        // Still only one vote
        $this->assertEquals(1, $poll->getTotalVotes());
    }

    public function testHasVoted(): void
    {
        $poll = $this->service->createPoll(1, 'Q', ['A']);
        $this->assertFalse($this->service->hasVoted($poll->getId(), '2'));
        $this->service->vote($poll->getId(), '2', [0]);
        $this->assertTrue($this->service->hasVoted($poll->getId(), '2'));
    }

    public function testGetUserVote(): void
    {
        $poll = $this->service->createPoll(1, 'Q', ['A']);
        $this->assertNull($this->service->getUserVote($poll->getId(), '2'));
        $vote = $this->service->vote($poll->getId(), '2', [0]);
        $retrieved = $this->service->getUserVote($poll->getId(), '2');
        $this->assertEquals($vote, $retrieved);
    }

    public function testGetPollResults(): void
    {
        $poll = $this->service->createPoll(1, 'Q', ['A', 'B']);
        $this->service->vote($poll->getId(), '2', [0]);
        $this->service->vote($poll->getId(), '3', [1]);
        $this->service->vote($poll->getId(), '4', [0]);

        $results = $this->service->getPollResults($poll->getId());
        $this->assertEquals($poll, $results['poll']);
        $this->assertEquals(3, $results['total_votes']);
        // Check percentages
        $optionA = $results['options'][0];
        $this->assertEquals(0, $optionA['index']);
        $this->assertEquals(2, $optionA['votes']);
        $this->assertEquals(66.67, $optionA['percentage']); // 2/3 *100
    }

    public function testGetPollResultsWithNoVotes(): void
    {
        $poll = $this->service->createPoll(1, 'Q', ['A']);
        $results = $this->service->getPollResults($poll->getId());
        $this->assertEquals(0, $results['total_votes']);
        $this->assertEquals(0.0, $results['options'][0]['percentage']);
    }

    public function testGetPollResultsNotFound(): void
    {
        $this->assertNull($this->service->getPollResults(999));
    }
}