<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\User;
use App\User\Entity\UserProfile;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\SocialRegistrationService;
use App\User\Service\UserIdGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;

#[AllowMockObjectsWithoutExpectations]
class SocialRegistrationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserRepositoryInterface&MockObject $userRepository;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private ValidatorInterface&MockObject $validator;
    private UserIdGeneratorService&MockObject $idGenerator;
    private SocialRegistrationService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->idGenerator = $this->createMock(UserIdGeneratorService::class);

        $this->service = new SocialRegistrationService(
            $this->entityManager,
            $this->userRepository,
            $this->passwordHasher,
            $this->validator,
            $this->idGenerator
        );
    }

    public function testRegisterSuccess(): void
    {
        $provider = 'google';
        $socialId = 'google123';
        $email = 'new@example.com';
        $displayName = 'New User';
        $avatar = 'https://example.com/avatar.jpg';
        $userId = 'user_123';

        $this->userRepository->expects($this->once())
            ->method('findBySocial')
            ->with($provider, $socialId)
            ->willReturn(null);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->idGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($userId);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_pw');

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationList::class));

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->service->register($provider, $socialId, $email, $displayName, $avatar);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($provider, $user->getSocialProvider());
        $this->assertEquals($socialId, $user->getSocialId());
    }

    public function testRegisterDuplicateSocial(): void
    {
        $provider = 'google';
        $socialId = 'google123';
        $email = 'any@example.com';
        $existingUser = new User('user_999', $email, ['ROLE_USER']);

        $this->userRepository->expects($this->once())
            ->method('findBySocial')
            ->with($provider, $socialId)
            ->willReturn($existingUser);
        // Should not call findByEmail
        $this->userRepository->expects($this->never())->method('findByEmail');
        $this->idGenerator->expects($this->never())->method('generate');
        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->validator->expects($this->never())->method('validate');
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Social account already linked');

        $this->service->register($provider, $socialId, $email, 'User');
    }

    public function testRegisterExistingEmail(): void
    {
        $provider = 'facebook';
        $socialId = 'fb456';
        $email = 'existing@example.com';
        $displayName = 'Existing';
        $existingUser = new User('user_999', $email, ['ROLE_USER']);

        $this->userRepository->expects($this->once())
            ->method('findBySocial')
            ->with($provider, $socialId)
            ->willReturn(null);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($existingUser);

        $this->idGenerator->expects($this->never())->method('generate');
        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->validator->expects($this->never())->method('validate');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->register($provider, $socialId, $email, $displayName);

        $this->assertSame($existingUser, $result);
        $this->assertEquals($provider, $existingUser->getSocialProvider());
        $this->assertEquals($socialId, $existingUser->getSocialId());
    }

    public function testRegisterValidationFails(): void
    {
        $provider = 'twitter';
        $socialId = 'tw789';
        $email = 'valid@example.com';
        $displayName = 'User';

        $this->userRepository->expects($this->once())
            ->method('findBySocial')
            ->with($provider, $socialId)
            ->willReturn(null);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->idGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('user_123');

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed');

        $violations = $this->createMock(ConstraintViolationList::class);
        $violations->method('count')->willReturn(1);
        $violations->method('__toString')->willReturn('Entity validation failed');
        $this->validator->expects($this->once())->method('validate')->willReturn($violations);

        // No persist/flush because validation fails before persistence
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity validation failed');

        $this->service->register($provider, $socialId, $email, $displayName);
    }
}
