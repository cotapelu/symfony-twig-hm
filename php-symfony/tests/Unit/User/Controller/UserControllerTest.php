<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Controller;

use PHPUnit\Framework\TestCase;
use App\User\Controller\UserController;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\UserRegistrationService;
use App\User\Service\UserProfileService;
use App\Shared\Service\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\User\Entity\User;

class UserControllerTest extends TestCase
{
    private UserRepositoryInterface $userRepo;
    private UserPasswordHasherInterface $passwordHasher;
    private JwtService $jwtService;
    private UserRegistrationService $registrationService;
    private UserProfileService $profileService;
    private EntityManagerInterface $entityManager;
    private UserController $controller;

    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->jwtService = $this->createMock(JwtService::class);
        $this->registrationService = $this->createMock(UserRegistrationService::class);
        $this->profileService = $this->createMock(UserProfileService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->controller = new UserController(
            $this->userRepo,
            $this->passwordHasher,
            $this->jwtService,
            $this->registrationService,
            $this->entityManager,
            $this->profileService
        );

        $this->setupContainer();
    }

    private function setupContainer(): void
    {
        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->set('serializer', new \Symfony\Component\Serializer\Serializer([], [new \Symfony\Component\Serializer\Encoder\JsonEncoder()]));
        $this->controller->setContainer($container);
    }

    private function createUser(array $override = []): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($override['id'] ?? '1');
        $user->method('getEmail')->willReturn($override['email'] ?? 'test@example.com');
        $user->method('getDisplayName')->willReturn($override['name'] ?? 'Test User');
        $user->method('getBio')->willReturn($override['bio'] ?? null);
        $user->method('getAvatarUrl')->willReturn($override['avatar'] ?? null);
        $user->method('getCreatedAt')->willReturn($override['created_at'] ?? new \DateTimeImmutable());
        $user->method('getLastLoginAt')->willReturn($override['last_login_at'] ?? null);
        $user->method('getRoles')->willReturn(['ROLE_USER']);
        $user->method('getUserIdentifier')->willReturn((string)($override['id'] ?? '1'));
        return $user;
    }

    private function jsonRequest(array $data = []): Request
    {
        return Request::create('/api/test', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    // ==================== Login Validation ====================

    public function testLogin_InvalidJson(): void
    {
        $request = Request::create('/api/login', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->login($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testLogin_MissingFields(): void
    {
        $request = $this->jsonRequest(['email' => 'test@example.com']);
        $response = $this->controller->login($request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('required', $data['error']);
    }

    public function testLogin_InvalidEmailFormat(): void
    {
        $request = $this->jsonRequest(['email' => 'invalid', 'password' => 'pass']);
        $response = $this->controller->login($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid email format', $response->getContent());
    }

    public function testLogin_UserNotFound(): void
    {
        $this->userRepo->method('findByEmail')->with('test@example.com')->willReturn(null);
        $request = $this->jsonRequest(['email' => 'test@example.com', 'password' => 'pass']);
        $response = $this->controller->login($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testLogin_WrongPassword(): void
    {
        $user = $this->createUser();
        $this->userRepo->method('findByEmail')->with('test@example.com')->willReturn($user);
        $this->passwordHasher->method('isPasswordValid')->with($user, 'wrong')->willReturn(false);
        $request = $this->jsonRequest(['email' => 'test@example.com', 'password' => 'wrong']);
        $response = $this->controller->login($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    // ==================== Register Validation ====================

    public function testRegister_InvalidJson(): void
    {
        $request = Request::create('/api/register', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->register($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRegister_MissingFields(): void
    {
        $request = $this->jsonRequest(['email' => 'new@example.com']);
        $response = $this->controller->register($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRegister_WeakPassword(): void
    {
        $request = $this->jsonRequest(['email' => 'new@example.com', 'password' => 'short', 'name' => 'New']);
        $response = $this->controller->register($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('at least 8', $response->getContent());
    }

    // ==================== Get Current User ====================

    public function testGetCurrentUser_Unauthenticated(): void
    {
        $this->controller->setTestUser(null);
        $request = new Request();
        $response = $this->controller->getCurrentUser($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    // ==================== Get User By ID ====================

    public function testGetUserById_InvalidId(): void
    {
        $request = new Request();
        $response = $this->controller->getUserById(0, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== Update Profile ====================

    public function testUpdateProfile_Unauthenticated(): void
    {
        $this->controller->setTestUser(null);
        $request = $this->jsonRequest(['bio' => 'New']);
        $response = $this->controller->updateProfile(1, $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUpdateProfile_AccessDenied(): void
    {
        $authUser = $this->createUser(['id' => '1']);
        $targetUser = $this->createUser(['id' => '2']);
        $this->userRepo->method('find')->with(2)->willReturn($targetUser);
        $this->controller->setTestUser($authUser);
        $request = $this->jsonRequest(['bio' => 'New']);
        $response = $this->controller->updateProfile(2, $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testUpdateProfile_NotFound(): void
    {
        $this->userRepo->method('find')->with(999)->willReturn(null);
        $this->controller->setTestUser($this->createUser());
        $request = $this->jsonRequest(['bio' => 'New']);
        $response = $this->controller->updateProfile(999, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateProfile_InvalidId(): void
    {
        $request = new Request();
        $response = $this->controller->updateProfile(0, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateProfile_Success(): void
    {
        $authUser = $this->createUser(['id' => 1]);
        $originalUser = $this->createUser([
            'id' => 1,
            'bio' => 'Old bio',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'avatar' => null,
        ]);
        $updatedUser = $this->createUser([
            'id' => 1,
            'bio' => 'New bio',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'avatar' => null,
        ]);

        $this->userRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($originalUser);

        $this->profileService->expects($this->once())
            ->method('updateProfile')
            ->with('1', $this->arrayHasKey('bio'))
            ->willReturn($updatedUser);

        // No direct entityManager flush in this method

        $this->controller->setTestUser($authUser);
        $request = $this->jsonRequest(['bio' => 'New bio']);
        $response = $this->controller->updateProfile(1, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('New bio', $data['bio']);
    }
}
