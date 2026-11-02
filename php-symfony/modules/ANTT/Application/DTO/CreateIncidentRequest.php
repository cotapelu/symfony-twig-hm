<?php

declare(strict_types=1);

namespace App\ANTT\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateIncidentRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $title;

    #[Assert\Length(max: 2000)]
    public ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['crime', 'traffic', 'noise', 'other', 'environment', 'public_health'])]
    public string $incidentType;

    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['low', 'medium', 'high', 'critical'])]
    public ?string $severity = 'medium';

    #[Assert\Length(max: 20)]
    public ?string $wardCode = null;

    #[Assert\Length(max: 500)]
    public ?string $locationAddress = null;

    #[Assert\Range(min: -90, max: 90)]
    public ?float $latitude = null;

    #[Assert\Range(min: -180, max: 180)]
    public ?float $longitude = null;

    #[Assert\Length(max: 100)]
    public ?string $reporterName = null;

    #[Assert\Length(max: 20)]
    #[Assert\Regex(pattern: '/^(\+84|0)[0-9]{9,10}$/', message: 'Phone number must be a valid Vietnamese number')]
    public ?string $reporterPhone = null;

    public bool $reporterAnonymous = false;
}
