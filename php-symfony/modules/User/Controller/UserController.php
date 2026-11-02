<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\Shared\Service\JwtService;
use App\User\Service\UserProfileService;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Shared\Controller\BaseController;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;

class UserController extends BaseController
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JwtService $jwtService,
        private UserRegistrationService $registrationService,
        private EntityManagerInterface $entityManager,
        private UserProfileService $profileService,
    ) {}

    public function logout(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new \DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'context'=>$context]));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode(['timestamp'=>(new \DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metric, 'tags'=>$tags, 'value'=>1]));
    }

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);
        return $user->getUserIdentifier();
    }

    private function validateLoginData(array $data): array
    {
        $errors = [];
        if (!isset($data['email']) || !isset($data['password'])) $errors[] = 'Email and password required';
        elseif (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
        return $errors;
    }

    private function validateRegisterData(array $data): array
    {
        $errors = [];
        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) $errors[] = 'All fields required';
        elseif (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
        elseif (isset($data['password']) && strlen($data['password']) < 8) $errors[] = 'Password must be at least 8 characters long';
        return $errors;
    }

    private function buildRegisterResponse(User $user, string $token): array
    {
        return [
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getDisplayName(),
                'email' => $user->getEmail(),
                'bio' => $user->getBio(),
                'avatar' => $user->getAvatarUrl(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d'),
            ],
            'token' => $token,
        ];
    }

    private function handle(callable $action): Response
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("UserController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['error' => 'Internal server error'], 500);
    }

    // ==================== Endpoints ====================

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): Response
    {
        return $this->handle(fn() => $this->loginAction($request));
    }

    private function loginAction(Request $request): Response
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;

        $errors = $this->validateLoginData($data);
        if (!empty($errors)) return $this->json(['error' => $errors[0]], 400);

        $email = $data['email']; $password = $data['password'];
        $user = $this->userRepository->findByEmail($email);
        if (!$user) return $this->json(['error' => 'Invalid credentials'], 401);
        if (!$this->passwordHasher->isPasswordValid($user, $password)) return $this->json(['error' => 'Invalid credentials'], 401);

        $this->entityManager->createQuery('UPDATE App\User\Entity\User u SET u.lastLoginAt = :now WHERE u.id = :id')
            ->setParameter('now', new \DateTime())->setParameter('id', $user->getId())->execute();

        $tokenPayload = ['userId'=>$user->getId(),'email'=>$user->getEmail(),'name'=>$user->getDisplayName()];
        $token = $this->jwtService->generateToken($tokenPayload);

        return $this->json([
            'user' => ['id'=>$user->getId(),'name'=>$user->getDisplayName(),'email'=>$user->getEmail(),'bio'=>$user->getBio(),'avatar'=>$user->getAvatarUrl(),'created_at'=>$user->getCreatedAt()->format('Y-m-d')],
            'token' => $token
        ]);
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        return $this->handle(fn() => $this->registerAction($request));
    }

    private function registerAction(Request $request): Response
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;

        $errors = $this->validateRegisterData($data);
        if (!empty($errors)) return $this->json(['error' => $errors[0]], 400);

        try {
            $user = $this->registrationService->registerWithEmail(
                email: $data['email'],
                password: $data['password'],
                displayName: $data['name']
            );
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $tokenPayload = ['userId'=>$user->getId(),'email'=>$user->getEmail(),'name'=>$user->getDisplayName()];
        $token = $this->jwtService->generateToken($tokenPayload);

        return $this->json($this->buildRegisterResponse($user, $token), Response::HTTP_CREATED);
    }

    #[Route('/api/user/device-token', name: 'api_user_device_token', methods: ['POST'])]
    public function registerDeviceToken(Request $request): JsonResponse
    {
        // Minimal stub: in a full implementation, this would save the device token to the user.
        return $this->json(['success' => true], 201);
    }

    #[Route('/api/users/me', name: 'api_users_me', methods: ['GET'])]
    #[Route('/api/user/profile', name: 'api_user_profile', methods: ['GET'])]
    public function getCurrentUser(Request $request): Response
    {
        return $this->handle(fn() => $this->getCurrentUserAction($request));
    }

    private function getCurrentUserAction(Request $request): Response
    {
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = $userIdOr401;

        $user = $this->userRepository->find($userId);
        if (!$user) return $this->json(['error' => 'User not found'], 404);

        return $this->json([
            'id' => $user->getId(),
            'name' => $user->getDisplayName(),
            'email' => $user->getEmail(),
            'bio' => $user->getBio(),
            'avatar' => $user->getAvatarUrl(),
            'created_at' => $user->getCreatedAt()->format('Y-m-d'),
            'last_login_at' => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/api/users/{id}', name: 'api_users_get', methods: ['GET'])]
    public function getUserById(int $id, Request $request): Response
    {
        return $this->handle(fn() => $this->getUserAction($id));
    }

    private function getUserAction(int $id): Response
    {
        if ($id <= 0) throw new InvalidArgumentException('User ID must be positive');
        $user = $this->userRepository->find((string)$id);
        if (!$user) return $this->json(['error' => 'User not found'], 404);
        return $this->json([
            'id' => $user->getId(),
            'name' => $user->getDisplayName(),
            'email' => $user->getEmail(),
            'bio' => $user->getBio(),
            'avatar' => $user->getAvatarUrl(),
            'created_at' => $user->getCreatedAt()->format('Y-m-d'),
        ]);
    }

    #[Route('/api/users/{id}/profile', name: 'api_users_profile', methods: ['PUT'])]
    public function updateProfile(int $id, Request $request): Response
    {
        return $this->handle(fn() => $this->updateProfileAction($id, $request));
    }

    private function updateProfileAction(int $id, Request $request): Response
    {
        if ($id <= 0) throw new InvalidArgumentException('User ID must be positive');
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $authUserId = (int)$userIdOr401;

        $user = $this->userRepository->find((string)$id);
        if (!$user) return $this->json(['error' => 'User not found'], 404);
        if ($id !== $authUserId) return $this->json(['error' => 'Access denied'], 403);

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;

        $updatedUser = $this->profileService->updateProfile((string)$id, $data);
        $this->logAction('profile_updated', ['user_id'=>$id]); $this->incrementMetric('profile_updated');

        return $this->json([
            'id' => $updatedUser->getId(),
            'name' => $updatedUser->getDisplayName(),
            'email' => $updatedUser->getEmail(),
            'bio' => $updatedUser->getBio(),
            'avatar' => $updatedUser->getAvatarUrl(),
        ]);
    }
}
