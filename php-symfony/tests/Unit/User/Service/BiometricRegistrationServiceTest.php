<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\User;
use App\User\Entity\UserProfile;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\BiometricRegistrationService;
use App\User\Service\UserIdGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;

#[AllowMockObjectsWithoutExpectations]
class BiometricRegistrationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserRepositoryInterface&MockObject $userRepository;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private ValidatorInterface&MockObject $validator;
    private UserIdGeneratorService&MockObject $idGenerator;
    private BiometricRegistrationService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->idGenerator = $this->createMock(UserIdGeneratorService::class);

        $this->service = new BiometricRegistrationService(
            $this->entityManager,
            $this->userRepository,
            $this->passwordHasher,
            $this->validator,
            $this->idGenerator
        );
    }

    public function testRegisterSuccess(): void
    {
        $biometric = str_repeat('a', 150);
        $email = 'bio@example.com';
        $displayName = 'Bio User';
        $phone = '+84912345678';

        $userId = 'user_123';
        $this->idGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($userId);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_pw');

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationList::class));

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->service->register($biometric, $email, $displayName, $phone);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($phone, $user->getPhoneNumber());
    }

    public function testRegisterInvalidBiometric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid biometric data');

        $this->userRepository->expects($this->never())->method('findByEmail');

        $this->service->register('short', 'test@example.com', 'User');
    }

    public function testRegisterDuplicateEmail(): void
    {
        $biometric = str_repeat('a', 100);
        $email = 'existing@example.com';
        $existingUser = new User('user_999', $email, ['ROLE_USER']);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($existingUser);
        $this->idGenerator->expects($this->never())->method('generate');
        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->validator->expects($this->never())->method('validate');
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email already registered');

        $this->service->register($biometric, $email, 'User');
    }

    public function testRegisterValidationFails(): void
    {
        $biometric = str_repeat('a', 100);
        $email = 'valid@example.com';
        $displayName = 'User';

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
        $violations->method('__toString')->willReturn('Validation errors');
        $this->validator->expects($this->once())->method('validate')->willReturn($violations);

        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation errors');

        $this->service->register($biometric, $email, $displayName);
    }

    public function testRegisterWithoutPhone(): void
    {
        $biometric = str_repeat('b', 150);
        $email = 'test@example.com';
        $displayName = 'NoPhone User';

        $this->idGenerator->expects($this->once())->method('generate')->willReturn('user_123');
        $this->userRepository->expects($this->once())->method('findByEmail')->with($email)->willReturn(null);
        $this->passwordHasher->expects($this->once())->method('hashPassword')->willReturn('hashed');
        $this->validator->expects($this->once())->method('validate')->willReturn($this->createMock(ConstraintViolationList::class));
        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->service->register($biometric, $email, $displayName);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($email, $user->getEmail());
        $this->assertNull($user->getPhoneNumber());
    }
}
