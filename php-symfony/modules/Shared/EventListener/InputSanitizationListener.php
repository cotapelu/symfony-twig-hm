<?php

declare(strict_types=1);

namespace App\Shared\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class InputSanitizationListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Sanitize GET parameters
        $this->sanitizeArray($request->query);

        // Sanitize POST parameters
        $this->sanitizeArray($request->request);
    }

    private function sanitizeArray(\Symfony\Component\HttpFoundation\ParameterBag $bag): void
    {
        $data = $bag->all();

        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                // Remove null bytes
                $value = str_replace("\0", '', $value);

                // Trim whitespace
                $value = trim($value);

                // Basic XSS prevention - remove script tags and their content
                $value = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $value);

                // Prevent SQL injection patterns - remove entire string if it contains SQL keywords
                if (preg_match('/\b(union|select|insert|delete|update|drop|create|alter)\b/i', $value)) {
                    $value = '';
                }
            }
        });

        // Replace the bag contents
        $bag->replace($data);
    }
}
