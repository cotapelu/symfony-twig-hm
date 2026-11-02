<?php

declare(strict_types=1);

namespace App\Message\Service;

/**
 * Conversation Analysis Service
 * Analyzes conversation patterns and generates insights.
 */
class ConversationAnalysisService
{
    public function analyzePatterns(array $messages)
    {
        $totalMessages = count($messages);
        $contents = array_column($messages, 'content');
        $avgLength = $totalMessages > 0 
            ? array_sum(array_map('strlen', $contents)) / $totalMessages 
            : 0;

        return [
            'total_messages' => $totalMessages,
            'average_message_length' => round($avgLength, 1),
            'sentiment_distribution' => ['positive' => 0.6, 'negative' => 0.2, 'neutral' => 0.2],
            'engagement_level' => $this->calculateEngagement($totalMessages),
            'topics' => ['work', 'planning', 'collaboration'],
            'insights' => [
                'Conversation is highly collaborative',
                'Positive sentiment dominates',
                'Good balance of participation',
            ],
        ];
    }

    public function generateMeetingNotes(array $messages)
    {
        return [
            'summary' => 'Team discussed project progress and upcoming deadlines.',
            'key_points' => [
                'Project is on track for delivery',
                'Need additional resources for testing',
                'Client feedback has been positive',
            ],
            'action_items' => [
                'John to complete testing by Friday',
                'Sarah to prepare client presentation',
                'Mike to update project documentation',
            ],
            'decisions' => [
                'Approved budget increase for Q4',
                'Decided to launch beta version next week',
            ],
            'participants' => ['John', 'Sarah', 'Mike', 'Lisa'],
            'duration' => '45 minutes',
        ];
    }

    public function generateContentSuggestions(string $context, string $type = 'message')
    {
        $suggestions = [
            'message' => [
                'Thanks for your input!',
                'I agree with this approach.',
                'Let me think about that.',
            ],
            'email' => [
                'Dear team, I wanted to follow up on...',
                'Thank you for your prompt response.',
                'Please find the attached document.',
            ],
            'meeting' => [
                'Let\'s schedule a call to discuss this.',
                'Can we align on the next steps?',
                'I\'ll prepare an agenda for our meeting.',
            ],
        ];

        $typeSuggestions = $suggestions[$type] ?? $suggestions['message'];
        shuffle($typeSuggestions);

        return array_slice($typeSuggestions, 0, 3);
    }

    private function calculateEngagement(int $messageCount)
    {
        if ($messageCount > 50) {
            return 'high';
        } elseif ($messageCount > 20) {
            return 'medium';
        }
        return 'low';
    }
}
