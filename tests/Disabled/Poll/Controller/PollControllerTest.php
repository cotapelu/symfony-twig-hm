<?php declare(strict_types=1);

namespace App\Tests\Unit\Poll\Controller;

use App\Poll\Entity\Poll;
use PHPUnit\Framework\TestCase;

class PollControllerTest extends TestCase
{
    public function testControllerCreatePollRequiresQuestion(): void
    {
        // Test Poll entity validation directly
        $this->expectException(\InvalidArgumentException::class);
        
        new Poll(
            id: 'poll-1',
            question: '',
            options: ['A', 'B'],
            createdBy: 'user-1'
        );
    }

    public function testControllerCreatePollRequiresAtLeastTwoOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Poll(
            id: 'poll-1',
            question: 'Question?',
            options: ['Only One'],
            createdBy: 'user-1'
        );
    }

    public function testPollEntityCreation(): void
    {
        $poll = new Poll(
            id: 'poll-1',
            question: 'What is your favorite framework?',
            options: ['Symfony', 'Laravel', 'Rails', 'Django'],
            createdBy: 'user-123',
            conversationId: 'conv-456',
            messageId: 'msg-789'
        );

        $this->assertEquals('poll-1', $poll->getId());
        $this->assertEquals('What is your favorite framework?', $poll->getQuestion());
        $this->assertEquals(['Symfony', 'Laravel', 'Rails', 'Django'], $poll->getOptions());
        $this->assertEquals('user-123', $poll->getCreatedBy());
        $this->assertEquals('conv-456', $poll->getConversationId());
        $this->assertEquals('msg-789', $poll->getMessageId());
    }

    public function testPollEntityDefaultValues(): void
    {
        $poll = new Poll(
            id: 'poll-1',
            question: 'Simple question?',
            options: ['Yes', 'No'],
            createdBy: 'user-1'
        );

        $this->assertFalse($poll->isMultiSelect());
        $this->assertFalse($poll->isAnonymous());
        $this->assertNull($poll->getExpiresAt());
        $this->assertEquals([0, 0], $poll->getVotes());
        $this->assertEquals(0, $poll->getTotalVotes());
    }

    public function testPollEntityWithMultiSelect(): void
    {
        $poll = new Poll(
            id: 'poll-1',
            question: 'Select all that apply?',
            options: ['A', 'B', 'C'],
            createdBy: 'user-1',
            isMultiSelect: true
        );

        $this->assertTrue($poll->isMultiSelect());
    }

    public function testPollEntityWithAnonymous(): void
    {
        $poll = new Poll(
            id: 'poll-1',
            question: 'Anonymous poll?',
            options: ['Yes', 'No'],
            createdBy: 'user-1',
            isAnonymous: true
        );

        $this->assertTrue($poll->isAnonymous());
    }

    public function testPollEntityWithExpiration(): void
    {
        $expiresAt = new \DateTime('2026-12-31 23:59:59');
        
        $poll = new Poll(
            id: 'poll-1',
            question: 'Time limited poll?',
            options: ['A', 'B'],
            createdBy: 'user-1',
            expiresAt: $expiresAt
        );

        $this->assertEquals($expiresAt, $poll->getExpiresAt());
    }

    public function testPollVote(): void
    {
        $poll = new Poll(
            id: 'poll-1',
            question: 'Question?',
            options: ['Option A', 'Option B', 'Option C'],
            createdBy: 'user-1'
        );

        $poll->vote(0);
        $poll->vote(1);
        $poll->vote(1);
        $poll->vote(2);

        $this->assertEquals([1, 2, 1], $poll->getVotes());
        $this->assertEquals(4, $poll->getTotalVotes());
    }

    public function testPollVoteInvalidOption(): void
    {
        $poll = new Poll(
            id: 'poll-1',
            question: 'Question?',
            options: ['A', 'B'],
            createdBy: 'user-1'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid option index');
        $poll->vote(10);
    }

    public function testPollVoteNegativeOption(): void
    {
        $poll = new Poll(
            id: 'poll-1',
            question: 'Question?',
            options: ['A', 'B'],
            createdBy: 'user-1'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid option index');
        $poll->vote(-1);
    }

    public function testPollToArrayContainsAllFields(): void
    {
        $poll = new Poll(
            id: 'poll-123',
            question: 'Test question?',
            options: ['Yes', 'No', 'Maybe'],
            createdBy: 'creator-1',
            conversationId: 'conv-1',
            messageId: 'msg-1',
            isMultiSelect: true,
            isAnonymous: false
        );

        $poll->vote(0);
        $poll->vote(2);

        $arr = $poll->toArray();

        $this->assertEquals('poll-123', $arr['id']);
        $this->assertEquals('Test question?', $arr['question']);
        $this->assertEquals(['Yes', 'No', 'Maybe'], $arr['options']);
        $this->assertEquals([1, 0, 1], $arr['votes']);
        $this->assertEquals(2, $arr['total_votes']);
        $this->assertEquals('creator-1', $arr['created_by']);
        $this->assertTrue($arr['is_multi_select']);
        $this->assertFalse($arr['is_anonymous']);
        $this->assertEquals('conv-1', $arr['conversation_id']);
        $this->assertEquals('msg-1', $arr['message_id']);
    }

    public function testMultiplePollsInConversation(): void
    {
        $poll1 = new Poll(
            id: 'poll-1',
            question: 'First question?',
            options: ['A', 'B'],
            createdBy: 'user-1',
            conversationId: 'conv-1'
        );

        $poll2 = new Poll(
            id: 'poll-2',
            question: 'Second question?',
            options: ['X', 'Y', 'Z'],
            createdBy: 'user-2',
            conversationId: 'conv-1'
        );

        $this->assertEquals('conv-1', $poll1->getConversationId());
        $this->assertEquals('conv-1', $poll2->getConversationId());
        $this->assertNotEquals($poll1->getId(), $poll2->getId());
    }

    public function testPollWithoutConversation(): void
    {
        $poll = new Poll(
            id: 'poll-standalone',
            question: 'Standalone question?',
            options: ['Yes', 'No'],
            createdBy: 'user-1'
        );

        $this->assertNull($poll->getConversationId());
        $this->assertNull($poll->getMessageId());
    }
}
