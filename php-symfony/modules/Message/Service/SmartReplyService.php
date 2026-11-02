<?php

declare(strict_types=1);

namespace App\Message\Service;

/**
 * Smart Reply Service
 * Generates AI-powered smart reply suggestions.
 */
class SmartReplyService
{
    private OpenAIClientService $openAIClient;

    public function __construct(OpenAIClientService $openAIClient)
    {
        $this->openAIClient = $openAIClient;
    }

    public function generateSmartReplies(string $messageContent, array $conversationContext = [])
    {
        $messageContent = trim($messageContent);
        if (empty($messageContent)) {
            return ['Please provide a message to reply to.'];
        }

        if (strlen($messageContent) > 1000) {
            $messageContent = substr($messageContent, 0, 1000) . '...';
        }

        $conversationContext = array_filter(array_map('trim', $conversationContext));
        $conversationContext = array_slice($conversationContext, -5);

        if (!$this->openAIClient->hasApiKey()) {
            return $this->getMockReplies();
        }

        try {
            $context = !empty($conversationContext) ? implode("\n", array_slice($conversationContext, -3)) : '';

            $prompt = "Generate 3 smart reply suggestions for this message: \"$messageContent\"\n";
            if ($context) {
                $prompt .= "Conversation context:\n$context\n";
            }
            $prompt .= 'Replies should be natural, helpful, and contextually appropriate. Keep each reply under 100 characters.';

            if (strlen($prompt) > 4000) {
                $prompt = substr($prompt, 0, 4000) . '...';
            }

            $messages = [
                ['role' => 'system', 'content' => 'You are a helpful assistant that generates smart reply suggestions.'],
                ['role' => 'user', 'content' => $prompt],
            ];

            $response = $this->openAIClient->chatCompletion($messages, 150, 0.7, 3);

            $replies = array_map(fn($choice) => trim($choice['message']['content'] ?? ''), $response['choices'] ?? []);
            $replies = array_filter($replies, fn($reply) => !empty($reply) && strlen($reply) <= 100);

            return !empty($replies) ? array_slice($replies, 0, 3) : $this->getMockReplies();
        } catch (\Exception $e) {
            error_log('SmartReplyService error: ' . $e->getMessage());
            return $this->getMockReplies();
        }
    }

    private function getMockReplies()
    {
        $replies = [
            'Thanks for sharing that!',
            "That's interesting. Can you tell me more?",
            'I agree with your point.',
            'Great work on this!',
            'Let me think about that.',
            "That's a good question.",
            'I appreciate your input.',
            'Thanks for the update.',
            'That makes sense.',
            "I'll look into this.",
        ];
        shuffle($replies);

        return array_slice($replies, 0, 3);
    }
}
