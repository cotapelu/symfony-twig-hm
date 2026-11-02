<?php

declare(strict_types=1);

namespace App\Message\Service;

/**
 * Content Moderation Service
 * Moderates content for inappropriate material.
 */
class ContentModerationService
{
    private OpenAIClientService $openAIClient;

    private array $inappropriateWords = ['spam', 'offensive', 'inappropriate', 'hate', 'violence', 'hateful', 'abusive', 'toxic', 'threat'];

    public function __construct(OpenAIClientService $openAIClient)
    {
        $this->openAIClient = $openAIClient;
    }

    public function moderate(string $content, string $context = 'message')
    {
        if (!$this->openAIClient->hasApiKey()) {
            return $this->moderateMock($content);
        }

        try {
            $result = $this->openAIClient->moderation($content);
            $moderation = $result['results'][0] ?? null;

            if (!$moderation) {
                return $this->moderateMock($content);
            }

            $categories = array_filter($moderation['categories'], fn($v) => $v === true);
            $categoryScores = $moderation['category_scores'] ?? [];
            $flagged = $moderation['flagged'] ?? false;

            $severity = $this->calculateSeverity($flagged, $categoryScores);

            return [
                'is_flagged' => $flagged,
                'severity' => $severity,
                'flags' => array_keys($categories),
                'confidence' => $flagged ? ($categoryScores['harassment'] ?? 0.5) : 0.95,
                'categories' => array_keys($categories),
                'recommendation' => $flagged ? 'Review content before publishing' : 'Content appears safe',
            ];
        } catch (\Exception $e) {
            error_log('ContentModerationService error: ' . $e->getMessage());
            return $this->moderateMock($content);
        }
    }

    private function calculateSeverity(bool $flagged, array $categoryScores)
    {
        if (!$flagged) {
            return 'low';
        }

        $maxScore = max($categoryScores) ?: 0;

        if ($maxScore > 0.8) {
            return 'high';
        } elseif ($maxScore > 0.5) {
            return 'medium';
        }

        return 'low';
    }

    private function moderateMock(string $content)
    {
        $flags = [];
        $lowerContent = strtolower($content);

        foreach ($this->inappropriateWords as $word) {
            if (strpos($lowerContent, $word) !== false) {
                $flags[] = $word;
            }
        }

        $isFlagged = !empty($flags);
        $severity = $isFlagged ? (count($flags) > 2 ? 'high' : 'medium') : 'low';

        return [
            'is_flagged' => $isFlagged,
            'severity' => $severity,
            'flags' => $flags,
            'confidence' => $isFlagged ? (0.8 + (count($flags) * 0.05)) : 0.95,
            'categories' => $isFlagged ? ['inappropriate_content'] : [],
            'recommendation' => $isFlagged ? 'Review content before publishing' : 'Content appears safe',
        ];
    }
}
