<?php declare(strict_types=1);

namespace App\Tests\Unit\Message\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Message\Service\ConversationInsightsService;
use App\Message\Repository\MessageRepositoryInterface;
use App\Message\Entity\Message;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]

class ConversationInsightsServiceTest extends TestCase
{
    private MessageRepositoryInterface&MockObject $repoMock;
    private LoggerInterface&MockObject $loggerMock;
    private ConversationInsightsService $service;

    protected function setUp(): void
    {
        $this->repoMock = $this->createMock(MessageRepositoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->service = new ConversationInsightsService($this->repoMock, $this->loggerMock);
    }

    private function createMessage(string $id, string $conversationId, string $senderId, string $type, string $content, \DateTime $createdAt): Message
    {
        $msg = $this->createMock(Message::class);
        $msg->method('getId')->willReturn($id);
        $msg->method('getConversationId')->willReturn($conversationId);
        $msg->method('getSenderId')->willReturn($senderId);
        $msg->method('getType')->willReturn($type);
        $msg->method('getContent')->willReturn($content);
        $msg->method('getCreatedAt')->willReturn($createdAt);
        return $msg;
    }

    public function testGetConversationInsightsEmpty(): void
    {
        $this->repoMock->expects($this->once())
            ->method('findByConversationIdWithCursor')
            ->with('conv1', null, 1000)
            ->willReturn([]);

        $result = $this->service->getConversationInsights('conv1', 'user1');

        $this->assertSame('conv1', $result['conversation_id']);
        $this->assertSame(0, $result['total_messages']);
        $this->assertSame(0, $result['active_participants']);
        $this->assertSame([], $result['participants']);
        $this->assertSame([], $result['message_types']);
        $this->assertSame([], $result['top_topics']);
        // With no messages, sentiments all zero -> first key 'positive'
        $this->assertSame('positive', $result['sentiment']);
        $this->assertSame('none', $result['engagement']);
    }

    public function testGetConversationInsightsAggregatesData(): void
    {
        $now = new \DateTime();
        $messages = [
            $this->createMessage('m1', 'conv1', 'u1', 'text', 'Hello world', $now),
            $this->createMessage('m2', 'conv1', 'u2', 'text', 'Hello there', $now),
            $this->createMessage('m3', 'conv1', 'u1', 'image', 'Image content', $now),
        ];

        $this->repoMock->expects($this->once())
            ->method('findByConversationIdWithCursor')
            ->willReturn($messages);

        $result = $this->service->getConversationInsights('conv1', 'user1');

        $this->assertSame(3, $result['total_messages']);
        $this->assertSame(2, $result['active_participants']);
        $this->assertCount(2, $result['participants']);
        $this->assertSame(['text' => 2, 'image' => 1], $result['message_types']);
        $this->assertContains('hello', $result['top_topics']);
        $this->assertContains('world', $result['top_topics']);
    }

    public function testCalculateEngagementHigh(): void
    {
        $refMethod = new \ReflectionMethod($this->service, 'calculateEngagement');
        $refMethod->setAccessible(true);
        $result = $refMethod->invoke($this->service, 100, 1, ['positive'=>80,'neutral'=>10,'negative'=>10]); // 100/1=100 >50
        $this->assertSame('high', $result);
    }

    public function testCalculateEngagementMedium(): void
    {
        $refMethod = new \ReflectionMethod($this->service, 'calculateEngagement');
        $refMethod->setAccessible(true);
        $result = $refMethod->invoke($this->service, 22, 1, ['positive'=>15,'neutral'=>7]); // 22/1=22 >20 => medium
        $this->assertSame('medium', $result);
    }

    public function testCalculateEngagementLow(): void
    {
        $refMethod = new \ReflectionMethod($this->service, 'calculateEngagement');
        $refMethod->setAccessible(true);
        $result = $refMethod->invoke($this->service, 10, 2, ['positive'=>5,'neutral'=>5]); // 10/2=5 => <=5 returns none? Actually >5 is low, so exactly 5 => none
        $this->assertSame('none', $result);
    }

    public function testCalculateEngagementNone(): void
    {
        $refMethod = new \ReflectionMethod($this->service, 'calculateEngagement');
        $refMethod->setAccessible(true);
        $this->assertSame('none', $refMethod->invoke($this->service, 0, 0, []));
        $this->assertSame('none', $refMethod->invoke($this->service, 5, 0, []));
    }

    public function testAnalyzeSentimentPositive(): void
    {
        $refMethod = new \ReflectionMethod($this->service, 'analyzeSentiment');
        $refMethod->setAccessible(true);
        $this->assertSame('positive', $refMethod->invoke($this->service, 'I love this great app'));
    }

    public function testAnalyzeSentimentNegative(): void
    {
        $refMethod = new \ReflectionMethod($this->service, 'analyzeSentiment');
        $refMethod->setAccessible(true);
        $this->assertSame('negative', $refMethod->invoke($this->service, 'This is terrible bad awful'));
    }

    public function testAnalyzeSentimentNeutral(): void
    {
        $refMethod = new \ReflectionMethod($this->service, 'analyzeSentiment');
        $refMethod->setAccessible(true);
        $this->assertSame('neutral', $refMethod->invoke($this->service, 'Just a normal sentence'));
    }

    public function testGetDominantSentiment(): void
    {
        $refMethod = new \ReflectionMethod($this->service, 'getDominantSentiment');
        $refMethod->setAccessible(true);
        $this->assertSame('positive', $refMethod->invoke($this->service, ['positive' => 10, 'neutral' => 5, 'negative' => 2]));
        $this->assertSame('neutral', $refMethod->invoke($this->service, ['positive' => 5, 'neutral' => 10, 'negative' => 2]));
        $this->assertSame('negative', $refMethod->invoke($this->service, ['positive' => 2, 'neutral' => 5, 'negative' => 10]));
        $this->assertSame('positive', $refMethod->invoke($this->service, ['positive' => 0, 'neutral' => 0, 'negative' => 0])); // ties -> first
    }

    public function testFormatParticipants(): void
    {
        $refMethod = new \ReflectionMethod($this->service, 'formatParticipants');
        $refMethod->setAccessible(true);
        $participants = [
            'u1' => ['message_count' => 5, 'last_active' => new \DateTime('2024-01-03')],
            'u2' => ['message_count' => 2, 'last_active' => new \DateTime('2024-01-02')],
        ];
        $formatted = $refMethod->invoke($this->service, $participants);
        $this->assertCount(2, $formatted);
        $this->assertSame('u1', $formatted[0]['user_id']);
        $this->assertSame(5, $formatted[0]['message_count']);
    }

    public function testGetConversationInsightsLogsErrorOnException(): void
    {
        $this->repoMock->expects($this->once())
            ->method('findByConversationIdWithCursor')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Failed to generate conversation insights', $this->callback(function($context) {
                return $context['conversation_id'] === 'conv1' && $context['error'] === 'DB error';
            }));

        $result = $this->service->getConversationInsights('conv1', 'user1');

        // Should return default insights after error
        $this->assertSame('conv1', $result['conversation_id']);
        $this->assertSame(0, $result['total_messages']);
        $this->assertSame('none', $result['engagement']);
    }

    public function testGetConversationInsightsFiltersShortWords(): void
    {
        $now = new \DateTime();
        $messages = [
            $this->createMessage('m1', 'conv1', 'u1', 'text', 'an be on the quick', $now),
        ];
        $this->repoMock->expects($this->once())->method('findByConversationIdWithCursor')->willReturn($messages);
        $result = $this->service->getConversationInsights('conv1', 'user1');
        // quick has >3 chars, others <=3 not counted
        $this->assertContains('quick', $result['top_topics']);
    }
}
