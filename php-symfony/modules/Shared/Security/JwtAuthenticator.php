<?php

declare(strict_types=1);

namespace App\Shared\Security;

use App\Shared\Service\JwtService;
use App\User\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private JwtService $jwtService,
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function supports(Request $request): bool
    {
        // Only support API routes
        $path = $request->server->get('PATH_INFO', '/');
        if (!str_starts_with($path, '/api/')) {
            return false;
        }

        // Skip login and register
        $excluded = ['/api/auth/login', '/api/auth/register', '/api/auth/forgot-password', '/api/auth/reset-password'];
        if (in_array($path, $excluded, true)) {
            return false;
        }

        // Check for Bearer token
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return true;
        }

        return false;
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new AuthenticationException('No Bearer token found');
        }

        $token = substr($authHeader, 7);
        $payload = $this->jwtService->validateToken($token);
        if (!$payload) {
            throw new AuthenticationException('Invalid or expired token');
        }

        $userId = $payload['userId'] ?? null;
        if (!$userId) {
            throw new AuthenticationException('Token missing user ID');
        }

        $user = $this->userRepository->find($userId);
        if ($user === null) {
            throw new AuthenticationException('User not found');
        }

        return new SelfValidatingPassport(new UserBadge($userId, function (string $userId) use ($user) {
            return $user;
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?\Symfony\Component\HttpFoundation\Response
    {
        // Continue request (return null means continue)
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?\Symfony\Component\HttpFoundation\Response
    {
        return new \Symfony\Component\HttpFoundation\JsonResponse(
            ['error' => 'Authentication failed: '.$exception->getMessageKey()],
            401
        );
    }
}
