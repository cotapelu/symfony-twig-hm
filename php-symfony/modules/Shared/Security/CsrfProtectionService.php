<?php

declare(strict_types=1);

namespace App\Shared\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * CSRF protection service for API endpoints.
 * Validates CSRF tokens for state-changing operations.
 */
class CsrfProtectionService
{
    use TargetPathTrait;

    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    /**
     * Validate CSRF token from request.
     */
    public function validateToken(Request $request, string $tokenId = 'default'): bool
    {
        // Skip CSRF validation for GET requests
        if ($request->isMethod('GET')) {
            return true;
        }

        $token = $request->headers->get('X-CSRF-Token')
            ?? $request->request->get('_token')
            ?? $request->request->get('csrf_token')
            ?? $request->query->get('_token');

        if (null === $token) {
            return false;
        }

        $csrfToken = new CsrfToken($tokenId, $token);

        return $this->csrfTokenManager->isTokenValid($csrfToken);
    }

    /**
     * Get CSRF token for a given ID.
     */
    public function getToken(string $tokenId = 'default'): string
    {
        return $this->csrfTokenManager->getToken($tokenId)->getValue();
    }

    /**
     * Refresh CSRF token (use after successful form submission).
     */
    public function refreshToken(string $tokenId = 'default'): string
    {
        return $this->csrfTokenManager->refreshToken($tokenId)->getValue();
    }
}
