<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\OAuthProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OAuthProviderTest extends TestCase
{
    private function createProvider(
        string $id = 'provider-id',
        string $userId = 'user-1',
        string $provider = 'google',
        string $providerUserId = 'google-123'
    ): OAuthProvider {
        return new OAuthProvider($id, $userId, $provider, $providerUserId);
    }

    public function testConstructor(): void
    {
        $p = $this->createProvider();
        $this->assertSame('provider-id', $p->getId());
        $this->assertSame('user-1', $p->getUserId());
        $this->assertSame('google', $p->getProvider());
        $this->assertSame('google-123', $p->getProviderUserId());
        $this->assertNull($p->getAccessToken());
        $this->assertNull($p->getRefreshToken());
        $this->assertNull($p->getTokenExpiresAt());
        $this->assertNull($p->getScope());
        $this->assertNull($p->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $p->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $p->getUpdatedAt());
    }

    public function testSetAccessToken(): void
    {
        $p = $this->createProvider();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setAccessToken('access-token-abc');
        $this->assertSame('access-token-abc', $p->getAccessToken());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setAccessToken(null);
        $this->assertNull($p->getAccessToken());
    }

    public function testSetRefreshToken(): void
    {
        $p = $this->createProvider();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setRefreshToken('refresh-token-xyz');
        $this->assertSame('refresh-token-xyz', $p->getRefreshToken());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setRefreshToken(null);
        $this->assertNull($p->getRefreshToken());
    }

    public function testSetTokenExpiresAt(): void
    {
        $p = $this->createProvider();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $expires = new \DateTime('+1 day');
        $p->setTokenExpiresAt($expires);
        $this->assertSame($expires, $p->getTokenExpiresAt());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setTokenExpiresAt(null);
        $this->assertNull($p->getTokenExpiresAt());
    }

    public function testSetScope(): void
    {
        $p = $this->createProvider();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setScope('read write');
        $this->assertSame('read write', $p->getScope());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setScope(null);
        $this->assertNull($p->getScope());
    }

    public function testSetMetadata(): void
    {
        $p = $this->createProvider();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $meta = ['extra' => 'data'];
        $p->setMetadata($meta);
        $this->assertSame($meta, $p->getMetadata());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setMetadata(null);
        $this->assertNull($p->getMetadata());
    }

    public function testIsTokenExpired(): void
    {
        $p = $this->createProvider();
        // no expires set
        $this->assertFalse($p->isTokenExpired());

        // future expiry
        $future = new \DateTime('+1 day');
        $p->setTokenExpiresAt($future);
        $this->assertFalse($p->isTokenExpired());

        // past expiry
        $past = new \DateTime('-1 hour');
        $p->setTokenExpiresAt($past);
        $this->assertTrue($p->isTokenExpired());
    }

    public function testNeedsRefresh(): void
    {
        $p = $this->createProvider();
        // no refresh token
        $this->assertFalse($p->needsRefresh());

        // set refresh token but not expired
        $future = new \DateTime('+1 day');
        $p->setRefreshToken('refresh123');
        $p->setTokenExpiresAt($future);
        $this->assertFalse($p->needsRefresh());

        // expired token -> needs refresh
        $past = new \DateTime('-1 hour');
        $p->setTokenExpiresAt($past);
        $this->assertTrue($p->needsRefresh());

        // remove refresh token even if expired
        $p->setRefreshToken(null);
        $this->assertFalse($p->needsRefresh());
    }
}
