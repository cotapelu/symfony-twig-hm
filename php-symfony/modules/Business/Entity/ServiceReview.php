<?php

declare(strict_types=1);

namespace App\Business\Entity;

use App\Core\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'service_reviews')]
class ServiceReview
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: ServiceBooking::class)]
    #[ORM\JoinColumn(name: 'booking_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ServiceBooking $booking;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'integer')]
    private int $rating;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(ServiceBooking $booking, User $user, int $rating)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->booking = $booking;
        $this->user = $user;
        $this->rating = $rating;
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getBooking()
    {
        return $this->booking;
    }

    public function setBooking(ServiceBooking $booking)
    {
        $this->booking = $booking;
        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getRating()
    {
        return $this->rating;
    }

    public function setRating(int $rating)
    {
        $this->rating = $rating;
        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment(?string $comment)
    {
        $this->comment = $comment;
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'bookingId' => $this->booking->getId(),
            'userId' => $this->user->getId(),
            'rating' => $this->rating,
            'comment' => $this->comment,
            'metadata' => $this->metadata,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
