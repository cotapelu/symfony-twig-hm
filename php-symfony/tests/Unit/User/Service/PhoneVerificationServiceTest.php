<?php
declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\User\Service\PhoneVerificationService;
use App\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\User\Entity\User;
use App\User\Entity\UserVerification;
use App\Shared\Service\CircuitBreakerService;

#[AllowMockObjectsWithoutExpectations]
class PhoneVerificationServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepositoryInterface $userRepository;
    private PhoneVerificationService $service;
    private string $userId = 'user_123';
    private string $phoneNumber = '+84912345678';

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->service = new PhoneVerificationService(
            $this->entityManager,
            $this->userRepository
        );
    }

    private function createUserMock(?UserVerification $verification = null): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($this->userId);
        $user->method('getVerification')->willReturn($verification);
        $user->method('setVerification')->willReturnCallback(function ($v) use (&$storedVerification) {
            $storedVerification = $v;
        });
        $user->method('getPhoneNumber')->willReturn($this->phoneNumber);
        $user->method('setPhoneNumber')->willReturnSelf();
        $user->method('setIsPhoneVerified')->willReturnSelf();
        return $user;
    }

    private function createVerificationMock(
        ?\DateTime $lastPhoneSentAt = null,
        ?string $phoneCode = null,
        ?\DateTime $expiresAt = null,
        int $attempts = 0,
        ?string $phone = null
    ): UserVerification {
        $v = $this->createMock(UserVerification::class);
        $v->method('getLastPhoneSentAt')->willReturn($lastPhoneSentAt);
        $v->method('getPhoneCode')->willReturn($phoneCode);
        $v->method('getPhoneCodeExpiresAt')->willReturn($expiresAt);
        $v->method('getPhoneCodeAttempts')->willReturn($attempts);
        $v->method('getPhoneNumber')->willReturn($phone);
        $v->method('setPhoneCode')->willReturnSelf();
        $v->method('setPhoneCodeExpiresAt')->willReturnSelf();
        $v->method('setLastPhoneSentAt')->willReturnSelf();
        $v->method('setPhoneNumber')->willReturnSelf();
        $v->method('setPhoneCodeAttempts')->willReturnSelf();
        return $v;
    }

    // sendVerificationCode tests
    public function testSendVerificationCodeUserNotFound(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($this->userId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User not found');

        $this->service->sendVerificationCode($this->userId, $this->phoneNumber);
    }

    public function testSendVerificationCodeCooldown(): void
    {
        $verification = $this->createVerificationMock(
            lastPhoneSentAt: (new \DateTime())->modify('-30 seconds')
        );
        $user = $this->createUserMock($verification);

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($this->userId)
            ->willReturn($user);

        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please wait before requesting another code');

        $this->service->sendVerificationCode($this->userId, $this->phoneNumber);
    }

    public function testSendVerificationCodeSuccessWithMockSms(): void
    {
        $user = $this->createUserMock(null);
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($this->userId)
            ->willReturn($user);

        $capturedVerification = null;
        $user->method('setVerification')->willReturnCallback(function ($v) use (&$capturedVerification) {
            $capturedVerification = $v;
        });

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->sendVerificationCode($this->userId, $this->phoneNumber);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('phone_number', $result);
        $expectedMask = substr($this->phoneNumber, 0, -6) . '******' . substr($this->phoneNumber, -2);
        $this->assertSame($expectedMask, $result['phone_number']);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertSame(600, $result['expires_in']);

        $this->assertInstanceOf(UserVerification::class, $capturedVerification);
    }

    public function testSendVerificationCodeSuccessWithCircuitBreaker(): void
    {
        putenv('TWILIO_ACCOUNT_SID=test_sid');
        putenv('TWILIO_AUTH_TOKEN=test_token');
        putenv('TWILIO_PHONE_NUMBER=+1234567890');

        $circuitBreaker = $this->createMock(CircuitBreakerService::class);
        $service = new PhoneVerificationService(
            $this->entityManager,
            $this->userRepository,
            $circuitBreaker
        );

        $user = $this->createUserMock(null);
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($this->userId)
            ->willReturn($user);

        $this->entityManager->expects($this->once())->method('flush');

        $circuitBreaker->expects($this->once())
            ->method('execute')
            ->with(
                $this->isCallable(),
                'twilio_api',
                3,
                60,
                $this->isCallable()
            )
            ->willReturn(true);

        $result = $service->sendVerificationCode($this->userId, $this->phoneNumber);
        $expectedMask = substr($this->phoneNumber, 0, -6) . '******' . substr($this->phoneNumber, -2);
        $this->assertTrue($result['success']);
        $this->assertSame($expectedMask, $result['phone_number']);

        putenv('TWILIO_ACCOUNT_SID');
        putenv('TWILIO_AUTH_TOKEN');
        putenv('TWILIO_PHONE_NUMBER');
    }

    public function testSendVerificationCodeSmsFailure(): void
    {
        putenv('TWILIO_ACCOUNT_SID=test_sid');
        putenv('TWILIO_AUTH_TOKEN=test_token');
        putenv('TWILIO_PHONE_NUMBER=+1234567890');

        $circuitBreaker = $this->createMock(CircuitBreakerService::class);
        $service = new PhoneVerificationService(
            $this->entityManager,
            $this->userRepository,
            $circuitBreaker
        );

        $user = $this->createUserMock(null);
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->entityManager->expects($this->once())->method('flush');

        $circuitBreaker->expects($this->once())
            ->method('execute')
            ->with($this->anything(), 'twilio_api', 3, 60, $this->anything())
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to send verification code');

        $service->sendVerificationCode($this->userId, $this->phoneNumber);

        putenv('TWILIO_ACCOUNT_SID');
        putenv('TWILIO_AUTH_TOKEN');
        putenv('TWILIO_PHONE_NUMBER');
    }

    // verifyPhoneNumber tests
    public function testVerifyPhoneNumberUserNotFound(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($this->userId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User not found');

        $this->service->verifyPhoneNumber($this->userId, '123456');
    }

    public function testVerifyPhoneNumberNoVerification(): void
    {
        $user = $this->createUserMock(null);
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No verification code found');

        $this->service->verifyPhoneNumber($this->userId, '123456');
    }

    public function testVerifyPhoneNumberNoCode(): void
    {
        $verification = $this->createVerificationMock(phoneCode: null);
        $user = $this->createUserMock($verification);
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No verification code found');

        $this->service->verifyPhoneNumber($this->userId, '123456');
    }

    public function testVerifyPhoneNumberExpired(): void
    {
        $verification = $this->createVerificationMock(
            phoneCode: password_hash('123456', PASSWORD_BCRYPT),
            expiresAt: (new \DateTime())->modify('-5 minutes')
        );
        $user = $this->createUserMock($verification);
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Verification code has expired');

        $this->service->verifyPhoneNumber($this->userId, '123456');
    }

    public function testVerifyPhoneNumberInvalidCodeAttemptsRemaining(): void
    {
        $hash = password_hash('correct', PASSWORD_BCRYPT);
        $verification = $this->createVerificationMock(
            phoneCode: $hash,
            attempts: 1
        );
        $verification->expects($this->once())
            ->method('setPhoneCodeAttempts')
            ->with(2)
            ->willReturnSelf();
        $verification->expects($this->never())->method('setPhoneCode');
        // getPhoneNumber not called in failure path

        $user = $this->createUserMock($verification);
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->entityManager->expects($this->once())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid code\. 1 attempts remaining/');

        $this->service->verifyPhoneNumber($this->userId, 'wrong');
    }

    public function testVerifyPhoneNumberInvalidCodeExhausted(): void
    {
        $hash = password_hash('correct', PASSWORD_BCRYPT);
        $verification = $this->createVerificationMock(
            phoneCode: $hash,
            attempts: 2
        );
        $verification->expects($this->exactly(2))
            ->method('setPhoneCodeAttempts')
            ->with($this->anything())
            ->willReturnSelf();
        $verification->expects($this->once())
            ->method('setPhoneCode')
            ->with(null)
            ->willReturnSelf();
        $verification->expects($this->never())->method('getPhoneNumber');

        $user = $this->createUserMock($verification);
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->entityManager->expects($this->once())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Too many failed attempts. Please request a new code');

        $this->service->verifyPhoneNumber($this->userId, 'wrong');
    }

    public function testVerifyPhoneNumberSuccess(): void
    {
        $correctCode = '123456';
        $hash = password_hash($correctCode, PASSWORD_BCRYPT);
        $verification = $this->createVerificationMock(
            phoneCode: $hash,
            phone: $this->phoneNumber
        );
        $verification->expects($this->once())
            ->method('setPhoneCode')
            ->with(null)
            ->willReturnSelf();
        $verification->expects($this->once())
            ->method('setPhoneCodeAttempts')
            ->with(0)
            ->willReturnSelf();
        $verification->expects($this->once())
            ->method('setPhoneCodeExpiresAt')
            ->with(null)
            ->willReturnSelf();

        $user = $this->createUserMock($verification);
        $user->expects($this->once())
            ->method('setPhoneNumber')
            ->with($this->phoneNumber)
            ->willReturnSelf();
        $user->expects($this->once())
            ->method('setIsPhoneVerified')
            ->with(true)
            ->willReturnSelf();

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->verifyPhoneNumber($this->userId, $correctCode);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertSame('Phone number verified successfully', $result['message']);
        $expectedMask = substr($this->phoneNumber, 0, -6) . '******' . substr($this->phoneNumber, -2);
        $this->assertSame($expectedMask, $result['phone_number']);
    }

    // Helper tests using reflection
    public function testFormatPhoneNumberWithPlus(): void
    {
        $method = new \ReflectionMethod($this->service, 'formatPhoneNumber');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, '+84912345678');
        $this->assertSame('+84912345678', $result);
    }

    public function testFormatPhoneNumberViettel(): void
    {
        $method = new \ReflectionMethod($this->service, 'formatPhoneNumber');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, '0912345678');
        $this->assertSame('+84912345678', $result);
    }

    public function testMaskPhoneNumber(): void
    {
        $method = new \ReflectionMethod($this->service, 'maskPhoneNumber');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, '+84912345678');
        $this->assertSame('+84912******78', $result);
    }

    public function testGenerateVerificationCodeFormat(): void
    {
        $method = new \ReflectionMethod($this->service, 'generateVerificationCode');
        $method->setAccessible(true);
        $code = $method->invoke($this->service);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }
}
