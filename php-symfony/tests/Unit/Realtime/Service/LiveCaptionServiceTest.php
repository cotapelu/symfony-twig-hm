<?php declare(strict_types=1);

namespace App\Tests\Unit\Realtime\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Service\LiveCaptionService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class LiveCaptionServiceTest extends TestCase
{
    private LiveCaptionService $service;

    protected function setUp(): void
    {
        $this->service = new LiveCaptionService();
    }

    public function testGenerateReturnsArrayWithExpectedKeys(): void
    {
        $result = $this->service->generate('audio-data', 'en');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('captions', $result);
        $this->assertArrayHasKey('language', $result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testGenerateUsesProvidedLanguage(): void
    {
        $result = $this->service->generate('data', 'fr');
        $this->assertSame('fr', $result['language']);
    }

    public function testGenerateReturnsDefaultLanguageWhenNotProvided(): void
    {
        $result = $this->service->generate('data');
        $this->assertSame('en', $result['language']);
    }

    public function testCaptionsArrayHasCorrectStructure(): void
    {
        $result = $this->service->generate('audio', 'es');
        $captions = $result['captions'];
        $this->assertIsArray($captions);
        $this->assertCount(2, $captions);
        foreach ($captions as $caption) {
            $this->assertArrayHasKey('text', $caption);
            $this->assertArrayHasKey('start_time', $caption);
            $this->assertArrayHasKey('end_time', $caption);
            $this->assertArrayHasKey('confidence', $caption);
            $this->assertIsFloat($caption['confidence']);
            $this->assertGreaterThanOrEqual(0, $caption['confidence']);
            $this->assertLessThanOrEqual(1, $caption['confidence']);
        }
    }

    public function testStatusIsActive(): void
    {
        $result = $this->service->generate('any');
        $this->assertSame('active', $result['status']);
    }
}
