<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.service_reviews')]
#[ORM\Index(name: 'idx_service_reviews_business_id', columns: ['business_id'])]
#[ORM\Index(name: 'idx_service_reviews_customer_id', columns: ['customer_id'])]
#[ORM\Index(name: 'idx_service_reviews_booking_id', columns: ['booking_id'])]
#[ORM\Index(name: 'idx_service_reviews_rating', columns: ['rating'])]
class ServiceReview
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $businessId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $customerId;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $bookingId = null;

    #[ORM\Column(type: 'integer')]
    private int $rating;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'PUBLISHED';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $businessId, string $customerId, int $rating)
    {
        $this->id = $id;
        $this->businessId = $businessId;
        $this->customerId = $customerId;
        $this->rating = $rating;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getBusinessId()
    {
        return $this->businessId;
    }

    public function setBusinessId(string $businessId)
    {
        $this->businessId = $businessId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCustomerId()
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId)
    {
        $this->customerId = $customerId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getBookingId()
    {
        return $this->bookingId;
    }

    public function setBookingId(?string $bookingId)
    {
        $this->bookingId = $bookingId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getRating()
    {
        return $this->rating;
    }

    public function setRating(int $rating)
    {
        $this->rating = $rating;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment(?string $comment)
    {
        $this->comment = $comment;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function isPublished()
    {
        return $this->status === 'PUBLISHED';
    }

    public function isHidden()
    {
        return $this->status === 'HIDDEN';
    }

    public function isDeleted()
    {
        return $this->status === 'DELETED';
    }

    public function isPositive()
    {
        return $this->rating >= 4;
    }

    public function isNeutral()
    {
        return $this->rating === 3;
    }

    public function isNegative()
    {
        return $this->rating <= 2;
    }

    public function hasBooking()
    {
        return $this->bookingId !== null;
    }

    public function hasComment()
    {
        return $this->comment !== null && trim($this->comment) !== '';
    }
}
