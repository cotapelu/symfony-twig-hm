<?php

declare(strict_types=1);

namespace App\Tests\Unit\Optimized;

use PHPUnit\Framework\TestCase;

// Load the file containing all entity classes (non-PSR-4)
require_once __DIR__.'/../../../src/Entity/Optimized/BusinessSocial.php';

use App\Core\Entity\Business;
use App\Core\Entity\BusinessCatalogItem;
use App\Core\Entity\ServiceBooking;
use App\Core\Entity\ServiceReview;
use App\Core\Entity\Conversation;
use App\Core\Entity\ConversationMember;
use App\Core\Entity\Message;
use App\Core\Entity\SocialPost;
use App\Core\Entity\SocialInteraction;

class BusinessSocialTest extends TestCase
{
    public function testBusiness(): void
    {
        $id = 'biz-'.uniqid();
        $ownerId = 'owner-'.uniqid();
        $type = Business::TYPE_RETAIL;
        $name = 'Test Business';

        $biz = new Business($id, $ownerId, $type, $name);

        $this->assertSame($id, $biz->getId());
        $this->assertSame($ownerId, $biz->getOwnerId());
        $this->assertSame($type, $biz->getType());
        $this->assertSame($name, $biz->getName());
        $this->assertSame(Business::STATUS_ACTIVE, $biz->getStatus());
        $this->assertInstanceOf(\DateTime::class, $biz->getCreatedAt());
    }

    public function testBusinessCatalogItem(): void
    {
        $id = 'item-'.uniqid();
        $businessId = 'biz-'.uniqid();
        $name = 'Catalog Item';
        $priceCents = 1999;

        $item = new BusinessCatalogItem($id, $businessId, $name, $priceCents);

        $this->assertSame($id, $item->getId());
        $this->assertSame($businessId, $item->getBusinessId());
        $this->assertSame($name, $item->getName());
        $this->assertSame($priceCents, $item->getPriceCents());
        $this->assertSame(0, $item->getStock());
        $this->assertNull($item->getCategory());
        $this->assertNull($item->getMetadata());
    }

    public function testServiceBooking(): void
    {
        $id = 'book-'.uniqid();
        $providerId = 'prov-'.uniqid();
        $customerId = 'cust-'.uniqid();
        $serviceType = 'CLEANING';

        $booking = new ServiceBooking($id, $providerId, $customerId, $serviceType);

        $this->assertSame($id, $booking->getId());
        $this->assertSame($providerId, $booking->getProviderId());
        $this->assertSame($customerId, $booking->getCustomerId());
        $this->assertSame($serviceType, $booking->getServiceType());
        $this->assertNull($booking->getScheduledAt());
        $this->assertSame(ServiceBooking::STATUS_PENDING, $booking->getStatus());
        $this->assertNull($booking->getData());
        $this->assertInstanceOf(\DateTime::class, $booking->getCreatedAt());
    }

    public function testServiceBookingSetStatus(): void
    {
        $booking = new ServiceBooking('id', 'p', 'c', 'TYPE');
        $booking->setStatus(ServiceBooking::STATUS_CONFIRMED);
        $this->assertSame(ServiceBooking::STATUS_CONFIRMED, $booking->getStatus());
        $this->assertSame($booking, $booking->setStatus(ServiceBooking::STATUS_COMPLETED)); // fluent
    }

    public function testServiceReview(): void
    {
        $id = 'rev-'.uniqid();
        $bookingId = 'book-'.uniqid();
        $userId = 'user-'.uniqid();
        $rating = 5;

        $review = new ServiceReview($id, $bookingId, $userId, $rating);

        $this->assertSame($id, $review->getId());
        $this->assertSame($bookingId, $review->getBookingId());
        $this->assertSame($userId, $review->getUserId());
        $this->assertSame($rating, $review->getRating());
        $this->assertNull($review->getComment());
        $this->assertNull($review->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $review->getCreatedAt());
    }

    public function testConversation(): void
    {
        $id = 'conv-'.uniqid();
        $type = Conversation::TYPE_GROUP;
        $createdBy = 'user-'.uniqid();

        $conv = new Conversation($id, $type, $createdBy);

        $this->assertSame($id, $conv->getId());
        $this->assertSame($type, $conv->getType());
        $this->assertNull($conv->getName());
        $this->assertSame($createdBy, $conv->getCreatedBy());
        $this->assertNull($conv->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $conv->getCreatedAt());
    }

    public function testConversationMember(): void
    {
        $id = 'cm-'.uniqid();
        $conversationId = 'conv-'.uniqid();
        $userId = 'user-'.uniqid();

        $member = new ConversationMember($id, $conversationId, $userId);

        $this->assertSame($id, $member->getId());
        $this->assertSame($conversationId, $member->getConversationId());
        $this->assertSame($userId, $member->getUserId());
        $this->assertSame(ConversationMember::ROLE_MEMBER, $member->getRole());
        $this->assertInstanceOf(\DateTime::class, $member->getJoinedAt());
    }

    public function testMessage(): void
    {
        $id = 'msg-'.uniqid();
        $conversationId = 'conv-'.uniqid();
        $senderId = 'user-'.uniqid();
        $content = 'Hello world';

        $msg = new Message($id, $conversationId, $senderId, $content);

        $this->assertSame($id, $msg->getId());
        $this->assertSame($conversationId, $msg->getConversationId());
        $this->assertSame($senderId, $msg->getSenderId());
        $this->assertSame(Message::TYPE_TEXT, $msg->getType());
        $this->assertSame($content, $msg->getContent());
        $this->assertNull($msg->getAttachments());
        $this->assertNull($msg->getReadAt());
        $this->assertInstanceOf(\DateTime::class, $msg->getCreatedAt());
    }

    public function testMessageMarkAsRead(): void
    {
        $msg = new Message('id', 'conv', 'sender', 'text');
        $this->assertNull($msg->getReadAt());
        $result = $msg->markAsRead();
        $this->assertInstanceOf(\DateTime::class, $msg->getReadAt());
        $this->assertSame($msg, $result); // fluent
    }

    public function testSocialPost(): void
    {
        $id = 'post-'.uniqid();
        $userId = 'user-'.uniqid();
        $content = 'My post content';

        $post = new SocialPost($id, $userId, $content);

        $this->assertSame($id, $post->getId());
        $this->assertSame($userId, $post->getUserId());
        $this->assertSame(SocialPost::TYPE_POST, $post->getType());
        $this->assertSame($content, $post->getContent());
        $this->assertNull($post->getMedia());
        $this->assertSame(0, $post->getLikesCount());
        $this->assertSame(0, $post->getCommentsCount());
        $this->assertInstanceOf(\DateTime::class, $post->getCreatedAt());
    }

    public function testSocialPostIncrementLikes(): void
    {
        $post = new SocialPost('id', 'uid', 'content');
        $post->incrementLikes();
        $this->assertSame(1, $post->getLikesCount());
        $post->incrementLikes();
        $this->assertSame(2, $post->getLikesCount());
    }

    public function testSocialPostIncrementComments(): void
    {
        $post = new SocialPost('id', 'uid', 'content');
        $post->incrementComments();
        $this->assertSame(1, $post->getCommentsCount());
    }

    public function testSocialInteraction(): void
    {
        $id = 'inter-'.uniqid();
        $postId = 'post-'.uniqid();
        $userId = 'user-'.uniqid();
        $type = SocialInteraction::TYPE_LIKE;

        $inter = new SocialInteraction($id, $postId, $userId, $type);

        $this->assertSame($id, $inter->getId());
        $this->assertSame($postId, $inter->getPostId());
        $this->assertSame($userId, $inter->getUserId());
        $this->assertSame($type, $inter->getType());
        $this->assertNull($inter->getContent());
        $this->assertInstanceOf(\DateTime::class, $inter->getCreatedAt());
    }
}
