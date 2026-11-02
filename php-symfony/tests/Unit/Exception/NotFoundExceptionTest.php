<?php declare(strict_types=1);

namespace App\Tests\Unit\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotFoundExceptionTest extends TestCase
{
    public function testCanBeInstantiatedWithMessage(): void
    {
        $e = new NotFoundException('Resource not found');
        $this->assertSame('Resource not found', $e->getMessage());
        $this->assertInstanceOf(\Exception::class, $e);
    }
}