<?php declare(strict_types=1);

namespace App\Tests\Unit\Realtime\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Service\SignLanguageService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SignLanguageServiceTest extends TestCase
{
    private SignLanguageService $service;

    protected function setUp(): void
    {
        $this->service = new SignLanguageService();
    }

    public function testIsSupportedReturnsTrueForSupportedLanguages(): void
    {
        $this->assertTrue($this->service->isSupported('asl'));
        $this->assertTrue($this->service->isSupported('bsl'));
        $this->assertTrue($this->service->isSupported('csl'));
    }

    public function testIsSupportedReturnsFalseForUnsupported(): void
    {
        $this->assertFalse($this->service->isSupported('xyz'));
        $this->assertFalse($this->service->isSupported(''));
    }

    public function testGetAllReturnsInterpretersArray(): void
    {
        $all = $this->service->getAll();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('asl', $all);
        $this->assertSame('American Sign Language', $all['asl']);
        $this->assertCount(12, $all);
    }

    public function testStartInterpretationReturnsExpectedArray(): void
    {
        $result = $this->service->startInterpretation('call-123', 'en', 'asl');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('interpreter_id', $result);
        $this->assertStringStartsWith('int_', $result['interpreter_id']);
        $this->assertSame('call-123', $result['call_id']);
        $this->assertSame('en', $result['source_language']);
        $this->assertSame('asl', $result['target_sign_language']);
        $this->assertSame('connecting', $result['status']);
        $this->assertSame(2.0, $result['estimated_delay']);
    }

    public function testStartInterpretationGeneratesUniqueIds(): void
    {
        $r1 = $this->service->startInterpretation('c1', 'en', 'bsl');
        $r2 = $this->service->startInterpretation('c1', 'en', 'bsl');
        $this->assertNotSame($r1['interpreter_id'], $r2['interpreter_id']);
    }

    public function testStopInterpretationReturnsTrue(): void
    {
        $result = $this->service->stopInterpretation('any_id');
        $this->assertTrue($result);
    }
}
