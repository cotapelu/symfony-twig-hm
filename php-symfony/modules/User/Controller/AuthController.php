<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\Service\AuthService;
use App\User\Entity\User;
use App\Shared\Infrastructure\RateLimitService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Shared\Service\RequestHelper;
use App\Shared\Service\ResponseHelper;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use InvalidArgumentException;

#[Route('/api/auth')]
class AuthController extends BaseController
{
    private AuthService $authService;
    private RateLimitService $rateLimitService;
    private ?CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(
        AuthService $authService,
        RateLimitService $rateLimitService,
        private \App\Shared\Service\JwtService $jwtService,
        ?CsrfTokenManagerInterface $csrfTokenManager = null
    ) {
        parent::__construct();
        $this->authService = $authService;
        $this->rateLimitService = $rateLimitService;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['status' => 400, 'error' => $e->getMessage()], 400);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['status' => 403, 'error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            error_log('AuthController error: ' . $e->getMessage());
            return $this->json(['status' => 500, 'error' => 'Internal server error'], 500);
        }
    }

    private function validateCsrfToken(Request $request, string $tokenId): bool
    {
        if ($this->getParameter('kernel.environment') === 'test') return true;
        if ($this->csrfTokenManager === null) return true;
        $tokenValue = $request->headers->get('X-CSRF-Token', $request->request->get('_csrf_token', ''));
        if (empty($tokenValue)) return false;
        $token = new CsrfToken($tokenId, $tokenValue);
        return $this->csrfTokenManager->isTokenValid($token);
    }

    private function buildJwtPayload(User $user): array
    {
        return [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => method_exists($this->authService, 'getUserFullName') ? $this->authService->getUserFullName($user) : $user->getEmail(),
            'roles' => $this->authService->getUserRoles($user),
            'permissions' => method_exists($this->authService, 'getUserPermissions') ? $this->authService->getUserPermissions($user) : $this->authService->getUserRoles($user),
        ];
    }

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        if (!$this->validateCsrfToken($request, 'auth_register')) {
            return $this->json(['error' => 'Invalid CSRF token'], 419);
        }
        $identifier = $request->getClientIp();
        if (!$this->rateLimitService->checkRateLimit($identifier, 'register')) {
            return $this->json(['error' => 'Too many requests. Please try again later.'], 429);
        }
        $response = $this->handle(fn() => $this->registerAction($request));
        $this->rateLimitService->logRequest($identifier, 'register', 'POST');
        return $response;
    }

    private function registerAction(Request $request): JsonResponse
    {
        $data = RequestHelper::getJsonData($request);
        RequestHelper::validateRequired($data, ['name', 'email', 'password']);
        $user = $this->authService->register($data);
        return $this->json(['user' => $this->authService->getUserProfile($user), 'message' => 'Registration successful'], 201);
    }

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $identifier = $request->getClientIp();
        if (!$this->rateLimitService->checkRateLimit($identifier, 'login')) {
            return $this->json(['error' => 'Too many login attempts. Please try again later.'], 429);
        }
        $response = $this->handle(fn() => $this->loginAction($request));
        $this->rateLimitService->logRequest($identifier, 'login', 'POST');
        return $response;
    }

    private function loginAction(Request $request): JsonResponse
    {
        $data = RequestHelper::getJsonData($request);
        RequestHelper::validateRequired($data, ['email', 'password']);
        $user = $this->authService->authenticate($data['email'], $data['password']);
        if (!$user) {
            return $this->json(['error' => 'Invalid credentials'], 401);
        }
        $payload = $this->buildJwtPayload($user);
        $token = $this->jwtService->generateToken($payload);
        return $this->json([
            'access_token' => $token,
            'expires_in' => 86400,
            'token_type' => 'Bearer',
            'user' => $this->authService->getUserProfile($user),
            'message' => 'Login successful',
        ]);
    }

    #[Route('/social/{provider}', name: 'auth_social_login', methods: ['POST'])]
    public function socialLogin(string $provider, Request $request): JsonResponse
    {
        $identifier = $request->getClientIp();
        if (!$this->rateLimitService->checkRateLimit($identifier, 'social_login')) {
            return $this->json(['error' => 'Too many social login attempts. Please try again later.'], 429);
        }
        $response = $this->handle(fn() => $this->socialLoginAction($provider, $request));
        $this->rateLimitService->logRequest($identifier, 'social_login', 'POST');
        return $response;
    }

    private function socialLoginAction(string $provider, Request $request): JsonResponse
    {
        $data = RequestHelper::getJsonData($request);
        $user = $this->authService->socialLogin($provider, $data);
        return $this->json(['user' => $this->authService->getUserProfile($user), 'message' => 'Social login successful']);
    }

    #[Route('/profile', name: 'auth_profile', methods: ['GET'])]
    public function getProfile(#[CurrentUser] ?User $user): JsonResponse
    {
        return $this->handle(fn() => $this->getProfileAction($user));
    }

    private function getProfileAction(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ResponseHelper::authError(), 401);
        }
        return $this->json(['user' => $this->authService->getUserProfile($user)]);
    }

    #[Route('/profile', name: 'auth_update_profile', methods: ['PUT'])]
    public function updateProfile(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        return $this->handle(fn() => $this->updateProfileAction($request, $user));
    }

    private function updateProfileAction(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ResponseHelper::authError(), 401);
        }
        $data = RequestHelper::getJsonData($request);
        $updatedUser = $this->authService->updateProfile($user, $data);
        return $this->json(['user' => $this->authService->getUserProfile($updatedUser), 'message' => 'Profile updated successfully']);
    }

    #[Route('/change-password', name: 'auth_change_password', methods: ['PUT'])]
    public function changePassword(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $identifier = $request->getClientIp();
        if (!$this->rateLimitService->checkRateLimit($identifier, 'change_password')) {
            return $this->json(['error' => 'Too many password change attempts. Please try again later.'], 429);
        }
        $response = $this->handle(fn() => $this->changePasswordAction($request, $user));
        $this->rateLimitService->logRequest($identifier, 'change_password', 'PUT');
        return $response;
    }

    private function changePasswordAction(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ResponseHelper::authError(), 401);
        }
        $data = RequestHelper::getJsonData($request);
        RequestHelper::validateRequired($data, ['currentPassword', 'newPassword']);
        $success = $this->authService->changePassword($user, $data['currentPassword'], $data['newPassword']);
        if (!$success) {
            return $this->json(['error' => 'Current password is incorrect'], 400);
        }
        return $this->json(['message' => 'Password changed successfully']);
    }

    #[Route('/forgot-password', name: 'auth_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $identifier = $request->getClientIp();
        if (!$this->rateLimitService->checkRateLimit($identifier, 'forgot_password')) {
            return $this->json(['error' => 'Too many password reset requests. Please try again later.'], 429);
        }
        $response = $this->handle(fn() => $this->forgotPasswordAction($request));
        $this->rateLimitService->logRequest($identifier, 'forgot_password', 'POST');
        return $response;
    }

    private function forgotPasswordAction(Request $request): JsonResponse
    {
        $data = RequestHelper::getJsonData($request);
        if (!isset($data['email'])) {
            throw new InvalidArgumentException('Email is required');
        }
        $this->authService->requestPasswordReset($data['email']);
        return $this->json(['message' => 'If the email exists, a password reset link has been sent']);
    }

    #[Route('/reset-password', name: 'auth_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $identifier = $request->getClientIp();
        if (!$this->rateLimitService->checkRateLimit($identifier, 'reset_password')) {
            return $this->json(['error' => 'Too many password reset attempts. Please try again later.'], 429);
        }
        $response = $this->handle(fn() => $this->resetPasswordAction($request));
        $this->rateLimitService->logRequest($identifier, 'reset_password', 'POST');
        return $response;
    }

    private function resetPasswordAction(Request $request): JsonResponse
    {
        $data = RequestHelper::getJsonData($request);
        if (!isset($data['token']) || !isset($data['password'])) {
            throw new InvalidArgumentException('Token and new password are required');
        }
        $success = $this->authService->resetPassword($data['token'], $data['password']);
        if (!$success) {
            return $this->json(['error' => 'Invalid or expired token'], 400);
        }
        return $this->json(['message' => 'Password reset successfully']);
    }

    #[Route('/verify-email', name: 'auth_verify_email', methods: ['POST'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $identifier = $request->getClientIp();
        if (!$this->rateLimitService->checkRateLimit($identifier, 'verify_email')) {
            return $this->json(['error' => 'Too many verification attempts. Please try again later.'], 429);
        }
        $response = $this->handle(fn() => $this->verifyEmailAction($request));
        $this->rateLimitService->logRequest($identifier, 'verify_email', 'POST');
        return $response;
    }

    private function verifyEmailAction(Request $request): JsonResponse
    {
        $data = RequestHelper::getJsonData($request);
        if (!isset($data['token'])) {
            throw new InvalidArgumentException('Verification token is required');
        }
        $success = $this->authService->verifyEmail($data['token']);
        if (!$success) {
            return $this->json(['error' => 'Invalid or expired token'], 400);
        }
        return $this->json(['message' => 'Email verified successfully']);
    }

    #[Route('/logout', name: 'auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->handle(fn() => $this->logoutAction());
    }

    private function logoutAction(): JsonResponse
    {
        return $this->json(['message' => 'Logged out successfully']);
    }

    #[Route('/me', name: 'auth_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        return $this->handle(fn() => $this->meAction($user));
    }

    private function meAction(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ResponseHelper::authError(), 401);
        }
        return $this->json(['user' => $this->authService->getUserProfile($user)]);
    }
}