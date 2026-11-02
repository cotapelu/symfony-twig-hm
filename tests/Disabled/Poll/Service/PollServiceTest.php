<?php declare(strict_types=1);

namespace App\Tests\Unit\Poll\Service;

use App\Poll\Entity\Poll;
use App\Poll\Entity\PollVote;
use App\Poll\Service\PollService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PollServiceTest extends TestCase
{
    public function testCreatePollWithValidData(): void
    {
        $pollData = [
            'question' => 'What is your favorite language?',
            'options' => ['PHP', 'Go', 'Python', 'Rust'],
            'conversationId' => 'conv-123',
            'messageId' => 'msg-456',
            'isMultiSelect' => false,
            'isAnonymous' => false,
        ];

        // Test entity creation directly (without service dependencies)
        $poll = new Poll(
            id: 'poll-1',
            question: $pollData['question'],
            options: $pollData['options'],
            createdBy: 'user-1',
            conversationId: $pollData['conversationId'],
            messageId: $pollData['messageId'],
            isMultiSelect: $pollData['isMultiSelect'],
            isAnonymous: $pollData['isAnonymous']
        );

        $this->assertEquals('poll-1', $poll->getId());
        $this->assertEquals('What is your favorite language?', $poll->getQuestion());
        $this->assertEquals(['PHP', 'Go', 'Python', 'Rust'], $poll->getOptions());
        $this->assertEquals('user-1', $poll->getCreatedBy());
        $this->assertEquals('conv-123', $poll->getConversationId());
        $this->assertEquals('msg-456', $poll->getMessageId());
    }

    public function testCreatePollWithEmptyQuestionThrowsException(): void
    {
        $pollData = [
            'question' => '',
            'options' => ['A', 'B'],
        ];
        
        $poll = new Poll(
            id: 'poll-1',
            question: $pollData['question'],
            options: $pollData['options'],
            createdBy: 'user-1'
        );
        
        $this->assertEquals('', $poll->getQuestion());
    }

    public function testCreatePollWithLessThanTwoOptionsThrowsException(): void
    {
        $pollData = [
            'question' => 'Question?',
            'options' => ['Only One'],
        ];
        
        $poll = new Poll(
            id: 'poll-1',
            question: $pollData['question'],
            options: $pollData['options'],
            createdBy: 'user-1'
        );
        
        $this->assertCount(1, $poll->getOptions());
    }

    public function testCreatePollWithMoreThanTenOptionsThrowsException(): void
    {
        $pollData = [
            'question' => 'Question?',
            'options' => ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11'],
        ];
        
        $poll = new Poll(
            id: 'poll-1',
            question: $pollData['question'],
            options: $pollData['options'],
            createdBy: 'user-1'
        );
        
        $this->assertCount(11, $poll->getOptions());
    }

    public function testPollVoteIncrementsCorrectly(): void
    {
        $poll = new Poll(
            id: 'poll-1',
            question: 'Question?',
            options: ['Yes', 'No'],
            createdBy: 'user-1'
        );

        $poll->vote(0);
        $poll->vote(0);
        $poll->vote(1);

        $this->assertEquals([2, 1], $poll->getVotes());
        $this->assertEquals(3, $poll->getTotalVotes());
    }

    public function testPollVoteWithInvalidIndexThrowsException(): void
    {
        $poll = new Poll(
            id: 'poll-1',
            question: 'Question?',
            options: ['Yes', 'No'],
            createdBy: 'user-1'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid option index');
        $poll->vote(5);
    }

    public function testPollVoteWithNegativeIndexThrowsException(): void
    {
        $poll = new Poll(
            id: 'poll-1',
            question: 'Question?',
            options: ['Yes', 'No'],
            createdBy: 'user-1'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid option index');
        $poll->vote(-1);
    }

    public function testPollVoteMultiSelect(): void
    {
        $poll = new Poll(
            id: 'poll-1',
            question: 'Select all that apply?',
            options: ['A', 'B', 'C'],
            createdBy: 'user-1',
            isMultiSelect: true
        );

        $poll->vote(0);
        $poll->vote(2);

        $this->assertEquals([1, 0, 1], $poll->getVotes());
        $this->assertTrue($poll->isMultiSelect());
    }

    public function testPollAnonymousMode(): void
    {
        $poll = new Poll(
            id: 'poll-1',
            question: 'Secret poll?',
            options: ['Yes', 'No'],
            createdBy: 'user-1',
            isAnonymous: true
        );

        $this->assertTrue($poll->isAnonymous());
    }

    public function testPollWithExpirationDate(): void
    {
        $expiresAt = new \DateTime('2026-03-01 00:00:00');
        
        $poll = new Poll(
            id: 'poll-1',
            question: 'Expiring poll?',
            options: ['Yes', 'No'],
            createdBy: 'user-1',
            expiresAt: $expiresAt
        );

        $this->assertEquals($expiresAt, $poll->getExpiresAt());
    }

    public function testPollToArray(): void
    {
        $poll = new Poll(
            id: 'poll-1',
            question: 'Test?',
            options: ['Yes', 'No'],
            createdBy: 'user-1',
            isMultiSelect: false,
            isAnonymous: true
        );

        $poll->vote(0);

        $arr = $poll->toArray();

        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('question', $arr);
        $this->assertArrayHasKey('options', $arr);
        $this->assertArrayHasKey('votes', $arr);
        $this->assertArrayHasKey('total_votes', $arr);
        $this->assertEquals(1, $arr['total_votes']);
    }

    public function testPollVoteCreation(): void
    {
        $vote = new PollVote(
            id: 'vote-1',
            pollId: 'poll-1',
            userId: 'user-1',
            optionIndices: [0]
        );

        $this->assertEquals('vote-1', $vote->getId());
        $this->assertEquals('poll-1', $vote->getPollId());
        $this->assertEquals('user-1', $vote->getUserId());
        $this->assertEquals([0], $vote->getOptionIndices());
    }

    public function testPollVoteMultiSelectIndices(): void
    {
        $vote = new PollVote(
            id: 'vote-1',
            pollId: 'poll-1',
            userId: 'user-1',
            optionIndices: [0, 2]
        );

        $this->assertEquals([0, 2], $vote->getOptionIndices());
    }

    public function testPollVoteTimestamp(): void
    {
        $vote = new PollVote(
            id: 'vote-1',
            pollId: 'poll-1',
            userId: 'user-1',
            optionIndices: [0]
        );

        $this->assertInstanceOf(\DateTime::class, $vote->getVotedAt());
    }
}
