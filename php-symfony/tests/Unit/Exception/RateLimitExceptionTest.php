<?php declare(strict_types=1);

namespace App\Tests\Unit\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Exception\RateLimitException;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RateLimitExceptionTest extends TestCase
{
    public function testCanBeInstantiatedWithMessage(): void
    {
        $e = new RateLimitException('Rate exceeded');
        $this->assertSame('Rate exceeded', $e->getMessage());
        $this->assertInstanceOf(\Exception::class, $e);
    }
}