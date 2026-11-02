<?php

declare(strict_types=1);

namespace App\Tests\Message\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Message\Service\TranslationService;
use App\Shared\Service\CircuitBreakerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class TranslationServiceTest extends TestCase
{
    private TranslationService $translationService;

    protected function setUp(): void
    {
        $circuitBreaker = $this->createMock(CircuitBreakerService::class);
        $logger = $this->createMock(LoggerInterface::class);

        $circuitBreaker->method('execute')->willReturnCallback(function ($fn) {
            return $fn();
        });

        $this->translationService = new TranslationService($circuitBreaker, $logger);
    }

    public function testTranslateEmptyText(): void
    {
        $result = $this->translationService->translate('', 'en', 'es');

        $this->assertEquals('', $result);
    }

    public function testTranslateWhitespaceOnly(): void
    {
        $result = $this->translationService->translate('   ', 'en', 'es');

        $this->assertEquals('', $result);
    }

    public function testTranslateSameLanguageReturnsOriginal(): void
    {
        $text = 'Hello World';
        $result = $this->translationService->translate($text, 'en', 'en');

        $this->assertEquals($text, $result);
    }

    public function testTranslateUnsupportedSourceLanguageUsesAuto(): void
    {
        $text = 'Hello';
        $result = $this->translationService->translate($text, 'unsupported', 'en');

        $this->assertIsString($result);
    }

    public function testTranslateUnsupportedTargetLanguageThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported target language');

        $this->translationService->translate('Hello', 'en', 'unsupported');
    }

    public function testTruncatesLongText(): void
    {
        $longText = str_repeat('A', 6000);
        $result = $this->translationService->translate($longText, 'en', 'es');

        $this->assertStringContainsString('...', $result);
    }

    public function testDetectLanguageEnglish(): void
    {
        $result = $this->translationService->detectLanguage('Hello world');

        $this->assertEquals('en', $result);
    }

    public function testDetectLanguageSpanish(): void
    {
        $result = $this->translationService->detectLanguage('Hola mundo');

        $this->assertEquals('es', $result);
    }

    public function testDetectLanguageFrench(): void
    {
        $result = $this->translationService->detectLanguage('Bonjour le monde');

        $this->assertEquals('fr', $result);
    }

    public function testDetectLanguageGerman(): void
    {
        $result = $this->translationService->detectLanguage('Hallo Welt');

        $this->assertEquals('de', $result);
    }

    public function testDetectLanguageUnknownReturnsEnglish(): void
    {
        $result = $this->translationService->detectLanguage('unknown text');

        $this->assertEquals('en', $result);
    }

    public function testGetSupportedLanguages(): void
    {
        $languages = $this->translationService->getSupportedLanguages();

        $this->assertIsArray($languages);
        $this->assertArrayHasKey('en', $languages);
        $this->assertArrayHasKey('es', $languages);
        $this->assertArrayHasKey('fr', $languages);
        $this->assertArrayHasKey('de', $languages);
    }

    public function testIsLanguageSupportedTrue(): void
    {
        $result = $this->translationService->isLanguageSupported('en');

        $this->assertTrue($result);
    }

    public function testIsLanguageSupportedFalse(): void
    {
        $result = $this->translationService->isLanguageSupported('unsupported');

        $this->assertFalse($result);
    }

    public function testTranslateReturnsMockTranslation(): void
    {
        $text = 'Hello';
        $result = $this->translationService->translate($text, 'en', 'es');

        $this->assertIsString($result);
    }

    public function testTranslateWithUTF8Encoding(): void
    {
        $text = 'Hello ñoño émoji 😊';
        $result = $this->translationService->translate($text, 'en', 'es');

        $this->assertIsString($result);
    }
}
