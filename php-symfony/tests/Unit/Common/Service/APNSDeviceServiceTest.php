<?php declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Common\Service\APNSDeviceService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class APNSDeviceServiceTest extends TestCase
{
    public function testDoSomethingReturnsFormattedString(): void
    {
        $service = new APNSDeviceService();
        $result = $service->doSomething('test');
        $this->assertSame('APNSDeviceService: test', $result);
    }

    public function testDoSomethingHandlesEmptyString(): void
    {
        $service = new APNSDeviceService();
        $result = $service->doSomething('');
        $this->assertSame('APNSDeviceService: ', $result);
    }

    public function testDoSomethingHandlesSpecialCharacters(): void
    {
        $service = new APNSDeviceService();
        $result = $service->doSomething('a & b <c>');
        $this->assertSame('APNSDeviceService: a & b <c>', $result);
    }
}