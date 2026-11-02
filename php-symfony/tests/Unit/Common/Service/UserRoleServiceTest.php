<?php declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Common\Service\UserRoleService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserRoleServiceTest extends TestCase
{
    public function testDoSomethingReturnsFormattedString(): void
    {
        $service = new UserRoleService();
        $result = $service->doSomething('test');
        $this->assertSame('UserRoleService: test', $result);
    }

    public function testDoSomethingHandlesEmptyString(): void
    {
        $service = new UserRoleService();
        $result = $service->doSomething('');
        $this->assertSame('UserRoleService: ', $result);
    }

    public function testDoSomethingHandlesSpecialCharacters(): void
    {
        $service = new UserRoleService();
        $result = $service->doSomething('a & b <c>');
        $this->assertSame('UserRoleService: a & b <c>', $result);
    }
}