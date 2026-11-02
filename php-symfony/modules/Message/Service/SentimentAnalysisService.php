<?php

declare(strict_types=1);

namespace App\Message\Service;

/**
 * Sentiment Analysis Service
 * Analyzes sentiment of text content.
 */
class SentimentAnalysisService
{
    private OpenAIClientService $openAIClient;

    private array $positiveWords = ['good', 'great', 'excellent', 'awesome', 'love', 'like', 'happy', 'thanks', 'thank', 'amazing', 'wonderful', 'perfect', 'best', 'fantastic', 'brilliant', 'excited', 'glad', 'pleased', 'satisfied'];

    private array $negativeWords = ['bad', 'terrible', 'hate', 'angry', 'sad', 'disappointed', 'sorry', 'problem', 'issue', 'worst', 'horrible', 'awful', 'upset', 'frustrated', 'annoyed', 'annoying', 'disappointing'];

    public function __construct(OpenAIClientService $openAIClient)
    {
        $this->openAIClient = $openAIClient;
    }

    public function analyze(string $messageContent)
    {
        if (!$this->openAIClient->hasApiKey()) {
            return $this->analyzeMock($messageContent);
        }

        try {
            $prompt = "Analyze the sentiment of this message: \"$messageContent\"\n";
            $prompt .= 'Return JSON with format: {"sentiment": "positive|negative|neutral", "confidence": 0.0-1.0, "scores": {"positive": 0.0-1.0, "negative": 0.0-1.0, "neutral": 0.0-1.0}}';

            $messages = [
                ['role' => 'system', 'content' => 'You are a sentiment analyzer. Return only JSON.'],
                ['role' => 'user', 'content' => $prompt],
            ];

            $result = $this->openAIClient->chatCompletion($messages, 200, 0.3);
            $content = $result['choices'][0]['message']['content'] ?? '';

            $analysis = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->analyzeMock($messageContent);
            }

            return $analysis;
        } catch (\Exception $e) {
            error_log('SentimentAnalysisService error: ' . $e->getMessage());
            return $this->analyzeMock($messageContent);
        }
    }

    private function analyzeMock(string $messageContent)
    {
        $lowerContent = strtolower($messageContent);
        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($this->positiveWords as $word) {
            if (strpos($lowerContent, $word) !== false) {
                ++$positiveCount;
            }
        }

        foreach ($this->negativeWords as $word) {
            if (strpos($lowerContent, $word) !== false) {
                ++$negativeCount;
            }
        }

        if ($positiveCount > $negativeCount) {
            $sentiment = 'positive';
            $confidence = min(0.95, 0.7 + ($positiveCount * 0.05));
        } elseif ($negativeCount > $positiveCount) {
            $sentiment = 'negative';
            $confidence = min(0.95, 0.7 + ($negativeCount * 0.05));
        } else {
            $sentiment = 'neutral';
            $confidence = 0.6;
        }

        return [
            'sentiment' => $sentiment,
            'confidence' => $confidence,
            'scores' => [
                'positive' => $positiveCount > 0 ? min(1.0, 0.5 + ($positiveCount * 0.1)) : 0.1,
                'negative' => $negativeCount > 0 ? min(1.0, 0.5 + ($negativeCount * 0.1)) : 0.1,
                'neutral' => max(0.1, 1.0 - ($positiveCount + $negativeCount) * 0.1),
            ],
        ];
    }
}
