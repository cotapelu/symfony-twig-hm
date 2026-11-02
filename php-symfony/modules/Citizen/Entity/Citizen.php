<?php

declare(strict_types=1);

namespace App\Citizen\Entity;

use App\Citizen\Domain\Entity\Citizen as DomainCitizen;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'citizens')]
class Citizen extends DomainCitizen
{
}
