<?php

declare(strict_types=1);

namespace App\Shared\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityHeadersListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        // HSTS (HTTP Strict Transport Security)
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy
        $csp = "default-src 'self'; ".
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; ".
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ".
               "img-src 'self' data: https: blob:; ".
               "font-src 'self' https://fonts.gstatic.com; ".
               "connect-src 'self' wss: https:; ".
               "media-src 'self' https: blob:; ".
               "object-src 'none'; ".
               "frame-src 'none'; ".
               "base-uri 'self'; ".
               "form-action 'self'; ".
               'upgrade-insecure-requests';

        $response->headers->set('Content-Security-Policy', $csp);
    }
}
