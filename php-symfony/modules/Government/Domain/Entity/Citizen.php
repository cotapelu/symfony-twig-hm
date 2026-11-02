<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use App\Government\Domain\ValueObject\CitizenId;
use App\Government\Domain\ValueObject\Address;

#[ORM\Entity]
#[ORM\Table(name: 'gov_citizens')]
#[ORM\Index(name: 'idx_gov_citizen_identity', columns: ['identity_number'])]
#[ORM\Index(name: 'idx_gov_citizen_full_name', columns: ['full_name'])]
class Citizen
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

    #[ORM\Column(name: 'identity_number', type: 'string', length: 20, unique: true)]
    private ?string $identityNumber = null; // CCCD/CMND

    #[ORM\Column(name: 'identity_issue_date', type: 'string', length: 50, nullable: true)]
    private ?string $identityIssueDate = null;

    #[ORM\Column(name: 'identity_issue_place', type: 'string', length: 255, nullable: true)]
    private ?string $identityIssuePlace = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $ethnicity = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $religion = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $nationality = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $gender;

    #[ORM\Column(name: 'blood_type', type: 'string', length: 5, nullable: true)]
    private ?string $bloodType = null;

    #[ORM\Column(name: 'death_date', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deathDate = null;

    #[ORM\Column(name: 'is_deceased', type: 'boolean', options: ['default' => false])]
    private bool $isDeceased = false;

    #[ORM\Embedded(class: Address::class, columnPrefix: 'reg_')]
    private Address $registeredAddress;

    #[ORM\Embedded(class: Address::class, columnPrefix: 'temp_')]
    private ?Address $temporaryAddress = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $occupation = null;

    #[ORM\Column(name: 'education_level', type: 'string', length: 100, nullable: true)]
    private ?string $educationLevel = null;

    #[ORM\Column(name: 'marital_status', type: 'string', length: 50, nullable: true)]
    private ?string $maritalStatus = null; // single, married, divorced, widowed

    #[ORM\Column(name: 'household_id', type: 'string', length: 36, nullable: true)]
    private ?string $householdId = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct(
        CitizenId $id,
        string $fullName,
        string $identityNumber,
        Address $registeredAddress
    ) {
        $this->id = $id->getValue();
        $this->fullName = $fullName;
        $this->identityNumber = $identityNumber;
        $this->registeredAddress = $registeredAddress;
        $this->createdAt = new DateTimeImmutable();
        $this->isActive = true;
    }

    // Getters
    public function getId(): CitizenId
    {
        return new CitizenId($this->id);
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
            if (!empty($phone) && !preg_match('/^[\d\s\+\-\(\)]{8,20}$/', $phone)) {
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

    public function setIdentityNumber(string $identityNumber): void
    {
        $identityNumber = trim($identityNumber);
        if (empty($identityNumber)) {
            throw new \InvalidArgumentException('Identity number cannot be empty');
        }
        // Vietnamese CCCD (12 digits) or CMND (9 digits) format
        if (!preg_match('/^\d{9}$|^\d{12}$/', $identityNumber)) {
            throw new \InvalidArgumentException('Identity number must be 9 digits (CMND) or 12 digits (CCCD)');
        }
        $this->identityNumber = $identityNumber;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getIdentityIssueDate(): ?string
    {
        return $this->identityIssueDate;
    }

    public function setIdentityIssueDate(?string $identityIssueDate): void
    {
        if ($identityIssueDate !== null) {
            $identityIssueDate = trim($identityIssueDate);
            if (!empty($identityIssueDate)) {
                try {
                    $date = new DateTimeImmutable($identityIssueDate);
                    $now = new DateTimeImmutable();
                    if ($date > $now) {
                        throw new \InvalidArgumentException('Identity issue date cannot be in the future');
                    }
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException('Invalid identity issue date format');
                }
            }
        }
        $this->identityIssueDate = $identityIssueDate;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getIdentityIssuePlace(): ?string
    {
        return $this->identityIssuePlace;
    }

    public function setIdentityIssuePlace(?string $identityIssuePlace): void
    {
        if ($identityIssuePlace !== null) {
            $identityIssuePlace = trim($identityIssuePlace);
            if (strlen($identityIssuePlace) > 255) {
                throw new \InvalidArgumentException('Identity issue place cannot exceed 255 characters');
            }
            $identityIssuePlace = htmlspecialchars($identityIssuePlace, ENT_QUOTES, 'UTF-8');
        }
        $this->identityIssuePlace = $identityIssuePlace;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getEthnicity(): ?string
    {
        return $this->ethnicity;
    }

    public function setEthnicity(?string $ethnicity): void
    {
        if ($ethnicity !== null) {
            $ethnicity = trim($ethnicity);
            if (strlen($ethnicity) > 100) {
                throw new \InvalidArgumentException('Ethnicity cannot exceed 100 characters');
            }
            $ethnicity = htmlspecialchars($ethnicity, ENT_QUOTES, 'UTF-8');
        }
        $this->ethnicity = $ethnicity;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getReligion(): ?string
    {
        return $this->religion;
    }

    public function setReligion(?string $religion): void
    {
        if ($religion !== null) {
            $religion = trim($religion);
            if (strlen($religion) > 100) {
                throw new \InvalidArgumentException('Religion cannot exceed 100 characters');
            }
            $religion = htmlspecialchars($religion, ENT_QUOTES, 'UTF-8');
        }
        $this->religion = $religion;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    public function setNationality(?string $nationality): void
    {
        if ($nationality !== null) {
            $nationality = trim($nationality);
            if (strlen($nationality) > 100) {
                throw new \InvalidArgumentException('Nationality cannot exceed 100 characters');
            }
            $nationality = htmlspecialchars($nationality, ENT_QUOTES, 'UTF-8');
        }
        $this->nationality = $nationality;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function setGender(string $gender): void
    {
        $validGenders = ['male', 'female', 'other'];
        if (!in_array($gender, $validGenders, true)) {
            throw new \InvalidArgumentException('Invalid gender. Valid values: ' . implode(', ', $validGenders));
        }
        $this->gender = $gender;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getBloodType(): ?string
    {
        return $this->bloodType;
    }

    public function setBloodType(?string $bloodType): void
    {
        if ($bloodType !== null) {
            $bloodType = trim($bloodType);
            $validTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
            if (!in_array($bloodType, $validTypes, true)) {
                throw new \InvalidArgumentException('Invalid blood type. Valid values: ' . implode(', ', $validTypes));
            }
        }
        $this->bloodType = $bloodType;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getDeathDate(): ?DateTimeImmutable
    {
        return $this->deathDate;
    }

    public function setDeathDate(?DateTimeImmutable $deathDate): void
    {
        if ($deathDate !== null) {
            $now = new DateTimeImmutable();
            if ($deathDate > $now) {
                throw new \InvalidArgumentException('Death date cannot be in the future');
            }
            if ($this->dateOfBirth !== null && $deathDate < $this->dateOfBirth) {
                throw new \InvalidArgumentException('Death date cannot be before birth date');
            }
        }
        $this->deathDate = $deathDate;
        $this->isDeceased = $deathDate !== null;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isDeceased(): bool
    {
        return $this->isDeceased;
    }

    public function getRegisteredAddress(): Address
    {
        return $this->registeredAddress;
    }

    public function setRegisteredAddress(Address $registeredAddress): void
    {
        $this->registeredAddress = $registeredAddress;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getTemporaryAddress(): ?Address
    {
        return $this->temporaryAddress;
    }

    public function setTemporaryAddress(?Address $temporaryAddress): void
    {
        $this->temporaryAddress = $temporaryAddress;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getOccupation(): ?string
    {
        return $this->occupation;
    }

    public function setOccupation(?string $occupation): void
    {
        if ($occupation !== null) {
            $occupation = trim($occupation);
            if (strlen($occupation) > 100) {
                throw new \InvalidArgumentException('Occupation cannot exceed 100 characters');
            }
            $occupation = htmlspecialchars($occupation, ENT_QUOTES, 'UTF-8');
        }
        $this->occupation = $occupation;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getEducationLevel(): ?string
    {
        return $this->educationLevel;
    }

    public function setEducationLevel(?string $educationLevel): void
    {
        if ($educationLevel !== null) {
            $educationLevel = trim($educationLevel);
            if (strlen($educationLevel) > 100) {
                throw new \InvalidArgumentException('Education level cannot exceed 100 characters');
            }
            $educationLevel = htmlspecialchars($educationLevel, ENT_QUOTES, 'UTF-8');
        }
        $this->educationLevel = $educationLevel;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getMaritalStatus(): ?string
    {
        return $this->maritalStatus;
    }

    public function setMaritalStatus(?string $maritalStatus): void
    {
        if ($maritalStatus !== null) {
            $maritalStatus = trim($maritalStatus);
            $validStatuses = ['single', 'married', 'divorced', 'widowed'];
            if (!in_array($maritalStatus, $validStatuses, true)) {
                throw new \InvalidArgumentException('Invalid marital status. Valid values: ' . implode(', ', $validStatuses));
            }
        }
        $this->maritalStatus = $maritalStatus;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getHouseholdId(): ?string
    {
        return $this->householdId;
    }

    public function setHouseholdId(?string $householdId): void
    {
        if ($householdId !== null) {
            $householdId = trim($householdId);
            if (strlen($householdId) > 36) {
                throw new \InvalidArgumentException('Household ID cannot exceed 36 characters');
            }
            if (!preg_match('/^[\w\-]+$/', $householdId)) {
                throw new \InvalidArgumentException('Household ID contains invalid characters');
            }
        }
        $this->householdId = $householdId;
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

    public function getAge(): ?int
    {
        if ($this->dateOfBirth === null) {
            return null;
        }

        $now = new DateTimeImmutable();
        $interval = $now->diff($this->dateOfBirth);
        return $interval->y;
    }

     public function toArray(): array
     {
         return [
             'id' => $this->id,
             'fullName' => $this->fullName,
             'email' => $this->email,
             'phone' => $this->phone,
             'dateOfBirth' => $this->dateOfBirth?->format('Y-m-d'),
             'identityNumber' => $this->identityNumber,
             'gender' => $this->gender,
             'registeredAddress' => $this->registeredAddress->toArray(),
             'isActive' => $this->isActive,
             'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
         ];
     }
}
