<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Repository\MessageRepositoryInterface;
use Psr\Log\LoggerInterface;

class ConversationInsightsService
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository,
        private LoggerInterface $logger
    ) {
    }

    public function getConversationInsights(string $conversationId, string $userId)
    {
        try {
            $messages = $this->messageRepository->findByConversationIdWithCursor($conversationId, null, 1000);
            [$participants, $messageTypes, $wordCounts, $sentiments] = $this->aggregateAllMetrics($messages);

            return [
                'conversation_id' => $conversationId,
                'total_messages' => count($messages),
                'active_participants' => count($participants),
                'participants' => $this->formatParticipants($participants),
                'message_types' => $messageTypes,
                'top_topics' => $this->extractTopTopics($wordCounts),
                'sentiment' => $this->getDominantSentiment($sentiments),
                'engagement' => $this->calculateEngagement(count($messages), count($participants), $sentiments),
                'generated_at' => new \DateTime(),
            ];
        } catch (\Exception $e) {
            $this->logInsightsError($conversationId, $e); return $this->getDefaultInsights($conversationId);
        }
    }

    private function aggregateAllMetrics(array $messages): array
    {
        return [
            $this->aggregateParticipants($messages),
            $this->aggregateMessageTypes($messages),
            $this->aggregateWordCounts($messages),
            $this->aggregateSentiments($messages),
        ];
    }

    private function aggregateParticipants(array $messages): array
    {
        $participants = [];
        foreach ($messages as $message) {
            $senderId = $message->getSenderId();
            if (!isset($participants[$senderId])) {
                $participants[$senderId] = [
                    'message_count' => 0,
                    'last_active' => $message->getCreatedAt(),
                ];
            }
            $participants[$senderId]['message_count']++;
            if ($message->getCreatedAt() > $participants[$senderId]['last_active']) {
                $participants[$senderId]['last_active'] = $message->getCreatedAt();
            }
        }
        return $participants;
    }

    private function aggregateMessageTypes(array $messages): array
    {
        $messageTypes = [];
        foreach ($messages as $message) {
            $type = $message->getType();
            if (!isset($messageTypes[$type])) {
                $messageTypes[$type] = 0;
            }
            $messageTypes[$type]++;
        }
        return $messageTypes;
    }

    private function aggregateWordCounts(array $messages): array
    {
        $wordCounts = [];
        foreach ($messages as $message) {
            $content = strtolower($message->getContent());
            $words = preg_split('/\s+/', $content);
            foreach ($words as $word) {
                if (strlen($word) > 3) {
                    $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;
                }
            }
        }
        arsort($wordCounts);
        return $wordCounts;
    }

    private function extractTopTopics(array $wordCounts): array
    {
        return array_slice(array_keys($wordCounts), 0, 10);
    }

    private function aggregateSentiments(array $messages): array
    {
        $sentiments = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
        foreach ($messages as $message) {
            $content = strtolower($message->getContent());
            $sentiment = $this->analyzeSentiment($content);
            $sentiments[$sentiment]++;
        }
        return $sentiments;
    }

    private function logInsightsError(string $conversationId, \Exception $e): void
    {
        $this->logger->error('Failed to generate conversation insights', [
            'conversation_id' => $conversationId,
            'error' => $e->getMessage(),
        ]);
    }

    private function analyzeSentiment(string $content)
    {
        $positiveWords = ['happy', 'great', 'awesome', 'love', 'thanks', 'good', 'amazing', 'wonderful'];
        $negativeWords = ['bad', 'hated', 'angry', 'sad', 'terrible', 'worst', 'awful', 'disappointed'];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($content, $word)) {
                $positiveCount++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($content, $word)) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        }
        if ($negativeCount > $positiveCount) {
            return 'negative';
        }
        return 'neutral';
    }

    private function calculateEngagement(int $totalMessages, int $participantCount, array $sentiments)
    {
        if ($totalMessages === 0 || $participantCount === 0) {
            return 'none';
        }

        $messagesPerParticipant = $totalMessages / $participantCount;

        if ($messagesPerParticipant > 50) {
            return 'high';
        }
        if ($messagesPerParticipant > 20) {
            return 'medium';
        }
        if ($messagesPerParticipant > 5) {
            return 'low';
        }
        return 'none';
    }

    private function getDominantSentiment(array $sentiments)
    {
        arsort($sentiments);
        return array_key_first($sentiments);
    }

    private function formatParticipants(array $participants)
    {
        $formatted = [];
        foreach ($participants as $userId => $data) {
            $formatted[] = [
                'user_id' => $userId,
                'message_count' => $data['message_count'],
                'last_active' => $data['last_active']->format('c'),
            ];
        }
        return $formatted;
    }

    private function getDefaultInsights(string $conversationId)
    {
        return [
            'conversation_id' => $conversationId,
            'total_messages' => 0,
            'active_participants' => 0,
            'participants' => [],
            'message_types' => [],
            'top_topics' => [],
            'sentiment' => 'neutral',
            'engagement' => 'none',
            'generated_at' => new \DateTime(),
        ];
    }
}
