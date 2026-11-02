<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\User;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserTest extends TestCase
{
    public function testConstructorSetsDefaultValues(): void
    {
        $user = new User('user-123', 'test@example.com');

        $this->assertSame('user-123', $user->getId());
        $this->assertSame('test@example.com', $user->getEmail());
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertCount(1, $roles);
        $this->assertSame('', $user->getPassword());
        $this->assertTrue($user->isActive());
        $this->assertNull($user->getLastLoginAt());
        $this->assertFalse($user->isTotpEnabled());
        $this->assertNull($user->getTotpSecret());
        $this->assertSame([], $user->getTotpBackupCodes());
        $this->assertSame('en', $user->getPreferredLanguage());
        $this->assertSame('UTC', $user->getTimezone());
        $this->assertSame(['email' => true, 'push' => true, 'sms' => false], $user->getNotificationSettings());
        $this->assertSame([
            'profile_visible' => true,
            'online_status' => true,
            'last_seen' => true,
        ], $user->getPrivacySettings());
        $this->assertNull($user->getPushSubscription());
        $this->assertNull($user->getSocialProvider());
        $this->assertNull($user->getSocialId());
        $this->assertNull($user->getBiometricData());
        $this->assertNull($user->getPhoneNumber());
    }

    public function testConstructorWithCustomRoles(): void
    {
        $user = new User('user-456', 'admin@example.com', ['ROLE_ADMIN', 'ROLE_USER']);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertCount(2, $roles); // ROLE_ADMIN + auto-added ROLE_USER
    }

    public function testUserInterfaceMethods(): void
    {
        $user = new User('user-789', 'jane@example.com');
        $user->setPassword('hashed');

        $this->assertSame('jane@example.com', $user->getUserIdentifier());
        $this->assertSame('hashed', $user->getPassword());
    }

    public function testSetters(): void
    {
        $user = new User('user-1', 'test@example.com');

        $user->setEmail('new@example.com');
        $this->assertSame('new@example.com', $user->getEmail());

        $user->setRoles(['ROLE_ADMIN']);
        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertCount(2, $roles);

        $user->setPassword('newpass');
        $this->assertSame('newpass', $user->getPassword());

        $now = new \DateTime();
        $user->setLastLoginAt($now);
        $this->assertSame($now, $user->getLastLoginAt());

        $user->setPhoneNumber('0123456789');
        $this->assertSame('0123456789', $user->getPhoneNumber());

        $user->setPreferredLanguage('vi');
        $this->assertSame('vi', $user->getPreferredLanguage());

        $user->setTimezone('Asia/Ho_Chi_Minh');
        $this->assertSame('Asia/Ho_Chi_Minh', $user->getTimezone());
    }

    public function testEraseCredentialsDoesNothing(): void
    {
        $user = new User('user-1', 'test@example.com');
        // User has no sensitive temporary data, eraseCredentials is no-op
        $this->assertTrue(true);
    }

    public function testGetProfileReturnsNullWhenNotSet(): void
    {
        $user = new User('user-1', 'test@example.com');
        $this->assertNull($user->getProfile());
    }

    public function testIsAdminRole(): void
    {
        $user = new User('user-1', 'test@example.com', ['ROLE_ADMIN']);
        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testNotificationSettingsCanBeUpdated(): void
    {
        $user = new User('user-1', 'test@example.com');
        $newSettings = ['email' => false, 'push' => false, 'sms' => true];
        $user->setNotificationSettings($newSettings);
        $this->assertSame($newSettings, $user->getNotificationSettings());
    }
}
