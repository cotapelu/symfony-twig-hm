<?php

declare(strict_types=1);

namespace App\Message\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * AI Service
 * Handles AI-powered features like smart replies, content moderation, sentiment analysis.
 */
class AIService
{
    private const LANGUAGES = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'zh' => 'Chinese',
        'vi' => 'Vietnamese',
    ];

    public function __construct(
        private ContentSuggestionFactory $suggestionFactory,
    ) {
        // Register default generators
        $this->suggestionFactory->registerGenerator(new MessageSuggestionGenerator());
        $this->suggestionFactory->registerGenerator(new MeetingSuggestionGenerator());
        $this->suggestionFactory->registerGenerator(new FeedbackSuggestionGenerator());
    }

    /**
     * Generate smart replies for a message.
     */
    public function generateSmartReplies(string $messageContent, array $conversationContext = []) {
        $messageContent = trim($messageContent);
        if (empty($messageContent)) return ['Please provide a message to reply to.'];
        if (strlen($messageContent) > 1000) $messageContent = substr($messageContent, 0, 1000).'...';
        $conversationContext = array_filter(array_map('trim', $conversationContext));
        $conversationContext = array_slice($conversationContext, -5);
        $apiKey = getenv('OPENAI_API_KEY');
        if (!$apiKey) return $this->getMockSmartReplies();
        try {
            $response = $this->callOpenAI($this->buildSmartRepliesPrompt($messageContent, $conversationContext), 3);
            return $this->processSmartRepliesResponse($response);
        } catch (\Exception $e) {
            error_log('OpenAI API error in generateSmartReplies: '.$e->getMessage().' | Input: '.substr($messageContent, 0, 100));
            return $this->getMockSmartReplies();
        }
    }

    private function buildSmartRepliesPrompt(string $messageContent, array $conversationContext): string {
        $context = !empty($conversationContext) ? implode("\n", array_slice($conversationContext, -3)) : '';
        $prompt = "Generate 3 smart reply suggestions for this message: \"$messageContent\"\n";
        if ($context) $prompt .= "Conversation context:\n$context\n";
        $prompt .= 'Replies should be natural, helpful, and contextually appropriate. Keep each reply under 100 characters.';
        if (strlen($prompt) > 4000) $prompt = substr($prompt, 0, 4000).'...';
        return $prompt;
    }

    private function processSmartRepliesResponse(array $response): array {
        $replies = array_map(fn($c)=>trim($c['message']['content']??''), $response['choices']??[]);
        $replies = array_filter($replies, fn($r)=>!empty($r) && strlen($r)<=100);
        return !empty($replies) ? array_slice($replies,0,3) : $this->getMockSmartReplies();
    }

    private function getMockSmartReplies()
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

    private function callOpenAI(string $prompt, int $count = 1)
    {
        $apiKey = getenv('OPENAI_API_KEY');
        $url = 'https://api.openai.com/v1/chat/completions';

        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant that generates smart reply suggestions.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
            'max_tokens' => 150,
            'n' => $count,
            'temperature' => 0.7,
        ];

        return $this->callOpenAIDirect($data);
    }

    private function callOpenAIDirect(array $data)
    {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json','Authorization: Bearer '.getenv('OPENAI_API_KEY')],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) throw new \Exception('Curl error: '.$error);
        if (200 !== $httpCode) throw new \Exception('OpenAI API error: '.$response);
        $result = json_decode($response, true);
        if (JSON_ERROR_NONE !== json_last_error()) throw new \Exception('Invalid JSON response from OpenAI');
        return $result;
    }

    /**
     * Analyze sentiment of a message.
     */
    public function analyzeSentiment(string $messageContent)
    {
        if (!getenv('OPENAI_API_KEY')) return $this->analyzeSentimentMock($messageContent);
        try {
            $start = microtime(true);
            $prompt = "Analyze the sentiment of this message: \"$messageContent\"\nReturn JSON with format: {\"sentiment\": \"positive|negative|neutral\", \"confidence\": 0.0-1.0, \"positive_score\": 0.0-1.0, \"negative_score\": 0.0-1.0, \"neutral_score\": 0.0-1.0}";
            $data = ['model'=>'gpt-3.5-turbo','messages'=>[['role'=>'system','content'=>'You are a sentiment analyzer. Return only JSON.'],['role'=>'user','content'=>$prompt]],'max_tokens'=>200,'temperature'=>0.3];
            $result = $this->callOpenAIDirect($data);
            $content = $result['choices'][0]['message']['content'] ?? '';
            $analysis = json_decode($content, true);
            if (JSON_ERROR_NONE !== json_last_error()) return $this->analyzeSentimentMock($messageContent);
            error_log("OpenAI sentiment analysis took ".(microtime(true)-$start)." seconds");
            return $analysis;
        } catch (\Exception $e) {
            error_log('OpenAI sentiment analysis error: '.$e->getMessage());
            return $this->analyzeSentimentMock($messageContent);
        }
    }

    private function analyzeSentimentMock(string $messageContent)
    {
        $positiveWords = ['good','great','excellent','awesome','love','like','happy','thanks','thank','amazing','wonderful','perfect','best','fantastic','brilliant','excited','glad','pleased','satisfied'];
        $negativeWords = ['bad','terrible','hate','angry','sad','disappointed','sorry','problem','issue','worst','horrible','awful','terrible','upset','frustrated','annoyed','annoying','disappointing'];
        $lower = strtolower($messageContent);
        $pos = $neg = 0;
        foreach ($positiveWords as $w) if (false!==strpos($lower,$w)) ++$pos;
        foreach ($negativeWords as $w) if (false!==strpos($lower,$w)) ++$neg;
        if ($pos > $neg) { $sentiment='positive'; $confidence=min(0.95,0.7+$pos*0.05); }
        elseif ($neg > $pos) { $sentiment='negative'; $confidence=min(0.95,0.7+$neg*0.05); }
        else { $sentiment='neutral'; $confidence=0.6; }
        $posScore = $pos>0 ? min(1.0,0.5+$pos*0.1) : 0.1;
        $negScore = $neg>0 ? min(1.0,0.5+$neg*0.1) : 0.1;
        $neuScore = max(0.1, 1.0 - $posScore - $negScore);
        return ['sentiment'=>$sentiment,'confidence'=>$confidence,'scores'=>['positive'=>$posScore,'negative'=>$negScore,'neutral'=>$neuScore]];
    }

    /**
     * Moderate content for inappropriate material.
     */
    public function moderateContent(string $content, string $context = 'message')
    {
        $apiKey = getenv('OPENAI_API_KEY');
        if (!$apiKey) {
            return $this->moderateContentMock($content);
        }
        try {
            $startTime = microtime(true);
            $result = $this->callModerationCurl($content);
            $moderation = $this->extractFirstModeration($result);
            if (!$moderation) {
                return $this->moderateContentMock($content);
            }
            $response = $this->buildModerationResponse($moderation);
            $this->logModerationDuration(microtime(true) - $startTime);
            return $response;
        } catch (\Exception $e) {
            error_log('OpenAI moderation error: '.$e->getMessage());
            return $this->moderateContentMock($content);
        }
    }

    private function callModerationCurl(string $content): array
    {
        $url = 'https://api.openai.com/v1/moderations';
        $data = ['input' => $content];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.getenv('OPENAI_API_KEY'),
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) throw new \Exception('Curl error: '.$error);
        if (200 !== $httpCode) throw new \Exception('OpenAI Moderation API error: '.$response);
        $result = json_decode($response, true);
        if (JSON_ERROR_NONE !== json_last_error()) throw new \Exception('Invalid JSON response from OpenAI');
        return $result;
    }

    private function extractFirstModeration(array $result): ?array
    {
        $moderation = $result['results'][0] ?? null;
        if (!$moderation) return null;
        // Validate keys exists? trust for now.
        return $moderation;
    }

    private function buildModerationResponse(array $moderation): array
    {
        $categories = array_filter($moderation['categories'], fn ($v) => $v === true);
        $categoryScores = $moderation['category_scores'] ?? [];
        $flagged = $moderation['flagged'] ?? false;
        $severity = 'low';
        if ($flagged) {
            $maxScore = 0;
            foreach ($categoryScores as $score) { if ($score > $maxScore) $maxScore = $score; }
            if ($maxScore > 0.8) $severity = 'high'; elseif ($maxScore > 0.5) $severity = 'medium';
        }
        return [
            'is_flagged' => $flagged,
            'severity' => $severity,
            'flags' => array_keys($categories),
            'confidence' => $flagged ? ($moderation['category_scores']['harassment'] ?? 0.5) : 0.95,
            'categories' => array_keys($categories),
            'recommendation' => $flagged ? 'Review content before publishing' : 'Content appears safe',
        ];
    }

    private function logModerationDuration(float $duration): void
    {
        error_log("OpenAI moderation check took {$duration} seconds");
    }

    private function moderateContentMock(string $content)
    {
        $inappropriateWords = ['spam', 'offensive', 'inappropriate', 'hate', 'violence', 'hateful', 'abusive', 'toxic', 'threat'];
        $flags = [];

        $lowerContent = strtolower($content);

        foreach ($inappropriateWords as $word) {
            if (false !== strpos($lowerContent, $word)) {
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

    /**
     * Generate content summary.
     */
    public function summarizeContent(string $content, int $maxLength = 100)
    {
        if (strlen($content) <= $maxLength) return $content;
        if (!getenv('OPENAI_API_KEY')) return $this->summarizeContentMock($content, $maxLength);
        try {
            $start = microtime(true);
            $data = ['model'=>'gpt-3.5-turbo','messages'=>[['role'=>'system','content'=>"You are a helpful summarizer. Summarize text concisely in under $maxLength characters."],['role'=>'user','content'=>"Summarize this: \"$content\""]],'max_tokens'=>ceil($maxLength/4),'temperature'=>0.5];
            $result = $this->callOpenAIDirect($data);
            $summary = trim($result['choices'][0]['message']['content'] ?? $content);
            if (strlen($summary) > $maxLength) $summary = substr($summary,0,$maxLength-3).'...';
            error_log("OpenAI summarization took ".(microtime(true)-$start)." seconds");
            return $summary;
        } catch (\Exception $e) {
            error_log('OpenAI summarization error: '.$e->getMessage());
            return $this->summarizeContentMock($content, $maxLength);
        }
    }

    private function summarizeContentMock(string $content, int $maxLength)
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $summary = '';

        foreach ($sentences as $sentence) {
            $trimmed = trim($sentence);
            if (strlen($summary.'. '.$trimmed) <= $maxLength) {
                $summary .= ($summary ? '. ' : '').$trimmed;
            } else {
                break;
            }
        }

        return $summary.(strlen($summary) < strlen($content) ? '...' : '');
    }

    /**
     * Detect language of content.
     */
    public function detectLanguage(string $content)
    {
        if (!getenv('OPENAI_API_KEY')) return $this->detectLanguageMock($content);
        try {
            $start = microtime(true);
            $data = ['model'=>'gpt-3.5-turbo','messages'=>[['role'=>'system','content'=>'You are a language detector. Return only JSON format: {"language": "en", "language_name": "English", "confidence": 0.95}'],['role'=>'user','content'=>"Detect the language of this text: \"$content\""]],'max_tokens'=>50,'temperature'=>0.1];
            $result = $this->callOpenAIDirect($data);
            $detected = json_decode($result['choices'][0]['message']['content'] ?? '{}', true);
            if (JSON_ERROR_NONE !== json_last_error()) return $this->detectLanguageMock($content);
            error_log("OpenAI language detection took ".(microtime(true)-$start)." seconds");
            return $detected;
        } catch (\Exception $e) {
            error_log('OpenAI language detection error: '.$e->getMessage());
            return $this->detectLanguageMock($content);
        }
    }

    private function detectLanguageMock(string $content): array
    {
        $lowerContent = strtolower($content);
        $detected = $this->detectByKeywords($lowerContent)
            ?? $this->detectByScript($content)
            ?? $this->detectVietnamese($lowerContent)
            ?? 'en';
        $confidence = $this->getConfidence($detected);
        return [
            'language' => $detected,
            'language_name' => self::LANGUAGES[$detected] ?? 'Unknown',
            'confidence' => $confidence,
            'alternatives' => array_slice(array_keys(self::LANGUAGES), 0, 3),
        ];
    }

    private function detectByKeywords(string $lowerContent): ?string
    {
        return $this->isSpanish($lowerContent) ? 'es'
            : ($this->isFrench($lowerContent) ? 'fr'
            : ($this->isGerman($lowerContent) ? 'de'
            : null));
    }

    private function isSpanish(string $lowerContent): bool
    {
        return false !== strpos($lowerContent, ' el ')
            || false !== strpos($lowerContent, ' la ')
            || false !== strpos($lowerContent, 'que ');
    }

    private function isFrench(string $lowerContent): bool
    {
        return false !== strpos($lowerContent, ' le ')
            || false !== strpos($lowerContent, ' la ')
            || false !== strpos($lowerContent, 'et ');
    }

    private function isGerman(string $lowerContent): bool
    {
        return false !== strpos($lowerContent, ' der ')
            || false !== strpos($lowerContent, ' die ')
            || false !== strpos($lowerContent, 'das ');
    }

    private function detectByScript(string $content): ?string
    {
        if (preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $content)) {
            return 'ja';
        }
        if (preg_match('/[\p{Hangul}]/u', $content)) {
            return 'ko';
        }
        if (preg_match('/[\p{Han}]/u', $content) && !preg_match('/[\p{Hiragana}\p{Katakana}]/u', $content)) {
            return 'zh';
        }
        return null;
    }

    private function detectVietnamese(string $lowerContent): ?string
    {
        if (preg_match('/[àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþß]/u', $lowerContent) && !preg_match('/[ñ]/u', $lowerContent)) {
            return 'vi';
        }
        return null;
    }

    private function getConfidence(string $language): float
    {
        return match($language) {
            'ja', 'ko' => 0.9,
            'zh' => 0.85,
            default => 0.8,
        };
    }

    /**
     * Translate content to target language.
     */
    public function translateContent(string $content, string $targetLanguage, string $sourceLanguage = 'auto') {
        $apiKey = getenv('OPENAI_API_KEY');
        if (!$apiKey) return $this->translateContentMock($content, $targetLanguage, $sourceLanguage);
        try {
            $startTime = microtime(true);
            $languageNames = ['en'=>'English','es'=>'Spanish','fr'=>'French','de'=>'German','it'=>'Italian','pt'=>'Portuguese','ru'=>'Russian','ja'=>'Japanese','ko'=>'Korean','zh'=>'Chinese','vi'=>'Vietnamese'];
            $sourcePrompt = 'auto' === $sourceLanguage ? 'Detect and translate' : "Translate from {$languageNames[$sourceLanguage]}";
            $prompt = "$sourcePrompt to {$languageNames[$targetLanguage]}: \"$content\"";
            $messages = [['role'=>'system','content'=>'You are a professional translator. Translate accurately while maintaining tone and context.'],['role'=>'user','content'=>$prompt]];
            $data = ['model'=>'gpt-3.5-turbo','messages'=>$messages,'max_tokens'=>500,'temperature'=>0.3];
            $result = $this->callOpenAIDirect($data);
            $translated = trim($result['choices'][0]['message']['content'] ?? $content);
            $duration = microtime(true)-$startTime;
            error_log("OpenAI translation took {$duration} seconds");
            return ['original_text'=>$content,'translated_text'=>$translated,'source_language'=>$sourceLanguage,'target_language'=>$targetLanguage,'confidence'=>0.9];
        } catch (\Exception $e) {
            error_log('OpenAI translation error: '.$e->getMessage());
            return $this->translateContentMock($content, $targetLanguage, $sourceLanguage);
        }
    }

    private function translateContentMock(string $content, string $targetLanguage, string $sourceLanguage)
    {
        $translations = [
            'en' => [
                'es' => 'Esta es una traducción simulada al español.',
                'fr' => 'Ceci est une traduction simulée en français.',
                'de' => 'Dies ist eine simulierte Übersetzung auf Deutsch.',
                'it' => 'Questa è una traduzione simulata in italiano.',
                'pt' => 'Esta é uma tradução simulada para português.',
                'ru' => 'Это симулированный перевод на русский.',
                'ja' => 'これは日本語へのシミュレートされた翻訳です。',
                'ko' => '이것은 한국어로 된 시뮬레이션된 번역입니다.',
                'zh' => '这是模拟的中文翻译。',
                'vi' => 'Đây là bản dịch giả lập sang tiếng Việt.',
            ],
        ];

        $sourceLang = 'auto' === $sourceLanguage ? 'en' : $sourceLanguage;
        $translated = $translations[$sourceLang][$targetLanguage] ?? 'Translation not available for this language pair.';

        return [
            'original_text' => $content,
            'translated_text' => $translated,
            'source_language' => $sourceLang,
            'target_language' => $targetLanguage,
            'confidence' => 0.85,
        ];
    }

    /**
     * Generate AI-powered content suggestions.
     */
    public function generateContentSuggestions(string $context, string $type = 'message')
    {
        $generator = $this->suggestionFactory->getGenerator($type);
        if (!$generator) {
            return []; // No suggestions for unknown type
        }

        $suggestions = $generator->generateSuggestions();
        shuffle($suggestions);

        return array_slice($suggestions, 0, 3);
    }

    /**
     * Analyze conversation patterns.
     */
    public function analyzeConversationPatterns(array $messages)
    {
        // Mock conversation analysis
        $totalMessages = count($messages);
        $avgLength = array_sum(array_map('strlen', array_column($messages, 'content'))) / $totalMessages;
        $sentimentDistribution = ['positive' => 0.6, 'negative' => 0.2, 'neutral' => 0.2];

        return [
            'total_messages' => $totalMessages,
            'average_message_length' => round($avgLength, 1),
            'sentiment_distribution' => $sentimentDistribution,
            'engagement_level' => $totalMessages > 10 ? 'high' : ($totalMessages > 5 ? 'medium' : 'low'),
            'topics' => ['work', 'planning', 'collaboration'], // Mock topics
            'insights' => [
                'Conversation is highly collaborative',
                'Positive sentiment dominates',
                'Good balance of participation',
            ],
        ];
    }

    /**
     * Generate meeting notes from conversation.
     */
    public function generateMeetingNotes(array $messages)
    {
        // Mock meeting notes generation
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
}
