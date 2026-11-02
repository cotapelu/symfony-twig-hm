<?php declare(strict_types=1);

namespace App\Tests\Service\Security;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Service\Security\OwnershipChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[AllowMockObjectsWithoutExpectations]
class OwnershipCheckerTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authChecker;
    private OwnershipChecker $checker;

    protected function setUp(): void
    {
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->checker = new OwnershipChecker($this->authChecker);
    }

    public function testAssertRoleThrowsWhenNotGranted(): void
    {
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access denied: Insufficient permissions');
        $this->checker->assertRole('ROLE_ADMIN');
    }

    public function testAssertRoleSucceedsWhenGranted(): void
    {
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true);

        $this->checker->assertRole('ROLE_USER'); // no exception
        $this->assertTrue(true);
    }

    public function testAssertAnyRoleThrowsWhenNoneGranted(): void
    {
        $this->authChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_ADMIN', false],
                ['ROLE_USER', false],
            ]);

        $this->expectException(AccessDeniedHttpException::class);
        $this->checker->assertAnyRole(['ROLE_ADMIN', 'ROLE_USER']);
    }

    public function testAssertAnyRoleSucceedsWhenOneGranted(): void
    {
        $this->authChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_ADMIN', false],
                ['ROLE_USER', true],
            ]);

        $this->checker->assertAnyRole(['ROLE_ADMIN', 'ROLE_USER']); // no exception
        $this->assertTrue(true);
    }

    public function testIsOwnerReturnsTrueWhenMatch(): void
    {
        $this->assertTrue($this->checker->isOwner('user-123', 'user-123'));
    }

    public function testIsOwnerReturnsFalseWhenMismatch(): void
    {
        $this->assertFalse($this->checker->isOwner('user-123', 'user-456'));
    }

    public function testAssertOwnershipThrowsWhenNotOwner(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access denied: You do not own this resource');
        $this->checker->assertOwnership('owner-1', 'other-2');
    }

    public function testAssertOwnershipSucceedsWhenOwner(): void
    {
        $this->checker->assertOwnership('user-1', 'user-1'); // no exception
        $this->assertTrue(true);
    }
}
