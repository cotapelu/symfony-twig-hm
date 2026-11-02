<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\UuidService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UuidServiceTest extends TestCase
{
    public function testGenerateReturnsValidUuid(): void
    {
        $service = new UuidService();
        $uuid = $service->generate();
        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid);
    }

    public function testGenerateWithPrefixPrependsPrefix(): void
    {
        $service = new UuidService();
        $prefixed = $service->generateWithPrefix('pre_');
        $this->assertStringStartsWith('pre_', $prefixed);
        $uuidPart = substr($prefixed, 4);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuidPart);
    }

    public function testRandomHexReturns32CharHex(): void
    {
        $hex = UuidService::randomHex(); // static method
        $this->assertIsString($hex);
        $this->assertSame(32, strlen($hex));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $hex);
    }
}
