<?php declare(strict_types=1);

namespace App\Tests\Unit\Realtime\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Service\CallAccessibilityService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CallAccessibilityServiceTest extends TestCase
{
    private CallAccessibilityService $service;

    protected function setUp(): void
    {
        $this->service = new CallAccessibilityService();
    }

    public function testIsLanguageSupportedReturnsTrueForSupported(): void
    {
        $this->assertTrue($this->service->isLanguageSupported('en'));
        $this->assertTrue($this->service->isLanguageSupported('es'));
    }

    public function testIsLanguageSupportedReturnsFalseForUnsupported(): void
    {
        $this->assertFalse($this->service->isLanguageSupported('jp'));
    }

    public function testGetSupportedLanguagesReturnsArray(): void
    {
        $langs = $this->service->getSupportedLanguages();
        $this->assertIsArray($langs);
        $this->assertArrayHasKey('en', $langs);
        $this->assertSame('English', $langs['en']);
    }

    public function testIsSignLanguageSupportedReturnsTrueForSupported(): void
    {
        $this->assertTrue($this->service->isSignLanguageSupported('asl'));
    }

    public function testIsSignLanguageSupportedReturnsFalseForUnsupported(): void
    {
        $this->assertFalse($this->service->isSignLanguageSupported('xyz'));
    }

    public function testGetSupportedSignLanguagesReturnsArray(): void
    {
        $langs = $this->service->getSupportedSignLanguages();
        $this->assertIsArray($langs);
        $this->assertArrayHasKey('asl', $langs);
        $this->assertSame('American Sign Language', $langs['asl']);
    }

    public function testGenerateLiveCaptionsReturnsExpectedStructure(): void
    {
        $result = $this->service->generateLiveCaptions('audio-data', 'es');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('captions', $result);
        $this->assertIsArray($result['captions']);
        $this->assertCount(2, $result['captions']);
        $this->assertArrayHasKey('text', $result['captions'][0]);
        $this->assertSame('es', $result['language']);
        $this->assertSame('active', $result['status']);
    }

    public function testStartSignLanguageInterpretationReturnsArrayWithKeys(): void
    {
        $result = $this->service->startSignLanguageInterpretation('call-123', 'en', 'asl');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('interpreter_id', $result);
        $this->assertSame('call-123', $result['call_id']);
        $this->assertSame('en', $result['source_language']);
        $this->assertSame('asl', $result['target_sign_language']);
        $this->assertSame('connecting', $result['status']);
        $this->assertSame(2.0, $result['estimated_delay']);
    }

    public function testStopSignLanguageInterpretationReturnsTrue(): void
    {
        $result = $this->service->stopSignLanguageInterpretation('int_123');
        $this->assertTrue($result);
    }

    public function testGenerateAccessibilityReportReturnsStructure(): void
    {
        $result = $this->service->generateAccessibilityReport('call-456');
        $this->assertIsArray($result);
        $this->assertSame('call-456', $result['call_id']);
        $this->assertArrayHasKey('accessibility_features', $result);
        $this->assertIsArray($result['accessibility_features']);
        $this->assertArrayHasKey('live_captions', $result['accessibility_features']);
        $this->assertArrayHasKey('sign_language_interpretation', $result['accessibility_features']);
    }
}
