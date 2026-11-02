<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\User;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\TOTPService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TOTPServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserRepositoryInterface&MockObject $userRepository;
    private TOTPService $service;
    private User $user;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->service = new TOTPService($this->entityManager, $this->userRepository);
        $this->user = new User('user_123', 'test@example.com');
    }

    public function testGenerateSecret(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with('user_123')
            ->willReturn($this->user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $secret = $this->service->generateSecret('user_123');

        // 16 bytes -> 32 hex characters
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $secret);
        $this->assertEquals($secret, $this->user->getTotpSecret());
    }

    public function testGenerateBackupCodes(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with('user_123')
            ->willReturn($this->user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $codes = $this->service->generateBackupCodes('user_123');

        $this->assertCount(10, $codes);
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^\d{10}$/', $code);
        }
        $this->assertEquals($codes, $this->user->getTotpBackupCodes());
    }

    public function testVerifyCodeSuccessWithBackupCode(): void
    {
        $backupCode = '1234567890';
        $this->user->setTotpBackupCodes([$backupCode]);
        $this->user->setTotpAttempts(0);
        // Set a non-empty secret; otherwise verifyCode returns false before backup check (current logic)
        $this->user->setTotpSecret('DUMMYSECRET123456'); // base32-like
        $this->userRepository->method('findById')->willReturn($this->user);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->verifyCode('user_123', $backupCode);

        $this->assertTrue($result);
        $this->assertEmpty($this->user->getTotpBackupCodes());
    }

    public function testVerifyCodeSuccessWithTOTP(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP'; // 16-char base32 secret
        $this->user->setTotpSecret($secret);
        $this->user->setTotpAttempts(0);
        $this->userRepository->method('findById')->willReturn($this->user);
        $this->entityManager->expects($this->once())->method('flush');

        // Compute expected TOTP using the service's private method
        $ref = new \ReflectionClass($this->service);
        $generateTOTP = $ref->getMethod('generateTOTP');
        $generateTOTP->setAccessible(true);
        $expectedCode = $generateTOTP->invoke($this->service, $secret);

        $result = $this->service->verifyCode('user_123', $expectedCode);

        $this->assertTrue($result);
        $this->assertEquals(0, $this->user->getTotpAttempts());
    }

    public function testVerifyCodeFailureWrongCodeIncrementsAttempts(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $this->user->setTotpSecret($secret);
        $this->user->setTotpAttempts(0);
        $this->userRepository->method('findById')->willReturn($this->user);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->verifyCode('user_123', '000000');

        $this->assertFalse($result);
        $this->assertEquals(1, $this->user->getTotpAttempts());
    }

    public function testVerifyCodeThrowsWhenAttemptsExceeded(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $this->user->setTotpSecret($secret);
        $this->user->setTotpAttempts(5); // at max
        $this->userRepository->method('findById')->willReturn($this->user);
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Too many verification attempts');

        $this->service->verifyCode('user_123', 'anycode');
    }

    public function testVerifyCodeReturnsFalseIfNoSecret(): void
    {
        $this->user->setTotpSecret(null);
        $this->userRepository->method('findById')->willReturn($this->user);
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->verifyCode('user_123', '123456');

        $this->assertFalse($result);
    }

    public function testVerifyCodeReturnsFalseIfUserNotFound(): void
    {
        $this->userRepository->method('findById')
            ->with('nonexistent')
            ->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->verifyCode('nonexistent', '123456');

        $this->assertFalse($result);
    }

    public function testEnableTOTPSuccess(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $this->user->setTotpSecret($secret);
        $this->user->setTotpAttempts(0);
        $this->userRepository->method('findById')->willReturn($this->user);
        // verifyCode will flush once; then enableTOTP will flush again
        $this->entityManager->expects($this->exactly(2))->method('flush');

        // Compute expected code
        $ref = new \ReflectionClass($this->service);
        $generateTOTP = $ref->getMethod('generateTOTP');
        $generateTOTP->setAccessible(true);
        $expectedCode = $generateTOTP->invoke($this->service, $secret);

        $result = $this->service->enableTOTP('user_123', $expectedCode);

        $this->assertTrue($result);
        $this->assertTrue($this->user->isTotpEnabled());
    }

    public function testEnableTOTPFailsWithInvalidCode(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $this->user->setTotpSecret($secret);
        $this->user->setTotpAttempts(0);
        $this->userRepository->method('findById')->willReturn($this->user);
        // verifyCode will flush once on failure (attempts increment)
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->enableTOTP('user_123', 'wrongcode');

        $this->assertFalse($result);
        // TOTP not enabled
        $this->assertFalse($this->user->isTotpEnabled());
    }

    public function testDisableTOTP(): void
    {
        $this->user->setTotpSecret('secret');
        $this->user->setTotpEnabled(true);
        $this->user->setTotpBackupCodes(['1234567890']);
        $this->userRepository->method('findById')->willReturn($this->user);
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->disableTOTP('user_123');

        $this->assertNull($this->user->getTotpSecret());
        $this->assertFalse($this->user->isTotpEnabled());
        $this->assertEmpty($this->user->getTotpBackupCodes());
    }

    public function testGetQRCodeUrlFormat(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $accountName = 'test@example.com';

        $url = $this->service->getQRCodeUrl($secret, $accountName);

        $this->assertStringStartsWith('otpauth://totp/', $url);
        // Both label and issuer are URL-encoded
        $this->assertStringContainsString('QCoder%20Platform', $url);
        $this->assertStringContainsString(rawurlencode($accountName), $url);
        $this->assertStringContainsString($secret, $url);
    }

    public function testGenerateCodeFormat(): void
    {
        $ref = new \ReflectionClass($this->service);
        $method = $ref->getMethod('generateCode');
        $method->setAccessible(true);
        for ($i = 0; $i < 10; $i++) {
            $code = $method->invoke($this->service, 6);
            $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        }
    }
}
