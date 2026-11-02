<?php

declare(strict_types=1);

namespace App\Tests\Modules\Shared\Service;
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

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid);
    }

    public function testGenerateWithPrefix(): void
    {
        $service = new UuidService();
        $uuid = $service->generateWithPrefix('user_');

        $this->assertStringStartsWith('user_', $uuid);
        $this->assertEquals(36 + 5, strlen($uuid)); // prefix + uuid length
    }

    public function testRandomHexReturns32HexChars(): void
    {
        $hex = UuidService::randomHex();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $hex);
    }
}
