<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\User;
use App\User\Entity\UserProfile;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\PhoneRegistrationService;
use App\User\Service\PhoneValidationService;
use App\User\Service\SmsVerificationService;
use App\User\Service\UserIdGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;

#[AllowMockObjectsWithoutExpectations]
class PhoneRegistrationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserRepositoryInterface&MockObject $userRepository;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private ValidatorInterface&MockObject $validator;
    private UserIdGeneratorService&MockObject $idGenerator;
    private SmsVerificationService&MockObject $smsVerification;
    private PhoneValidationService&MockObject $phoneValidator;
    private PhoneRegistrationService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->idGenerator = $this->createMock(UserIdGeneratorService::class);
        $this->smsVerification = $this->createMock(SmsVerificationService::class);
        $this->phoneValidator = $this->createMock(PhoneValidationService::class);

        $this->service = new PhoneRegistrationService(
            $this->entityManager,
            $this->userRepository,
            $this->passwordHasher,
            $this->validator,
            $this->idGenerator,
            $this->smsVerification,
            $this->phoneValidator
        );
    }

    public function testRegisterSuccess(): void
    {
        $phone = '+84912345678';
        $code = '123456';
        $displayName = 'Phone User';
        $email = 'phone@example.com';

        $userId = 'user_123';
        $this->idGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($userId);

        $this->phoneValidator->expects($this->once())
            ->method('validate')
            ->with($phone);

        $this->userRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn(null);

        $this->smsVerification->expects($this->once())
            ->method('verifyCode')
            ->with($phone, $code)
            ->willReturn(true);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_pw');

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationList::class));

        // Expect persist for User and UserProfile
        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->service->register($phone, $code, $displayName, $email);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($phone, $user->getPhoneNumber());
        $this->assertEquals($email, $user->getEmail());
    }

    public function testRegisterInvalidPhoneFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // PhoneValidationService->validate will throw for invalid format
        $this->phoneValidator->expects($this->once())
            ->method('validate')
            ->with('invalid')
            ->willThrowException(new \InvalidArgumentException('Invalid phone number'));

        // No further calls
        $this->userRepository->expects($this->never())->method('findByPhoneNumber');
        $this->smsVerification->expects($this->never())->method('verifyCode');

        $this->service->register('invalid', '123456', 'User');
    }

    public function testRegisterPhoneAlreadyExists(): void
    {
        $phone = '+84912345678';
        $existingUser = new User('user_999', 'existing@example.com');

        $this->phoneValidator->expects($this->once())->method('validate')->with($phone);
        $this->userRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn($existingUser);
        $this->smsVerification->expects($this->never())->method('verifyCode');
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone number already registered');

        $this->service->register($phone, '123456', 'User');
    }

    public function testRegisterInvalidSmsCode(): void
    {
        $phone = '+84912345678';
        $this->phoneValidator->expects($this->once())->method('validate')->with($phone);
        $this->userRepository->expects($this->once())->method('findByPhoneNumber')->willReturn(null);
        $this->smsVerification->expects($this->once())
            ->method('verifyCode')
            ->with($phone, 'wrong')
            ->willReturn(false);
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid verification code');

        $this->service->register($phone, 'wrong', 'User');
    }

    public function testRegisterValidationFails(): void
    {
        $phone = '+84912345678';
        $code = '123456';
        $displayName = 'User';
        $email = 'valid@example.com'; // must be valid email

        $this->phoneValidator->expects($this->once())->method('validate')->with($phone);
        $this->userRepository->expects($this->once())->method('findByPhoneNumber')->willReturn(null);
        $this->smsVerification->expects($this->once())->method('verifyCode')->with($phone, $code)->willReturn(true);
        $this->idGenerator->expects($this->once())->method('generate')->willReturn('user_123');
        $this->passwordHasher->expects($this->once())->method('hashPassword')->willReturn('hashed');

        // Simulate validation errors
        $violations = $this->createMock(ConstraintViolationList::class);
        $violations->method('count')->willReturn(2);
        $violations->method('__toString')->willReturn('Validation errors');
        $this->validator->expects($this->once())->method('validate')->willReturn($violations);

        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation errors');

        $this->service->register($phone, $code, $displayName, $email);
    }

    public function testRegisterWithEmailOptional(): void
    {
        $phone = '+84912345678';
        $code = '123456';
        $displayName = 'Phone User';
        $email = 'phone@example.com';

        $this->idGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('user_123');

        $this->phoneValidator->expects($this->once())
            ->method('validate')
            ->with($phone);

        $this->userRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn(null);

        $this->smsVerification->expects($this->once())
            ->method('verifyCode')
            ->with($phone, $code)
            ->willReturn(true);

        $this->passwordHasher->expects($this->once())->method('hashPassword')->willReturn('hashed_pw');
        $this->validator->expects($this->once())->method('validate')->willReturn($this->createMock(ConstraintViolationList::class));
        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->service->register($phone, $code, $displayName, $email);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($phone, $user->getPhoneNumber());
    }
}
