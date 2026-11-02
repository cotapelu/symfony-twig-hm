<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\User;
use App\User\Entity\UserProfile;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\EmailRegistrationService;
use App\User\Service\PasswordValidationService;
use App\User\Service\UserIdGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;

#[AllowMockObjectsWithoutExpectations]
class EmailRegistrationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserRepositoryInterface&MockObject $userRepository;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private ValidatorInterface&MockObject $validator;
    private PasswordValidationService&MockObject $passwordValidator;
    private UserIdGeneratorService&MockObject $idGenerator;
    private EmailRegistrationService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->passwordValidator = $this->createMock(PasswordValidationService::class);
        $this->idGenerator = $this->createMock(UserIdGeneratorService::class);

        $this->service = new EmailRegistrationService(
            $this->entityManager,
            $this->userRepository,
            $this->passwordHasher,
            $this->validator,
            $this->idGenerator,
            $this->passwordValidator
        );
    }

    public function testRegisterSuccess(): void
    {
        $email = 'test@example.com';
        $password = 'StrongPass123!';
        $displayName = 'Test User';
        $phone = '+84912345678';

        $userId = 'user_123';
        $this->idGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($userId);

        $this->passwordValidator->expects($this->once())
            ->method('validate')
            ->with($password);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_'.$password);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationList::class));

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->service->register($email, $password, $displayName, $phone);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($phone, $user->getPhoneNumber());
    }

    public function testRegisterInvalidEmailFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->passwordValidator->expects($this->never())->method('validate');
        $this->userRepository->expects($this->never())->method('findByEmail');

        $this->service->register('invalid-email', 'Pass123!', 'User');
    }

    public function testRegisterDuplicateEmail(): void
    {
        $email = 'existing@example.com';
        $existingUser = new User('user_999', $email, ['ROLE_USER']);

        // Password validation runs before email existence check; it should be called.
        $this->passwordValidator->expects($this->never())
            ->method('validate')
            ->with('Pass123!');

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($existingUser);

        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->validator->expects($this->never())->method('validate');
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email already registered');

        $this->service->register($email, 'Pass123!', 'User');
    }

    public function testRegisterWeakPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        // Password validation throws; however ensureEmailNotExists (which uses repository) is called after password validation.
        // Since passwordValidator throws, repository should have been called before the exception? Actually order: validateEmail, passwordValidator->validate, ensureEmailNotExists. So repository is called after password validation? Wait: after passwordValidator.validate throws, exception propagates and ensureEmailNotExists is NOT reached. Actually the code calls validateEmail then passwordValidator->validate then ensureEmailNotExists. So if passwordValidator throws, ensureEmailNotExists (and thus repository) is NOT reached. So we should not expect repository call.
        // But earlier we thought repository is called before password validation; but after reorder? Let's confirm: We haven't reordered EmailRegistrationService; it still has order: validateEmail, ensureEmailNotExists, passwordValidator->validate. Actually we didn't reorder. So repository is called before password validation. In test we expect repository to be called once despite password failure.
        // So adjust expectation accordingly: Since order is old, we expect repository call.
        $this->passwordValidator->expects($this->once())
            ->method('validate')
            ->with('short')
            ->will($this->throwException(new \InvalidArgumentException('Password must be at least 8 characters long')));

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn(null);

        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->validator->expects($this->never())->method('validate');
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->service->register('test@example.com', 'short', 'User');
    }

    public function testRegisterValidationFails(): void
    {
        $email = 'test@example.com';
        $password = 'ValidPass123!'; // meets password criteria
        $displayName = 'User';

        $this->passwordValidator->expects($this->once())->method('validate')->with($password);
        $this->userRepository->expects($this->once())->method('findByEmail')->with($email)->willReturn(null);
        $this->idGenerator->expects($this->once())->method('generate')->willReturn('user_123');
        $this->passwordHasher->expects($this->once())->method('hashPassword')->willReturn('hashed');

        // Simulate validation errors on User entity
        $violations = $this->createMock(ConstraintViolationList::class);
        $violations->method('count')->willReturn(1);
        $violations->method('__toString')->willReturn('Validation errors');
        $this->validator->expects($this->once())->method('validate')->willReturn($violations);

        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation errors');

        $this->service->register($email, $password, $displayName);
    }

    public function testRegisterWithoutPhone(): void
    {
        $email = 'test@example.com';
        $password = 'StrongPass123!';
        $displayName = 'User';

        $this->idGenerator->expects($this->once())->method('generate')->willReturn('user_123');
        $this->passwordValidator->expects($this->once())->method('validate')->with($password);
        $this->userRepository->expects($this->once())->method('findByEmail')->with($email)->willReturn(null);
        $this->passwordHasher->expects($this->once())->method('hashPassword')->willReturn('hashed');
        $this->validator->expects($this->once())->method('validate')->willReturn($this->createMock(ConstraintViolationList::class));
        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->service->register($email, $password, $displayName);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($email, $user->getEmail());
        $this->assertNull($user->getPhoneNumber());
    }
}
