<?php declare(strict_types=1);

namespace App\Tests\Unit\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Exception\AuthenticationException;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AuthenticationExceptionTest extends TestCase
{
    public function testCanBeInstantiatedWithMessage(): void
    {
        $e = new AuthenticationException('Invalid credentials');
        $this->assertSame('Invalid credentials', $e->getMessage());
        $this->assertInstanceOf(\Exception::class, $e);
    }
}