<?php

declare(strict_types=1);

namespace App\Government\Entity;

use App\Repository\Government\AdministrativeProcedureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdministrativeProcedureRepository::class)]
#[ORM\Table(name: 'administrative_procedure')]
#[ORM\HasLifecycleCallbacks]
class AdministrativeProcedure
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private ?string $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $procedureCode = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $processingDays = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $requiredDocuments = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $fee = null;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProcedureCode()
    {
        return $this->procedureCode;
    }

    public function setProcedureCode(string $procedureCode)
    {
        $this->procedureCode = $procedureCode;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
        return $this;
    }

    public function getDepartment()
    {
        return $this->department;
    }

    public function setDepartment(?string $department)
    {
        $this->department = $department;
        return $this;
    }

    public function getProcessingDays()
    {
        return $this->processingDays;
    }

    public function setProcessingDays(?int $processingDays)
    {
        $this->processingDays = $processingDays;
        return $this;
    }

    public function getRequiredDocuments()
    {
        return $this->requiredDocuments;
    }

    public function setRequiredDocuments(?array $requiredDocuments)
    {
        $this->requiredDocuments = $requiredDocuments;
        return $this;
    }

    public function getFee()
    {
        return $this->fee;
    }

    public function setFee(?string $fee)
    {
        $this->fee = $fee;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp()
    {
        $this->updatedAt = new \DateTime();
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'procedureCode' => $this->procedureCode,
            'name' => $this->name,
            'description' => $this->description,
            'department' => $this->department,
            'processingDays' => $this->processingDays,
            'requiredDocuments' => $this->requiredDocuments,
            'fee' => $this->fee,
            'status' => $this->status,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
