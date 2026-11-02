<?php

declare(strict_types=1);

namespace Tests\Unit\Message\Service;

use App\Message\Service\AIService;
use App\Message\Service\ContentSuggestionFactory;
use PHPUnit\Framework\TestCase;

// Stub for ContentSuggestionFactory
class StubContentSuggestionFactory extends ContentSuggestionFactory
{
    private array $generators = [];

    public function registerGenerator($generator): void
    {
        $this->generators[] = $generator;
    }

    public function getGenerator(string $type)
    {
        return $this->generators[0] ?? null;
    }
}

// Stub generator
class StubSuggestionGenerator
{
    public function generateSuggestions(): array
    {
        return ['Suggestion 1', 'Suggestion 2'];
    }
}

class AIServiceTest extends TestCase
{
    private string $originalEnv;

    protected function setUp(): void
    {
        $this->originalEnv = getenv('OPENAI_API_KEY') ?: '';
        putenv('OPENAI_API_KEY=');
    }

    protected function tearDown(): void
    {
        putenv('OPENAI_API_KEY=' . $this->originalEnv);
    }

    /**
     * Create an AIService instance with stubbed factory, bypassing constructor.
     */
    private function getAIService(): AIService
    {
        $reflection = new \ReflectionClass(AIService::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        // Inject stubbed factory
        $factory = new StubContentSuggestionFactory();
        $factory->registerGenerator(new StubSuggestionGenerator());

        $prop = $reflection->getProperty('suggestionFactory');
        $prop->setAccessible(true);
        $prop->setValue($instance, $factory);

        return $instance;
    }

    // ==================== generateSmartReplies ====================

    public function testGenerateSmartReplies_EmptyMessage_ReturnsPlaceholder(): void
    {
        $service = $this->getAIService();
        $result = $service->generateSmartReplies('');
        $this->assertIsArray($result);
        $this->assertStringContainsString('Please provide a message', $result[0]);
    }

    public function testGenerateSmartReplies_LongMessage_Truncated(): void
    {
        $service = $this->getAIService();
        $long = str_repeat('a', 1500);
        $result = $service->generateSmartReplies($long);
        $this->assertIsArray($result);
    }

    public function testGenerateSmartReplies_NoApiKey_UsesMock(): void
    {
        $service = $this->getAIService();
        $result = $service->generateSmartReplies('Hello');
        $this->assertIsArray($result);
    }

    // ==================== analyzeSentiment ====================

    public function testAnalyzeSentiment_ReturnsArrayWithKeys(): void
    {
        $service = $this->getAIService();
        $result = $service->analyzeSentiment('I love this!');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('scores', $result);
    }

    // ==================== moderateContent ====================

    public function testModerateContent_SafeContent_ReturnsLowSeverity(): void
    {
        $service = $this->getAIService();
        $result = $service->moderateContent('This is a safe message.');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_flagged', $result);
        $this->assertArrayHasKey('severity', $result);
        $this->assertSame('low', $result['severity']);
    }

    public function testModerateContent_WithSpamWord_Flags(): void
    {
        $service = $this->getAIService();
        $result = $service->moderateContent('This is spam content');
        $this->assertTrue($result['is_flagged']);
        $this->assertContains('spam', $result['flags']);
    }

    // ==================== summarizeContent ====================

    public function testSummarizeContent_ShortText_ReturnsSame(): void
    {
        $service = $this->getAIService();
        $text = 'Short text.';
        $summary = $service->summarizeContent($text, 100);
        $this->assertSame($text, $summary);
    }

    public function testSummarizeContent_LongText_ReturnsTruncated(): void
    {
        $service = $this->getAIService();
        $text = str_repeat('This is a sentence. ', 20);
        $summary = $service->summarizeContent($text, 50);
        $this->assertLessThanOrEqual(50, strlen($summary));
    }

    // ==================== detectLanguage ====================

    public function testDetectLanguage_English_ReturnsEn(): void
    {
        $service = $this->getAIService();
        $result = $service->detectLanguage('Hello world');
        $this->assertSame('en', $result['language']);
    }

    public function testDetectLanguage_Vietnamese_ReturnsVi(): void
    {
        $service = $this->getAIService();
        $result = $service->detectLanguage('Xin chào, tôi là người Việt Nam');
        $this->assertSame('vi', $result['language']);
    }

    public function testDetectLanguage_Spanish_ReturnsEs(): void
    {
        $service = $this->getAIService();
        // Use a Spanish phrase with a keyword that the mock detection recognizes ('el')
        $result = $service->detectLanguage('Hola el mundo');
        $this->assertSame('es', $result['language']);
    }

    public function testDetectLanguage_Empty_ReturnsEn(): void
    {
        $service = $this->getAIService();
        $result = $service->detectLanguage('');
        $this->assertSame('en', $result['language']);
    }

    // ==================== translateContent ====================

    public function testTranslateContent_FromEnToEs_ReturnsTranslation(): void
    {
        $service = $this->getAIService();
        $result = $service->translateContent('Hello', 'es', 'en');
        $this->assertSame('en', $result['source_language']);
        $this->assertSame('es', $result['target_language']);
        $this->assertNotEmpty($result['translated_text']);
    }

    public function testTranslateContent_AutoDetect_ReturnsTranslated(): void
    {
        $service = $this->getAIService();
        $result = $service->translateContent('Hello world', 'fr');
        $this->assertSame('fr', $result['target_language']);
        $this->assertNotEmpty($result['translated_text']);
    }

    // ==================== generateContentSuggestions ====================

    public function testGenerateContentSuggestions_ReturnsArray(): void
    {
        $service = $this->getAIService();
        $suggestions = $service->generateContentSuggestions('Some context');
        $this->assertIsArray($suggestions);
        $this->assertLessThanOrEqual(3, count($suggestions));
    }

    // ==================== analyzeConversationPatterns ====================

    public function testAnalyzeConversationPatterns_ReturnsStats(): void
    {
        $service = $this->getAIService();
        $messages = [
            ['content' => 'Hello'],
            ['content' => 'How are you?'],
            ['content' => 'I am fine, thanks!'],
        ];
        $result = $service->analyzeConversationPatterns($messages);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_messages', $result);
        $this->assertSame(3, $result['total_messages']);
        $this->assertArrayHasKey('engagement_level', $result);
    }

    // ==================== generateMeetingNotes ====================

    public function testGenerateMeetingNotes_ReturnsStructure(): void
    {
        $service = $this->getAIService();
        $messages = [
            ['content' => 'Lets discuss the project timeline.'],
            ['content' => 'I will complete testing by Friday.'],
            ['content' => 'Sarah will prepare the client presentation.'],
        ];
        $result = $service->generateMeetingNotes($messages);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('action_items', $result);
        $this->assertArrayHasKey('decisions', $result);
        $this->assertArrayHasKey('participants', $result);
    }
}
