<?php declare(strict_types=1);

namespace App\Tests\Unit\Realtime\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Service\LanguageSupportService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class LanguageSupportServiceTest extends TestCase
{
    private LanguageSupportService $service;

    protected function setUp(): void
    {
        $this->service = new LanguageSupportService();
    }

    public function testIsSupportedReturnsTrueForSupportedLanguage(): void
    {
        $this->assertTrue($this->service->isSupported('en'));
        $this->assertTrue($this->service->isSupported('zh'));
    }

    public function testIsSupportedReturnsFalseForUnsupportedLanguage(): void
    {
        $this->assertFalse($this->service->isSupported('jp'));
    }

    public function testGetAllReturnsSupportedLanguagesArray(): void
    {
        $langs = $this->service->getAll();
        $this->assertIsArray($langs);
        $this->assertArrayHasKey('en', $langs);
        $this->assertArrayHasKey('fr', $langs);
        $this->assertSame('English', $langs['en']);
    }

    public function testGetNameReturnsLanguageNameForValidCode(): void
    {
        $this->assertSame('English', $this->service->getName('en'));
        $this->assertSame('Spanish', $this->service->getName('es'));
    }

    public function testGetNameReturnsNullForInvalidCode(): void
    {
        $this->assertNull($this->service->getName('invalid'));
    }
}
