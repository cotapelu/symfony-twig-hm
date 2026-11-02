<?php declare(strict_types=1);

namespace App\Tests\Unit\Core\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Core\Entity\OAuthProvider;
use App\Core\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OAuthProviderTest extends TestCase
{
    private User&MockObject $userMock;

    protected function setUp(): void
    {
        $this->userMock = $this->createMock(User::class);
        $this->userMock->method('getId')->willReturn(123);
    }

    public function testConstructorInitializesProperties(): void
    {
        $provider = new OAuthProvider($this->userMock, 'google', 'google_user_1');
        $this->assertSame($this->userMock, $provider->getUser());
        $this->assertSame('google', $provider->getProvider());
        $this->assertSame('google_user_1', $provider->getProviderUserId());
        $this->assertNull($provider->getAccessToken());
        $this->assertNull($provider->getRefreshToken());
        $this->assertNull($provider->getExpiresAt());
        $this->assertInstanceOf(\DateTime::class, $provider->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $provider->getUpdatedAt());
        $this->assertFalse($provider->isExpired());
        $this->assertTrue($provider->needsRefresh()); // no access token, needs refresh
    }

    public function testSettersUpdateProperties(): void
    {
        $op = new OAuthProvider($this->userMock, 'github', 'gh_456');
        $op->setAccessToken('token_abc');
        $this->assertSame('token_abc', $op->getAccessToken());

        $op->setRefreshToken('refresh_abc');
        $this->assertSame('refresh_abc', $op->getRefreshToken());

        $future = new \DateTime('+1 year');
        $op->setExpiresAt($future);
        $this->assertSame($future, $op->getExpiresAt());
        $this->assertFalse($op->isExpired());

        $op->setExpiresAt(new \DateTime('-1 day'));
        $this->assertTrue($op->isExpired());
    }

    public function testNeedsRefreshWhenAccessTokenNull(): void
    {
        $op = new OAuthProvider($this->userMock, 'facebook', 'fb_789');
        $op->setAccessToken(null);
        $this->assertTrue($op->needsRefresh());
    }

    public function testNeedsRefreshWhenExpired(): void
    {
        $op = new OAuthProvider($this->userMock, 'twitter', 'tw_101');
        $op->setAccessToken('some_token');
        $op->setExpiresAt(new \DateTime('-1 hour'));
        $this->assertTrue($op->needsRefresh());
    }

    public function testNeedsRefreshWhenTokenValidAndNotExpired(): void
    {
        $op = new OAuthProvider($this->userMock, 'linkedin', 'li_202');
        $op->setAccessToken('valid_token');
        $op->setExpiresAt(new \DateTime('+1 hour'));
        $this->assertFalse($op->needsRefresh());
    }

    public function testToArrayReturnsExpectedFormat(): void
    {
        $now = new \DateTime('2024-06-01 12:00:00');
        $op = new OAuthProvider($this->userMock, 'microsoft', 'ms_303');
        $op->setExpiresAt($now); // set a deterministic expiresAt

        $arr = $op->toArray();

        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertIsString($arr['id']);
        $this->assertNotEmpty($arr['id']);
        $this->assertArrayHasKey('userId', $arr);
        $this->assertSame(123, $arr['userId']);
        $this->assertArrayHasKey('provider', $arr);
        $this->assertSame('microsoft', $arr['provider']);
        $this->assertArrayHasKey('providerUserId', $arr);
        $this->assertSame('ms_303', $arr['providerUserId']);
        $this->assertArrayHasKey('expiresAt', $arr);
        $this->assertSame($now->format('Y-m-d H:i:s'), $arr['expiresAt']);
        $this->assertArrayHasKey('createdAt', $arr);
        $this->assertIsString($arr['createdAt']);
        $this->assertArrayHasKey('updatedAt', $arr);
        $this->assertIsString($arr['updatedAt']);
        // Sensitive fields are intentionally excluded from toArray
        $this->assertArrayNotHasKey('accessToken', $arr);
        $this->assertArrayNotHasKey('refreshToken', $arr);
    }
}
