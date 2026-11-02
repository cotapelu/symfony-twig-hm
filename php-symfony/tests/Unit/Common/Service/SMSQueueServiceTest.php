<?php declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Common\Service\SMSQueueService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SMSQueueServiceTest extends TestCase
{
    public function testDoSomethingReturnsFormattedString(): void
    {
        $service = new SMSQueueService();
        $result = $service->doSomething('test');
        $this->assertSame('SMSQueueService: test', $result);
    }

    public function testDoSomethingHandlesEmptyString(): void
    {
        $service = new SMSQueueService();
        $result = $service->doSomething('');
        $this->assertSame('SMSQueueService: ', $result);
    }

    public function testDoSomethingHandlesSpecialCharacters(): void
    {
        $service = new SMSQueueService();
        $result = $service->doSomething('a & b <c>');
        $this->assertSame('SMSQueueService: a & b <c>', $result);
    }
}