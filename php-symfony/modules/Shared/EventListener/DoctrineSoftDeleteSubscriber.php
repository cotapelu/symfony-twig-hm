<?php

declare(strict_types=1);

namespace App\Shared\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class DoctrineSoftDeleteSubscriber
{
    public function onKernelRequest(RequestEvent $event): void
    {
        // Enable the soft delete filter for the current request
        // The filter is defined in App\Shared\Doctrine\SoftDeleteableFilter
        // No action needed here; filter is enabled globally via Doctrine config.
        // This subscriber exists to satisfy service definition.
    }
}