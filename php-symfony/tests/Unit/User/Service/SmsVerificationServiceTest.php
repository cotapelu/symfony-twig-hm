<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\UserVerification;
use App\User\Repository\UserVerificationRepositoryInterface;
use App\User\Service\SmsVerificationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Error;

#[AllowMockObjectsWithoutExpectations]
class SmsVerificationServiceTest extends TestCase
{
    private UserVerificationRepositoryInterface&MockObject $verificationRepository;
    private SmsVerificationService $service;

    protected function setUp(): void
    {
        $this->verificationRepository = $this->createMock(UserVerificationRepositoryInterface::class);
        $this->service = new SmsVerificationService($this->verificationRepository);
    }

    public function testSendVerificationCodeSuccess(): void
    {
        $phone = '+84912345678';
        $this->verificationRepository->expects($this->once())
            ->method('countRecentByPhone')
            ->with($phone, $this->anything()) // one hour ago
            ->willReturn(0);
        $this->verificationRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn(null);
        $this->verificationRepository->expects($this->once())
            ->method('save');

        $result = $this->service->sendVerificationCode($phone);

        $this->assertTrue($result['success']);
        $this->assertEquals('Verification code sent', $result['message']);
    }

    public function testSendVerificationCodeRateLimitExceeded(): void
    {
        $phone = '+84912345678';
        $this->verificationRepository->expects($this->once())
            ->method('countRecentByPhone')
            ->with($phone, $this->anything())
            ->willReturn(10); // >= limit
        $this->verificationRepository->expects($this->never())->method('findByPhoneNumber');
        $this->verificationRepository->expects($this->never())->method('save');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $this->service->sendVerificationCode($phone);
    }

    public function testSendVerificationCodeAlreadySentValid(): void
    {
        $phone = '+84912345678';
        $verification = new UserVerification('sms_' . bin2hex(random_bytes(8)));
        $verification->setPhoneNumber($phone);
        $verification->setPhoneCode('123456');
        $verification->setPhoneCodeExpiresAt((new \DateTime())->modify('+5 minutes'));

        $this->verificationRepository->expects($this->once())
            ->method('countRecentByPhone')
            ->willReturn(0);
        $this->verificationRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn($verification);
        $this->verificationRepository->expects($this->never())->method('save');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Verification code already sent');

        $this->service->sendVerificationCode($phone);
    }

    public function testVerifyCodeSuccess(): void
    {
        $phone = '+84912345678';
        $code = '123456';
        $userId = 'user_123';

        $verification = new UserVerification($userId);
        $verification->setPhoneNumber($phone);
        $verification->setPhoneCode($code);
        $verification->setPhoneCodeExpiresAt((new \DateTime())->modify('+5 minutes'));
        $verification->setPhoneCodeAttempts(0);

        $this->verificationRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn($verification);
        $this->verificationRepository->expects($this->once())
            ->method('save');

        $result = $this->service->verifyCode($phone, $code);

        $this->assertTrue($result);
        $this->assertNull($verification->getPhoneCode());
        $this->assertNotNull($verification->getPhoneVerifiedAt());
    }

    public function testVerifyCodeNoCodeFound(): void
    {
        $phone = '+84912345678';
        $this->verificationRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn(null);
        $this->verificationRepository->expects($this->never())->method('save');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No verification code found');

        $this->service->verifyCode($phone, '123456');
    }

    public function testVerifyCodeExpired(): void
    {
        $phone = '+84912345678';
        $code = '123456';
        $userId = 'user_123';

        $verification = new UserVerification($userId);
        $verification->setPhoneNumber($phone);
        $verification->setPhoneCode($code);
        $verification->setPhoneCodeExpiresAt((new \DateTime())->modify('-5 minutes')); // expired

        $this->verificationRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn($verification);
        $this->verificationRepository->expects($this->never())->method('save');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Verification code expired');

        $this->service->verifyCode($phone, $code);
    }

    public function testVerifyCodeTooManyAttempts(): void
    {
        $phone = '+84912345678';
        $code = '123456';
        $userId = 'user_123';

        $verification = new UserVerification($userId);
        $verification->setPhoneNumber($phone);
        $verification->setPhoneCode($code);
        $verification->setPhoneCodeExpiresAt((new \DateTime())->modify('+5 minutes'));
        $verification->setPhoneCodeAttempts(3); // at max

        $this->verificationRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn($verification);
        $this->verificationRepository->expects($this->once())
            ->method('save');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Too many failed attempts');

        $this->service->verifyCode($phone, $code);
    }

    public function testVerifyCodeInvalidCodeIncrementsAttempts(): void
    {
        $phone = '+84912345678';
        $wrongCode = 'wrong';
        $userId = 'user_123';

        $verification = new UserVerification($userId);
        $verification->setPhoneNumber($phone);
        $verification->setPhoneCode('correct');
        $verification->setPhoneCodeExpiresAt((new \DateTime())->modify('+5 minutes'));
        $verification->setPhoneCodeAttempts(0);

        $this->verificationRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn($verification);
        $this->verificationRepository->expects($this->once())
            ->method('save');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid verification code. 2 attempts remaining.');

        $this->service->verifyCode($phone, $wrongCode);

        $this->assertEquals(1, $verification->getPhoneCodeAttempts());
    }

    public function testIsPhoneVerifiedReturnsTrue(): void
    {
        $phone = '+84912345678';
        $userId = 'user_123';

        $verification = new UserVerification($userId);
        $verification->setPhoneNumber($phone);
        $verification->setPhoneVerifiedAt(new \DateTime());

        $this->verificationRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn($verification);

        $result = $this->service->isPhoneVerified($phone);
        $this->assertTrue($result);
    }

    public function testIsPhoneVerifiedReturnsFalseNoVerification(): void
    {
        $phone = '+84912345678';
        $this->verificationRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn(null);

        $result = $this->service->isPhoneVerified($phone);
        $this->assertFalse($result);
    }

    public function testIsPhoneVerifiedReturnsFalseNotVerified(): void
    {
        $phone = '+84912345678';
        $userId = 'user_123';
        $verification = new UserVerification($userId);
        $verification->setPhoneNumber($phone);
        $verification->setPhoneVerifiedAt(null);

        $this->verificationRepository->expects($this->once())
            ->method('findByPhoneNumber')
            ->with($phone)
            ->willReturn($verification);

        $result = $this->service->isPhoneVerified($phone);
        $this->assertFalse($result);
    }

    public function testGenerateSecureCodeLength(): void
    {
        $ref = new \ReflectionClass($this->service);
        $method = $ref->getMethod('generateSecureCode');
        $method->setAccessible(true);
        for ($i = 0; $i < 10; $i++) {
            $code = $method->invoke($this->service);
            $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        }
    }
}
