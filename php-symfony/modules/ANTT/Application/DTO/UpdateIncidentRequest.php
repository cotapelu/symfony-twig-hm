<?php

declare(strict_types=1);

namespace App\ANTT\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateIncidentRequest
{
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\Length(max: 2000)]
    public ?string $description = null;

    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['crime', 'traffic', 'noise', 'other', 'environment', 'public_health'])]
    public ?string $incidentType = null;

    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['low', 'medium', 'high', 'critical'])]
    public ?string $severity = null;

    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['reported', 'assigned', 'in_progress', 'resolved', 'closed', 'escalated'])]
    public ?string $status = null;

    #[Assert\Length(max: 1000)]
    public ?string $resolutionSummary = null;
}
