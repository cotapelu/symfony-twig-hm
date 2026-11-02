<?php declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Common\Service\MediaFileService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MediaFileServiceTest extends TestCase
{
    public function testDoSomethingReturnsFormattedString(): void
    {
        $service = new MediaFileService();
        $result = $service->doSomething('test');
        $this->assertSame('MediaFileService: test', $result);
    }

    public function testDoSomethingHandlesEmptyString(): void
    {
        $service = new MediaFileService();
        $result = $service->doSomething('');
        $this->assertSame('MediaFileService: ', $result);
    }

    public function testDoSomethingHandlesSpecialCharacters(): void
    {
        $service = new MediaFileService();
        $result = $service->doSomething('a & b <c>');
        $this->assertSame('MediaFileService: a & b <c>', $result);
    }
}