<?php declare(strict_types=1);

namespace App\Tests\Unit\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Exception\AuthorizationException;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AuthorizationExceptionTest extends TestCase
{
    public function testCanBeInstantiatedWithMessage(): void
    {
        $e = new AuthorizationException('Not authorized');
        $this->assertSame('Not authorized', $e->getMessage());
        $this->assertInstanceOf(\Exception::class, $e);
    }
}