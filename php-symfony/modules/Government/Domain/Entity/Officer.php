<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use App\Government\Domain\ValueObject\OfficerId;
use App\Government\Domain\ValueObject\Department;
use App\Government\Domain\ValueObject\Position;

#[ORM\Entity]
#[ORM\Table(name: 'gov_officers')]
class Officer
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'full_name', type: 'string', length: 255)]
    private string $fullName;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(name: 'date_of_birth', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $dateOfBirth = null;

    #[ORM\Column(name: 'identity_number', type: 'string', length: 20, nullable: true)]
    private ?string $identityNumber = null;

    #[ORM\Embedded(class: Position::class)]
    private Position $position;

    #[ORM\Embedded(class: Department::class)]
    private Department $department;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct(
        OfficerId $id,
        string $fullName,
        Position $position,
        Department $department
    ) {
        $this->id = $id->getValue();
        $this->fullName = $fullName;
        $this->position = $position;
        $this->department = $department;
        $this->createdAt = new DateTimeImmutable();
        $this->isActive = true;
    }

    public function getId(): OfficerId
    {
        return new OfficerId($this->id);
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $fullName = trim($fullName);
        if (empty($fullName)) {
            throw new \InvalidArgumentException('Full name cannot be empty');
        }
        if (strlen($fullName) > 255) {
            throw new \InvalidArgumentException('Full name cannot exceed 255 characters');
        }
        $this->fullName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        if ($email !== null) {
            $email = trim($email);
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Invalid email format');
            }
            if (strlen($email) > 180) {
                throw new \InvalidArgumentException('Email cannot exceed 180 characters');
            }
        }
        $this->email = $email;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        if ($phone !== null) {
            $phone = trim($phone);
            if (!preg_match('/^[\d\s\+\-\(\)]{8,20}$/', $phone)) {
                throw new \InvalidArgumentException('Invalid phone number format');
            }
        }
        $this->phone = $phone;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getDateOfBirth(): ?DateTimeImmutable
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?DateTimeImmutable $dateOfBirth): void
    {
        if ($dateOfBirth !== null) {
            $now = new DateTimeImmutable();
            if ($dateOfBirth > $now) {
                throw new \InvalidArgumentException('Date of birth cannot be in the future');
            }
            $minDate = new DateTimeImmutable('1900-01-01');
            if ($dateOfBirth < $minDate) {
                throw new \InvalidArgumentException('Date of birth cannot be before 1900');
            }
        }
        $this->dateOfBirth = $dateOfBirth;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getIdentityNumber(): ?string
    {
        return $this->identityNumber;
    }

    public function setIdentityNumber(?string $identityNumber): void
    {
        if ($identityNumber !== null) {
            $identityNumber = trim($identityNumber);
            if (empty($identityNumber)) {
                throw new \InvalidArgumentException('Identity number cannot be empty');
            }
            if (!preg_match('/^\d{9}$|^\d{12}$/', $identityNumber)) {
                throw new \InvalidArgumentException('Identity number must be 9 digits (CMND) or 12 digits (CCCD)');
            }
        }
        $this->identityNumber = $identityNumber;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function setPosition(Position $position): void
    {
        $this->position = $position;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getDepartment(): Department
    {
        return $this->department;
    }

    public function setDepartment(Department $department): void
    {
        $this->department = $department;
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }
}
