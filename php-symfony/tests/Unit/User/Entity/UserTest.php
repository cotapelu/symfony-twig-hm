<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\User;
use App\User\Entity\UserProfile;
use App\User\Entity\UserVerification;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserTest extends TestCase
{
    private function createUser(string $id = 'user-123', string $email = 'test@example.com', array $roles = []): User
    {
        return new User($id, $email, $roles);
    }

    public function testConstructor(): void
    {
        $user = $this->createUser();
        $this->assertSame('user-123', $user->getId());
        $this->assertSame('test@example.com', $user->getEmail());
        // getRoles always guarantees ROLE_USER
        $this->assertSame(['ROLE_USER'], $user->getRoles());
        $this->assertSame('', $user->getPassword());
        $this->assertInstanceOf(\DateTime::class, $user->getCreatedAt());
        $this->assertNull($user->getLastLoginAt());
        $this->assertTrue($user->isActive());
        $this->assertNull($user->getPhoneNumber());
        $this->assertNull($user->getSocialProvider());
        $this->assertNull($user->getSocialId());
        $this->assertNull($user->getBiometricData());
        $this->assertSame('en', $user->getPreferredLanguage());
        $this->assertSame('UTC', $user->getTimezone());
        $this->assertFalse($user->isTotpEnabled());
        $this->assertEmpty($user->getTotpBackupCodes());
        $this->assertSame(0, $user->getTotpAttempts());
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = $this->createUser();
        $this->assertSame('test@example.com', $user->getUserIdentifier());
    }

    public function testSetEmail(): void
    {
        $user = $this->createUser();
        $user->setEmail('new@example.com');
        $this->assertSame('new@example.com', $user->getEmail());
    }

    public function testSetRoles(): void
    {
        $user = $this->createUser();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testSetPassword(): void
    {
        $user = $this->createUser();
        $user->setPassword('hashed');
        $this->assertSame('hashed', $user->getPassword());
    }

    public function testEraseCredentialsClearsBiometricPlain(): void
    {
        $user = $this->createUser();
        $ref = new \ReflectionProperty($user, 'biometricDataPlain');
        $ref->setAccessible(true);
        $ref->setValue($user, 'plain biometric data');
        $this->assertSame('plain biometric data', $ref->getValue($user));
        $user->eraseCredentials();
        $this->assertNull($ref->getValue($user));
    }

    public function testSetIsActive(): void
    {
        $user = $this->createUser();
        $user->setIsActive(false);
        $this->assertFalse($user->isActive());
        $user->setIsActive(true);
        $this->assertTrue($user->isActive());
    }

    public function testSetPhoneNumber(): void
    {
        $user = $this->createUser();
        $user->setPhoneNumber('1234567890');
        $this->assertSame('1234567890', $user->getPhoneNumber());
        $user->setPhoneNumber(null);
        $this->assertNull($user->getPhoneNumber());
    }

    public function testSetSocialProviderAndId(): void
    {
        $user = $this->createUser();
        $user->setSocialProvider('google');
        $user->setSocialId('google-123');
        $this->assertSame('google', $user->getSocialProvider());
        $this->assertSame('google-123', $user->getSocialId());
    }

    public function testSetPreferredLanguage(): void
    {
        $user = $this->createUser();
        $user->setPreferredLanguage('vi');
        $this->assertSame('vi', $user->getPreferredLanguage());
    }

    public function testSetTimezone(): void
    {
        $user = $this->createUser();
        $user->setTimezone('Asia/Ho_Chi_Minh');
        $this->assertSame('Asia/Ho_Chi_Minh', $user->getTimezone());
    }

    public function testTotpMethods(): void
    {
        $user = $this->createUser();
        $user->setTotpEnabled(true);
        $this->assertTrue($user->isTotpEnabled());
        $user->setTotpSecret('secret123');
        $this->assertSame('secret123', $user->getTotpSecret());
        $user->setTotpBackupCodes(['code1', 'code2']);
        $this->assertSame(['code1', 'code2'], $user->getTotpBackupCodes());
        $user->setTotpAttempts(3);
        $this->assertSame(3, $user->getTotpAttempts());
    }

    public function testSetNotificationSettings(): void
    {
        $user = $this->createUser();
        $settings = ['email' => true, 'push' => false];
        $user->setNotificationSettings($settings);
        $this->assertSame($settings, $user->getNotificationSettings());
    }

    public function testSetPrivacySettings(): void
    {
        $user = $this->createUser();
        $privacy = ['profile_public' => true];
        $user->setPrivacySettings($privacy);
        $this->assertSame($privacy, $user->getPrivacySettings());
    }

    public function testSetPushSubscription(): void
    {
        $user = $this->createUser();
        $sub = ['token' => 'abc'];
        $user->setPushSubscription($sub);
        $this->assertSame($sub, $user->getPushSubscription());
    }

    public function testSetLastLoginAt(): void
    {
        $user = $this->createUser();
        $now = new \DateTime();
        $user->setLastLoginAt($now);
        $this->assertSame($now, $user->getLastLoginAt());
    }

    public function testSetDeletedAt(): void
    {
        $user = $this->createUser();
        $deleted = new \DateTime('-1 day');
        $user->setDeletedAt($deleted);
        $this->assertSame($deleted, $user->getDeletedAt());
    }

    public function testGetAndSetProfile(): void
    {
        $user = $this->createUser();
        $profile = new UserProfile($user->getId());
        $user->setProfile($profile);
        $this->assertSame($profile, $user->getProfile());
    }

    public function testGetAndSetVerification(): void
    {
        $user = $this->createUser();
        $ver = new UserVerification($user->getId());
        $user->setVerification($ver);
        $this->assertSame($ver, $user->getVerification());
    }
}
