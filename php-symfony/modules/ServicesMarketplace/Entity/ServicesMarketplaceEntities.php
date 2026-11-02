<?php declare(strict_types=1);
namespace App\ServicesMarketplace\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: App\ServicesMarketplace\Repository\ServiceProviderRepository::class)]
#[ORM\Table(name: "sm_service_providers")]
class ServiceProvider {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;
    #[ORM\Column(type: "string", length: 36)] private string $userId;
    #[ORM\Column(type: "string", length: 200)] private string $businessName;
    #[ORM\Column(type: "string", length: 200, nullable: true)] private ?string $displayName = null;
    #[ORM\Column(type: "string", length: 500, nullable: true)] private ?string $description = null;
    #[ORM\Column(type: "string", length: 100)] private string $category;
    #[ORM\Column(type: "string", length: 100, nullable: true)] private ?string $subcategory = null;
    #[ORM\Column(type: "string", length: 500)] private string $address;
    #[ORM\Column(type: "decimal", precision: 10, scale: 7)] private float $latitude;
    #[ORM\Column(type: "decimal", precision: 10, scale: 7)] private float $longitude;
    #[ORM\Column(type: "string", length: 20, nullable: true)] private ?string $phone = null;
    #[ORM\Column(type: "string", length: 255, nullable: true)] private ?string $imageUrl = null;
    #[ORM\Column(type: "float")] private float $rating = 0;
    #[ORM\Column(type: "integer")] private int $reviewCount = 0;
    #[ORM\Column(type: "decimal", precision: 10, scale: 2)] private float $startingPrice = 0;
    #[ORM\Column(type: "string", length: 20)] private string $status = 'pending';
    #[ORM\Column(type: "boolean")] private bool $isVerified = false;
    #[ORM\Column(type: "integer")] private int $completedJobs = 0;
    #[ORM\Column(type: "datetime")] private \DateTime $createdAt;
    #[ORM\Column(type: "datetime", nullable: true)] private ?\DateTime $updatedAt = null;
    public function __construct() { $this->createdAt = new \DateTime(); }
    public function getId() { return $this->id; }

    public function getUserId() { return $this->userId; }
    public function setUserId(string $userId) { $this->userId = $userId; return $this; }
    public function getBusinessName() { return $this->businessName; }
    public function setBusinessName(string $businessName) { $this->businessName = $businessName; return $this; }
    public function getDisplayName() { return $this->displayName; }
    public function setDisplayName(?string $displayName) { $this->displayName = $displayName; return $this; }
    public function getDescription() { return $this->description; }
    public function setDescription(?string $description) { $this->description = $description; return $this; }
    public function getCategory() { return $this->category; }
    public function setCategory(string $category) { $this->category = $category; return $this; }
    public function getSubcategory() { return $this->subcategory; }
    public function setSubcategory(?string $subcategory) { $this->subcategory = $subcategory; return $this; }
    public function getAddress() { return $this->address; }
    public function setAddress(string $address) { $this->address = $address; return $this; }
    public function getLatitude() { return $this->latitude; }
    public function setLatitude(float $latitude) { $this->latitude = $latitude; return $this; }
    public function getLongitude() { return $this->longitude; }
    public function setLongitude(float $longitude) { $this->longitude = $longitude; return $this; }
    public function getPhone() { return $this->phone; }
    public function setPhone(?string $phone) { $this->phone = $phone; return $this; }
    public function getImageUrl() { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl) { $this->imageUrl = $imageUrl; return $this; }
    public function getRating() { return $this->rating; }
    public function setRating(float $rating) { $this->rating = $rating; return $this; }
    public function getReviewCount() { return $this->reviewCount; }
    public function setReviewCount(int $reviewCount) { $this->reviewCount = $reviewCount; return $this; }
    public function getStartingPrice() { return $this->startingPrice; }
    public function setStartingPrice(float $startingPrice) { $this->startingPrice = $startingPrice; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function isVerified() { return $this->isVerified; }
    public function setVerified(bool $isVerified) { $this->isVerified = $isVerified; return $this; }
    public function getCompletedJobs() { return $this->completedJobs; }
    public function setCompletedJobs(int $completedJobs) { $this->completedJobs = $completedJobs; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTime $updatedAt) { $this->updatedAt = $updatedAt; return $this; }
}

#[ORM\Entity(repositoryClass: App\ServicesMarketplace\Repository\ServiceRepository::class)]
#[ORM\Table(name: "sm_services")]
class Service {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;
    #[ORM\ManyToOne(targetEntity: ServiceProvider::class, inversedBy: "services")] #[ORM\JoinColumn(name: "provider_id", referencedColumnName: "id")] private ServiceProvider $provider;
    #[ORM\Column(type: "string", length: 200)] private string $name;
    #[ORM\Column(type: "string", length: 500, nullable: true)] private ?string $description = null;
    #[ORM\Column(type: "string", length: 100)] private string $category;
    #[ORM\Column(type: "string", length: 100, nullable: true)] private ?string $subcategory = null;
    #[ORM\Column(type: "decimal", precision: 10, scale: 2)] private float $price;
    #[ORM\Column(type: "string", length: 20)] private string $priceType = 'fixed';
    #[ORM\Column(type: "integer", nullable: true)] private ?int $estimatedDuration = null;
    #[ORM\Column(type: "text", nullable: true)] private ?string $includes = null;
    #[ORM\Column(type: "text", nullable: true)] private ?string $excludes = null;
    #[ORM\Column(type: "string", length: 255, nullable: true)] private ?string $imageUrl = null;
    #[ORM\Column(type: "boolean")] private bool $isActive = true;
    #[ORM\Column(type: "integer")] private int $bookedCount = 0;
    #[ORM\Column(type: "datetime")] private \DateTime $createdAt;
    public function __construct() { $this->createdAt = new \DateTime(); }
    public function getId() { return $this->id; }

    public function getProvider() { return $this->provider; }
    public function setProvider(ServiceProvider $provider) { $this->provider = $provider; return $this; }
    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; return $this; }
    public function getDescription() { return $this->description; }
    public function setDescription(?string $description) { $this->description = $description; return $this; }
    public function getCategory() { return $this->category; }
    public function setCategory(string $category) { $this->category = $category; return $this; }
    public function getSubcategory() { return $this->subcategory; }
    public function setSubcategory(?string $subcategory) { $this->subcategory = $subcategory; return $this; }
    public function getPrice() { return $this->price; }
    public function setPrice(float $price) { $this->price = $price; return $this; }
    public function getPriceType() { return $this->priceType; }
    public function setPriceType(string $priceType) { $this->priceType = $priceType; return $this; }
    public function getEstimatedDuration() { return $this->estimatedDuration; }
    public function setEstimatedDuration(?int $estimatedDuration) { $this->estimatedDuration = $estimatedDuration; return $this; }
    public function getIncludes() { return $this->includes; }
    public function setIncludes(?string $includes) { $this->includes = $includes; return $this; }
    public function getExcludes() { return $this->excludes; }
    public function setExcludes(?string $excludes) { $this->excludes = $excludes; return $this; }
    public function getImageUrl() { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl) { $this->imageUrl = $imageUrl; return $this; }
    public function isActive() { return $this->isActive; }
    public function setActive(bool $isActive) { $this->isActive = $isActive; return $this; }
    public function getBookedCount() { return $this->bookedCount; }
    public function setBookedCount(int $bookedCount) { $this->bookedCount = $bookedCount; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
}

#[ORM\Entity(repositoryClass: App\ServicesMarketplace\Repository\BookingRepository::class)]
#[ORM\Table(name: "sm_bookings")]
class Booking {
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_DISPUTED = 'disputed';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;
    #[ORM\Column(type: "string", length: 36)] private string $userId;
    #[ORM\ManyToOne(targetEntity: ServiceProvider::class)] #[ORM\JoinColumn(name: "provider_id", referencedColumnName: "id")] private ServiceProvider $provider;
    #[ORM\ManyToOne(targetEntity: Service::class)] #[ORM\JoinColumn(name: "service_id", referencedColumnName: "id")] private Service $service;
    #[ORM\Column(type: "string", length: 500)] private string $address;
    #[ORM\Column(type: "decimal", precision: 10, scale: 7)] private float $latitude;
    #[ORM\Column(type: "decimal", precision: 10, scale: 7)] private float $longitude;
    #[ORM\Column(type: "datetime")] private \DateTime $scheduledAt;
    #[ORM\Column(type: "integer")] private int $duration;
    #[ORM\Column(type: "string", length: 20)] private string $status = self::STATUS_PENDING;
    #[ORM\Column(type: "decimal", precision: 10, scale: 2)] private float $price;
    #[ORM\Column(type: "decimal", precision: 10, scale: 2)] private float $platformFee;
    #[ORM\Column(type: "decimal", precision: 10, scale: 2)] private float $providerEarning;
    #[ORM\Column(type: "string", length: 50, nullable: true)] private ?string $paymentMethod = null;
    #[ORM\Column(type: "string", length: 100, nullable: true)] private ?string $paymentStatus = null;
    #[ORM\Column(type: "text", nullable: true)] private ?string $userNote = null;
    #[ORM\Column(type: "string", length: 100, nullable: true)] private ?string $couponCode = null;
    #[ORM\Column(type: "decimal", precision: 10, scale: 2, nullable: true)] private ?float $discount = null;
    #[ORM\Column(type: "datetime")] private \DateTime $createdAt;
    #[ORM\Column(type: "datetime", nullable: true)] private ?\DateTime $completedAt = null;
    public function __construct() { $this->createdAt = new \DateTime(); }
    public function getId() { return $this->id; }

    public function getUserId() { return $this->userId; }
    public function setUserId(string $userId) { $this->userId = $userId; return $this; }
    public function getProvider() { return $this->provider; }
    public function setProvider(ServiceProvider $provider) { $this->provider = $provider; return $this; }
    public function getService() { return $this->service; }
    public function setService(Service $service) { $this->service = $service; return $this; }
    public function getAddress() { return $this->address; }
    public function setAddress(string $address) { $this->address = $address; return $this; }
    public function getLatitude() { return $this->latitude; }
    public function setLatitude(float $latitude) { $this->latitude = $latitude; return $this; }
    public function getLongitude() { return $this->longitude; }
    public function setLongitude(float $longitude) { $this->longitude = $longitude; return $this; }
    public function getScheduledAt() { return $this->scheduledAt; }
    public function setScheduledAt(\DateTime $scheduledAt) { $this->scheduledAt = $scheduledAt; return $this; }
    public function getDuration() { return $this->duration; }
    public function setDuration(int $duration) { $this->duration = $duration; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getPrice() { return $this->price; }
    public function setPrice(float $price) { $this->price = $price; return $this; }
    public function getPlatformFee() { return $this->platformFee; }
    public function setPlatformFee(float $platformFee) { $this->platformFee = $platformFee; return $this; }
    public function getProviderEarning() { return $this->providerEarning; }
    public function setProviderEarning(float $providerEarning) { $this->providerEarning = $providerEarning; return $this; }
    public function getPaymentMethod() { return $this->paymentMethod; }
    public function setPaymentMethod(?string $paymentMethod) { $this->paymentMethod = $paymentMethod; return $this; }
    public function getPaymentStatus() { return $this->paymentStatus; }
    public function setPaymentStatus(?string $paymentStatus) { $this->paymentStatus = $paymentStatus; return $this; }
    public function getUserNote() { return $this->userNote; }
    public function setUserNote(?string $userNote) { $this->userNote = $userNote; return $this; }
    public function getCouponCode() { return $this->couponCode; }
    public function setCouponCode(?string $couponCode) { $this->couponCode = $couponCode; return $this; }
    public function getDiscount() { return $this->discount; }
    public function setDiscount(?float $discount) { $this->discount = $discount; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getCompletedAt() { return $this->completedAt; }
    public function setCompletedAt(?\DateTime $completedAt) { $this->completedAt = $completedAt; return $this; }
}

#[ORM\Entity(repositoryClass: App\ServicesMarketplace\Repository\ReviewRepository::class)]
#[ORM\Table(name: "sm_reviews")]
class Review {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;
    #[ORM\ManyToOne(targetEntity: Booking::class)] #[ORM\JoinColumn(name: "booking_id", referencedColumnName: "id")] private Booking $booking;
    #[ORM\ManyToOne(targetEntity: ServiceProvider::class)] #[ORM\JoinColumn(name: "provider_id", referencedColumnName: "id")] private ServiceProvider $provider;
    #[ORM\Column(type: "string", length: 36)] private string $userId;
    #[ORM\Column(type: "float")] private float $rating;
    #[ORM\Column(type: "text", nullable: true)] private ?string $comment = null;
    #[ORM\Column(type: "string", length: 50, nullable: true)] private ?string $serviceType = null;
    #[ORM\Column(type: "datetime")] private \DateTime $createdAt;
    public function __construct() { $this->createdAt = new \DateTime(); }
    public function getId() { return $this->id; }

    public function getBooking() { return $this->booking; }
    public function setBooking(Booking $booking) { $this->booking = $booking; return $this; }
    public function getProvider() { return $this->provider; }
    public function setProvider(ServiceProvider $provider) { $this->provider = $provider; return $this; }
    public function getUserId() { return $this->userId; }
    public function setUserId(string $userId) { $this->userId = $userId; return $this; }
    public function getRating() { return $this->rating; }
    public function setRating(float $rating) { $this->rating = $rating; return $this; }
    public function getComment() { return $this->comment; }
    public function setComment(?string $comment) { $this->comment = $comment; return $this; }
    public function getServiceType() { return $this->serviceType; }
    public function setServiceType(?string $serviceType) { $this->serviceType = $serviceType; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
}

#[ORM\Entity(repositoryClass: App\ServicesMarketplace\Repository\CategoryRepository::class)]
#[ORM\Table(name: "sm_categories")]
class Category {
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
    public function getDescription() { return $this->description; }
    public function setDescription(?string $description) { $this->description = $description; return $this; }
    public function getIconUrl() { return $this->iconUrl; }
    public function setIconUrl(?string $iconUrl) { $this->iconUrl = $iconUrl; return $this; }
    public function getDisplayOrder() { return $this->displayOrder; }
    public function setDisplayOrder(int $displayOrder) { $this->displayOrder = $displayOrder; return $this; }
    public function isActive() { return $this->isActive; }
    public function setActive(bool $isActive) { $this->isActive = $isActive; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
}
