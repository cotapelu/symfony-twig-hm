<?php declare(strict_types=1);

namespace App\Tests\Unit\Service\Security;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Service\Security\OwnershipChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[AllowMockObjectsWithoutExpectations]
class OwnershipCheckerTest extends TestCase
{
    private AuthorizationCheckerInterface $authCheckerMock;
    private OwnershipChecker $checker;

    protected function setUp(): void
    {
        $this->authCheckerMock = $this->createMock(AuthorizationCheckerInterface::class);
        $this->checker = new OwnershipChecker($this->authCheckerMock);
    }

    public function testAssertOwnershipSucceedsWhenOwnerMatches(): void
    {
        $ownerId = 'user-123';
        $currentUserId = 'user-123';

        // Should not throw
        $this->checker->assertOwnership($ownerId, $currentUserId);
        $this->assertTrue(true);
    }

    public function testAssertOwnershipThrowsWhenOwnerMismatch(): void
    {
        $ownerId = 'user-123';
        $currentUserId = 'user-456';

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access denied: You do not own this resource');
        $this->checker->assertOwnership($ownerId, $currentUserId);
    }

    public function testAssertRoleSucceedsWhenGranted(): void
    {
        $this->authCheckerMock->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $this->checker->assertRole('ROLE_ADMIN');
        $this->assertTrue(true);
    }

    public function testAssertRoleThrowsWhenNotGranted(): void
    {
        $this->authCheckerMock->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access denied: Insufficient permissions');
        $this->checker->assertRole('ROLE_USER');
    }

    public function testAssertAnyRoleSucceedsWhenAnyGranted(): void
    {
        // Single role that is granted should succeed
        $this->authCheckerMock->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_MODERATOR')
            ->willReturn(true);
        $this->checker->assertAnyRole(['ROLE_MODERATOR']);
    }

    public function testAssertAnyRoleThrowsWhenNoneGranted(): void
    {
        // isGranted will be called for each role until one returns true, but all false
        $this->authCheckerMock->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access denied: Insufficient permissions');
        $this->checker->assertAnyRole(['ROLE_ADMIN', 'ROLE_USER']);
    }

    public function testIsOwnerReturnsTrueWhenMatch(): void
    {
        $result = $this->checker->isOwner('user-1', 'user-1');
        $this->assertTrue($result);
    }

    public function testIsOwnerReturnsFalseWhenMismatch(): void
    {
        $result = $this->checker->isOwner('user-1', 'user-2');
        $this->assertFalse($result);
    }
}
