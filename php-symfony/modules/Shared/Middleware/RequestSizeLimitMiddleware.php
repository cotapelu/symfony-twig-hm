<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class RequestSizeLimitMiddleware implements EventSubscriberInterface
{
    private const MAX_CONTENT_LENGTH = 10485760; // 10 MB

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $contentLength = $request->headers->get('Content-Length');
        if ($contentLength !== null && (int)$contentLength > self::MAX_CONTENT_LENGTH) {
            $event->setResponse(new \Symfony\Component\HttpFoundation\JsonResponse(
                ['error' => 'Request entity too large'],
                413
            ));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 255],
        ];
    }
}
