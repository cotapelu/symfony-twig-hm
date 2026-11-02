<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateCitizenRequest
{
    public function __construct(
        #[Assert\Length(
            min: 2,
            max: 100,
            minMessage: 'Full name must be at least {{ limit }} characters',
            maxMessage: 'Full name cannot be longer than {{ limit }} characters'
        )]
        public ?string $fullName = null,

        #[Assert\Email(message: 'Invalid email address')]
        public ?string $email = null,

        #[Assert\Regex(
            pattern: '/^[0-9]{10,15}$/',
            message: 'Invalid phone number format'
        )]
        public ?string $phone = null,

        #[Assert\Date(message: 'Invalid date of birth')]
        public ?string $dateOfBirth = null,

        #[Assert\Length(max: 100)]
        public ?string $occupation = null,

        #[Assert\Choice(
            choices: ['none', 'primary', 'secondary', 'high_school', 'college', 'university', 'postgraduate'],
            message: 'Invalid education level'
        )]
        public ?string $educationLevel = null,

        #[Assert\Choice(
            choices: ['single', 'married', 'divorced', 'widowed'],
            message: 'Invalid marital status'
        )]
        public ?string $maritalStatus = null,

        #[Assert\Length(max: 50)]
        public ?string $householdId = null
    ) {
    }
}
