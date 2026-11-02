<?php declare(strict_types=1);

namespace App\Tests\Entity\Core;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Core\Entity\User;
use App\Core\Entity\UserSession;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserSessionTest extends TestCase
{
    private User $user;
    private string $token;
    private \DateTime $expiresAt;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->token = 'tok_' . bin2hex(random_bytes(10));
        $this->expiresAt = new \DateTime('+1 hour');
    }

    public function testConstructorGeneratesIdAndSetsProperties(): void
    {
        $session = new UserSession($this->user, $this->token, $this->expiresAt);
        $this->assertNotEmpty($session->getId());
        $this->assertSame($this->user, $session->getUser());
        $this->assertSame($this->token, $session->getToken());
        $this->assertSame($this->expiresAt, $session->getExpiresAt());
        $this->assertInstanceOf(\DateTime::class, $session->getCreatedAt());
        $this->assertNull($session->getRefreshToken());
    }

    public function testSettersAndGetters(): void
    {
        $session = new UserSession($this->user, $this->token, $this->expiresAt);
        $refresh = 'refresh_' . bin2hex(random_bytes(10));
        $session->setRefreshToken($refresh);
        $this->assertSame($refresh, $session->getRefreshToken());

        $newToken = 'newtoken';
        $session->setToken($newToken);
        $this->assertSame($newToken, $session->getToken());

        $newUser = new User();
        $session->setUser($newUser);
        $this->assertSame($newUser, $session->getUser());
    }
}
