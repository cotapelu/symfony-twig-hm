<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'gov_administrative_procedures')]
class AdministrativeProcedure
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $code;

    #[ORM\Column(type: 'string', length: 50)]
    private string $category;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(name: 'required_documents', type: 'json')]
    private array $requiredDocuments;

    #[ORM\Column(name: 'processing_time_days', type: 'integer')]
    private int $processingTimeDays;

    #[ORM\Column(type: 'float')]
    private float $fee;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status; // active, inactive

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const CATEGORY_REGISTRATION = 'registration';
    public const CATEGORY_CERTIFICATE = 'certificate';
    public const CATEGORY_PERMIT = 'permit';
    public const CATEGORY_LICENSE = 'license';
    public const CATEGORY_OTHER = 'other';

    public function __construct(
        string $name,
        string $code,
        string $category,
        string $description,
        array $requiredDocuments,
        int $processingTimeDays,
        float $fee
    ) {
        $this->id = Uuid::uuid4()->toString();
        $this->name = $name;
        $this->code = $code;
        $this->category = $category;
        $this->description = $description;
        $this->requiredDocuments = $requiredDocuments;
        $this->processingTimeDays = $processingTimeDays;
        $this->fee = $fee;
        $this->status = self::STATUS_ACTIVE;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getRequiredDocuments(): array
    {
        return $this->requiredDocuments;
    }

    public function setRequiredDocuments(array $requiredDocuments): void
    {
        $this->requiredDocuments = $requiredDocuments;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getProcessingTimeDays(): int
    {
        return $this->processingTimeDays;
    }

    public function setProcessingTimeDays(int $processingTimeDays): void
    {
        $this->processingTimeDays = $processingTimeDays;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getFee(): float
    {
        return $this->fee;
    }

    public function setFee(float $fee): void
    {
        $this->fee = $fee;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->status = self::STATUS_INACTIVE;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'category' => $this->category,
            'description' => $this->description,
            'requiredDocuments' => $this->requiredDocuments,
            'processingTimeDays' => $this->processingTimeDays,
            'fee' => $this->fee,
            'status' => $this->status,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
