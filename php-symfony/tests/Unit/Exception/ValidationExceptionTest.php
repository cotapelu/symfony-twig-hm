<?php declare(strict_types=1);

namespace App\Tests\Unit\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ValidationExceptionTest extends TestCase
{
    public function testCanBeInstantiatedWithMessage(): void
    {
        $e = new ValidationException('Validation failed');
        $this->assertSame('Validation failed', $e->getMessage());
        $this->assertInstanceOf(\Exception::class, $e);
    }
}