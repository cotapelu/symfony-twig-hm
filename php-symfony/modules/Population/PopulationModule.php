<?php

declare(strict_types=1);

namespace App\Population;

use App\Kernel\Module\BaseModule;
use App\Kernel\Module\ModuleInterface;

class PopulationModule extends BaseModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Population Management';
    }

    public function getPrefix(): string
    {
        return '/api/population';
    }

    public function getControllers(): array
    {
        return [
            Controller\PopulationController::class,
        ];
    }

    public function getServices(): array
    {
        return [
            Service\PopulationService::class,
        ];
    }
}
