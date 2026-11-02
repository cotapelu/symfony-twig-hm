<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\User;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\BiometricEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BiometricEncryptionServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserRepositoryInterface&MockObject $userRepository;
    private BiometricEncryptionService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->service = new BiometricEncryptionService(
            $this->entityManager,
            $this->userRepository
        );
    }

    private function createUserWithPassword(string $userId = 'user_123', string $email = 'test@example.com', ?string $password = null): User
    {
        $user = new User($userId, $email);
        if ($password === null) {
            $password = str_repeat('a', 32); // 32-byte key for AES-256
        }
        $user->setPassword($password);
        return $user;
    }

    public function testGenerateKeyPairSuccess(): void
    {
        $userId = 'user_123';
        $user = $this->createUserWithPassword($userId);

        $this->userRepository->expects($this->atLeastOnce())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->service->generateKeyPair($userId);

        $this->assertArrayHasKey('public_key', $result);
        $this->assertArrayHasKey('version', $result);
        $this->assertEquals(1, $result['version']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['public_key']);

        $biometricData = json_decode($user->getBiometricData(), true);
        $this->assertIsArray($biometricData);
        $this->assertEquals($result['public_key'], $biometricData['public_key']);
        $this->assertArrayHasKey('private_key_encrypted', $biometricData);
    }

    public function testGenerateKeyPairUserNotFound(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with('unknown')
            ->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User not found');

        $this->service->generateKeyPair('unknown');
    }

    public function testEncryptMessageSuccess(): void
    {
        $message = 'Hello, World!';
        $publicKeyHex = $this->generateTestPublicKeyHex();

        $encrypted = $this->service->encryptMessage($message, $publicKeyHex);

        $this->assertIsString($encrypted);
        $this->assertNotEmpty($encrypted);
        $this->assertGreaterThan(0, strlen(base64_decode($encrypted, true)));
    }

    public function testEncryptMessageInvalidHex(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid recipient public key format');

        $this->service->encryptMessage('test', 'invalid-hex');
    }

    public function testEncryptMessageInvalidLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid recipient public key length');

        $this->service->encryptMessage('test', 'abcd1234'); // too short
    }

    public function testDecryptMessageSuccess(): void
    {
        $userId = 'user_123';
        $user = $this->createUserWithPassword($userId);

        // Setup repository to be called multiple times
        $this->userRepository->expects($this->atLeastOnce())
            ->method('findById')
            ->willReturn($user);
        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');

        // Generate keys
        $keyPair = $this->service->generateKeyPair($userId);

        // Encrypt and decrypt
        $message = 'Secret message';
        $encrypted = $this->service->encryptMessage($message, $keyPair['public_key']);
        $decrypted = $this->service->decryptMessage($encrypted, $userId);

        $this->assertEquals($message, $decrypted);
    }

    public function testDecryptMessageUserNotFound(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with('unknown')
            ->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User not found');

        $this->service->decryptMessage('encrypted', 'unknown');
    }

    public function testDecryptMessageMissingKeys(): void
    {
        $userId = 'user_123';
        $user = new User($userId, 'test@example.com');
        $user->setBiometricData(null);

        $this->userRepository->expects($this->atLeastOnce())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Encryption keys not found');

        $this->service->decryptMessage('encrypted', $userId);
    }

    public function testDecryptMessageMalformedBiometricData(): void
    {
        $userId = 'user_123';
        $user = new User($userId, 'test@example.com');
        $user->setBiometricData('not json');

        $this->userRepository->expects($this->atLeastOnce())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->expectException(\Exception::class);
            $this->expectExceptionMessage('Encryption keys not found');

        $this->service->decryptMessage('encrypted', $userId);
    }

    public function testDecryptMessageInvalidBase64(): void
    {
        $userId = 'user_123';
        $user = $this->createUserWithPassword($userId);

        $this->userRepository->expects($this->atLeastOnce())
            ->method('findById')
            ->willReturn($user);
        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');

        // Generate keys first
        $this->service->generateKeyPair($userId);

        // Attempt to decrypt invalid base64
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid encrypted message format');

        $this->service->decryptMessage('invalid-base64!@#', $userId);
    }

    public function testRotateKeysSuccess(): void
    {
        $userId = 'user_123';
        $user = $this->createUserWithPassword($userId);

        $this->userRepository->expects($this->atLeastOnce())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->service->rotateKeys($userId);

        $this->assertArrayHasKey('public_key', $result);
        $this->assertEquals(1, $result['version']);
    }

    public function testGetPublicKeySuccess(): void
    {
        $userId = 'user_123';
        $publicKey = 'abcd1234'.str_repeat('0', 60);
        $user = new User($userId, 'test@example.com');
        $user->setBiometricData(json_encode(['public_key' => $publicKey]));

        $this->userRepository->expects($this->atLeastOnce())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $result = $this->service->getPublicKey($userId);

        $this->assertEquals($publicKey, $result);
    }

    public function testGetPublicKeyUserNotFound(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with('unknown')
            ->willReturn(null);

        $result = $this->service->getPublicKey('unknown');

        $this->assertNull($result);
    }

    public function testGetPublicKeyNoBiometricData(): void
    {
        $userId = 'user_123';
        $user = new User($userId, 'test@example.com');
        $user->setBiometricData(null);

        $this->userRepository->expects($this->atLeastOnce())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $result = $this->service->getPublicKey($userId);

        $this->assertNull($result);
    }

    public function testGetPublicKeyMissingPublicKeyField(): void
    {
        $userId = 'user_123';
        $user = new User($userId, 'test@example.com');
        $user->setBiometricData(json_encode(['private_key_encrypted' => 'xxx']));

        $this->userRepository->expects($this->atLeastOnce())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $result = $this->service->getPublicKey($userId);

        $this->assertNull($result);
    }

    public function testHasEncryptionEnabledTrue(): void
    {
        $userId = 'user_123';
        $user = new User($userId, 'test@example.com');
        $user->setBiometricData(json_encode(['public_key' => 'abcd1234']));

        $this->userRepository->expects($this->atLeastOnce())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $result = $this->service->hasEncryptionEnabled($userId);
        $this->assertTrue($result);
    }

    public function testHasEncryptionEnabledFalse(): void
    {
        $userId = 'user_123';
        $user = new User($userId, 'test@example.com');
        $user->setBiometricData(null);

        $this->userRepository->expects($this->atLeastOnce())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $result = $this->service->hasEncryptionEnabled($userId);
        $this->assertFalse($result);
    }

    private function generateTestPublicKeyHex(): string
    {
        $keyPair = sodium_crypto_box_keypair();
        $publicKey = sodium_crypto_box_publickey($keyPair);
        $hex = sodium_bin2hex($publicKey);
        sodium_memzero($keyPair);
        return $hex;
    }
}
