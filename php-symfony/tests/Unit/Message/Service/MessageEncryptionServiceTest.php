<?php declare(strict_types=1);

namespace App\Tests\Unit\Message\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Message\Service\MessageEncryptionService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MessageEncryptionServiceTest extends TestCase
{
    private MessageEncryptionService $service;
    private string $encryptionKey = 'test-secret-key-32-bytes-long';

    protected function setUp(): void
    {
        $this->service = new MessageEncryptionService($this->encryptionKey);
    }

    public function testConstructorThrowsIfNoKeyProvidedAndEnvMissing(): void
    {
        putenv('MESSAGE_ENCRYPTION_KEY=');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MESSAGE_ENCRYPTION_KEY environment variable must be set for message encryption');
        new MessageEncryptionService();
    }

    public function testEncryptThrowsIfMessageEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message and user ID are required');
        $this->service->encryptMessage('', 'user123');
    }

    public function testEncryptThrowsIfUserIdEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->encryptMessage('hello', '');
    }

    public function testEncryptReturnsNonEmptyString(): void
    {
        $message = 'Secret message';
        $userId = 'user123';
        $encrypted = $this->service->encryptMessage($message, $userId);
        $this->assertIsString($encrypted);
        $this->assertNotEmpty($encrypted);
        // Should be base64 encoded without null bytes
        $this->assertFalse(str_contains($encrypted, "\0"));
    }

    public function testEncryptDecryptRoundTrip(): void
    {
        $message = 'Hello, this is a secret!';
        $userId = 'user456';
        $encrypted = $this->service->encryptMessage($message, $userId);
        $decrypted = $this->service->decryptMessage($encrypted, $userId);
        $this->assertSame($message, $decrypted);
    }

    public function testEncryptProducesDifferentCiphertextsEachTime(): void
    {
        $message = 'Same message';
        $userId = 'user789';
        $encrypted1 = $this->service->encryptMessage($message, $userId);
        $encrypted2 = $this->service->encryptMessage($message, $userId);
        $this->assertNotSame($encrypted1, $encrypted2);
    }

    public function testDecryptThrowsIfEncryptedMessageEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->decryptMessage('', 'user123');
    }

    public function testDecryptThrowsIfUserIdEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->decryptMessage('something', '');
    }

    public function testDecryptThrowsIfInvalidBase64(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid encrypted message format');
        $this->service->decryptMessage('invalid-base64!!!', 'user123');
    }

    public function testDecryptThrowsIfDataTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid encrypted message format');
        // base64 of a short string < 16 bytes after decode
        $this->service->decryptMessage(base64_encode('short'), 'user123');
    }

    public function testDecryptFailsWithWrongUser(): void
    {
        $message = 'Test';
        $encrypted = $this->service->encryptMessage($message, 'userA');
        // Decrypt with different userId (different derived key) should fail
        $this->expectException(\RuntimeException::class);
        $this->service->decryptMessage($encrypted, 'userB');
    }

    public function testDecryptFailsWithCorruptedCiphertext(): void
    {
        $message = 'Original';
        $encrypted = $this->service->encryptMessage($message, 'userXYZ');
        $decoded = base64_decode($encrypted);
        // flip a bit in the middle (after IV)
        $mid = (int)(strlen($decoded) / 2);
        $decoded[$mid] = chr(ord($decoded[$mid]) ^ 0xFF);
        $tampered = base64_encode($decoded);
        $this->expectException(\RuntimeException::class);
        $this->service->decryptMessage($tampered, 'userXYZ');
    }

    public function testGenerateKeyPairReturnsArrayWithKeys(): void
    {
        $keyPair = $this->service->generateKeyPair();
        $this->assertIsArray($keyPair);
        $this->assertArrayHasKey('private_key', $keyPair);
        $this->assertArrayHasKey('public_key', $keyPair);
        $this->assertIsString($keyPair['private_key']);
        $this->assertIsString($keyPair['public_key']);
        $this->assertNotEmpty($keyPair['private_key']);
        $this->assertNotEmpty($keyPair['public_key']);
    }
}
