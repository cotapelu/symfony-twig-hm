<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Controller;

use App\User\Controller\UserProfileController;
use App\User\Service\UserProfileService;
use App\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UserProfileControllerTest extends TestCase
{
    private UserProfileService $userProfileService;
    private UserProfileController $controller;

    /** @return TokenStorageInterface */
    private function createTokenStorageMock(?User $user): TokenStorageInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);
        return $tokenStorage;
    }

    /** @return AuthorizationCheckerInterface */
    private function createAuthCheckerMock(bool $granted = true): AuthorizationCheckerInterface
    {
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->with('IS_AUTHENTICATED_REMEMBERED')->willReturn($granted);
        return $authChecker;
    }

    /** @return User */
    private function createUserMock(int $id): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getUserIdentifier')->willReturn("user{$id}@example.com");
        return $user;
    }



    protected function setUp(): void
    {
        $this->userProfileService = $this->createMock(UserProfileService::class);
        // Default controller will be overridden per test as needed
    }

    // ==================== createProfile ====================

    public function testCreateProfile_Unauthenticated(): void
    {
        $this->controller = new UserProfileController($this->userProfileService, null, null);
        $request = Request::create('/api/profile', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json']);
        $response = $this->controller->createProfile($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['error' => 'Authentication required'], json_decode($response->getContent(), true));
    }

    public function testCreateProfile_Success(): void
    {
        $userId = 42;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $userIdStr = (string) $userId;
        $this->userProfileService->method('getUserProfile')->with($this->equalTo($userIdStr))->willReturn(null);
        $profile = ['display_name' => null, 'user_id' => $userId];
        $this->userProfileService->method('createProfile')->with($this->equalTo($userIdStr))->willReturn($profile);

        $request = Request::create('/api/profile', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json']);
        $response = $this->controller->createProfile($request);

        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(['status' => 201, 'data' => ['display_name' => null, 'user_id' => $userId]], $data);
    }

    public function testCreateProfile_AlreadyExists(): void
    {
        $userId = 42;
        $userIdStr = (string) $userId;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $this->userProfileService->method('getUserProfile')->with($this->equalTo($userIdStr))->willReturn(['display_name' => 'Existing']);

        $request = Request::create('/api/profile', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json']);
        $response = $this->controller->createProfile($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Profile already exists'], json_decode($response->getContent(), true));
    }

    // ==================== getMyProfile ====================

    public function testGetMyProfile_Unauthenticated(): void
    {
        $this->controller = new UserProfileController($this->userProfileService, null, null);
        $response = $this->controller->getMyProfile();
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['error' => 'Authentication required'], json_decode($response->getContent(), true));
    }

    public function testGetMyProfile_NotFound(): void
    {
        $userId = 42;
        $userIdStr = (string) $userId;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $this->userProfileService->method('getUserProfile')->with($this->equalTo($userIdStr))->willReturn(null);

        $response = $this->controller->getMyProfile();
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Profile not found'], json_decode($response->getContent(), true));
    }

    public function testGetMyProfile_Success(): void
    {
        $userId = 42;
        $userIdStr = (string) $userId;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $profile = ['display_name' => 'Test', 'user_id' => $userId];
        $this->userProfileService->method('getUserProfile')->with($this->equalTo($userIdStr))->willReturn($profile);

        $response = $this->controller->getMyProfile();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['status' => 200, 'data' => ['display_name' => 'Test', 'user_id' => $userId]], json_decode($response->getContent(), true));
    }

    // ==================== updateMyProfile ====================

    public function testUpdateMyProfile_Unauthenticated(): void
    {
        $this->controller = new UserProfileController($this->userProfileService, null, null);
        $request = Request::create('/api/profile', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['display_name' => 'Test']));
        $response = $this->controller->updateMyProfile($request);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['error' => 'Authentication required'], json_decode($response->getContent(), true));
    }

    public function testUpdateMyProfile_InvalidJson(): void
    {
        $userId = 42;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $request = Request::create('/api/profile', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');
        $response = $this->controller->updateMyProfile($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateMyProfile_ValidationDisplayNameTooLong(): void
    {
        $userId = 42;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $request = Request::create('/api/profile', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['display_name' => str_repeat('a', 101)]));
        $response = $this->controller->updateMyProfile($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Display name too long (max 100 chars)'], json_decode($response->getContent(), true));
    }

    public function testUpdateMyProfile_ValidationBioTooLong(): void
    {
        $userId = 42;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $request = Request::create('/api/profile', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['bio' => str_repeat('a', 501)]));
        $response = $this->controller->updateMyProfile($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Bio too long (max 500 chars)'], json_decode($response->getContent(), true));
    }

    public function testUpdateMyProfile_ValidationInvalidEmail(): void
    {
        $userId = 42;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $request = Request::create('/api/profile', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'invalid-email']));
        $response = $this->controller->updateMyProfile($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Invalid email format'], json_decode($response->getContent(), true));
    }

    public function testUpdateMyProfile_Success(): void
    {
        $userId = 42;
        $userIdStr = (string) $userId;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $profile = ['display_name' => 'Updated', 'email' => 'test@example.com', 'user_id' => $userId];
        $this->userProfileService->expects($this->once())
            ->method('updateProfile')
            ->with($this->equalTo($userIdStr), $this->anything())
            ->willReturn($profile);

        $request = Request::create('/api/profile', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['display_name' => 'Updated', 'email' => 'test@example.com']));
        $response = $this->controller->updateMyProfile($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['status' => 200, 'data' => ['display_name' => 'Updated', 'email' => 'test@example.com', 'user_id' => $userId]], json_decode($response->getContent(), true));
    }

    // ==================== deleteMyProfile ====================

    public function testDeleteMyProfile_Unauthenticated(): void
    {
        $this->controller = new UserProfileController($this->userProfileService, null, null);
        $response = $this->controller->deleteMyProfile();
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['error' => 'Authentication required'], json_decode($response->getContent(), true));
    }

    public function testDeleteMyProfile_Success(): void
    {
        $userId = 42;
        $userIdStr = (string) $userId;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $this->userProfileService->expects($this->once())
            ->method('deleteProfile')
            ->with($this->equalTo($userIdStr))
            ->willReturn(true);

        $response = $this->controller->deleteMyProfile();
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame(['status' => 204, 'data' => null], json_decode($response->getContent(), true));
    }

    public function testDeleteMyProfile_NotFound(): void
    {
        $userId = 42;
        $userIdStr = (string) $userId;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $this->userProfileService->expects($this->once())
            ->method('deleteProfile')
            ->with($this->equalTo($userIdStr))
            ->willReturn(false);

        $response = $this->controller->deleteMyProfile();
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Profile not found'], json_decode($response->getContent(), true));
    }

    // ==================== getUserProfile (by ID) ====================

    public function testGetUserProfile_Unauthenticated(): void
    {
        $this->controller = new UserProfileController($this->userProfileService, null, null);
        $response = $this->controller->getUserProfile(1);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['error' => 'Authentication required'], json_decode($response->getContent(), true));
    }

    public function testGetUserProfile_AccessDenied(): void
    {
        $currentUserId = 42;
        $targetUserId = 43;
        $user = $this->createUserMock($currentUserId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $response = $this->controller->getUserProfile($targetUserId);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(['error' => 'Access denied'], json_decode($response->getContent(), true));
    }

    public function testGetUserProfile_NotFound(): void
    {
        $userId = 42;
        $userIdStr = (string) $userId;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $this->userProfileService->method('getUserProfile')->with($this->equalTo($userIdStr))->willReturn(null);

        $response = $this->controller->getUserProfile($userId);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Profile not found'], json_decode($response->getContent(), true));
    }

    public function testGetUserProfile_Success(): void
    {
        $userId = 42;
        $userIdStr = (string) $userId;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $profile = ['display_name' => 'Test', 'user_id' => $userId];
        $this->userProfileService->expects($this->once())
            ->method('getUserProfile')
            ->with($this->equalTo($userIdStr))
            ->willReturn($profile);

        $response = $this->controller->getUserProfile($userId);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['status' => 200, 'data' => ['display_name' => 'Test', 'user_id' => $userId]], json_decode($response->getContent(), true));
    }

    // ==================== updateUserProfile ====================

    public function testUpdateUserProfile_Unauthenticated(): void
    {
        $this->controller = new UserProfileController($this->userProfileService, null, null);
        $request = Request::create('/api/profile/1', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['display_name' => 'Test']));
        $response = $this->controller->updateUserProfile($request, 1);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['error' => 'Authentication required'], json_decode($response->getContent(), true));
    }

    public function testUpdateUserProfile_AccessDenied(): void
    {
        $currentUserId = 42;
        $targetUserId = 43;
        $user = $this->createUserMock($currentUserId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $request = Request::create('/api/profile/'.$targetUserId, 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['display_name' => 'Test']));
        $response = $this->controller->updateUserProfile($request, $targetUserId);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(['error' => 'Access denied'], json_decode($response->getContent(), true));
    }

    public function testUpdateUserProfile_ValidationPhoneTooLong(): void
    {
        $userId = 42;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $request = Request::create('/api/profile/'.$userId, 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['phone' => str_repeat('1', 21)]));
        $response = $this->controller->updateUserProfile($request, $userId);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Phone number too long'], json_decode($response->getContent(), true));
    }

    public function testUpdateUserProfile_ValidationAddressTooLong(): void
    {
        $userId = 42;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $request = Request::create('/api/profile/'.$userId, 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['address' => str_repeat('a', 501)]));
        $response = $this->controller->updateUserProfile($request, $userId);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Address too long'], json_decode($response->getContent(), true));
    }

    public function testUpdateUserProfile_Success(): void
    {
        $userId = 42;
        $userIdStr = (string) $userId;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $profile = ['display_name' => 'Updated', 'phone' => '+1234567890', 'user_id' => $userId];
        $this->userProfileService->expects($this->once())
            ->method('updateProfile')
            ->with($this->equalTo($userIdStr), $this->anything())
            ->willReturn($profile);

        $request = Request::create('/api/profile/'.$userId, 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['display_name' => 'Updated', 'phone' => '+1234567890']));
        $response = $this->controller->updateUserProfile($request, $userId);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['status' => 200, 'data' => ['display_name' => 'Updated', 'phone' => '+1234567890', 'user_id' => $userId]], json_decode($response->getContent(), true));
    }

    // ==================== deleteUserProfile ====================

    public function testDeleteUserProfile_Unauthenticated(): void
    {
        $this->controller = new UserProfileController($this->userProfileService, null, null);
        $response = $this->controller->deleteUserProfile(1);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['error' => 'Authentication required'], json_decode($response->getContent(), true));
    }

    public function testDeleteUserProfile_AccessDenied(): void
    {
        $currentUserId = 42;
        $targetUserId = 43;
        $user = $this->createUserMock($currentUserId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $response = $this->controller->deleteUserProfile($targetUserId);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(['error' => 'Access denied'], json_decode($response->getContent(), true));
    }

    public function testDeleteUserProfile_Success(): void
    {
        $userId = 42;
        $userIdStr = (string) $userId;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $this->userProfileService->expects($this->once())
            ->method('deleteProfile')
            ->with($this->equalTo($userIdStr))
            ->willReturn(true);

        $response = $this->controller->deleteUserProfile($userId);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame(['status' => 204, 'data' => null], json_decode($response->getContent(), true));
    }

    public function testDeleteUserProfile_NotFound(): void
    {
        $userId = 42;
        $userIdStr = (string) $userId;
        $user = $this->createUserMock($userId);
        $tokenStorage = $this->createTokenStorageMock($user);
        $authChecker = $this->createAuthCheckerMock(true);
        $this->controller = new UserProfileController($this->userProfileService, $tokenStorage, $authChecker);
        $this->controller->setContainer(new ContainerBuilder());

        $this->userProfileService->expects($this->once())
            ->method('deleteProfile')
            ->with($this->equalTo($userIdStr))
            ->willReturn(false);

        $response = $this->controller->deleteUserProfile($userId);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Profile not found'], json_decode($response->getContent(), true));
    }

    // ==================== Validation helpers (via reflection) ====================

    public function testValidateDisplayName_TooLong(): void
    {
        $this->controller = new UserProfileController($this->userProfileService);
        $reflection = new \ReflectionMethod($this->controller, 'validateDisplayName');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->controller, str_repeat('a', 101));
        $this->assertSame('Display name too long (max 100 chars)', $result);
    }

    public function testValidateDisplayName_Valid(): void
    {
        $this->controller = new UserProfileController($this->userProfileService);
        $reflection = new \ReflectionMethod($this->controller, 'validateDisplayName');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->controller, 'Short');
        $this->assertNull($result);
    }

    public function testValidateBio_TooLong(): void
    {
        $this->controller = new UserProfileController($this->userProfileService);
        $reflection = new \ReflectionMethod($this->controller, 'validateBio');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->controller, str_repeat('a', 501));
        $this->assertSame('Bio too long (max 500 chars)', $result);
    }

    public function testValidateAvatar_TooLong(): void
    {
        $this->controller = new UserProfileController($this->userProfileService);
        $reflection = new \ReflectionMethod($this->controller, 'validateAvatar');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->controller, str_repeat('a', 501));
        $this->assertSame('Avatar URL too long', $result);
    }

    public function testValidateEmail_Invalid(): void
    {
        $this->controller = new UserProfileController($this->userProfileService);
        $reflection = new \ReflectionMethod($this->controller, 'validateEmail');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->controller, 'bad-email');
        $this->assertSame('Invalid email format', $result);
    }

    public function testValidateEmail_Valid(): void
    {
        $this->controller = new UserProfileController($this->userProfileService);
        $reflection = new \ReflectionMethod($this->controller, 'validateEmail');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->controller, 'test@example.com');
        $this->assertNull($result);
    }

    public function testValidatePhone_TooLong(): void
    {
        $this->controller = new UserProfileController($this->userProfileService);
        $reflection = new \ReflectionMethod($this->controller, 'validatePhone');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->controller, str_repeat('1', 21));
        $this->assertSame('Phone number too long', $result);
    }

    public function testValidateAddress_TooLong(): void
    {
        $this->controller = new UserProfileController($this->userProfileService);
        $reflection = new \ReflectionMethod($this->controller, 'validateAddress');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->controller, str_repeat('a', 501));
        $this->assertSame('Address too long', $result);
    }

    public function testValidateCity_TooLong(): void
    {
        $this->controller = new UserProfileController($this->userProfileService);
        $reflection = new \ReflectionMethod($this->controller, 'validateCity');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->controller, str_repeat('a', 101));
        $this->assertSame('City name too long', $result);
    }

    public function testValidateCountry_TooLong(): void
    {
        $this->controller = new UserProfileController($this->userProfileService);
        $reflection = new \ReflectionMethod($this->controller, 'validateCountry');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->controller, str_repeat('a', 101));
        $this->assertSame('Country name too long', $result);
    }
}
