<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\Service\UserProfileService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use InvalidArgumentException;

#[Route('/api/profile')]
class UserProfileController extends BaseController
{
    private const MAX_DISPLAY_NAME_LENGTH = 100;
    private const MAX_BIO_LENGTH = 500;
    private const MAX_URL_LENGTH = 500;
    private const MAX_PHONE_LENGTH = 20;
    private const MAX_ADDRESS_LENGTH = 500;
    private const MAX_CITY_LENGTH = 100;
    private const MAX_COUNTRY_LENGTH = 100;

    public function __construct(
        private UserProfileService $userProfileService,
        private ?TokenStorageInterface $tokenStorage = null,
        ?AuthorizationCheckerInterface $authChecker = null
    ) {
        parent::__construct();
        $this->authChecker = $authChecker;
    }

    private function isAuthenticated(): bool
    {
        if ($this->tokenStorage === null || $this->authChecker === null) {
            return false;
        }
        return $this->tokenStorage->getToken() !== null 
            && $this->authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }

    private function getCurrentUserId(): ?int
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return null;
        }
        $user = $token->getUser();
        if (!$user || !method_exists($user, 'getId')) {
            return null;
        }
        return (int) $user->getId();
    }

    private function requireAuth(): JsonResponse|true
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        return true;
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['status' => 400, 'error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            error_log('UserProfileController error: ' . $e->getMessage());
            return $this->json(['status' => 500, 'error' => 'Internal server error'], 500);
        }
    }

    // ==================== Validation helpers ====================
    private function validateDisplayName(?string $value): ?string
    {
        if ($value !== null && strlen($value) > self::MAX_DISPLAY_NAME_LENGTH) {
            return 'Display name too long (max ' . self::MAX_DISPLAY_NAME_LENGTH . ' chars)';
        }
        return null;
    }

    private function validateBio(?string $value): ?string
    {
        if ($value !== null && strlen($value) > self::MAX_BIO_LENGTH) {
            return 'Bio too long (max ' . self::MAX_BIO_LENGTH . ' chars)';
        }
        return null;
    }

    private function validateAvatar(?string $value): ?string
    {
        if ($value !== null && strlen($value) > self::MAX_URL_LENGTH) {
            return 'Avatar URL too long';
        }
        return null;
    }

    private function validateEmail(?string $value): ?string
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email format';
        }
        return null;
    }

    private function validatePhone(?string $value): ?string
    {
        if ($value !== null && strlen($value) > self::MAX_PHONE_LENGTH) {
            return 'Phone number too long';
        }
        return null;
    }

    private function validateAddress(?string $value): ?string
    {
        if ($value !== null && strlen($value) > self::MAX_ADDRESS_LENGTH) {
            return 'Address too long';
        }
        return null;
    }

    private function validateCity(?string $value): ?string
    {
        if ($value !== null && strlen($value) > self::MAX_CITY_LENGTH) {
            return 'City name too long';
        }
        return null;
    }

    private function validateCountry(?string $value): ?string
    {
        if ($value !== null && strlen($value) > self::MAX_COUNTRY_LENGTH) {
            return 'Country name too long';
        }
        return null;
    }

    private function validateProfileInput(array $data): ?string
    {
        $validators = [
            'display_name' => 'validateDisplayName',
            'bio' => 'validateBio',
            'avatar' => 'validateAvatar',
            'email' => 'validateEmail',
            'phone' => 'validatePhone',
            'address' => 'validateAddress',
            'city' => 'validateCity',
 'country' => 'validateCountry',
        ];
        foreach ($validators as $field => $method) {
            $value = $data[$field] ?? null;
            $error = $this->$method($value);
            if ($error) return $error;
        }
        return null;
    }

    // ==================== Wrappers ====================
    #[Route('', methods: ['POST'])]
    public function createProfile(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createProfileAction($request));
    }

    #[Route('', methods: ['GET'])]
    public function getMyProfile(): JsonResponse
    {
        return $this->handle(fn() => $this->getMyProfileAction());
    }

    #[Route('', methods: ['PUT'])]
    public function updateMyProfile(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateMyProfileAction($request));
    }

    #[Route('', methods: ['DELETE'])]
    public function deleteMyProfile(): JsonResponse
    {
        return $this->handle(fn() => $this->deleteMyProfileAction());
    }

    #[Route('/{userId}', methods: ['GET'])]
    public function getUserProfile(int $userId): JsonResponse
    {
        return $this->handle(fn() => $this->getUserProfileAction($userId));
    }

    #[Route('/{userId}', methods: ['PUT'])]
    public function updateUserProfile(Request $request, int $userId): JsonResponse
    {
        return $this->handle(fn() => $this->updateUserProfileAction($request, $userId));
    }

    #[Route('/{userId}', methods: ['DELETE'])]
    public function deleteUserProfile(int $userId): JsonResponse
    {
        return $this->handle(fn() => $this->deleteUserProfileAction($userId));
    }

    // ==================== Actions ====================
    private function createProfileAction(Request $request): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $userId = $this->getCurrentUserId();
        if ($userId === null) return $this->json(['error' => 'User not found'], 401);
        $userIdStr = (string) $userId;
        $existing = $this->userProfileService->getUserProfile($userIdStr);
        if ($existing) return $this->json(['error' => 'Profile already exists'], 400);
        $profile = $this->userProfileService->createProfile($userIdStr);
        return $this->json(['status' => 201, 'data' => $profile], 201);
    }

    private function getMyProfileAction(): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $userId = $this->getCurrentUserId();
        if ($userId === null) return $this->json(['error' => 'User not found'], 401);
        $userIdStr = (string) $userId;
        $profile = $this->userProfileService->getUserProfile($userIdStr);
        if (!$profile) return $this->json(['error' => 'Profile not found'], 404);
        return $this->json(['status' => 200, 'data' => $profile]);
    }

    private function updateMyProfileAction(Request $request): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $userId = $this->getCurrentUserId();
        if ($userId === null) return $this->json(['error' => 'User not found'], 401);
        $userIdStr = (string) $userId;
        $data = $this->decodeJsonOr400($request);
        if ($data instanceof JsonResponse) return $data;
        $error = $this->validateProfileInput($data);
        if ($error) return $this->json(['error' => $error], 400);
        $profile = $this->userProfileService->updateProfile($userIdStr, $data);
        return $this->json(['status' => 200, 'data' => $profile]);
    }

    private function deleteMyProfileAction(): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $userId = $this->getCurrentUserId();
        if ($userId === null) return $this->json(['error' => 'User not found'], 401);
        $userIdStr = (string) $userId;
        $deleted = $this->userProfileService->deleteProfile($userIdStr);
        if (!$deleted) return $this->json(['error' => 'Profile not found'], 404);
        return $this->json(['status' => 204, 'data' => null], 204);
    }

    private function getUserProfileAction(int $userId): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId === null) return $this->json(['error' => 'User not found'], 401);
        if ($currentUserId !== $userId) return $this->json(['error' => 'Access denied'], 403);
        $userIdStr = (string) $userId;
        $profile = $this->userProfileService->getUserProfile($userIdStr);
        if (!$profile) return $this->json(['error' => 'Profile not found'], 404);
        return $this->json(['status' => 200, 'data' => $profile]);
    }

    private function updateUserProfileAction(Request $request, int $userId): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId === null) return $this->json(['error' => 'User not found'], 401);
        if ($currentUserId !== $userId) return $this->json(['error' => 'Access denied'], 403);
        $userIdStr = (string) $userId;
        $data = $this->decodeJsonOr400($request);
        if ($data instanceof JsonResponse) return $data;
        $error = $this->validateProfileInput($data);
        if ($error) return $this->json(['error' => $error], 400);
        $profile = $this->userProfileService->updateProfile($userIdStr, $data);
        return $this->json(['status' => 200, 'data' => $profile]);
    }

    private function deleteUserProfileAction(int $userId): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId === null) return $this->json(['error' => 'User not found'], 401);
        if ($currentUserId !== $userId) return $this->json(['error' => 'Access denied'], 403);
        $userIdStr = (string) $userId;
        $deleted = $this->userProfileService->deleteProfile($userIdStr);
        if (!$deleted) return $this->json(['error' => 'Profile not found'], 404);
        return $this->json(['status' => 204, 'data' => null], 204);
    }
}