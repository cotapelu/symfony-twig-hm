<?php

declare(strict_types=1);

namespace App\Shared\EventListener;

use App\Shared\Service\JwtService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(event: 'kernel.request', priority: 10)]
class JwtAuthenticationListener
{
    public function __construct(
        private JwtService $jwtService,
        private array $excludedPaths = [
            '/api/auth/login',
            '/api/auth/register',
            '/api/auth/forgot-password',
            '/api/auth/reset-password',
            '/api/user/profile',
            '/',
            // Actual public endpoints
            '/api/login',
            '/api/register',
            '/register',
            '/login',
            '/forgot-password',
            '/reset-password',
        ],
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        file_put_contents('/tmp/jwt_listener.log', "JwtListener: path=$path\n", FILE_APPEND);

        // Skip excluded paths
        if (in_array($path, $this->excludedPaths, true)) {
            file_put_contents('/tmp/jwt_listener.log', "  -> excluded\n", FILE_APPEND);
            return;
        }

        // Public education endpoints (GET list/details) – allow without token
        if (str_starts_with($path, '/api/education')) {
            file_put_contents('/tmp/jwt_listener.log', "  -> education, skip\n", FILE_APPEND);
            return;
        }

        // Skip non-API paths
        if (!str_starts_with($path, '/api/')) {
            file_put_contents('/tmp/jwt_listener.log', "  -> not /api/, skip\n", FILE_APPEND);
            return;
        }

        $authHeader = $request->headers->get('Authorization');
        file_put_contents('/tmp/jwt_listener.log', "  -> authHeader: " . ($authHeader ?? 'null') . "\n", FILE_APPEND);
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            file_put_contents('/tmp/jwt_listener.log', "  -> missing or not Bearer, set 401\n", FILE_APPEND);
            $event->setResponse(new JsonResponse(['error' => 'Authorization header missing'], 401));

            return;
        }

        $parts = explode(' ', $authHeader, 2);
        $token = $parts[1] ?? '';
        $user = $this->jwtService->getUserFromToken($token);
        file_put_contents('/tmp/jwt_listener.log', "  -> user from token: " . json_encode($user) . "\n", FILE_APPEND);

        if (!$user) {
            file_put_contents('/tmp/jwt_listener.log', "  -> no user, set 401\n", FILE_APPEND);
            $event->setResponse(new JsonResponse(['error' => 'Invalid token'], 401));

            return;
        }

        // Store user in request attributes
        $request->attributes->set('user', $user);
        file_put_contents('/tmp/jwt_listener.log', "  -> user set in attributes\n", FILE_APPEND);
    }
}
