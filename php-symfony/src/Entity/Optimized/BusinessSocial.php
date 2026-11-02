<?php

declare(strict_types=1);

namespace App\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity]
#[ORM\Table(name: 'php.businesses')]
#[ORM\Index(name: 'idx_businesses_owner', columns: ['owner_id'])]
#[ORM\Index(name: 'idx_businesses_type', columns: ['type'])]
#[HasLifecycleCallbacks]
class Business
{
    public const TYPE_RETAIL = 'RETAIL';
    public const TYPE_SERVICE = 'SERVICE';
    public const TYPE_MANUFACTURING = 'MANUFACTURING';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'owner_id', type: 'string', length: 36)]
    private string $ownerId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $license = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $ownerId, string $type, string $name)
    {
        $this->id = $id;
        $this->ownerId = $ownerId;
        $this->type = $type;
        $this->name = $name;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getOwnerId() { return $this->ownerId; }
    public function getType() { return $this->type; }
    public function getName() { return $this->name; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.business_catalog')]
#[ORM\Index(name: 'idx_business_catalog_business', columns: ['business_id'])]
#[ORM\Index(name: 'idx_business_catalog_category', columns: ['category'])]
class BusinessCatalogItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'business_id', type: 'string', length: 36)]
    private string $businessId;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'price_cents', type: 'integer')]
    private int $priceCents;

    #[ORM\Column(type: 'integer')]
    private int $stock = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct(string $id, string $businessId, string $name, int $priceCents)
    {
        $this->id = $id;
        $this->businessId = $businessId;
        $this->name = $name;
        $this->priceCents = $priceCents;
    }

    public function getId() { return $this->id; }
    public function getBusinessId() { return $this->businessId; }
    public function getCategory() { return $this->category; }
    public function getName() { return $this->name; }
    public function getPriceCents() { return $this->priceCents; }
    public function getStock() { return $this->stock; }
    public function getMetadata() { return $this->metadata; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.service_bookings')]
#[ORM\Index(name: 'idx_service_bookings_provider', columns: ['provider_id'])]
#[ORM\Index(name: 'idx_service_bookings_customer', columns: ['customer_id'])]
#[ORM\Index(name: 'idx_service_bookings_status', columns: ['status'])]
class ServiceBooking
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_CANCELLED = 'CANCELLED';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'provider_id', type: 'string', length: 36)]
    private string $providerId;

    #[ORM\Column(name: 'customer_id', type: 'string', length: 36)]
    private string $customerId;

    #[ORM\Column(name: 'service_type', type: 'string', length: 50)]
    private string $serviceType;

    #[ORM\Column(name: 'scheduled_at', type: 'datetime', nullable: true)]
    private ?\DateTime $scheduledAt = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $providerId, string $customerId, string $serviceType)
    {
        $this->id = $id;
        $this->providerId = $providerId;
        $this->customerId = $customerId;
        $this->serviceType = $serviceType;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getProviderId() { return $this->providerId; }
    public function getCustomerId() { return $this->customerId; }
    public function getServiceType() { return $this->serviceType; }
    public function getScheduledAt() { return $this->scheduledAt; }
    public function getStatus() { return $this->status; }
    public function getData() { return $this->data; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setStatus(string $status) { $this->status = $status; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.service_reviews')]
#[ORM\Index(name: 'idx_service_reviews_booking', columns: ['booking_id'])]
#[ORM\Index(name: 'idx_service_reviews_user', columns: ['user_id'])]
class ServiceReview
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'booking_id', type: 'string', length: 36)]
    private string $bookingId;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'integer')]
    private int $rating;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $bookingId, string $userId, int $rating)
    {
        $this->id = $id;
        $this->bookingId = $bookingId;
        $this->userId = $userId;
        $this->rating = $rating;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getBookingId() { return $this->bookingId; }
    public function getUserId() { return $this->userId; }
    public function getRating() { return $this->rating; }
    public function getComment() { return $this->comment; }
    public function getMetadata() { return $this->metadata; }
    public function getCreatedAt() { return $this->createdAt; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.conversations')]
#[ORM\Index(name: 'idx_conversations_creator', columns: ['created_by'])]
#[ORM\Index(name: 'idx_conversations_type', columns: ['type'])]
class Conversation
{
    public const TYPE_DIRECT = 'DIRECT';
    public const TYPE_GROUP = 'GROUP';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(name: 'created_by', type: 'string', length: 36)]
    private string $createdBy;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $type, string $createdBy)
    {
        $this->id = $id;
        $this->type = $type;
        $this->createdBy = $createdBy;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getType() { return $this->type; }
    public function getName() { return $this->name; }
    public function getCreatedBy() { return $this->createdBy; }
    public function getMetadata() { return $this->metadata; }
    public function getCreatedAt() { return $this->createdAt; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.conversation_members')]
#[ORM\Index(name: 'idx_conv_members_conv', columns: ['conversation_id'])]
#[ORM\Index(name: 'idx_conv_members_user', columns: ['user_id'])]
class ConversationMember
{
    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_MEMBER = 'MEMBER';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'conversation_id', type: 'string', length: 36)]
    private string $conversationId;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $role = self::ROLE_MEMBER;

    #[ORM\Column(name: 'joined_at', type: 'datetime')]
    private \DateTime $joinedAt;

    public function __construct(string $id, string $conversationId, string $userId)
    {
        $this->id = $id;
        $this->conversationId = $conversationId;
        $this->userId = $userId;
        $this->joinedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getConversationId() { return $this->conversationId; }
    public function getUserId() { return $this->userId; }
    public function getRole() { return $this->role; }
    public function getJoinedAt() { return $this->joinedAt; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.messages')]
#[ORM\Index(name: 'idx_messages_conversation', columns: ['conversation_id'])]
#[ORM\Index(name: 'idx_messages_sender', columns: ['sender_id'])]
#[ORM\Index(name: 'idx_messages_created', columns: ['created_at'])]
class Message
{
    public const TYPE_TEXT = 'TEXT';
    public const TYPE_IMAGE = 'IMAGE';
    public const TYPE_FILE = 'FILE';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'conversation_id', type: 'string', length: 36)]
    private string $conversationId;

    #[ORM\Column(name: 'sender_id', type: 'string', length: 36)]
    private string $senderId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type = self::TYPE_TEXT;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $attachments = null;

    #[ORM\Column(name: 'read_at', type: 'datetime', nullable: true)]
    private ?\DateTime $readAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $conversationId, string $senderId, string $content)
    {
        $this->id = $id;
        $this->conversationId = $conversationId;
        $this->senderId = $senderId;
        $this->content = $content;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getConversationId() { return $this->conversationId; }
    public function getSenderId() { return $this->senderId; }
    public function getType() { return $this->type; }
    public function getContent() { return $this->content; }
    public function getAttachments() { return $this->attachments; }
    public function getReadAt() { return $this->readAt; }
    public function getCreatedAt() { return $this->createdAt; }

    public function markAsRead() { $this->readAt = new \DateTime(); return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.social_posts')]
#[ORM\Index(name: 'idx_social_posts_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_social_posts_created', columns: ['created_at'])]
class SocialPost
{
    public const TYPE_POST = 'POST';
    public const TYPE_IMAGE = 'IMAGE';
    public const TYPE_VIDEO = 'VIDEO';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type = self::TYPE_POST;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $media = null;

    #[ORM\Column(name: 'likes_count', type: 'integer', options: ['default' => 0])]
    private int $likesCount = 0;

    #[ORM\Column(name: 'comments_count', type: 'integer', options: ['default' => 0])]
    private int $commentsCount = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId, string $content)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->content = $content;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getType() { return $this->type; }
    public function getContent() { return $this->content; }
    public function getMedia() { return $this->media; }
    public function getLikesCount() { return $this->likesCount; }
    public function getCommentsCount() { return $this->commentsCount; }
    public function getCreatedAt() { return $this->createdAt; }

    public function incrementLikes() { $this->likesCount++; return $this; }
    public function incrementComments() { $this->commentsCount++; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.social_interactions')]
#[ORM\Index(name: 'idx_social_interactions_post', columns: ['post_id'])]
#[ORM\Index(name: 'idx_social_interactions_user', columns: ['user_id'])]
class SocialInteraction
{
    public const TYPE_LIKE = 'LIKE';
    public const TYPE_COMMENT = 'COMMENT';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'post_id', type: 'string', length: 36)]
    private string $postId;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $postId, string $userId, string $type)
    {
        $this->id = $id;
        $this->postId = $postId;
        $this->userId = $userId;
        $this->type = $type;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getPostId() { return $this->postId; }
    public function getUserId() { return $this->userId; }
    public function getType() { return $this->type; }
    public function getContent() { return $this->content; }
    public function getCreatedAt() { return $this->createdAt; }
}