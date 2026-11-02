<?php
declare(strict_types=1);
namespace App\Government\Contracts\DTO;
use Symfony\Component\Validator\Constraints as Assert;
class CreateCitizenRequest {
    public function __construct(
        #[Assert\NotBlank(message:'Full name is required')] #[Assert\Length(min:2,max:100,minMessage:'Full name must be at least {{ limit }} characters',maxMessage:'Full name cannot be longer than {{ limit }} characters')] public string $fullName,
        #[Assert\NotBlank(message:'Identity number is required')] #[Assert\Length(min:9,max:20,minMessage:'Identity number must be at least {{ limit }} characters',maxMessage:'Identity number cannot be longer than {{ limit }} characters')] #[Assert\Regex(pattern:'/^[0-9]+$/', message:'Identity number must contain only digits')] public string $identityNumber,
        #[Assert\NotBlank(message:'Province is required')] #[Assert\Length(max:100)] public string $province,
        #[Assert\NotBlank(message:'District is required')] #[Assert\Length(max:100)] public string $district,
        #[Assert\NotBlank(message:'Ward is required')] #[Assert\Length(max:100)] public string $ward,
        #[Assert\NotBlank(message:'Street is required')] #[Assert\Length(max:255)] public string $street,
        #[Assert\Length(max:100)] public ?string $apartmentNumber = null,
        #[Assert\Email(message:'Invalid email address')] public ?string $email = null,
        #[Assert\Regex(pattern:'/^[0-9]{10,15}$/', message:'Invalid phone number format')] public ?string $phone = null,
        #[Assert\Date(message:'Invalid date of birth')] public ?string $dateOfBirth = null,
        #[Assert\Choice(choices:['male','female','other'], message:'Invalid gender')] public string $gender = 'other',
        #[Assert\Length(max:50)] public ?string $ethnicity = null,
        #[Assert\Length(max:50)] public ?string $nationality = 'Vietnam',
        #[Assert\Length(max:100)] public ?string $occupation = null,
        #[Assert\Choice(choices:['none','primary','secondary','high_school','college','university','postgraduate'], message:'Invalid education level')] public ?string $educationLevel = null,
        #[Assert\Choice(choices:['single','married','divorced','widowed'], message:'Invalid marital status')] public ?string $maritalStatus = null,
        #[Assert\Length(max:50)] public ?string $householdId = null
    ) {} }
