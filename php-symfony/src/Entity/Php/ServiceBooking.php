<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.service_bookings')]
#[ORM\Index(name: 'idx_service_bookings_citizen_id', columns: ['citizen_id'])]
#[ORM\Index(name: 'idx_service_bookings_catalog_item_id', columns: ['catalog_item_id'])]
#[ORM\Index(name: 'idx_service_bookings_booking_time', columns: ['booking_time'])]
#[ORM\Index(name: 'idx_service_bookings_status', columns: ['status'])]
class ServiceBooking
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $citizenId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $catalogItemId;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $bookingTime;

    #[ORM\Column(type: 'integer')]
    private int $durationMinutes;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'PENDING';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(
        string $id,
        string $citizenId,
        string $catalogItemId,
        \DateTime $bookingTime,
        int $durationMinutes
    ) {
        $this->id = $id;
        $this->citizenId = $citizenId;
        $this->catalogItemId = $catalogItemId;
        $this->bookingTime = $bookingTime;
        $this->durationMinutes = $durationMinutes;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCitizenId()
    {
        return $this->citizenId;
    }

    public function setCitizenId(string $citizenId)
    {
        $this->citizenId = $citizenId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCatalogItemId()
    {
        return $this->catalogItemId;
    }

    public function setCatalogItemId(string $catalogItemId)
    {
        $this->catalogItemId = $catalogItemId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getBookingTime()
    {
        return $this->bookingTime;
    }

    public function setBookingTime(\DateTime $bookingTime)
    {
        $this->bookingTime = $bookingTime;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getDurationMinutes()
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(int $durationMinutes)
    {
        $this->durationMinutes = $durationMinutes;
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

    public function getNotes()
    {
        return $this->notes;
    }

    public function setNotes(?string $notes)
    {
        $this->notes = $notes;
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
}
