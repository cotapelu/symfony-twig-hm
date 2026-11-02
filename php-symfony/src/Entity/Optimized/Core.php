<?php

declare(strict_types=1);

namespace App\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity]
#[ORM\Table(name: 'php.user_sessions')]
#[ORM\Index(name: 'idx_user_sessions_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_user_sessions_token', columns: ['token_hash'], unique: true)]
#[ORM\Index(name: 'idx_user_sessions_expires', columns: ['expires_at'])]
#[HasLifecycleCallbacks]
class UserSession
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_REVOKED = 'REVOKED';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(name: 'token_hash', type: 'string', length: 64)]
    private string $tokenHash;

    #[ORM\Column(name: 'ip_address', type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(name: 'user_agent', type: 'text', nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $deviceInfo = null;

    #[ORM\Column(name: 'expires_at', type: 'datetime')]
    private \DateTime $expiresAt;

    #[ORM\Column(name: 'last_activity_at', type: 'datetime')]
    private \DateTime $lastActivityAt;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId, string $tokenHash, \DateTime $expiresAt)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->tokenHash = $tokenHash;
        $this->expiresAt = $expiresAt;
        $this->lastActivityAt = new \DateTime();
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getTokenHash() { return $this->tokenHash; }
    public function getIpAddress() { return $this->ipAddress; }
    public function getUserAgent() { return $this->userAgent; }
    public function getDeviceInfo() { return $this->deviceInfo; }
    public function getExpiresAt() { return $this->expiresAt; }
    public function getLastActivityAt() { return $this->lastActivityAt; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setIpAddress(?string $ip) { $this->ipAddress = $ip; return $this; }
    public function setUserAgent(?string $ua) { $this->userAgent = $ua; return $this; }
    public function setDeviceInfo(?array $info) { $this->deviceInfo = $info; return $this; }
    public function updateLastActivity() { $this->lastActivityAt = new \DateTime(); return $this; }
    public function expire() { $this->status = self::STATUS_EXPIRED; return $this; }
    public function revoke() { $this->status = self::STATUS_REVOKED; return $this; }

    public function isExpired()
    {
        return $this->expiresAt < new \DateTime() || $this->status === self::STATUS_EXPIRED;
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE && !$this->isExpired();
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.device_tokens')]
#[ORM\Index(name: 'idx_device_tokens_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_device_tokens_token', columns: ['token'], unique: true)]
#[ORM\Index(name: 'idx_device_tokens_platform', columns: ['platform'])]
#[HasLifecycleCallbacks]
class DeviceToken
{
    public const PLATFORM_IOS = 'IOS';
    public const PLATFORM_ANDROID = 'ANDROID';
    public const PLATFORM_WEB = 'WEB';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $token;

    #[ORM\Column(type: 'string', length: 20)]
    private string $platform;

    #[ORM\Column(name: 'device_name', type: 'string', length: 100, nullable: true)]
    private ?string $deviceName = null;

    #[ORM\Column(name: 'device_model', type: 'string', length: 100, nullable: true)]
    private ?string $deviceModel = null;

    #[ORM\Column(name: 'os_version', type: 'string', length: 50, nullable: true)]
    private ?string $osVersion = null;

    #[ORM\Column(name: 'app_version', type: 'string', length: 50, nullable: true)]
    private ?string $appVersion = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(name: 'last_used_at', type: 'datetime', nullable: true)]
    private ?\DateTime $lastUsedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId, string $token, string $platform)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->token = $token;
        $this->platform = $platform;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getToken() { return $this->token; }
    public function getPlatform() { return $this->platform; }
    public function getDeviceName() { return $this->deviceName; }
    public function getDeviceModel() { return $this->deviceModel; }
    public function getOsVersion() { return $this->osVersion; }
    public function getAppVersion() { return $this->appVersion; }
    public function getStatus() { return $this->status; }
    public function getLastUsedAt() { return $this->lastUsedAt; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setDeviceName(?string $name) { $this->deviceName = $name; return $this; }
    public function setDeviceModel(?string $model) { $this->deviceModel = $model; return $this; }
    public function setOsVersion(?string $version) { $this->osVersion = $version; return $this; }
    public function setAppVersion(?string $version) { $this->appVersion = $version; return $this; }
    public function updateLastUsed() { $this->lastUsedAt = new \DateTime(); return $this; }
    public function deactivate() { $this->status = self::STATUS_INACTIVE; return $this; }
    public function activate() { $this->status = self::STATUS_ACTIVE; return $this; }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.oauth_providers')]
#[ORM\Index(name: 'idx_oauth_providers_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_oauth_providers_provider', columns: ['provider'])]
#[ORM\Index(name: 'idx_oauth_providers_uid', columns: ['provider', 'provider_user_id'], unique: true)]
#[HasLifecycleCallbacks]
class OAuthProvider
{
    public const PROVIDER_GOOGLE = 'GOOGLE';
    public const PROVIDER_FACEBOOK = 'FACEBOOK';
    public const PROVIDER_APPLE = 'APPLE';
    public const PROVIDER_GITHUB = 'GITHUB';
    public const PROVIDER_LINKEDIN = 'LINKEDIN';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $provider;

    #[ORM\Column(name: 'provider_user_id', type: 'string', length: 255)]
    private string $providerUserId;

    #[ORM\Column(name: 'access_token', type: 'text', nullable: true)]
    private ?string $accessToken = null;

    #[ORM\Column(name: 'refresh_token', type: 'text', nullable: true)]
    private ?string $refreshToken = null;

    #[ORM\Column(name: 'token_expires_at', type: 'datetime', nullable: true)]
    private ?\DateTime $tokenExpiresAt = null;

    #[ORM\Column(name: 'scope', type: 'json', nullable: true)]
    private ?array $scope = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $userId, string $provider, string $providerUserId)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->provider = $provider;
        $this->providerUserId = $providerUserId;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getProvider() { return $this->provider; }
    public function getProviderUserId() { return $this->providerUserId; }
    public function getAccessToken() { return $this->accessToken; }
    public function getRefreshToken() { return $this->refreshToken; }
    public function getTokenExpiresAt() { return $this->tokenExpiresAt; }
    public function getScope() { return $this->scope; }
    public function getMetadata() { return $this->metadata; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    public function setAccessToken(?string $token) { $this->accessToken = $token; $this->updatedAt = new \DateTime(); return $this; }
    public function setRefreshToken(?string $token) { $this->refreshToken = $token; $this->updatedAt = new \DateTime(); return $this; }
    public function setTokenExpiresAt(?\DateTime $expires) { $this->tokenExpiresAt = $expires; $this->updatedAt = new \DateTime(); return $this; }
    public function setScope(?array $scope) { $this->scope = $scope; $this->updatedAt = new \DateTime(); return $this; }
    public function setMetadata(?array $meta) { $this->metadata = $meta; $this->updatedAt = new \DateTime(); return $this; }

    public function isTokenExpired()
    {
        return $this->tokenExpiresAt !== null && $this->tokenExpiresAt < new \DateTime();
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.user_roles')]
#[ORM\Index(name: 'idx_user_roles_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_user_roles_role', columns: ['role'])]
#[ORM\Index(name: 'idx_user_roles_user_role', columns: ['user_id', 'role'], unique: true)]
#[HasLifecycleCallbacks]
class UserRole
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_OFFICER = 'ROLE_OFFICER';
    public const ROLE_BUSINESS = 'ROLE_BUSINESS';
    public const ROLE_CITIZEN = 'ROLE_CITIZEN';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $role;

    #[ORM\Column(name: 'granted_by', type: 'string', length: 36, nullable: true)]
    private ?string $grantedBy = null;

    #[ORM\Column(name: 'granted_at', type: 'datetime')]
    private \DateTime $grantedAt;

    #[ORM\Column(name: 'expires_at', type: 'datetime', nullable: true)]
    private ?\DateTime $expiresAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct(string $id, string $userId, string $role)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->role = $role;
        $this->grantedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getRole() { return $this->role; }
    public function getGrantedBy() { return $this->grantedBy; }
    public function getGrantedAt() { return $this->grantedAt; }
    public function getExpiresAt() { return $this->expiresAt; }
    public function getMetadata() { return $this->metadata; }

    public function setGrantedBy(?string $userId) { $this->grantedBy = $userId; return $this; }
    public function setExpiresAt(?\DateTime $expires) { $this->expiresAt = $expires; return $this; }
    public function setMetadata(?array $meta) { $this->metadata = $meta; return $this; }

    public function isExpired()
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTime();
    }

    public function isActive()
    {
        return !$this->isExpired();
    }
}
