<?php

declare(strict_types=1);

namespace Platform\IAM;

use Platform\IAM\AuthServiceInterface;
use Platform\IAM\RBAC\RoleBasedAccessControl;
use Platform\IAM\Exception\IAMException;
use Platform\IAM\Model\User;

class IAMService
{
    public function __construct(
        private AuthServiceInterface $authService,
        private RoleBasedAccessControl $rbac
    ) {}

    public function authenticate(string $username, string $password): ?array
    {
        $this->validateAuthenticationInput($username, $password);
        try {
            $user = $this->authService->authenticate($username, $password);
            if (!$user) {
                throw $this->createAuthenticationException(
                    sprintf('[ERROR] IAMService.authenticate - Invalid credentials for user: %s', $this->sanitizeForLog($username)),
                    401
                );
            }
            return $this->buildAuthenticationResponse($user);
        } catch (\Exception $e) {
            throw new IAMException('[ERROR] IAMService.authenticate - System error - Contact administrator', 500, $e);
        }
    }

    public function authorize(string $token, string $permission, ?string $resource = null): array
    {
        $this->validateAuthorizationInput($token, $permission);
        try {
            return $this->performAuthorizationLogic($token, $permission, $resource);
        } catch (IAMException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new IAMException('[ERROR] IAMService.authorize - System error - Contact administrator', 500, $e);
        }
    }

    public function getUserId(string $token): ?string
    {
        $this->validateTokenInput($token);
        try {
            $user = $this->authService->validateToken($token);
            return $user ? $user->id : null;
        } catch (\Exception $e) {
            throw new IAMException('[ERROR] IAMService.getUserId - Invalid token', 401, $e);
        }
    }

    public function validateCredentials(string $credentials): bool
    {
        $this->validateCredentialsInput($credentials);
        if (strlen($credentials) < 8) {
            throw new IAMException('[ERROR] IAMService.validateCredentials - Password too short (min 8 chars)', 400);
        }
        return true;
    }

    private function sanitizeForLog(string $input): string
    {
        $sanitized = preg_replace('/[<>"\']/', '', $input);
        return substr($sanitized, 0, 50);
    }

    private function validateAuthenticationInput(string $username, string $password): void
    {
        if (empty($username) || empty($password)) {
            throw new IAMException('[ERROR] IAMService.authenticate - Empty credentials', 400);
        }
    }

    private function validateAuthorizationInput(string $token, string $permission): void
    {
        if (empty($token)) throw new IAMException('[ERROR] IAMService.authorize - Empty token', 401);
        if (empty($permission)) throw new IAMException('[ERROR] IAMService.authorize - Empty permission', 400);
    }

    private function validateTokenInput(string $token): void
    {
        if (empty($token)) throw new IAMException('[ERROR] IAMService.getUserId - Empty token', 401);
    }

    private function validateCredentialsInput(string $credentials): void
    {
        if (empty($credentials)) throw new IAMException('[ERROR] IAMService.validateCredentials - Empty credentials', 400);
    }

    private function buildAuthenticationResponse(User $user): array
    {
        $token = $this->authService->generateToken($user);
        return [
            'user' => $user->toArray(),
            'token' => $token,
            'roles' => $this->authService->getUserRoles($user),
            'permissions' => $this->authService->getUserPermissions($user)
        ];
    }

    private function buildAuthorizationResponse(User $user): array
    {
        return [
            'authorized' => true,
            'user' => $user->toArray(),
            'roles' => $this->authService->getUserRoles($user),
            'permissions' => $this->authService->getUserPermissions($user)
        ];
    }

    private function performAuthorizationLogic(string $token, string $permission, ?string $resource): array
    {
        $user = $this->authService->validateToken($token);
        if (!$user) {
            throw new IAMException('[ERROR] IAMService.authorize - Invalid or expired token - Please log in again', 401);
        }
        if (!$this->rbac->checkPermission($user, $permission, $resource)) {
            throw new IAMException(
                sprintf('[ERROR] IAMService.authorize - Insufficient permissions for user: %s', $user->username),
                403
            );
        }
        return $this->buildAuthorizationResponse($user);
    }

    private function createAuthenticationException(string $message, int $code): IAMException
    {
        return new IAMException($message, $code);
    }
}
