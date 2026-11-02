<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\User;
use App\User\Entity\UserProfile;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;

#[AllowMockObjectsWithoutExpectations]
class UserRegistrationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserRepositoryInterface&MockObject $userRepository;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private ValidatorInterface&MockObject $validator;
    private UserRegistrationService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->service = new UserRegistrationService(
            $this->userRepository,
            $this->entityManager,
            $this->passwordHasher,
            $this->validator
        );
    }

    public function testRegisterWithEmailSuccess(): void
    {
        $email = 'test@example.com';
        $password = 'StrongPass123!';
        $displayName = 'Test User';

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

        $result = $this->service->registerWithEmail($email, $password, $displayName);

        $this->assertInstanceOf(User::class, $result);
        $this->assertNotEmpty($result->getId());
        $this->assertStringStartsWith('user_', $result->getId());
        $this->assertEquals($email, $result->getEmail());
    }

    public function testRegisterWithEmailInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->userRepository->expects($this->never())->method('findByEmail');

        $this->service->registerWithEmail('invalid-email', 'Pass123!', 'Test');
    }

    public function testRegisterWithEmailDuplicate(): void
    {
        $email = 'existing@example.com';
        $existingUser = new User('user_123', $email, ['ROLE_USER']);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($existingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email already registered');

        $this->service->registerWithEmail($email, 'Pass123!', 'Test');
    }

    public function testRegisterWithEmailWeakPasswordShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        $this->userRepository->expects($this->never())->method('findByEmail');

        $this->service->registerWithEmail('test@example.com', '123', 'Test');
    }

    public function testRegisterWithEmailWeakPasswordMissingUppercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least one uppercase letter');

        $this->userRepository->expects($this->never())->method('findByEmail');

        $this->service->registerWithEmail('test@example.com', 'lowercaseonly1!', 'Test');
    }

    public function testRegisterWithEmailWeakPasswordMissingLowercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least one lowercase letter');

        $this->userRepository->expects($this->never())->method('findByEmail');

        $this->service->registerWithEmail('test@example.com', 'UPPERCASE1!', 'Test');
    }

    public function testRegisterWithEmailWeakPasswordMissingNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least one number');

        $this->userRepository->expects($this->never())->method('findByEmail');

        $this->service->registerWithEmail('test@example.com', 'NoNumber!', 'Test');
    }

    public function testRegisterWithEmailWeakPasswordMissingSpecial(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least one special character');

        $this->userRepository->expects($this->never())->method('findByEmail');

        $this->service->registerWithEmail('test@example.com', 'MissingSpecial1', 'Test');
    }

    public function testRegisterWithEmailWeakPasswordCommon(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password is too common');

        $this->userRepository->expects($this->never())->method('findByEmail');

        // Use a password that meets complexity but is in common list
        $this->service->registerWithEmail('test@example.com', 'P@ssw0rd!', 'Test');
    }

    public function testRegisterWithEmailValidationFails(): void
    {
        $email = 'test@example.com';
        $password = 'StrongPass123!';

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $violations = $this->createMock(ConstraintViolationList::class);
        $violations->method('count')->willReturn(1);
        $violations->method('__toString')->willReturn('Validation error on field');
        $this->validator->expects($this->once())->method('validate')->willReturn($violations);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation error on field');

        $this->service->registerWithEmail($email, $password, 'Test');
    }

    public function testRegisterWithEmailAndPhone(): void
    {
        $email = 'test@example.com';
        $password = 'StrongPass123!';
        $displayName = 'Test User';
        $phone = '+84912345678';

        // Only email check is performed
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->passwordHasher->expects($this->once())->method('hashPassword')->willReturn('hashed_pw');
        $this->validator->expects($this->once())->method('validate')->willReturn($this->createMock(ConstraintViolationList::class));
        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->registerWithEmail($email, $password, $displayName, $phone);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($phone, $result->getPhoneNumber());
    }

    public function testRegisterWithSocialEmailExists(): void
    {
        $provider = 'google';
        $socialId = 'google123';
        $email = 'existing@example.com';
        $displayName = 'Existing User';

        // Social account not exists, but email exists
        $existingUser = new User('user_123', $email, ['ROLE_USER']);
        $this->userRepository->expects($this->once())
            ->method('findBySocial')
            ->with($provider, $socialId)
            ->willReturn(null);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($existingUser);

        // No need to validate, just flush to update
        $this->validator->expects($this->never())->method('validate');
        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->registerWithSocial($provider, $socialId, $email, $displayName);

        $this->assertSame($existingUser, $result);
        $this->assertEquals($provider, $existingUser->getSocialProvider());
        $this->assertEquals($socialId, $existingUser->getSocialId());
    }

    public function testRegisterWithSocialNewUser(): void
    {
        $provider = 'facebook';
        $socialId = 'fb456';
        $email = 'new@example.com';
        $displayName = 'New User';

        // Both social and email not exist
        $this->userRepository->expects($this->once())->method('findBySocial')->with($provider, $socialId)->willReturn(null);
        $this->userRepository->expects($this->once())->method('findByEmail')->with($email)->willReturn(null);

        $this->passwordHasher->expects($this->once())->method('hashPassword')->willReturn('hashed_pw');
        $this->validator->expects($this->once())->method('validate')->willReturn($this->createMock(ConstraintViolationList::class));
        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->registerWithSocial($provider, $socialId, $email, $displayName);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($provider, $result->getSocialProvider());
        $this->assertEquals($socialId, $result->getSocialId());
    }

    public function testRegisterWithSocialDuplicateSocial(): void
    {
        $provider = 'google';
        $socialId = 'google123';
        $email = 'another@example.com';

        $existingUser = new User('user_999', $email, ['ROLE_USER']);

        $this->userRepository->expects($this->once())->method('findBySocial')->with($provider, $socialId)->willReturn($existingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Social account already linked');

        $this->userRepository->expects($this->never())->method('findByEmail');

        $this->service->registerWithSocial($provider, $socialId, $email, 'User');
    }

    public function testGenerateSecureUserIdUniqueness(): void
    {
        $ids = [];
        for ($i = 0; $i < 20; $i++) {
            $ref = new \ReflectionClass($this->service);
            $method = $ref->getMethod('generateSecureUserId');
            $method->setAccessible(true);
            $id = $method->invoke($this->service);
            $this->assertMatchesRegularExpression('/^user_[a-f0-9]{32}$/', $id);
            $this->assertArrayNotHasKey($id, $ids);
            $ids[$id] = true;
        }
    }

    public function testRegisterWithPhoneSuccess(): void
    {
        $phone = '+84912345678';
        $code = '123456';
        $displayName = 'Phone User';
        $email = 'phone@example.com';

        // Phone not taken
        $this->userRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn(null);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_pw');
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationList::class));
        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->registerWithPhone($phone, $code, $displayName, $email);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($phone, $result->getPhoneNumber());
        $this->assertEquals($email, $result->getEmail());
    }

    public function testRegisterWithPhoneDuplicate(): void
    {
        $phone = '+84912345678';
        $existingUser = new User('user_123', 'existing@example.com', ['ROLE_USER']);

        $this->userRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn($existingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone number already registered');

        $this->service->registerWithPhone($phone, '123456', 'User', null);
    }

    public function testRegisterWithPhoneInvalidCode(): void
    {
        $phone = '+84912345678';
        $code = '12345'; // 5 digits

        $this->userRepository->expects($this->never())->method('findByPhoneNumber');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid verification code');

        $this->service->registerWithPhone($phone, $code, 'User');
    }

    public function testRegisterWithPhoneInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number format');

        // Very short number
        $this->service->registerWithPhone('123', '123456', 'User');
    }

    public function testRegisterWithBiometricSuccess(): void
    {
        $biometric = str_repeat('a', 150); // > 100 bytes
        $email = 'bio@example.com';
        $displayName = 'Bio User';

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->passwordHasher->expects($this->once())->method('hashPassword')->willReturn('hashed_pw');
        $this->validator->expects($this->once())->method('validate')->willReturn($this->createMock(ConstraintViolationList::class));
        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->registerWithBiometric($biometric, $email, $displayName);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($email, $result->getEmail());
    }

    public function testRegisterWithBiometricInvalidData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid biometric data');

        $this->userRepository->expects($this->never())->method('findByEmail');

        $this->service->registerWithBiometric('short', 'test@example.com', 'User');
    }
}
