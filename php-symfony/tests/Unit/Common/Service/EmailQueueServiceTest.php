<?php declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Common\Service\EmailQueueService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EmailQueueServiceTest extends TestCase
{
    public function testDoSomethingReturnsFormattedString(): void
    {
        $service = new EmailQueueService();
        $result = $service->doSomething('queue');
        $this->assertSame('EmailQueueService: queue', $result);
    }

    public function testDoSomethingHandlesEmptyString(): void
    {
        $service = new EmailQueueService();
        $result = $service->doSomething('');
        $this->assertSame('EmailQueueService: ', $result);
    }
}