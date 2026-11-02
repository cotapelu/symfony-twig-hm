<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\User;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\AuthService;
use App\Shared\Service\UuidService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AllowMockObjectsWithoutExpectations]
class AuthServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserRepositoryInterface&MockObject $userRepository;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private UuidService&MockObject $uuidService;
    private AuthService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->uuidService = $this->createMock(UuidService::class);
        $this->service = new AuthService(
            $this->userRepository,
            $this->entityManager,
            $this->passwordHasher,
            $this->uuidService,
            null // event publisher
        );
    }

    public function testRegisterSuccess(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'StrongPass123!',
            'name' => 'Test User',
            'phoneNumber' => '+84912345678',
        ];
        $userId = 'user_123';
        $this->uuidService->expects($this->once())
            ->method('generateWithPrefix')
            ->with('user_')
            ->willReturn($userId);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($data['email'])
            ->willReturn(null);
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_pw');
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->service->register($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($data['email'], $user->getEmail());
        $this->assertEquals($data['phoneNumber'], $user->getPhoneNumber());
    }

    public function testRegisterMissingFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name, email and password are required');
        $this->service->register(['email' => 'test@example.com', 'password' => 'pass']);
    }

    public function testRegisterInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');
        $this->userRepository->expects($this->never())->method('findByEmail');
        $this->service->register(['email' => 'invalid', 'password' => 'Pass123!', 'name' => 'User']);
    }

    public function testRegisterWeakPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');
        $this->userRepository->expects($this->never())->method('findByEmail');
        $this->service->register(['email' => 'test@example.com', 'password' => 'short', 'name' => 'User']);
    }

    public function testRegisterDuplicateEmail(): void
    {
        $email = 'existing@example.com';
        $existingUser = new User('user_999', $email, ['ROLE_USER']);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($existingUser);
        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User already exists');

        $this->service->register(['email' => $email, 'password' => 'Pass123!', 'name' => 'User']);
    }

    public function testAuthenticateSuccess(): void
    {
        $email = 'user@example.com';
        $password = 'correct';
        $user = new User('user_123', $email, ['ROLE_USER']);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $password)
            ->willReturn(true);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->authenticate($email, $password);

        $this->assertSame($user, $result);
        $this->assertNotNull($user->getLastLoginAt());
    }

    public function testAuthenticateFailureWrongPassword(): void
    {
        $email = 'user@example.com';
        $password = 'wrong';
        $user = new User('user_123', $email, ['ROLE_USER']);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $password)
            ->willReturn(false);
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->authenticate($email, $password);

        $this->assertNull($result);
    }

    public function testAuthenticateFailureUserNotFound(): void
    {
        $email = 'unknown@example.com';
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);
        $this->passwordHasher->expects($this->never())->method('isPasswordValid');
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->authenticate($email, 'any');

        $this->assertNull($result);
    }

    public function testSocialLoginNewUser(): void
    {
        $provider = 'google';
        $socialData = ['id' => 'google123', 'email' => 'social@example.com'];
        $userId = 'user_999';
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($socialData['email'])
            ->willReturn(null);
        $this->uuidService->expects($this->once())
            ->method('generateWithPrefix')
            ->with('user_')
            ->willReturn($userId);
        $this->uuidService->expects($this->once())
            ->method('generate')
            ->willReturn('randompass');
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_social');
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->service->socialLogin($provider, $socialData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($provider, $user->getSocialProvider());
        $this->assertEquals($socialData['id'], $user->getSocialId());
    }

    public function testSocialLoginExistingUser(): void
    {
        $provider = 'facebook';
        $socialData = ['id' => 'fb456', 'email' => 'existing@example.com'];
        $existingUser = new User('user_123', $socialData['email'], ['ROLE_USER']);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($socialData['email'])
            ->willReturn($existingUser);
        $this->uuidService->expects($this->never())->method('generateWithPrefix');
        $this->uuidService->expects($this->never())->method('generate');
        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->service->socialLogin($provider, $socialData);

        $this->assertSame($existingUser, $user);
        $this->assertEquals($provider, $existingUser->getSocialProvider());
        $this->assertEquals($socialData['id'], $existingUser->getSocialId());
    }

    public function testChangePasswordSuccess(): void
    {
        $user = new User('user_123', 'test@example.com', ['ROLE_USER']);
        $current = 'oldpass';
        $new = 'NewPass123!';
        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $current)
            ->willReturn(true);
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, $new)
            ->willReturn('new_hashed');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->changePassword($user, $current, $new);

        $this->assertTrue($result);
        $this->assertEquals('new_hashed', $user->getPassword());
    }

    public function testChangePasswordInvalidCurrent(): void
    {
        $user = new User('user_123', 'test@example.com', ['ROLE_USER']);
        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'wrong')
            ->willReturn(false);
        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->changePassword($user, 'wrong', 'NewPass123!');

        $this->assertFalse($result);
    }

    public function testChangePasswordTooShort(): void
    {
        $user = new User('user_123', 'test@example.com', ['ROLE_USER']);
        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'oldpass')
            ->willReturn(true);
        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->entityManager->expects($this->never())->method('flush');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');
        $this->service->changePassword($user, 'oldpass', 'short');
    }

    public function testChangePasswordSameAsCurrent(): void
    {
        $user = new User('user_123', 'test@example.com', ['ROLE_USER']);
        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'samepass')
            ->willReturn(true);
        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->entityManager->expects($this->never())->method('flush');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('New password must be different from current password');
        $this->service->changePassword($user, 'samepass', 'samepass');
    }
}
