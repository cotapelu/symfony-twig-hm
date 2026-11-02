<?php declare(strict_types=1);

namespace App\Tests\Entity\Core;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Core\Entity\User;
use App\Core\Entity\OAuthProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OAuthProviderTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testConstructorGeneratesIdAndSetsProperties(): void
    {
        $provider = new OAuthProvider($this->user, 'google', 'google-123');
        $this->assertNotEmpty($provider->getId());
        $this->assertSame($this->user, $provider->getUser());
        $this->assertSame('google', $provider->getProvider());
        $this->assertSame('google-123', $provider->getProviderUserId());
        $this->assertNull($provider->getAccessToken());
        $this->assertNull($provider->getRefreshToken());
        $this->assertNull($provider->getExpiresAt());
        $this->assertInstanceOf(\DateTime::class, $provider->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $provider->getUpdatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $provider = new OAuthProvider($this->user, 'facebook', 'fb-456');
        $access = 'token';
        $refresh = 'refresh';
        $expires = new \DateTime('+1 year');

        $provider->setAccessToken($access);
        $this->assertSame($access, $provider->getAccessToken());

        $provider->setRefreshToken($refresh);
        $this->assertSame($refresh, $provider->getRefreshToken());

        $provider->setExpiresAt($expires);
        $this->assertSame($expires, $provider->getExpiresAt());

        // Test isExpired and needsRefresh
        $this->assertFalse($provider->isExpired());
        $provider->setExpiresAt(new \DateTime('-1 hour'));
        $this->assertTrue($provider->isExpired());
        $this->assertTrue($provider->needsRefresh());
    }
}
