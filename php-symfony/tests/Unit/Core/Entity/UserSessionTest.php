<?php declare(strict_types=1);

namespace App\Tests\Unit\Core\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Core\Entity\UserSession;
use App\Core\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserSessionTest extends TestCase
{
    private User&MockObject $userMock;

    protected function setUp(): void
    {
        $this->userMock = $this->createMock(User::class);
        $this->userMock->method('getId')->willReturn(42);
    }

    public function testConstructorInitializesProperties(): void
    {
        $token = 'sess_token_123';
        $expiresAt = new \DateTime('+1 hour');

        $session = new UserSession($this->userMock, $token, $expiresAt);

        $this->assertSame($this->userMock, $session->getUser());
        $this->assertSame($token, $session->getToken());
        $this->assertSame($expiresAt, $session->getExpiresAt());
        $this->assertInstanceOf(\DateTime::class, $session->getCreatedAt());
        // createdAt should be close to now; we can assert it's within a few seconds
        $this->assertLessThan(5, abs($session->getCreatedAt()->getTimestamp() - time()));
        $this->assertFalse($session->isExpired());
        $this->assertTrue($session->isValid());
    }

    public function testSettersUpdateProperties(): void
    {
        $session = new UserSession($this->userMock, 'old_token', new \DateTime('+1 day'));

        $newToken = 'new_token';
        $session->setToken($newToken);
        $this->assertSame($newToken, $session->getToken());

        $newRefresh = 'refresh_456';
        $session->setRefreshToken($newRefresh);
        $this->assertSame($newRefresh, $session->getRefreshToken());

        $newExpires = new \DateTime('+2 days');
        $session->setExpiresAt($newExpires);
        $this->assertSame($newExpires, $session->getExpiresAt());
    }

    public function testExpirationChecks(): void
    {
        $session = new UserSession($this->userMock, 'token', new \DateTime('+1 minute'));

        $this->assertFalse($session->isExpired());
        $this->assertTrue($session->isValid());

        // Set expiration to past
        $session->setExpiresAt(new \DateTime('-1 hour'));
        $this->assertTrue($session->isExpired());
        $this->assertFalse($session->isValid());
    }

    public function testToArrayReturnsExpectedFormat(): void
    {
        $token = 'abc123';
        $expires = new \DateTime('2025-01-01 12:00:00');
        $session = new UserSession($this->userMock, $token, $expires);
        // createdAt will be now; we can adjust its expectation by mocking? Hard. We'll get actual.
        $created = $session->getCreatedAt();

        $arr = $session->toArray();

        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('userId', $arr);
        $this->assertArrayHasKey('token', $arr);
        $this->assertArrayHasKey('expiresAt', $arr);
        $this->assertArrayHasKey('createdAt', $arr);

        $this->assertSame(42, $arr['userId']);
        $this->assertSame($token, $arr['token']);
        $this->assertSame($expires->format('Y-m-d H:i:s'), $arr['expiresAt']);
        $this->assertSame($created->format('Y-m-d H:i:s'), $arr['createdAt']);
        // id should be a non-empty string
        $this->assertIsString($arr['id']);
        $this->assertNotEmpty($arr['id']);
    }

    public function testSetUserUpdatesUser(): void
    {
        $session = new UserSession($this->userMock, 'token', new \DateTime('+1 day'));

        $newUser = $this->createMock(User::class);
        $newUser->method('getId')->willReturn(99);

        $session->setUser($newUser);
        $this->assertSame($newUser, $session->getUser());
        // toArray reflects new user id
        $arr = $session->toArray();
        $this->assertSame(99, $arr['userId']);
    }
}