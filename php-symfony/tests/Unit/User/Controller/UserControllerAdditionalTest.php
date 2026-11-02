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

class UserControllerAdditionalTest extends TestCase
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

    // ==================== Login Success ====================

    public function testLogin_Success(): void
    {
        $user = $this->createUser([
            'id' => '42',
            'email' => 'user@example.com',
            'name' => 'User Name',
        ]);
        $this->userRepo->method('findByEmail')->with('user@example.com')->willReturn($user);
        $this->passwordHasher->method('isPasswordValid')->with($user, 'password123')->willReturn(true);

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('execute')->willReturn(1);
        $this->entityManager->expects($this->once())
            ->method('createQuery')
            ->willReturn($query);

        $this->jwtService->method('generateToken')->with([
            'userId' => '42',
            'email' => 'user@example.com',
            'name' => 'User Name'
        ])->willReturn('jwt-token-123');

        $request = $this->jsonRequest(['email' => 'user@example.com', 'password' => 'password123']);
        $response = $this->controller->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('token', $data);
        $this->assertSame('jwt-token-123', $data['token']);
    }

    // ==================== Register Success ====================

    public function testRegister_Success(): void
    {
        $user = $this->createUser([
            'id' => '99',
            'email' => 'new@example.com',
            'name' => 'New User',
        ]);
        $this->registrationService->method('registerWithEmail')
            ->with('new@example.com', 'strongpass', 'New User')
            ->willReturn($user);

        $this->jwtService->method('generateToken')->with([
            'userId' => '99',
            'email' => 'new@example.com',
            'name' => 'New User'
        ])->willReturn('new-token');

        $request = $this->jsonRequest([
            'email' => 'new@example.com',
            'password' => 'strongpass',
            'name' => 'New User'
        ]);
        $response = $this->controller->register($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('token', $data);
        $this->assertSame('new-token', $data['token']);
    }

    // ==================== Get Current User Success ====================

    public function testGetCurrentUser_Success(): void
    {
        $authUser = $this->createUser([
            'id' => '7',
            'name' => 'Auth User',
            'last_login_at' => new \DateTimeImmutable(),
        ]);
        $this->userRepo->method('find')->with('7')->willReturn($authUser);
        $this->controller->setTestUser($authUser);

        $request = new Request();
        $response = $this->controller->getCurrentUser($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Auth User', $data['name']);
    }

    // ==================== Get User By ID Success ====================

    public function testGetUserById_Success(): void
    {
        $user = $this->createUser([
            'id' => '55',
            'name' => 'Found User',
        ]);
        $this->userRepo->method('find')->with('55')->willReturn($user);

        $request = new Request();
        $response = $this->controller->getUserById(55, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Found User', $data['name']);
    }

    // ==================== Register Device Token ====================

    public function testRegisterDeviceToken_Success(): void
    {
        $request = $this->jsonRequest(['token' => 'device-token-abc']);
        $response = $this->controller->registerDeviceToken($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    // ==================== Logout ====================

    public function testLogout_Success(): void
    {
        $request = new Request();
        $response = $this->controller->logout();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame('Logged out successfully', $data['message']);
    }

    // ==================== Update Profile Exception ====================

    public function testUpdateProfile_ServiceThrowsException(): void
    {
        $authUser = $this->createUser(['id' => '1']);
        $this->userRepo->method('find')->with('1')->willReturn($authUser);
        $this->profileService->method('updateProfile')
            ->with('1', $this->anything())
            ->willThrowException(new \RuntimeException('Update failed'));

        $this->controller->setTestUser($authUser);
        $request = $this->jsonRequest(['bio' => 'New']);
        $response = $this->controller->updateProfile(1, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Internal server error', $data['error']);
    }

    // ==================== Get Current User Not Found ====================

    public function testGetCurrentUser_UserNotFound(): void
    {
        $authUser = $this->createUser(['id' => '1']);
        $this->userRepo->method('find')->with('1')->willReturn(null);
        $this->controller->setTestUser($authUser);

        $request = new Request();
        $response = $this->controller->getCurrentUser($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }
}
