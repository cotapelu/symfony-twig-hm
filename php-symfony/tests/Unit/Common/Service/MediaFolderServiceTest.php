<?php declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Common\Service\MediaFolderService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MediaFolderServiceTest extends TestCase
{
    public function testDoSomethingReturnsFormattedString(): void
    {
        $service = new MediaFolderService();
        $result = $service->doSomething('test');
        $this->assertSame('MediaFolderService: test', $result);
    }

    public function testDoSomethingHandlesEmptyString(): void
    {
        $service = new MediaFolderService();
        $result = $service->doSomething('');
        $this->assertSame('MediaFolderService: ', $result);
    }

    public function testDoSomethingHandlesSpecialCharacters(): void
    {
        $service = new MediaFolderService();
        $result = $service->doSomething('a & b <c>');
        $this->assertSame('MediaFolderService: a & b <c>', $result);
    }
}