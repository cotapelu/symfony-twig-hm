<?php

declare(strict_types=1);

namespace App\User\Entity;

use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * Doctrine entity for UserVerification - Infrastructure layer.
 */
#[ORM\Entity]
#[ORM\Table(name: 'communication_user_verifications')]
class UserVerification
{
    #[ORM\Id]
    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(name: 'email_verified_at', type: 'datetime', nullable: true)]
    private ?\DateTime $emailVerifiedAt = null;

    #[ORM\Column(name: 'phone_verified_at', type: 'datetime', nullable: true)]
    private ?\DateTime $phoneVerifiedAt = null;

    #[ORM\Column(name: 'biometric_verified_at', type: 'datetime', nullable: true)]
    private ?\DateTime $biometricVerifiedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(name: 'verification_token_expires_at', type: 'datetime', nullable: true)]
    private ?\DateTime $verificationTokenExpiresAt = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'basic'])]
    private string $verificationLevel;

    #[ORM\Column(name: 'phone_code', type: 'string', length: 255, nullable: true)]
    private ?string $phoneCode = null;

    #[ORM\Column(name: 'phone_code_expires_at', type: 'datetime', nullable: true)]
    private ?\DateTime $phoneCodeExpiresAt = null;

    #[ORM\Column(name: 'phone_code_attempts', type: 'integer', options: ['default' => 0])]
    private int $phoneCodeAttempts = 0;

    #[ORM\Column(name: 'phone_number', type: 'string', length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(name: 'last_phone_sent_at', type: 'datetime', nullable: true)]
    private ?\DateTime $lastPhoneSentAt = null;

    public function __construct(
        string $userId,
        string $verificationLevel = 'basic',
    ) {
        $this->userId = $userId;
        $this->emailVerifiedAt = null;
        $this->phoneVerifiedAt = null;
        $this->biometricVerifiedAt = null;
        $this->verificationToken = null;
        $this->verificationTokenExpiresAt = null;
        $this->verificationLevel = $verificationLevel;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmailVerifiedAt()
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTime $emailVerifiedAt)
    {
        $this->emailVerifiedAt = $emailVerifiedAt;

        return $this;
    }

    public function getPhoneVerifiedAt()
    {
        return $this->phoneVerifiedAt;
    }

    public function setPhoneVerifiedAt(?\DateTime $phoneVerifiedAt)
    {
        $this->phoneVerifiedAt = $phoneVerifiedAt;

        return $this;
    }

    public function getBiometricVerifiedAt()
    {
        return $this->biometricVerifiedAt;
    }

    public function setBiometricVerifiedAt(?\DateTime $biometricVerifiedAt)
    {
        $this->biometricVerifiedAt = $biometricVerifiedAt;

        return $this;
    }

    public function getVerificationToken()
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken)
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    public function getVerificationTokenExpiresAt()
    {
        return $this->verificationTokenExpiresAt;
    }

    public function setVerificationTokenExpiresAt(?\DateTime $verificationTokenExpiresAt)
    {
        $this->verificationTokenExpiresAt = $verificationTokenExpiresAt;

        return $this;
    }

    public function getVerificationLevel()
    {
        return $this->verificationLevel;
    }

    public function setVerificationLevel(string|int $verificationLevel)
    {
        $this->verificationLevel = (string) $verificationLevel;

        return $this;
    }

    /**
     * Get verification level as int.
     */
    public function getVerificationLevelAsInt()
    {
        return (int) $this->verificationLevel;
    }

    public function getPhoneCode()
    {
        return $this->phoneCode;
    }

    public function setPhoneCode(?string $phoneCode)
    {
        $this->phoneCode = $phoneCode;
    }

    public function getPhoneCodeExpiresAt()
    {
        return $this->phoneCodeExpiresAt;
    }

    public function setPhoneCodeExpiresAt(?\DateTime $phoneCodeExpiresAt)
    {
        $this->phoneCodeExpiresAt = $phoneCodeExpiresAt;
    }

    public function getPhoneCodeAttempts()
    {
        return $this->phoneCodeAttempts;
    }

    public function setPhoneCodeAttempts(int $phoneCodeAttempts)
    {
        $this->phoneCodeAttempts = $phoneCodeAttempts;
    }

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getLastPhoneSentAt()
    {
        return $this->lastPhoneSentAt;
    }

    public function setLastPhoneSentAt(?\DateTime $lastPhoneSentAt)
    {
        $this->lastPhoneSentAt = $lastPhoneSentAt;
    }
}
