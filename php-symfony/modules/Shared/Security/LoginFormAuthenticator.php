<?php

declare(strict_types=1);

namespace App\Shared\Security;

use App\Shared\Service\JwtService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class LoginFormAuthenticator extends AbstractAuthenticator
{
    public function __construct(private JwtService $jwtService)
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST') && '/api/login' === $request->getPathInfo();
    }

    public function authenticate(Request $request): Passport
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Generate JWT token
        $user = $token->getUser();
        $payload = [
            'userId' => $user->getUserIdentifier(), // Use email as ID for now
            'email' => $user->getUserIdentifier(),
            'name' => 'User', // TODO: Get from profile
        ];
        $jwt = $this->jwtService->generateToken($payload);

        // Set httpOnly cookie with JWT
        $response = new Response(json_encode([
            'user' => [
                'id' => $user->getUserIdentifier(),
                'email' => $user->getUserIdentifier(),
                'name' => 'User',
            ],
            'message' => 'Login successful',
        ]));

        $response->headers->setCookie(
            new \Symfony\Component\HttpFoundation\Cookie(
                'auth_token',
                $jwt,
                time() + 86400 * 30, // 30 days
                '/',
                null,
                true, // secure
                true  // httpOnly
            )
        );

        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response(
            json_encode(['error' => 'Invalid credentials']),
            Response::HTTP_UNAUTHORIZED,
            ['Content-Type' => 'application/json']
        );
    }
}
