<?php declare(strict_types=1);

namespace App\Tests\Unit\Realtime\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Service\AccessibilityReportService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AccessibilityReportServiceTest extends TestCase
{
    private AccessibilityReportService $service;

    protected function setUp(): void
    {
        $this->service = new AccessibilityReportService();
    }

    public function testGenerateReturnsArrayWithRequiredKeys(): void
    {
        $callId = 'call-123';
        $result = $this->service->generate($callId);

        $this->assertIsArray($result);
        $this->assertSame($callId, $result['call_id']);
        $this->assertArrayHasKey('accessibility_features', $result);
        $this->assertArrayHasKey('compliance', $result);
        $this->assertArrayHasKey('generated_at', $result);
    }

    public function testAccessibilityFeaturesStructure(): void
    {
        $result = $this->service->generate('call-456');
        $features = $result['accessibility_features'];

        $this->assertArrayHasKey('live_captions', $features);
        $this->assertArrayHasKey('sign_language_interpretation', $features);
        $this->assertArrayHasKey('audio_description', $features);
        $this->assertArrayHasKey('high_contrast_mode', $features);
        $this->assertArrayHasKey('screen_reader_support', $features);
        $this->assertArrayHasKey('keyboard_navigation', $features);
    }

    public function testLiveCaptionsFeatureValues(): void
    {
        $features = $this->service->generate('c1')['accessibility_features']['live_captions'];
        $this->assertTrue($features['enabled']);
        $this->assertContains('en', $features['languages']);
        $this->assertContains('es', $features['languages']);
        $this->assertSame(0.94, $features['quality_score']);
    }

    public function testSignLanguageInterpretationFeatureValues(): void
    {
        $features = $this->service->generate('c2')['accessibility_features']['sign_language_interpretation'];
        $this->assertFalse($features['enabled']);
        $this->assertSame(3, $features['available_interpreters']);
        $this->assertSame(0, $features['active_interpreters']);
    }

    public function testAudioDescriptionFeatureValues(): void
    {
        $features = $this->service->generate('c3')['accessibility_features']['audio_description'];
        $this->assertFalse($features['enabled']);
        $this->assertTrue($features['available']);
    }

    public function testHighContrastModeFeatureValues(): void
    {
        $features = $this->service->generate('c4')['accessibility_features']['high_contrast_mode'];
        $this->assertFalse($features['enabled']);
        $this->assertTrue($features['available']);
    }

    public function testScreenReaderSupportFeatureValues(): void
    {
        $features = $this->service->generate('c5')['accessibility_features']['screen_reader_support'];
        $this->assertTrue($features['enabled']);
        $this->assertSame(0.98, $features['compatibility_score']);
    }

    public function testKeyboardNavigationFeatureValues(): void
    {
        $features = $this->service->generate('c6')['accessibility_features']['keyboard_navigation'];
        $this->assertTrue($features['enabled']);
        $this->assertSame(0.96, $features['accessibility_score']);
    }

    public function testComplianceFlagsAllTrue(): void
    {
        $compliance = $this->service->generate('c7')['compliance'];
        $this->assertTrue($compliance['wcag_2_1_aa']);
        $this->assertTrue($compliance['section_508']);
        $this->assertTrue($compliance['ada_compliant']);
    }

    public function testGeneratedAtIsRecentTimestamp(): void
    {
        $before = time();
        $result = $this->service->generate('c8');
        $after = time();
        $generated = $result['generated_at'];
        $this->assertGreaterThanOrEqual($before, $generated);
        $this->assertLessThanOrEqual($after, $generated);
    }

    public function testDifferentCallIdsProduceSameStructure(): void
    {
        $ids = ['c1', 'c2', 'c3', 'c999'];
        foreach ($ids as $id) {
            $result = $this->service->generate($id);
            $this->assertSame($id, $result['call_id']);
            $this->assertArrayHasKey('accessibility_features', $result);
        }
    }
}
