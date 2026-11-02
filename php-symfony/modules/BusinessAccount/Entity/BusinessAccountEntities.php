<?php declare(strict_types=1);
namespace App\BusinessAccount\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: \App\BusinessAccount\Repository\BusinessRepository::class)]
#[ORM\Table(name: "ba_businesses")]
class Business implements PasswordAuthenticatedUserInterface {
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;
    #[ORM\Column(type: "string", length: 36)] private string $ownerId;
    #[ORM\Column(type: "string", length: 200)] private string $name;
    #[ORM\Column(type: "string", length: 200, nullable: true)] private ?string $displayName = null;
    #[ORM\Column(type: "string", length: 500, nullable: true)] private ?string $description = null;
    #[ORM\Column(type: "string", length: 100)] private string $category;
    #[ORM\Column(type: "string", length: 100, nullable: true)] private ?string $subcategory = null;
    #[ORM\Column(type: "string", length: 255, nullable: true)] private ?string $logoUrl = null;
    #[ORM\Column(type: "string", length: 255, nullable: true)] private ?string $coverUrl = null;
    #[ORM\Column(type: "string", length: 500, nullable: true)] private ?string $address = null;
    #[ORM\Column(type: "string", length: 20, nullable: true)] private ?string $phone = null;
    #[ORM\Column(type: "string", length: 100, nullable: true)] private ?string $email = null;
    #[ORM\Column(type: "string", length: 255, nullable: true)] private ?string $website = null;
    #[ORM\Column(type: "string", length: 20)] private string $status = self::STATUS_PENDING;
    #[ORM\Column(type: "string", length: 50)] private string $subscription = 'basic';
    #[ORM\Column(type: "datetime", nullable: true)] private ?\DateTime $subscriptionExpiresAt = null;
    #[ORM\Column(type: "boolean")] private bool $isVerified = false;
    #[ORM\Column(type: "string", length: 255, nullable: true)] private ?string $businessHours = null;
    #[ORM\Column(type: "text", nullable: true)] private ?string $autoReplyMessage = null;
    #[ORM\Column(type: "integer")] private int $totalEmployees = 1;
    #[ORM\Column(type: "integer")] private int $totalCatalogItems = 0;
    #[ORM\Column(type: "integer")] private int $totalMessages = 0;
    #[ORM\Column(type: "datetime")] private \DateTime $createdAt;
    #[ORM\Column(type: "datetime", nullable: true)] private ?\DateTime $updatedAt = null;
    public function __construct() { $this->createdAt = new \DateTime(); }
    public function getId() { return $this->id; }

    public function getOwnerId() { return $this->ownerId; }
    public function setOwnerId(string $ownerId) { $this->ownerId = $ownerId; return $this; }
    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; return $this; }
    public function getDisplayName() { return $this->displayName; }
    public function setDisplayName(?string $displayName) { $this->displayName = $displayName; return $this; }
    public function getDescription() { return $this->description; }
    public function setDescription(?string $description) { $this->description = $description; return $this; }
    public function getCategory() { return $this->category; }
    public function setCategory(string $category) { $this->category = $category; return $this; }
    public function getSubcategory() { return $this->subcategory; }
    public function setSubcategory(?string $subcategory) { $this->subcategory = $subcategory; return $this; }
    public function getLogoUrl() { return $this->logoUrl; }
    public function setLogoUrl(?string $logoUrl) { $this->logoUrl = $logoUrl; return $this; }
    public function getCoverUrl() { return $this->coverUrl; }
    public function setCoverUrl(?string $coverUrl) { $this->coverUrl = $coverUrl; return $this; }
    public function getAddress() { return $this->address; }
    public function setAddress(?string $address) { $this->address = $address; return $this; }
    public function getPhone() { return $this->phone; }
    public function setPhone(?string $phone) { $this->phone = $phone; return $this; }
    public function getEmail() { return $this->email; }
    public function setEmail(?string $email) { $this->email = $email; return $this; }
    public function getWebsite() { return $this->website; }
    public function setWebsite(?string $website) { $this->website = $website; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getSubscription() { return $this->subscription; }
    public function setSubscription(string $subscription) { $this->subscription = $subscription; return $this; }
    public function getSubscriptionExpiresAt() { return $this->subscriptionExpiresAt; }
    public function setSubscriptionExpiresAt(?\DateTime $subscriptionExpiresAt) { $this->subscriptionExpiresAt = $subscriptionExpiresAt; return $this; }
    public function isVerified() { return $this->isVerified; }
    public function setVerified(bool $isVerified) { $this->isVerified = $isVerified; return $this; }
    public function getBusinessHours() { return $this->businessHours; }
    public function setBusinessHours(?string $businessHours) { $this->businessHours = $businessHours; return $this; }
    public function getAutoReplyMessage() { return $this->autoReplyMessage; }
    public function setAutoReplyMessage(?string $autoReplyMessage) { $this->autoReplyMessage = $autoReplyMessage; return $this; }
    public function getTotalEmployees() { return $this->totalEmployees; }
    public function setTotalEmployees(int $totalEmployees) { $this->totalEmployees = $totalEmployees; return $this; }
    public function getTotalCatalogItems() { return $this->totalCatalogItems; }
    public function setTotalCatalogItems(int $totalCatalogItems) { $this->totalCatalogItems = $totalCatalogItems; return $this; }
    public function getTotalMessages() { return $this->totalMessages; }
    public function setTotalMessages(int $totalMessages) { $this->totalMessages = $totalMessages; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTime $updatedAt) { $this->updatedAt = $updatedAt; return $this; }
    
    // Additional methods for BusinessAccountService compatibility
    private ?float $latitude = null;
    private ?float $longitude = null;
    private ?string $instagram = null;
    private ?string $facebook = null;
    private ?string $password = null;
    private ?string $logo = null;
    private ?string $coverImage = null;
    private bool $isActive = true;
    
    public function getLatitude() { return $this->latitude; }
    public function setLatitude(?float $latitude) { $this->latitude = $latitude; return $this; }
    public function getLongitude() { return $this->longitude; }
    public function setLongitude(?float $longitude) { $this->longitude = $longitude; return $this; }
    public function getInstagram() { return $this->instagram; }
    public function setInstagram(?string $instagram) { $this->instagram = $instagram; return $this; }
    public function getFacebook() { return $this->facebook; }
    public function setFacebook(?string $facebook) { $this->facebook = $facebook; return $this; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(?string $password) { $this->password = $password; return $this; }
    public function getLogo() { return $this->logo; }
    public function setLogo(?string $logo) { $this->logo = $logo; return $this; }
    public function getCoverImage() { return $this->coverImage; }
    public function setCoverImage(?string $coverImage) { $this->coverImage = $coverImage; return $this; }
    public function isActive() { return $this->isActive; }
    public function setIsActive(bool $isActive) { $this->isActive = $isActive; return $this; }
    public function isIsVerified() { return $this->isVerified; }
    public function setIsVerified(bool $isVerified) { $this->isVerified = $isVerified; return $this; }

    // PasswordAuthenticatedUserInterface & UserInterface
    public function getRoles()
    {
        return ['ROLE_BUSINESS'];
    }

    public function eraseCredentials()
    {
        // No temporary credentials stored
    }

    public function getUserIdentifier()
    {
        return $this->ownerId;
    }
}

#[ORM\Entity(repositoryClass: \App\BusinessAccount\Repository\CatalogItemRepository::class)]
#[ORM\Table(name: "ba_catalog_items")]
class CatalogItem {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;
    #[ORM\ManyToOne(targetEntity: Business::class, inversedBy: "catalogItems")] #[ORM\JoinColumn(name: "business_id", referencedColumnName: "id")] private Business $business;
    #[ORM\Column(type: "string", length: 200)] private string $name;
    #[ORM\Column(type: "string", length: 500, nullable: true)] private ?string $description = null;
    #[ORM\Column(type: "decimal", precision: 10, scale: 2)] private float $price;
    #[ORM\Column(type: "string", length: 50, nullable: true)] private ?string $currency = null;
    #[ORM\Column(type: "string", length: 100)] private string $category;
    #[ORM\Column(type: "string", length: 255, nullable: true)] private ?string $imageUrl = null;
    #[ORM\Column(type: "string", length: 100, nullable: true)] private ?string $sku = null;
    #[ORM\Column(type: "integer", nullable: true)] private ?int $stock = null;
    #[ORM\Column(type: "string", length: 50, nullable: true)] private ?string $status = null;
    #[ORM\Column(type: "boolean")] private bool $isAvailable = true;
    #[ORM\Column(type: "integer")] private int $viewCount = 0;
    #[ORM\Column(type: "integer")] private int $orderCount = 0;
    #[ORM\Column(type: "datetime")] private \DateTime $createdAt;
    public function __construct() { $this->createdAt = new \DateTime(); }
    public function getId() { return $this->id; }

    public function getBusiness() { return $this->business; }
    public function setBusiness(Business $business) { $this->business = $business; return $this; }
    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; return $this; }
    public function getDescription() { return $this->description; }
    public function setDescription(?string $description) { $this->description = $description; return $this; }
    public function getPrice() { return $this->price; }
    public function setPrice(float $price) { $this->price = $price; return $this; }
    public function getCurrency() { return $this->currency; }
    public function setCurrency(?string $currency) { $this->currency = $currency; return $this; }
    public function getCategory() { return $this->category; }
    public function setCategory(string $category) { $this->category = $category; return $this; }
    public function getImageUrl() { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl) { $this->imageUrl = $imageUrl; return $this; }
    public function getSku() { return $this->sku; }
    public function setSku(?string $sku) { $this->sku = $sku; return $this; }
    public function getStock() { return $this->stock; }
    public function setStock(?int $stock) { $this->stock = $stock; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(?string $status) { $this->status = $status; return $this; }
    public function isAvailable() { return $this->isAvailable; }
    public function setAvailable(bool $isAvailable) { $this->isAvailable = $isAvailable; return $this; }
    public function isActive(): bool { return $this->isAvailable; }
    public function setIsActive(bool $active): self { $this->isAvailable = $active; return $this; }
    public function isIsActive(): bool { return $this->isActive(); }
    public function getViewCount() { return $this->viewCount; }
    public function setViewCount(int $viewCount) { $this->viewCount = $viewCount; return $this; }
    public function getOrderCount() { return $this->orderCount; }
    public function setOrderCount(int $orderCount) { $this->orderCount = $orderCount; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
}

#[ORM\Entity(repositoryClass: \App\BusinessAccount\Repository\SubscriptionRepository::class)]
#[ORM\Table(name: "ba_subscriptions")]
class Subscription {
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;
    #[ORM\ManyToOne(targetEntity: Business::class)] #[ORM\JoinColumn(name: "business_id", referencedColumnName: "id")] private Business $business;
    #[ORM\Column(type: "string", length: 50)] private string $plan;
    #[ORM\Column(type: "decimal", precision: 10, scale: 2)] private float $price;
    #[ORM\Column(type: "string", length: 20)] private string $billingCycle = 'monthly';
    #[ORM\Column(type: "datetime")] private \DateTime $startedAt;
    #[ORM\Column(type: "datetime")] private \DateTime $expiresAt;
    #[ORM\Column(type: "string", length: 20)] private string $status = self::STATUS_ACTIVE;
    #[ORM\Column(type: "string", length: 100, nullable: true)] private ?string $paymentMethod = null;
    #[ORM\Column(type: "string", length: 100, nullable: true)] private ?string $transactionId = null;
    #[ORM\Column(type: "datetime")] private \DateTime $createdAt;
    public function __construct() { $this->createdAt = new \DateTime(); }
    public function getId() { return $this->id; }

    public function getBusiness() { return $this->business; }
    public function setBusiness(Business $business) { $this->business = $business; return $this; }
    public function getPlan() { return $this->plan; }
    public function setPlan(string $plan) { $this->plan = $plan; return $this; }
    public function getPrice() { return $this->price; }
    public function setPrice(float $price) { $this->price = $price; return $this; }
    public function getBillingCycle() { return $this->billingCycle; }
    public function setBillingCycle(string $billingCycle) { $this->billingCycle = $billingCycle; return $this; }
    public function getStartedAt() { return $this->startedAt; }
    public function setStartedAt(\DateTime $startedAt) { $this->startedAt = $startedAt; return $this; }
    public function getExpiresAt() { return $this->expiresAt; }
    public function setExpiresAt(\DateTime $expiresAt) { $this->expiresAt = $expiresAt; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getPaymentMethod() { return $this->paymentMethod; }
    public function setPaymentMethod(?string $paymentMethod) { $this->paymentMethod = $paymentMethod; return $this; }
    public function getTransactionId() { return $this->transactionId; }
    public function setTransactionId(?string $transactionId) { $this->transactionId = $transactionId; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
}

#[ORM\Entity(repositoryClass: \App\BusinessAccount\Repository\ApiKeyRepository::class)]
#[ORM\Table(name: "ba_api_keys")]
class ApiKey {
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;
    #[ORM\ManyToOne(targetEntity: Business::class)] #[ORM\JoinColumn(name: "business_id", referencedColumnName: "id")] private Business $business;
    #[ORM\Column(type: "string", length: 64)] private string $key;
    #[ORM\Column(type: "string", length: 100)] private string $name;
    #[ORM\Column(type: "string", length: 20)] private string $status = self::STATUS_ACTIVE;
    #[ORM\Column(type: "string", length: 50, nullable: true)] private ?string $permissions = null;
    #[ORM\Column(type: "integer")] private int $requestLimit = 1000;
    #[ORM\Column(type: "integer")] private int $requestCount = 0;
    #[ORM\Column(type: "datetime")] private \DateTime $lastUsedAt;
    #[ORM\Column(type: "datetime")] private \DateTime $createdAt;
    #[ORM\Column(type: "datetime", nullable: true)] private ?\DateTime $expiresAt = null;
    public function __construct() { $this->createdAt = new \DateTime(); $this->lastUsedAt = new \DateTime(); }
    public function getId() { return $this->id; }

    public function getBusiness() { return $this->business; }
    public function setBusiness(Business $business) { $this->business = $business; return $this; }
    public function getKey() { return $this->key; }
    public function setKey(string $key) { $this->key = $key; return $this; }
    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getPermissions() { return $this->permissions; }
    public function setPermissions(?string $permissions) { $this->permissions = $permissions; return $this; }
    public function getRequestLimit() { return $this->requestLimit; }
    public function setRequestLimit(int $requestLimit) { $this->requestLimit = $requestLimit; return $this; }
    public function getRequestCount() { return $this->requestCount; }
    public function setRequestCount(int $requestCount) { $this->requestCount = $requestCount; return $this; }
    public function getLastUsedAt() { return $this->lastUsedAt; }
    public function setLastUsedAt(\DateTime $lastUsedAt) { $this->lastUsedAt = $lastUsedAt; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getExpiresAt() { return $this->expiresAt; }
    public function setExpiresAt(?\DateTime $expiresAt) { $this->expiresAt = $expiresAt; return $this; }
    public function isActive(): bool { return $this->status === self::STATUS_ACTIVE; }
    public function setIsActive(bool $active): self { $this->status = $active ? self::STATUS_ACTIVE : self::STATUS_REVOKED; return $this; }
}

#[ORM\Entity(repositoryClass: \App\BusinessAccount\Repository\BusinessCategoryRepository::class)]
#[ORM\Table(name: "ba_categories")]
class BusinessCategory {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;
    #[ORM\Column(type: "string", length: 100)] private string $name;
    #[ORM\Column(type: "string", length: 100)] private string $slug;
    #[ORM\Column(type: "string", length: 500, nullable: true)] private ?string $description = null;
    #[ORM\Column(type: "string", length: 255, nullable: true)] private ?string $iconUrl = null;
    #[ORM\Column(type: "integer")] private int $displayOrder = 0;
    #[ORM\Column(type: "boolean")] private bool $isActive = true;
    #[ORM\Column(type: "datetime")] private \DateTime $createdAt;
    public function __construct() { $this->createdAt = new \DateTime(); }
    public function getId() { return $this->id; }

    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; return $this; }
    public function getSlug() { return $this->slug; }
    public function setSlug(string $slug) { $this->slug = $slug; return $this; }
    public function getIconUrl() { return $this->iconUrl; }
    public function setIconUrl(?string $iconUrl) { $this->iconUrl = $iconUrl; return $this; }
    public function getDisplayOrder() { return $this->displayOrder; }
    public function setDisplayOrder(int $displayOrder) { $this->displayOrder = $displayOrder; return $this; }
    public function isActive() { return $this->isActive; }
    public function setActive(bool $isActive) { $this->isActive = $isActive; return $this; }
    public function setIsActive(bool $isActive) { return $this->setActive($isActive); }
    public function getDescription() { return $this->description; }
    public function setDescription(?string $description) { $this->description = $description; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
}
