<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ramsey\Uuid\Uuid;

class CorrelationIdSubscriber implements EventSubscriberInterface
{
    private const HEADER_NAME = 'X-Request-ID';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 255],
            KernelEvents::RESPONSE => ['onKernelResponse', -255],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $correlationId = $request->headers->get(self::HEADER_NAME);
        if (!$correlationId) {
            $correlationId = Uuid::uuid4()->toString();
        }
        $request->attributes->set(self::HEADER_NAME, $correlationId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $correlationId = $request->attributes->get(self::HEADER_NAME);
        if ($correlationId) {
            $event->getResponse()->headers->set(self::HEADER_NAME, $correlationId);
        }
    }
}