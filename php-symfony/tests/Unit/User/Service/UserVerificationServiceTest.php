<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\User;
use App\User\Mailer\UserMailerInterface;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\UserVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserVerificationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserRepositoryInterface&MockObject $userRepository;
    private UserMailerInterface&MockObject $mailer;
    private UserVerificationService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->mailer = $this->createMock(UserMailerInterface::class);
        $this->service = new UserVerificationService(
            $this->entityManager,
            $this->userRepository,
            $this->mailer
        );
    }

    private function createUser(string $userId = 'user_123', string $email = 'test@example.com'): User
    {
        $user = new User($userId, $email);
        return $user;
    }

    public function testSendEmailVerification(): void
    {
        $user = $this->createUser();

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->mailer->expects($this->once())
            ->method('sendVerificationEmail')
            ->with($user, $this->callback(function (string $token) {
                return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
            }));

        $this->service->sendEmailVerification($user);

        // Verify token and expiry set via getters
        $this->assertNotNull($user->getVerificationToken());
        $this->assertNotNull($user->getVerificationTokenExpiresAt());
    }

    public function testVerifyEmailSuccess(): void
    {
        $token = 'abc123'.bin2hex(random_bytes(16));
        $expires = (new \DateTime())->modify('+1 hour');

        $user = $this->createUser();
        $user->setVerificationToken($token);
        $user->setVerificationTokenExpiresAt($expires);

        $this->userRepository->expects($this->once())
            ->method('findByVerificationToken')
            ->with($token)
            ->willReturn($user);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->service->verifyEmail($token);

        $this->assertTrue($result);
        $this->assertNotNull($user->getEmailVerifiedAt());
        $this->assertNull($user->getVerificationToken());
        $this->assertEquals(0, $user->getVerificationLevel());
    }

    public function testVerifyEmailNotFound(): void
    {
        $token = 'nonexistent';
        $this->userRepository->expects($this->once())
            ->method('findByVerificationToken')
            ->with($token)
            ->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->verifyEmail($token);
        $this->assertFalse($result);
    }

    public function testVerifyEmailExpired(): void
    {
        $token = 'expired'.bin2hex(random_bytes(16));
        $expires = (new \DateTime())->modify('-1 hour');

        $user = $this->createUser();
        $user->setVerificationToken($token);
        $user->setVerificationTokenExpiresAt($expires);

        $this->userRepository->expects($this->once())
            ->method('findByVerificationToken')
            ->with($token)
            ->willReturn($user);
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->verifyEmail($token);
        $this->assertFalse($result);
    }

    public function testSendPhoneVerification(): void
    {
        $user = $this->createUser();

        $this->entityManager->expects($this->once())
            ->method('flush');

        $code = $this->service->sendPhoneVerification($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertEquals($code, $user->getVerificationToken());
        $this->assertNotNull($user->getVerificationTokenExpiresAt());
    }

    public function testVerifyPhoneSuccess(): void
    {
        $phone = '+84912345678';
        $code = '123456';
        $expires = (new \DateTime())->modify('+10 minutes');

        $user = $this->createUser();
        $user->setPhoneNumber($phone);
        $user->setVerificationToken($code);
        $user->setVerificationTokenExpiresAt($expires);

        $this->userRepository->expects($this->once())
            ->method('findByPhoneAndVerification')
            ->with($phone, $code)
            ->willReturn($user);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->service->verifyPhone($phone, $code);

        $this->assertTrue($result);
        $this->assertNotNull($user->getPhoneVerifiedAt());
        $this->assertNull($user->getVerificationToken());
        $this->assertEquals(0, $user->getVerificationLevel());
    }

    public function testVerifyPhoneFailureWrongCode(): void
    {
        $phone = '+84912345678';
        $code = '123456';

        $this->userRepository->expects($this->once())
            ->method('findByPhoneAndVerification')
            ->with($phone, $code)
            ->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->verifyPhone($phone, $code);
        $this->assertFalse($result);
    }

    public function testVerifyPhoneFailureExpired(): void
    {
        $phone = '+84912345678';
        $code = '123456';
        $expires = (new \DateTime())->modify('-1 hour');

        $user = $this->createUser();
        $user->setPhoneNumber($phone);
        $user->setVerificationToken($code);
        $user->setVerificationTokenExpiresAt($expires);

        $this->userRepository->expects($this->once())
            ->method('findByPhoneAndVerification')
            ->with($phone, $code)
            ->willReturn($user);
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->verifyPhone($phone, $code);
        $this->assertFalse($result);
    }

    public function testRegisterBiometric(): void
    {
        $user = $this->createUser();
        $biometricData = 'base64encodeddata';

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->registerBiometric($user, $biometricData);

        $this->assertEquals($biometricData, $user->getBiometricData());
        $this->assertNotNull($user->getBiometricVerifiedAt());
        $this->assertEquals(0, $user->getVerificationLevel());
    }

    public function testVerifyBiometricSuccess(): void
    {
        $email = 'test@example.com';
        $biometricData = 'base64data';
        $user = $this->createUser();
        $user->setBiometricData($biometricData);
        $user->setBiometricVerifiedAt(new \DateTime());

        $this->userRepository->expects($this->once())
            ->method('findByEmailAndBiometric')
            ->with($email, $biometricData)
            ->willReturn($user);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->service->verifyBiometric($email, $biometricData);

        $this->assertSame($user, $result);
        $this->assertNotNull($user->getLastLoginAt());
    }

    public function testVerifyBiometricFailureNotFound(): void
    {
        $email = 'test@example.com';
        $biometricData = 'wrong';

        $this->userRepository->expects($this->once())
            ->method('findByEmailAndBiometric')
            ->with($email, $biometricData)
            ->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->verifyBiometric($email, $biometricData);
        $this->assertNull($result);
    }

    public function testVerifyBiometricFailureNotVerified(): void
    {
        $email = 'test@example.com';
        $biometricData = 'base64data';
        $user = $this->createUser();
        $user->setBiometricData($biometricData);
        // Not setting biometricVerifiedAt

        $this->userRepository->expects($this->once())
            ->method('findByEmailAndBiometric')
            ->with($email, $biometricData)
            ->willReturn($user);
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->verifyBiometric($email, $biometricData);
        $this->assertNull($result);
    }

    public function testGetVerificationStatus(): void
    {
        $user = $this->createUser();

        // Use setters to set verification timestamps
        $user->setEmailVerifiedAt(new \DateTime());
        $user->setPhoneVerifiedAt(new \DateTime());
        $user->setBiometricVerifiedAt(new \DateTime());
        $user->setVerificationLevel('biometric'); // will be cast to int 0 via getVerificationLevel()

        $status = $this->service->getVerificationStatus($user);

        $this->assertTrue($status['email_verified']);
        $this->assertTrue($status['phone_verified']);
        $this->assertTrue($status['biometric_verified']);
        $this->assertEquals(0, $status['level']);
    }
}
