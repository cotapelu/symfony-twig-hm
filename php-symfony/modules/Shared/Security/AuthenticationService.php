<?php

declare(strict_types=1);

namespace App\Shared\Security;

use App\Shared\Service\JwtService;
use Symfony\Component\HttpFoundation\Request;

class AuthenticationService
{
    public function __construct(
        private JwtService $jwtService,
    ) {
    }

    public function authenticateUser(Request $request): ?array
    {
        $token = $request->headers->get('Authorization');

        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return null;
        }

        $jwtToken = substr($token, 7);

        return $this->jwtService->getUserFromToken($jwtToken);
    }

    public function requireAuthentication(Request $request): array
    {
        $user = $this->authenticateUser($request);
        if (!$user) {
            throw new \Exception('Authentication required', 401);
        }

        return $user;
    }

    public function getCurrentUserId(Request $request): ?string
    {
        $user = $this->authenticateUser($request);

        return $user['id'] ?? null;
    }

    public function getCurrentUser(Request $request): ?array
    {
        return $this->authenticateUser($request);
    }
}
