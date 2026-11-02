<?php declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Common\Service\UserSessionService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserSessionServiceTest extends TestCase
{
    public function testDoSomethingReturnsFormattedString(): void
    {
        $service = new UserSessionService();
        $result = $service->doSomething('test');
        $this->assertSame('UserSessionService: test', $result);
    }

    public function testDoSomethingHandlesEmptyString(): void
    {
        $service = new UserSessionService();
        $result = $service->doSomething('');
        $this->assertSame('UserSessionService: ', $result);
    }

    public function testDoSomethingHandlesSpecialCharacters(): void
    {
        $service = new UserSessionService();
        $result = $service->doSomething('a & b <c>');
        $this->assertSame('UserSessionService: a & b <c>', $result);
    }
}