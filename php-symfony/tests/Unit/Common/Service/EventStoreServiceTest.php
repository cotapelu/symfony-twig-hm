<?php declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Common\Service\EventStoreService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EventStoreServiceTest extends TestCase
{
    public function testDoSomethingReturnsFormattedString(): void
    {
        $service = new EventStoreService();
        $result = $service->doSomething('test');
        $this->assertSame('EventStoreService: test', $result);
    }

    public function testDoSomethingHandlesEmptyString(): void
    {
        $service = new EventStoreService();
        $result = $service->doSomething('');
        $this->assertSame('EventStoreService: ', $result);
    }

    public function testDoSomethingHandlesSpecialCharacters(): void
    {
        $service = new EventStoreService();
        $result = $service->doSomething('a & b <c>');
        $this->assertSame('EventStoreService: a & b <c>', $result);
    }
}