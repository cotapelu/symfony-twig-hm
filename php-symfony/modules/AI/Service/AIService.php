<?php

declare(strict_types=1);

namespace App\AI\Service;

use App\Shared\Service\CircuitBreakerService;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $openaiApiKey,
        private CircuitBreakerService $circuitBreaker,
        private LoggerInterface $logger,
        private array $fallbacks = [],
        private int $maxRetries = 3,
        private float $retryDelayMultiplier = 1.0,
    ) {
    }

    private function validateStringInput(string $input, string $fieldName, int $maxLength = 1000)
    {
        if (empty($input) || trim($input) === '') {
            $this->logger->warning("Validation failed: {$fieldName} is empty");
            throw new \InvalidArgumentException("$fieldName must not be empty");
        }

        if (strlen($input) > $maxLength) {
            $this->logger->warning("Validation failed: {$fieldName} exceeds maximum length of {$maxLength}");
            throw new \InvalidArgumentException("$fieldName must not exceed $maxLength characters");
        }

        // Basic prompt injection prevention - avoid common injection patterns
        $injectionPatterns = [
            '/ignore.*previous.*instructions/i',
            '/system.*:/i',
            '/developer.*:/i',
            '/assistant.*:/i',
        ];

        foreach ($injectionPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logger->warning("Validation failed: {$fieldName} contains potentially harmful content");
                throw new \InvalidArgumentException("$fieldName contains potentially harmful content");
            }
        }
    }

    public function generateSmartReply(string $message)
    {
        $this->validateStringInput($message, 'Message', 2000);
        $this->logger->info("Generating smart reply", ['message_length' => strlen($message)]);
        $result = $this->executeSmartReplyRequest($message);
        $this->logger->info("Smart reply generated", ['reply_length' => strlen($result)]);
        return $result;
    }

    private function makeOpenAIRequest(array $data, string $operation)
    {
        $this->logger->info("Making OpenAI API request for operation: {$operation}", ['data' => $data]);
        $lastException = null;
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $this->requestOpenAIAttempt($data, $attempt, $operation);
            } catch (\Throwable $e) {
                $lastException = $e;
                if ($attempt === $this->maxRetries) {
                    $this->logger->error("OpenAI API request failed for operation: {$operation} after {$attempt} attempts", [
                        'error' => $e->getMessage(),
                        'data' => $data
                    ]);
                    throw $e;
                }
                $delay = $this->calculateRetryDelay($attempt);
                $this->logger->warning("OpenAI API request failed for operation: {$operation} (attempt {$attempt}) - will retry if attempts remain", [
                    'error' => $e->getMessage(),
                    'delay_ms' => $delay,
                    'data' => $data
                ]);
                usleep($delay * 1000);
            }
        }
        throw new \RuntimeException("Unknown error in OpenAI API request after {$this->maxRetries} attempts");
    }

    private function requestOpenAIAttempt(array $data, int $attempt, string $operation): string
    {
        $startTime = microtime(true);
        $response = $this->performHttpRequest($data, $attempt, $operation);
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        $statusCode = $response->getStatusCode();
        $this->ensureSuccessStatus($response, $statusCode, $operation, $attempt, $duration, $data);
        $responseData = $response->toArray();
        return $this->extractContent($responseData, $operation, $attempt, $duration);
    }

    private function performHttpRequest(array $data, int $attempt, string $operation)
    {
        try {
            return $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->openaiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error("OpenAI API request failed for operation: {$operation} (attempt {$attempt}) - network error", [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    private function ensureSuccessStatus($response, int $statusCode, string $operation, int $attempt, float $duration, array $data): void
    {
        if ($statusCode < 200 || $statusCode >= 300) {
            $this->logger->error("OpenAI API request failed for operation: {$operation} (attempt {$attempt}) - HTTP {$statusCode}", [
                'status_code' => $statusCode,
                'duration_ms' => $duration,
                'data' => $data
            ]);
            if ($statusCode >= 400 && $statusCode < 500) {
                throw new \RuntimeException("OpenAI API request failed with status code: $statusCode");
            }
            throw new \RuntimeException("OpenAI API request failed with status code: $statusCode");
        }
    }

    private function extractContent(array $responseData, string $operation, int $attempt, float $duration): string
    {
        $this->ensureChoicesArray($responseData, $operation, $attempt, $duration);
        $this->ensureMessageObject($responseData, $operation, $attempt, $duration);
        return $this->ensureContentExists($responseData, $operation, $attempt, $duration);
    }

    private function ensureChoicesArray(array $responseData, string $operation, int $attempt, float $duration): void
    {
        if (empty($responseData['choices']) || !is_array($responseData['choices']) || count($responseData['choices']) === 0) {
            $this->logger->error("Invalid OpenAI API response for operation: {$operation} (attempt {$attempt}) - missing or empty choices array", [
                'response' => $responseData,
                'duration_ms' => $duration
            ]);
            throw new \RuntimeException("Invalid OpenAI response: missing or empty choices array");
        }
    }

    private function ensureMessageObject(array $responseData, string $operation, int $attempt, float $duration): void
    {
        if (empty($responseData['choices'][0]['message']) || !is_array($responseData['choices'][0]['message'])) {
            $this->logger->error("Invalid OpenAI API response for operation: {$operation} (attempt {$attempt}) - missing or invalid message object", [
                'response' => $responseData,
                'duration_ms' => $duration
            ]);
            throw new \RuntimeException("Invalid OpenAI response: missing message object");
        }
    }

    private function ensureContentExists(array $responseData, string $operation, int $attempt, float $duration): string
    {
        if (!isset($responseData['choices'][0]['message']['content'])) {
            $this->logger->error("Invalid OpenAI API response for operation: {$operation} (attempt {$attempt}) - missing content in message", [
                'response' => $responseData,
                'duration_ms' => $duration
            ]);
            throw new \RuntimeException("Invalid OpenAI response: missing content");
        }
        return $responseData['choices'][0]['message']['content'];
    }

    private function calculateRetryDelay(int $attempt): int
    {
        return (int)($this->retryDelayMultiplier * pow(2, $attempt - 1)) * 100; // milliseconds
    }

    public function analyzeSentiment(string $message)
    {
        $this->validateStringInput($message, 'Message', 2000);
        $this->logger->info("Analyzing sentiment", ['message_length' => strlen($message)]);
        $result = $this->executeSentimentAnalysis($message);
        $decoded = $this->parseSentimentResult($result);
        $this->logger->info("Sentiment analyzed", $decoded);
        return $decoded;
    }

    public function translateMessage(string $message, string $targetLang)
    {
        $this->validateStringInput($message, 'Message', 2000);
        $this->validateStringInput($targetLang, 'Target Language', 10);
        $this->logger->info("Translating message", ['message_length' => strlen($message), 'target_lang' => $targetLang]);
        $result = $this->executeTranslateMessageRequest($message, $targetLang);
        $this->logger->info("Message translated", ['result_length' => strlen($result)]);
        return $result;
    }

    public function generateSmartReplies(string $message)
    {
        $this->validateStringInput($message, 'Message', 2000);
        $this->logger->info("Generating smart replies", ['message_length' => strlen($message)]);
        $result = $this->executeSmartReplyRequest($message);
        $replies = $this->parseAndNormalizeSmartReplies($result);
        $this->logger->info("Smart replies generated", ['replies_count' => count($replies)]);
        return $replies;
    }

    public function moderateContent(string $content)
    {
        $this->validateStringInput($content, 'Content', 4000);
        $this->logger->info("Moderating content", ['content_length' => strlen($content)]);
        $result = $this->circuitBreaker->execute(
            fn () => $this->makeModerationRequest($content),
            'openai_moderations_api',
            3,
            60,
            fn () => $this->fallbacks['moderate_content'] ?? ['isSafe'=>true,'score'=>0.1,'categories'=>[],'flagged'=>false]
        );
        $this->logModerationResult($result);
        return $result;
    }

    private function makeModerationRequest(string $content): array
    {
        $this->logModerationStart($content);
        $start = microtime(true);
        try {
            $response = $this->doModerationHttpRequest($content);
            $duration = $this->elapsedMs($start);
            $this->validateResponseStatus($response, $duration, strlen($content));
            $data = $response->toArray();
            $result = $this->extractFirstResult($data);
            $this->validateResult($result);
            $formatted = $this->formatModerationResult($result, $duration);
            $this->logModerationSuccess($formatted, $duration);
            return $formatted;
        } catch (\Exception $e) {
            $duration = $this->elapsedMs($start);
            $this->logModerationError($e, $content, $duration);
            throw $e;
        }
    }

    private function logModerationStart(string $content): void
    {
        $this->logger->info("Making OpenAI Moderation API request", ['content_length' => strlen($content)]);
    }

    private function doModerationHttpRequest(string $content)
    {
        return $this->httpClient->request('POST', 'https://api.openai.com/v1/moderations', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->openaiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => ['input' => $content],
        ]);
    }

    private function elapsedMs(float $start): float
    {
        return (microtime(true) - $start) * 1000;
    }

    private function validateResponseStatus($response, float $duration, int $contentLength): void
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            $this->logger->error("OpenAI Moderation API request failed", [
                'status_code' => $statusCode,
                'duration_ms' => $duration,
                'content_length' => $contentLength,
            ]);
            throw new \RuntimeException("OpenAI Moderation API request failed with status code: $statusCode");
        }
    }

    private function extractFirstResult(array $data): array
    {
        if (!isset($data['results'][0])) {
            throw new \RuntimeException("Invalid OpenAI Moderation API response: missing first result");
        }
        return $data['results'][0];
    }

    private function validateResult(array $result): void
    {
        if (!isset($result['categories']) || !is_array($result['categories'])) {
            throw new \RuntimeException("Invalid OpenAI Moderation API response: missing or invalid categories");
        }
        if (!isset($result['category_scores']) || !is_array($result['category_scores'])) {
            throw new \RuntimeException("Invalid OpenAI Moderation API response: missing or invalid category_scores");
        }
        if (!array_key_exists('flagged', $result)) {
            throw new \RuntimeException("Invalid OpenAI Moderation API response: missing flagged property");
        }
    }

    private function formatModerationResult(array $result, float $duration): array
    {
        $flaggedCategories = [];
        $scores = [];
        foreach ($result['categories'] as $category => $flagged) {
            if ($flagged) {
                $flaggedCategories[] = $category;
                $scores[$category] = $result['category_scores'][$category] ?? 0.0;
            }
        }
        $isSafe = !$result['flagged'];
        $score = $result['flagged'] ? (empty($scores) ? 0.5 : max($scores)) : 0.1;
        return [
            'isSafe' => $isSafe,
            'score' => $score,
            'categories' => $flaggedCategories,
            'flagged' => $result['flagged'],
        ];
    }

    private function logModerationSuccess(array $formatted, float $duration): void
    {
        $this->logger->info("OpenAI Moderation API request successful", [
            'duration_ms' => $duration,
            'flagged' => $formatted['flagged'],
            'score' => $formatted['score'],
        ]);
    }

    private function logModerationError(\Exception $e, string $content, float $duration): void
    {
        $this->logger->error("OpenAI Moderation API request failed", [
            'error' => $e->getMessage(),
            'duration_ms' => $duration,
            'content_length' => strlen($content),
        ]);
    }

    public function translateContent(string $text, string $targetLanguage, string $sourceLanguage = 'auto')
    {
        $this->validateStringInput($text, 'Text', 5000);
        $this->validateStringInput($targetLanguage, 'Target Language', 10);
        if ($sourceLanguage !== 'auto') {
            $this->validateStringInput($sourceLanguage, 'Source Language', 10);
        }
        $this->logger->info("Translating content", ['text_length' => strlen($text), 'target_language' => $targetLanguage, 'source_language' => $sourceLanguage]);
        $systemPrompt = $this->buildSystemPrompt($targetLanguage, $sourceLanguage);
        $result = $this->executeTranslation($text, $systemPrompt);
        $response = $this->buildTranslationResponse($result, $targetLanguage, $sourceLanguage);
        $this->logger->info("Content translated", ['result_length' => strlen($result), 'target_language' => $targetLanguage]);
        return $response;
    }

    public function generateConversationInsights(array $messages)
    {
        $conversationText = $this->buildConversationText($messages);
        $this->logger->info("Generating conversation insights", ['message_count' => count($messages), 'conversation_length' => strlen($conversationText)]);
        $result = $this->callInsightsAPI($conversationText);
        $insights = $this->parseInsights($result);
        $this->logger->info("Conversation insights generated", $insights);
        return $insights;
    }

    private function buildSystemPrompt(string $targetLanguage, string $sourceLanguage): string
    {
        if ($sourceLanguage === 'auto') {
            return "Translate this text to {$targetLanguage}. Only return the translation. Detect the source language automatically.";
        }
        return "Translate this text from {$sourceLanguage} to {$targetLanguage}. Only return the translation.";
    }

    private function executeTranslation(string $text, string $systemPrompt): string
    {
        return $this->circuitBreaker->execute(
            fn () => $this->makeOpenAIRequest([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $text],
                ],
                'max_tokens' => 500,
                'temperature' => 0.3,
            ], 'openai_translate_content'),
            'openai_chat_api',
            3,
            60,
            fn () => $this->fallbacks['translate_content'] ?? $text
        );
    }

    private function buildTranslationResponse(string $result, string $targetLanguage, string $sourceLanguage): array
    {
        return [
            'translatedText' => $result,
            'detectedSourceLanguage' => $sourceLanguage === 'auto' ? 'auto-detected' : $sourceLanguage,
            'targetLanguage' => $targetLanguage,
            'confidence' => 0.95,
        ];
    }

    private function buildConversationText(array $messages): string
    {
        if (!is_array($messages)) {
            $this->logger->warning('Invalid input for generateConversationInsights: messages is not an array');
            throw new \InvalidArgumentException('Messages must be an array');
        }

        $conversationText = '';
        foreach ($messages as $index => $message) {
            if (!is_array($message) || !isset($message['content'])) {
                $this->logger->warning("Invalid message format at index {$index}", ['message' => $message]);
                throw new \InvalidArgumentException("Each message must be an array with a 'content' key");
            }
            $this->validateStringInput($message['content'], "Message content at index {$index}", 1000);
            $conversationText .= $message['content'].' ';
        }
        return $conversationText;
    }

    private function callInsightsAPI(string $conversationText): string
    {
        return $this->circuitBreaker->execute(
            fn () => $this->makeOpenAIRequest([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'Analyze this conversation and provide insights: 1) Overall sentiment, 2) Main topics discussed, 3) Key takeaways, 4) Action items if any. Return as JSON.'],
                    ['role' => 'user', 'content' => $conversationText],
                ],
                'max_tokens' => 300,
            ], 'openai_insights'),
            'openai_chat_api',
            3,
            60,
            fn () => $this->fallbacks['generate_conversation_insights'] ?? '{"sentiment":"neutral","topics":["general conversation"],"takeaways":["Conversation completed"],"action_items":[]}'
        );
    }

    private function parseInsights(string $result): array
    {
        return json_decode($result, true) ?? $this->fallbacks['generate_conversation_insights_decoded'] ?? [
            'sentiment' => 'neutral',
            'topics' => ['general conversation'],
            'takeaways' => ['Conversation completed'],
            'action_items' => [],
        ];
    }

    private function executeSmartReplyRequest(string $message): string
    {
        return $this->circuitBreaker->execute(
            fn () => $this->makeOpenAIRequest([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'Generate 3 short, context-aware reply suggestions to this message. Return as a JSON array of strings.'],
                    ['role' => 'user', 'content' => $message],
                ],
                'max_tokens' => 150,
                'temperature' => 0.8,
            ], 'openai_replies'),
            'openai_chat_api',
            3,
            60,
            fn () => $this->fallbacks['generate_smart_replies'] ?? '["Thanks!","Got it","Sounds good"]'
        );
    }

    private function parseAndNormalizeSmartReplies(string $result): array
    {
        $replies = json_decode($result, true);
        if (is_array($replies) && count($replies) >= 3) {
            return array_slice($replies, 0, 3);
        }
        return $this->fallbacks['generate_smart_replies_decoded'] ?? ['Thanks!', 'Got it', 'Sounds good'];
    }

    private function logModerationResult(array $result): void
    {
        $this->logger->info("Content moderation completed", [
            'is_safe' => $result['isSafe'] ?? false,
            'score' => $result['score'] ?? 0.1,
            'flagged' => $result['flagged'] ?? false
        ]);
    }

    private function executeSentimentAnalysis(string $message): string
    {
        return $this->circuitBreaker->execute(
            fn () => $this->makeOpenAIRequest([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'Analyze the sentiment of this message. Return JSON with sentiment (positive/negative/neutral) and confidence (0-1).'],
                    ['role' => 'user', 'content' => $message],
                ],
                'max_tokens' => 100,
            ], 'openai_sentiment'),
            'openai_chat_api',
            3,
            60,
            fn () => $this->fallbacks['analyze_sentiment'] ?? '{"sentiment":"neutral","confidence":0.5}'
        );
    }

    private function parseSentimentResult(string $result): array
    {
        return json_decode($result, true) ?? $this->fallbacks['analyze_sentiment_decoded'] ?? ['sentiment' => 'neutral', 'confidence' => 0.5];
    }

    private function executeTranslateMessageRequest(string $message, string $targetLang): string
    {
        $result = $this->circuitBreaker->execute(
            fn () => $this->makeOpenAIRequest([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => "Translate this message to {$targetLang}. Only return the translation."],
                    ['role' => 'user', 'content' => $message],
                ],
                'max_tokens' => 200,
            ], 'openai_translate'),
            'openai_chat_api',
            3,
            60,
            fn () => $this->fallbacks['translate_message'] ?? $message
        );
        return $result ?: ($this->fallbacks['translate_message'] ?? $message);
    }
}
