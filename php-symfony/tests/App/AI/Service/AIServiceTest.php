<?php

declare(strict_types=1);

namespace App\AI\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Shared\Service\CircuitBreakerService;

/**
 * @covers \App\AI\Service\AIService
 */
#[AllowMockObjectsWithoutExpectations]
class AIServiceTest extends TestCase
{
    private AIService $service;
    private HttpClientInterface $httpClientMock;
    private LoggerInterface $loggerMock;
    private CircuitBreakerService $circuitBreakerMock;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->circuitBreakerMock = $this->createMock(CircuitBreakerService::class);
        
        $this->service = new AIService(
            $this->httpClientMock,
            'test-openai-key',
            $this->circuitBreakerMock,
            $this->loggerMock,
            [],
            3,
            1.0
        );
    }

    public function testGenerateSmartReplyWithValidInput(): void
    {
        $this->circuitBreakerMock
            ->expects($this->any())
            ->method('execute')
            ->willReturn('Smart reply');

        $result = $this->service->generateSmartReply('Hello, how are you?');
        
        $this->assertEquals('Smart reply', $result);
    }

    public function testGenerateSmartReplyWithEmptyInputThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message must not be empty');
        
        $this->service->generateSmartReply('');
    }

    public function testGenerateSmartReplyWithWhitespaceOnlyInputThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message must not be empty');
        
        $this->service->generateSmartReply('   ');
    }

    public function testGenerateSmartReplyWithTooLongInputThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message must not exceed 2000 characters');
        
        $this->service->generateSmartReply(str_repeat('a', 2001));
    }

    public function testAnalyzeSentimentWithValidInput(): void
    {
        $this->circuitBreakerMock
            ->expects($this->any())
            ->method('execute')
            ->willReturn('{"sentiment":"positive","confidence":0.9}');

        $result = $this->service->analyzeSentiment('I love this product!');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertEquals('positive', $result['sentiment']);
        $this->assertEquals(0.9, $result['confidence']);
    }

    public function testAnalyzeSentimentWithEmptyInputThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message must not be empty');
        
        $this->service->analyzeSentiment('');
    }

    public function testTranslateMessageWithValidInput(): void
    {
        $this->circuitBreakerMock
            ->expects($this->any())
            ->method('execute')
            ->willReturn('Hola, ¿cómo estás?');

        $result = $this->service->translateMessage('Hello, how are you?', 'es');
        
        $this->assertEquals('Hola, ¿cómo estás?', $result);
    }

    public function testTranslateMessageWithEmptyMessageThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message must not be empty');
        
        $this->service->translateMessage('', 'es');
    }

    public function testTranslateMessageWithEmptyTargetLanguageThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target Language must not be empty');
        
        $this->service->translateMessage('Hello', '');
    }

    public function testGenerateSmartRepliesWithValidInput(): void
    {
        $this->circuitBreakerMock
            ->expects($this->any())
            ->method('execute')
            ->willReturn('["Thanks!","Got it","Sounds good"]');

        $result = $this->service->generateSmartReplies('Hello!');
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('Thanks!', $result[0]);
        $this->assertEquals('Got it', $result[1]);
        $this->assertEquals('Sounds good', $result[2]);
    }

    public function testGenerateSmartRepliesWithEmptyInputThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message must not be empty');
        
        $this->service->generateSmartReplies('');
    }

    public function testModerateContentWithValidInput(): void
    {
        $this->circuitBreakerMock
            ->expects($this->any())
            ->method('execute')
            ->willReturn([
                'isSafe' => true,
                'score' => 0.1,
                'categories' => [],
                'flagged' => false
            ]);

        $result = $this->service->moderateContent('This is safe content');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('isSafe', $result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('categories', $result);
        $this->assertArrayHasKey('flagged', $result);
        $this->assertTrue($result['isSafe']);
        $this->assertEquals(0.1, $result['score']);
        $this->assertEmpty($result['categories']);
        $this->assertFalse($result['flagged']);
    }

    public function testModerateContentWithEmptyInputThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Content must not be empty');
        
        $this->service->moderateContent('');
    }

    public function testModerateContentWithTooLongInputThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Content must not exceed 4000 characters');
        
        $this->service->moderateContent(str_repeat('a', 4001));
    }

    public function testValidateStringInputThrowsExceptionForEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('test field must not be empty');
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateStringInput');
        $method->setAccessible(true);
        $method->invoke($this->service, '', 'test field');
    }

    public function testValidateStringInputThrowsExceptionForWhitespaceOnlyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('test field must not be empty');
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateStringInput');
        $method->setAccessible(true);
        $method->invoke($this->service, '   ', 'test field');
    }

    public function testValidateStringInputThrowsExceptionForTooLongString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('test field must not exceed 10 characters');
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateStringInput');
        $method->setAccessible(true);
        $method->invoke($this->service, 'this is too long', 'test field', 10);
    }

    public function testValidateStringInputDoesNotThrowForValidString(): void
    {
        $this->expectNotToPerformAssertions();
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateStringInput');
        $method->setAccessible(true);
        $method->invoke($this->service, 'valid input', 'test field');
    }
}