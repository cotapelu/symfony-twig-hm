<?php

declare(strict_types=1);

namespace App\Shared\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

trait AuthenticationHelperTrait
{
    /**
     * Get current authenticated user ID or return 401 response.
     */
    protected function requireAuth(): string|JsonResponse
    {
        try {
            $currentUser = $this->getUser();
            if (!$currentUser) {
                return $this->json(['error' => 'Authentication required'], 401);
            }
            return $currentUser->getUserIdentifier();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
    }

    /**
     * Check if user is authenticated, return user ID or null.
     */
    protected function getCurrentUserId(): ?string
    {
        try {
            $currentUser = $this->getUser();
            if (!$currentUser) {
                return null;
            }
            return $currentUser->getUserIdentifier();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get current user object or throw exception.
     */
    protected function getRequiredUser(): object
    {
        try {
            $currentUser = $this->getUser();
            if (!$currentUser) {
                throw new \RuntimeException('Authentication required');
            }
            return $currentUser;
        } catch (\Exception $e) {
            throw new \RuntimeException('Authentication required');
        }
    }
}
