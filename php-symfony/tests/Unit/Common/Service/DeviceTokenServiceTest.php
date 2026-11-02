<?php declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Common\Service\DeviceTokenService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeviceTokenServiceTest extends TestCase
{
    public function testDoSomethingReturnsFormattedString(): void
    {
        $service = new DeviceTokenService();
        $result = $service->doSomething('token123');
        $this->assertSame('DeviceTokenService: token123', $result);
    }

    public function testDoSomethingHandlesEmptyString(): void
    {
        $service = new DeviceTokenService();
        $result = $service->doSomething('');
        $this->assertSame('DeviceTokenService: ', $result);
    }

    public function testDoSomethingHandlesSpecialChars(): void
    {
        $service = new DeviceTokenService();
        $result = $service->doSomething('a&b<c>d');
        $this->assertSame('DeviceTokenService: a&b<c>d', $result);
    }
}