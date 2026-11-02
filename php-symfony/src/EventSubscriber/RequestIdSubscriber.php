<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Uid\Uuid;

#[AsEventListener]
class RequestIdSubscriber
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        // Generate or extract X-Request-ID
        $requestId = $request->headers->get('X-Request-ID');
        if (!$requestId) {
            $requestId = Uuid::v4()->toRfc4122();
        }
        // Set as request attribute so controllers can use it for logging
        $request->attributes->set('X-Request-ID', $requestId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        // Get request ID from request attributes (set in onKernelRequest)
        $requestId = $request->attributes->get('X-Request-ID');
        if ($requestId) {
            $response->headers->set('X-Request-ID', $requestId);
        }
    }
}
