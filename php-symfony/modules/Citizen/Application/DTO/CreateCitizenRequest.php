<?php

declare(strict_types=1);

namespace App\Citizen\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateCitizenRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Assert\Regex(pattern: '/^[0-9]{12}$/', message: 'Identity number must be exactly 12 digits')]
    public string $identityNumber;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $fullName;

    #[Assert\Type('datetime')]
    #[Assert\LessThanOrEqual('today', message: 'Date of birth cannot be in the future')]
    public ?\DateTimeInterface $dateOfBirth = null;

    #[Assert\Choice(choices: ['male', 'female', 'other'])]
    public ?string $gender = null;

    #[Assert\Length(max: 20)]
    #[Assert\Regex(pattern: '/^(\+84|0)[0-9]{9,10}$/', message: 'Phone number must be a valid Vietnamese number')]
    public ?string $phoneNumber = null;

    #[Assert\Email]
    #[Assert\Length(max: 100)]
    public ?string $email = null;

    #[Assert\Length(max: 200)]
    public ?string $permanentAddress = null;

    #[Assert\Length(max: 200)]
    public ?string $currentAddress = null;

    #[Assert\Length(max: 20)]
    public ?string $ethnicity = null;

    #[Assert\Length(max: 20)]
    public ?string $religion = null;

    #[Assert\Length(max: 20)]
    public ?string $educationLevel = null;

    #[Assert\Length(max: 100)]
    public ?string $occupation = null;

    #[Assert\Length(max: 20)]
    public ?string $maritalStatus = null;

    #[Assert\Uuid]
    public ?string $householdId = null;

    #[Assert\Length(max: 50)]
    public ?string $relationshipToHouseholdHead = null;

    public bool $isHouseholdHead = false;

    #[Assert\Type('array')]
    public array $specialCategories = [];

    #[Assert\Length(max: 1000)]
    public ?string $notes = null;

    public ?string $userId = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        self::applyIdentityNumber($dto, $data);
        self::applyFullName($dto, $data);
        self::applyDateOfBirth($dto, $data);
        self::applyGender($dto, $data);
        self::applyPhoneNumber($dto, $data);
        self::applyEmail($dto, $data);
        self::applyPermanentAddress($dto, $data);
        self::applyCurrentAddress($dto, $data);
        self::applyEthnicity($dto, $data);
        self::applyReligion($dto, $data);
        self::applyEducationLevel($dto, $data);
        self::applyOccupation($dto, $data);
        self::applyMaritalStatus($dto, $data);
        self::applyHouseholdId($dto, $data);
        self::applyRelationshipToHouseholdHead($dto, $data);
        self::applyIsHouseholdHead($dto, $data);
        self::applySpecialCategories($dto, $data);
        self::applyNotes($dto, $data);
        self::applyUserId($dto, $data);
        return $dto;
    }

    private static function applyIdentityNumber(self $dto, array $data): void
    {
        $dto->identityNumber = $data['identityNumber'] ?? $data['identity_number'] ?? '';
    }

    private static function applyFullName(self $dto, array $data): void
    {
        $dto->fullName = $data['fullName'] ?? $data['full_name'] ?? '';
    }

    private static function applyDateOfBirth(self $dto, array $data): void
    {
        if (isset($data['dateOfBirth'])) {
            $dto->dateOfBirth = new \DateTimeImmutable($data['dateOfBirth']);
        } else {
            $dto->dateOfBirth = null;
        }
    }

    private static function applyGender(self $dto, array $data): void
    {
        $dto->gender = $data['gender'] ?? null;
    }

    private static function applyPhoneNumber(self $dto, array $data): void
    {
        $dto->phoneNumber = $data['phoneNumber'] ?? $data['phone_number'] ?? null;
    }

    private static function applyEmail(self $dto, array $data): void
    {
        $dto->email = $data['email'] ?? null;
    }

    private static function applyPermanentAddress(self $dto, array $data): void
    {
        $dto->permanentAddress = $data['permanentAddress'] ?? $data['permanent_address'] ?? null;
    }

    private static function applyCurrentAddress(self $dto, array $data): void
    {
        $dto->currentAddress = $data['currentAddress'] ?? $data['current_address'] ?? null;
    }

    private static function applyEthnicity(self $dto, array $data): void
    {
        $dto->ethnicity = $data['ethnicity'] ?? null;
    }

    private static function applyReligion(self $dto, array $data): void
    {
        $dto->religion = $data['religion'] ?? null;
    }

    private static function applyEducationLevel(self $dto, array $data): void
    {
        $dto->educationLevel = $data['educationLevel'] ?? $data['education_level'] ?? null;
    }

    private static function applyOccupation(self $dto, array $data): void
    {
        $dto->occupation = $data['occupation'] ?? null;
    }

    private static function applyMaritalStatus(self $dto, array $data): void
    {
        $dto->maritalStatus = $data['maritalStatus'] ?? $data['marital_status'] ?? null;
    }

    private static function applyHouseholdId(self $dto, array $data): void
    {
        $dto->householdId = $data['householdId'] ?? $data['household_id'] ?? null;
    }

    private static function applyRelationshipToHouseholdHead(self $dto, array $data): void
    {
        $dto->relationshipToHouseholdHead = $data['relationshipToHouseholdHead'] ?? $data['relationship_to_household_head'] ?? null;
    }

    private static function applyIsHouseholdHead(self $dto, array $data): void
    {
        $dto->isHouseholdHead = $data['isHouseholdHead'] ?? $data['is_household_head'] ?? false;
    }

    private static function applySpecialCategories(self $dto, array $data): void
    {
        $dto->specialCategories = $data['specialCategories'] ?? $data['special_categories'] ?? [];
    }

    private static function applyNotes(self $dto, array $data): void
    {
        $dto->notes = $data['notes'] ?? null;
    }

    private static function applyUserId(self $dto, array $data): void
    {
        $dto->userId = $data['userId'] ?? $data['user_id'] ?? null;
    }
}
