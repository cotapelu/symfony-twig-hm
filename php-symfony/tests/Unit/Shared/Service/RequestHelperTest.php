<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\RequestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class RequestHelperTest extends TestCase
{
    public function testGetJsonDataReturnsArrayForValidJson(): void
    {
        $data = ['key' => 'value', 'num' => 123];
        $request = new Request([], [], [], [], [], [], json_encode($data));
        $result = RequestHelper::getJsonData($request);
        $this->assertSame($data, $result);
    }

    public function testGetJsonDataThrowsForInvalidJson(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON data');
        $request = new Request([], [], [], [], [], [], 'invalid json');
        RequestHelper::getJsonData($request);
    }

    public function testValidateRequiredPassesWhenAllPresent(): void
    {
        $data = ['field1' => 'val', 'field2' => 'ok'];
        RequestHelper::validateRequired($data, ['field1', 'field2']);
        $this->addToAssertionCount(1);
    }

    public function testValidateRequiredThrowsWhenMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'missing' is required");
        RequestHelper::validateRequired(['present' => 'ok'], ['present', 'missing']);
    }

    public function testValidateRequiredThrowsWhenEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'field' is required");
        RequestHelper::validateRequired(['field' => '   '], ['field']);
    }

    public function testGetClientIpReturnsIpOrFallback(): void
    {
        $request = new Request();
        $this->assertSame('127.0.0.1', RequestHelper::getClientIp($request));

        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '192.168.1.1']);
        $this->assertSame('192.168.1.1', RequestHelper::getClientIp($request));
    }
}
