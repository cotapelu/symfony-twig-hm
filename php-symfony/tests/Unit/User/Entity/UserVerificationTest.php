<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\UserVerification;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserVerificationTest extends TestCase
{
    private function createVerification(string $userId = 'user-123', string $level = 'basic'): UserVerification
    {
        return new UserVerification($userId, $level);
    }

    public function testConstructor(): void
    {
        $ver = $this->createVerification();
        $this->assertSame('user-123', $ver->getUserId());
        $this->assertNull($ver->getEmailVerifiedAt());
        $this->assertNull($ver->getPhoneVerifiedAt());
        $this->assertNull($ver->getBiometricVerifiedAt());
        $this->assertNull($ver->getVerificationToken());
        $this->assertNull($ver->getVerificationTokenExpiresAt());
        $this->assertSame('basic', $ver->getVerificationLevel());
        $this->assertSame(0, $ver->getPhoneCodeAttempts());
        $this->assertNull($ver->getPhoneNumber());
        $this->assertNull($ver->getLastPhoneSentAt());
    }

    public function testConstructorWithCustomLevel(): void
    {
        $ver = $this->createVerification('u456', '6');
        $this->assertSame('u456', $ver->getUserId());
        $this->assertSame('6', $ver->getVerificationLevel());
        $this->assertSame(6, $ver->getVerificationLevelAsInt());
    }

    public function testSetters(): void
    {
        $ver = $this->createVerification();
        $now = new \DateTime();

        $ver->setEmailVerifiedAt($now);
        $this->assertSame($now, $ver->getEmailVerifiedAt());

        $ver->setPhoneVerifiedAt($now);
        $this->assertSame($now, $ver->getPhoneVerifiedAt());

        $ver->setBiometricVerifiedAt($now);
        $this->assertSame($now, $ver->getBiometricVerifiedAt());

        $ver->setVerificationToken('token123');
        $this->assertSame('token123', $ver->getVerificationToken());

        $expires = new \DateTime('+1 day');
        $ver->setVerificationTokenExpiresAt($expires);
        $this->assertSame($expires, $ver->getVerificationTokenExpiresAt());

        $ver->setVerificationLevel('2');
        $this->assertSame('2', $ver->getVerificationLevel());
        $this->assertSame(2, $ver->getVerificationLevelAsInt());

        $ver->setPhoneCode('123456');
        $this->assertSame('123456', $ver->getPhoneCode());

        $ver->setPhoneCodeExpiresAt($expires);
        $this->assertSame($expires, $ver->getPhoneCodeExpiresAt());

        $ver->setPhoneCodeAttempts(5);
        $this->assertSame(5, $ver->getPhoneCodeAttempts());

        $ver->setPhoneNumber('+84912345678');
        $this->assertSame('+84912345678', $ver->getPhoneNumber());

        $lastSent = new \DateTime('-1 hour');
        $ver->setLastPhoneSentAt($lastSent);
        $this->assertSame($lastSent, $ver->getLastPhoneSentAt());
    }
}
