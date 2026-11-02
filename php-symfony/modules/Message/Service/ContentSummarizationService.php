<?php

declare(strict_types=1);

namespace App\Message\Service;

/**
 * Content Summarization Service
 * Generates content summaries.
 */
class ContentSummarizationService
{
    private OpenAIClientService $openAIClient;

    public function __construct(OpenAIClientService $openAIClient)
    {
        $this->openAIClient = $openAIClient;
    }

    public function summarize(string $content, int $maxLength = 100)
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        if (!$this->openAIClient->hasApiKey()) {
            return $this->summarizeMock($content, $maxLength);
        }

        try {
            $messages = [
                ['role' => 'system', 'content' => "You are a helpful summarizer. Summarize text concisely in under $maxLength characters."],
                ['role' => 'user', 'content' => "Summarize this: \"$content\""],
            ];

            $result = $this->openAIClient->chatCompletion($messages, (int) ceil($maxLength / 4), 0.5);
            $summary = trim($result['choices'][0]['message']['content'] ?? $content);

            if (strlen($summary) > $maxLength) {
                $summary = substr($summary, 0, $maxLength - 3) . '...';
            }

            return $summary;
        } catch (\Exception $e) {
            error_log('ContentSummarizationService error: ' . $e->getMessage());
            return $this->summarizeMock($content, $maxLength);
        }
    }

    private function summarizeMock(string $content, int $maxLength)
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $summary = '';

        foreach ($sentences as $sentence) {
            if (strlen($summary . ' ' . $sentence) > $maxLength - 3) {
                break;
            }
            $summary .= ($summary ? ' ' : '') . $sentence;
        }

        if (empty($summary)) {
            $summary = substr($content, 0, $maxLength - 3);
        }

        return rtrim($summary) . '...';
    }
}
