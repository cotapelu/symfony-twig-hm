<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Poll\Entity\Poll;
use App\Poll\Entity\PollVote;
use PHPUnit\Framework\TestCase;

/**
 * Poll Module Integration Test
 * 
 * Tests the Poll module as a real system.
 */
#[AllowMockObjectsWithoutExpectations]
class PollIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }
    public function testCompletePollWorkflow(): void
    {
        // Create poll
        $poll = new Poll(
            id: 1,
            question: 'What is your favorite programming language?',
            options: ['PHP', 'Python', 'Go', 'Rust', 'TypeScript'],
            creatorId: 1,
            conversationId: 'conv-123',
            messageId: 'msg-456',
            allowMultiple: false,
            isAnonymous: false
        );

        // Verify initial state
        $this->assertEquals(0, $poll->getTotalVotes());
        $this->assertEquals([0, 0, 0, 0, 0], $poll->getVotes());

        // User 1 votes for PHP
        $poll->vote(0);
        $this->assertEquals(1, $poll->getTotalVotes());

        // User 2 votes for Python
        $poll->vote(1);
        $this->assertEquals(2, $poll->getTotalVotes());

        // User 3 votes for Go
        $poll->vote(2);
        $this->assertEquals(3, $poll->getTotalVotes());

        // User 4 votes for PHP
        $poll->vote(0);
        $this->assertEquals(4, $poll->getTotalVotes());

        // Verify final votes
        $this->assertEquals([2, 1, 1, 0, 0], $poll->getVotes());

        // Get results
        $results = $poll->toArray();
        $this->assertEquals(4, $results['total_votes']);
    }

    public function testMultiSelectPollWorkflow(): void
    {
        $poll = new Poll(
            id: 1,
            question: 'Which languages do you use?',
            options: ['PHP', 'Python', 'Go', 'Rust'],
            creatorId: 1,
            allowMultiple: true
        );

        // User votes for multiple options
        $poll->vote(0); // PHP
        $poll->vote(2); // Rust

        $this->assertEquals(2, $poll->getTotalVotes());
        $this->assertEquals([1, 0, 1, 0], $poll->getVotes());
        $this->assertTrue($poll->isMultiSelect());
    }

    public function testAnonymousPoll(): void
    {
        $poll = new Poll(
            id: 1,
            question: 'Secret vote?',
            options: ['Yes', 'No'],
            creatorId: 1,
            isAnonymous: true
        );

        $poll->vote(0);
        $poll->vote(1);

        $this->assertTrue($poll->isAnonymous());
        
        $arr = $poll->toArray();
        $this->assertTrue($arr['is_anonymous']);
    }

    public function testPollWithExpiration(): void
    {
        $expiresAt = new \DateTime('2026-12-31 23:59:59');
        
        $poll = new Poll(
            id: 1,
            question: 'Limited time poll?',
            options: ['A', 'B'],
            creatorId: 1,
            expiresAt: $expiresAt
        );

        $this->assertEquals($expiresAt, $poll->getExpiresAt());
        
        $arr = $poll->toArray();
        $this->assertEquals($expiresAt->format('Y-m-d H:i:s'), $arr['expires_at']);
    }

    public function testMultiplePollsInConversation(): void
    {
        // Create first poll in conversation
        $poll1 = new Poll(
            id: 1,
            question: 'First question?',
            options: ['Yes', 'No'],
            creatorId: 1,
            conversationId: 'conv-1',
            messageId: 'msg-1'
        );

        // Create second poll in same conversation
        $poll2 = new Poll(
            id: 1,
            question: 'Second question?',
            options: ['A', 'B', 'C'],
            creatorId: 2,
            conversationId: 'conv-1',
            messageId: 'msg-2'
        );

        // Vote on both polls
        $poll1->vote(0);
        $poll2->vote(1);
        $poll2->vote(2);

        $this->assertEquals('conv-1', $poll1->getConversationId());
        $this->assertEquals('conv-1', $poll2->getConversationId());
        $this->assertEquals(1, $poll1->getTotalVotes());
        $this->assertEquals(2, $poll2->getTotalVotes());
    }

    public function testPollResultsCalculation(): void
    {
        $poll = new Poll(
            id: 1,
            question: 'Test results?',
            options: ['Option A', 'Option B', 'Option C'],
            creatorId: 1
        );

        // 10 votes for A, 5 votes for B, 3 votes for C
        for ($i = 0; $i < 10; $i++) $poll->vote(0);
        for ($i = 0; $i < 5; $i++) $poll->vote(1);
        for ($i = 0; $i < 3; $i++) $poll->vote(2);

        $results = $poll->toArray();
        
        $this->assertEquals(18, $results['total_votes']);
        $this->assertEquals([10, 5, 3], $results['votes']);
        
        // Calculate percentages manually
        $expectedA = round((10 / 18) * 100, 2);
        $expectedB = round((5 / 18) * 100, 2);
        $expectedC = round((3 / 18) * 100, 2);
        
        // Verify percentage calculation in toArray
        $this->assertEquals($expectedA, $results['options'][0]['percentage'] ?? 0);
    }

    public function testPollVoteEntity(): void
    {
        $vote = new PollVote(
            id: 1,
            pollId: 1,
            userId: 'user-1',
            optionIndices: [0]
        );

        $this->assertEquals(1, $vote->getId());
        $this->assertEquals(1, $vote->getPollId());
        $this->assertEquals('user-1', $vote->getUserId());
        $this->assertEquals(0, $vote->getOptionIndices()[0]);
        $this->assertInstanceOf(\DateTime::class, $vote->getVotedAt());
    }

    public function testPollVoteEntityMultiSelect(): void
    {
        $vote = new PollVote(
            id: 1,
            pollId: 1,
            userId: 'user-1',
            optionIndices: [0, 1, 2]
        );

        $this->assertEquals([0, 1, 2], $vote->getOptionIndices());
    }

    public function testPollIntegrationWithVotes(): void
    {
        // Create poll
        $poll = new Poll(
            id: 1,
            question: 'Integration test poll?',
            options: ['Red', 'Green', 'Blue'],
            creatorId: 1,
            conversationId: 'conv-test',
            messageId: 'msg-test',
            allowMultiple: false,
            isAnonymous: true
        );

        // Multiple users vote
        $votes = [
            ['user' => 'user-1', 'option' => 0], // Red
            ['user' => 'user-2', 'option' => 0], // Red
            ['user' => 'user-3', 'option' => 1], // Green
            ['user' => 'user-4', 'option' => 2], // Blue
            ['user' => 'user-5', 'option' => 0], // Red
        ];

        foreach ($votes as $vote) {
            $poll->vote($vote['option']);
        }

        // Verify results
        $this->assertEquals(5, $poll->getTotalVotes());
        $this->assertEquals([3, 1, 1], $poll->getVotes());

        // Export results
        $export = $poll->toArray();
        $this->assertEquals('Integration test poll?', $export['question']);
        $this->assertTrue($export['is_anonymous']);
        $this->assertEquals('conv-test', $export['conversation_id']);
    }

    public function testEmptyPollResults(): void
    {
        $poll = new Poll(
            id: 1,
            question: 'No votes yet?',
            options: ['A', 'B'],
            creatorId: 1
        );

        $results = $poll->toArray();
        
        $this->assertEquals(0, $results['total_votes']);
        $this->assertEquals([0, 0], $results['votes']);
    }

    public function testPollWithAllOptionsVoted(): void
    {
        $poll = new Poll(
            id: 1,
            question: 'All options?',
            options: ['A', 'B', 'C'],
            creatorId: 1
        );

        // Vote for all options equally
        for ($i = 0; $i < 3; $i++) {
            $poll->vote(0);
            $poll->vote(1);
            $poll->vote(2);
        }

        $this->assertEquals(9, $poll->getTotalVotes());
        $this->assertEquals([3, 3, 3], $poll->getVotes());
    }
}
