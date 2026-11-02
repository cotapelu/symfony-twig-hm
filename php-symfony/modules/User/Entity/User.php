<?php

declare(strict_types=1);

namespace App\User\Entity;

use App\User\Entity\UserProfile;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Doctrine entity for User - Infrastructure layer.
 */
#[ORM\Entity(repositoryClass: \App\User\Repository\UserRepository::class)]
#[ORM\Table(name: 'communication_users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'json')]
    /** @var array<string> */
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'last_login_at', type: 'datetime', nullable: true)]
    private ?\DateTime $lastLoginAt = null;

    #[ORM\Column(name: 'deleted_at', type: 'datetime', nullable: true)]
    private ?\DateTime $deletedAt = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $socialProvider = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $socialId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $biometricData = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $preferredLanguage = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $timezone = null;

    #[ORM\Transient]
    private ?string $biometricDataPlain = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $notificationSettings = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $privacySettings = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $pushSubscription = null;

    #[ORM\Column(name: 'totp_secret', type: 'string', length: 64, nullable: true)]
    private ?string $totpSecret = null;

    #[ORM\Column(name: 'totp_enabled', type: 'boolean', options: ['default' => false])]
    private bool $totpEnabled;

    #[ORM\Column(name: 'totp_backup_codes', type: 'json', nullable: true)]
    private ?array $totpBackupCodes = null;

    #[ORM\Column(name: 'totp_attempts', type: 'integer', options: ['default' => 0])]
    private int $totpAttempts;

    // #[ORM\OneToOne(targetEntity: UserProfile::class, cascade: ['persist', 'remove'])]
    // #[ORM\JoinColumn(name: 'id', referencedColumnName: 'user_id')]
    private ?UserProfile $profile = null;

    // #[ORM\OneToOne(targetEntity: UserVerification::class, cascade: ['persist', 'remove'])]
    // #[ORM\JoinColumn(name: 'id', referencedColumnName: 'user_id')]
    private ?UserVerification $verification = null;

    public function __construct(
        string $id,
        string $email,
        array $roles = ['ROLE_USER'],
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->roles = $roles;
        $this->password = '';
        $this->createdAt = new \DateTime();
        $this->isActive = true;
        $this->lastLoginAt = null;
        $this->totpEnabled = false;
        $this->totpSecret = null;
        $this->totpBackupCodes = [];
        $this->totpAttempts = 0;
        $this->phoneNumber = null;
        $this->socialProvider = null;
        $this->socialId = null;
        $this->biometricData = null;
        $this->preferredLanguage = 'en';
        $this->timezone = 'UTC';
        $this->notificationSettings = [
            'email' => true,
            'push' => true,
            'sms' => false,
        ];
        $this->privacySettings = [
            'profile_visible' => true,
            'online_status' => true,
            'last_seen' => true,
        ];
        $this->pushSubscription = null;
        $this->profile = null;
        $this->verification = null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getLastLoginAt()
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTime $lastLoginAt)
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getSocialProvider()
    {
        return $this->socialProvider;
    }

    public function setSocialProvider(?string $socialProvider)
    {
        $this->socialProvider = $socialProvider;

        return $this;
    }

    public function getSocialId()
    {
        return $this->socialId;
    }

    public function setSocialId(?string $socialId)
    {
        $this->socialId = $socialId;

        return $this;
    }

    public function getBiometricData()
    {
        return $this->biometricDataPlain ?? $this->biometricData;
    }

    public function setBiometricData(?string $plain)
    {
        $this->biometricDataPlain = $plain;

        return $this;
    }

    public function getBiometricDataEncrypted()
    {
        return $this->biometricData;
    }

    public function setBiometricDataEncrypted(?string $encrypted)
    {
        $this->biometricData = $encrypted;

        return $this;
    }

    public function getBiometricDataPlain()
    {
        return $this->biometricDataPlain;
    }

    public function setBiometricDataPlain(?string $plain)
    {
        $this->biometricDataPlain = $plain;
        return $this;
    }

    public function getPreferredLanguage()
    {
        return $this->preferredLanguage;
    }

    public function setPreferredLanguage(?string $preferredLanguage)
    {
        $this->preferredLanguage = $preferredLanguage;

        return $this;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getNotificationSettings()
    {
        return $this->notificationSettings;
    }

    public function setNotificationSettings(?array $notificationSettings)
    {
        $this->notificationSettings = $notificationSettings;

        return $this;
    }

    public function getPrivacySettings()
    {
        return $this->privacySettings;
    }

    public function setPrivacySettings(?array $privacySettings)
    {
        $this->privacySettings = $privacySettings;

        return $this;
    }

    public function getProfile()
    {
        return $this->profile;
    }

    public function setProfile(?UserProfile $profile)
    {
        $this->profile = $profile;
        return $this;
    }

    public function getVerification()
    {
        return $this->verification;
    }

    public function setVerification(?UserVerification $verification)
    {
        $this->verification = $verification;
        return $this;
    }

    public function getPushSubscription()
    {
        return $this->pushSubscription;
    }

    public function setPushSubscription(?array $pushSubscription)
    {
        $this->pushSubscription = $pushSubscription;

        return $this;
    }

    public function isPhoneVerified()
    {
        return null !== $this->verification?->getPhoneVerifiedAt();
    }

    public function setIsPhoneVerified(bool $verified)
    {
        if ($verified && !$this->verification) {
            $this->verification = new \App\User\Entity\UserVerification($this->id);
        }

        if ($this->verification) {
            $this->verification->setPhoneVerifiedAt($verified ? new \DateTime() : null);
        }
    }

    public function getDisplayName()
    {
        return $this->profile?->getDisplayName() ?? $this->email;
    }

    public function getBio()
    {
        return $this->profile?->getBio();
    }

    public function setDisplayName(string $displayName)
    {
        if ($this->profile) {
            $this->profile->setDisplayName($displayName);
        }
        return $this;
    }

    public function setBio(?string $bio)
    {
        if ($this->profile) {
            $this->profile->setBio($bio);
        }
        return $this;
    }

    public function getLocation()
    {
        return $this->profile?->getLocation();
    }

    public function getWebsite()
    {
        return $this->profile?->getWebsite();
    }

    public function getSocialLinks()
    {
        return $this->profile?->getSocialLinks();
    }

    public function getBirthDate()
    {
        return $this->profile?->getBirthDate();
    }

    public function getGender()
    {
        return $this->profile?->getGender();
    }

    public function getOccupation()
    {
        return $this->profile?->getOccupation();
    }

    public function getInterests()
    {
        return $this->profile?->getInterests();
    }

    public function getCustomFields()
    {
        return $this->profile?->getCustomFields();
    }

    public function getProfileUpdatedAt()
    {
        return $this->profile?->getProfileUpdatedAt();
    }

    public function getPreferredTheme()
    {
        return $this->profile?->getPreferredTheme();
    }

    public function getAvatarUrl()
    {
        return $this->profile?->getAvatarUrl();
    }

    // Verification methods
    public function getVerificationToken()
    {
        return $this->verification?->getVerificationToken();
    }

    public function setVerificationToken(?string $token)
    {
        if (!$this->verification) {
            $this->verification = new \App\User\Entity\UserVerification($this->id);
        }
        $this->verification->setVerificationToken($token);
        return $this;
    }

    public function getVerificationTokenExpiresAt()
    {
        return $this->verification?->getVerificationTokenExpiresAt();
    }

    public function setVerificationTokenExpiresAt(?\DateTime $expiresAt)
    {
        if (!$this->verification) {
            $this->verification = new \App\User\Entity\UserVerification($this->id);
        }
        $this->verification->setVerificationTokenExpiresAt($expiresAt);
        return $this;
    }

    public function getEmailVerifiedAt()
    {
        return $this->verification?->getEmailVerifiedAt();
    }

    public function setEmailVerifiedAt(?\DateTime $verifiedAt)
    {
        if (!$this->verification) {
            $this->verification = new \App\User\Entity\UserVerification($this->id);
        }
        $this->verification->setEmailVerifiedAt($verifiedAt);
        return $this;
    }

    public function getPhoneVerifiedAt()
    {
        return $this->verification?->getPhoneVerifiedAt();
    }

    public function setPhoneVerifiedAt(?\DateTime $verifiedAt)
    {
        if (!$this->verification) {
            $this->verification = new \App\User\Entity\UserVerification($this->id);
        }
        $this->verification->setPhoneVerifiedAt($verifiedAt);
        return $this;
    }

    public function getBiometricVerifiedAt()
    {
        return $this->verification?->getBiometricVerifiedAt();
    }

    public function setBiometricVerifiedAt(?\DateTime $verifiedAt)
    {
        if (!$this->verification) {
            $this->verification = new \App\User\Entity\UserVerification($this->id);
        }
        $this->verification->setBiometricVerifiedAt($verifiedAt);
        return $this;
    }

    public function getVerificationLevel()
    {
        return $this->verification?->getVerificationLevelAsInt() ?? 0;
    }

    public function setVerificationLevel(string|int $level)
    {
        if (!$this->verification) {
            $this->verification = new \App\User\Entity\UserVerification($this->id);
        }
        $this->verification->setVerificationLevel($level);
        return $this;
    }

    public function eraseCredentials()
    {
        // Clear any temporary, sensitive data
        $this->biometricDataPlain = null;
    }

    public function getTotpSecret()
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $totpSecret)
    {
        $this->totpSecret = $totpSecret;

        return $this;
    }

    public function isTotpEnabled()
    {
        return $this->totpEnabled;
    }

    public function setTotpEnabled(bool $totpEnabled)
    {
        $this->totpEnabled = $totpEnabled;

        return $this;
    }

    public function getTotpBackupCodes()
    {
        return $this->totpBackupCodes;
    }

    public function setTotpBackupCodes(?array $totpBackupCodes)
    {
        $this->totpBackupCodes = $totpBackupCodes;

        return $this;
    }

    public function getTotpAttempts()
    {
        return $this->totpAttempts;
    }

    public function setTotpAttempts(int $totpAttempts)
    {
        $this->totpAttempts = $totpAttempts;

        return $this;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function isDeleted()
    {
        return $this->deletedAt !== null;
    }

    public function markAsDeleted()
    {
        $this->deletedAt = new \DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'roles' => $this->roles,
            'phoneNumber' => $this->phoneNumber,
            'socialProvider' => $this->socialProvider,
            'socialId' => $this->socialId,
            'preferredLanguage' => $this->preferredLanguage,
            'timezone' => $this->timezone,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt?->format('c'),
            'lastLoginAt' => $this->lastLoginAt?->format('c'),
            'deletedAt' => $this->deletedAt?->format('c'),
        ];
    }
}
