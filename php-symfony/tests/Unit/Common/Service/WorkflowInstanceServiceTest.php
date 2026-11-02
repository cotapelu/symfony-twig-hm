<?php declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Common\Service\WorkflowInstanceService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WorkflowInstanceServiceTest extends TestCase
{
    public function testDoSomethingReturnsFormattedString(): void
    {
        $service = new WorkflowInstanceService();
        $result = $service->doSomething('test');
        $this->assertSame('WorkflowInstanceService: test', $result);
    }

    public function testDoSomethingHandlesEmptyString(): void
    {
        $service = new WorkflowInstanceService();
        $result = $service->doSomething('');
        $this->assertSame('WorkflowInstanceService: ', $result);
    }

    public function testDoSomethingHandlesSpecialCharacters(): void
    {
        $service = new WorkflowInstanceService();
        $result = $service->doSomething('a & b <c>');
        $this->assertSame('WorkflowInstanceService: a & b <c>', $result);
    }
}