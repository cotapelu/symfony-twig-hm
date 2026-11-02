<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\BiometricEncryptionService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BiometricEncryptionServiceTest extends TestCase
{
    private BiometricEncryptionService $service;

    protected function setUp(): void
    {
        // Use a deterministic key for testing
        putenv('BIOMETRIC_ENCRYPTION_KEY=test123456789012345678901234567890');
        $this->service = new BiometricEncryptionService();
    }

    public function testEncryptAndDecryptRoundTrip(): void
    {
        $plaintext = 'Sensitive biometric data';
        $encrypted = $this->service->encrypt($plaintext);
        $this->assertIsString($encrypted);
        $this->assertNotEmpty($encrypted);

        $decrypted = $this->service->decrypt($encrypted);
        $this->assertSame($plaintext, $decrypted);
    }

    public function testEncryptProducesDifferentCiphertextsDueToIV(): void
    {
        $plaintext = 'same plaintext';
        $enc1 = $this->service->encrypt($plaintext);
        $enc2 = $this->service->encrypt($plaintext);
        $this->assertNotSame($enc1, $enc2);
        $this->assertSame($plaintext, $this->service->decrypt($enc1));
        $this->assertSame($plaintext, $this->service->decrypt($enc2));
    }

    public function testEncryptNullReturnsNull(): void
    {
        $this->assertNull($this->service->encrypt(null));
    }

    public function testDecryptNullReturnsNull(): void
    {
        $this->assertNull($this->service->decrypt(null));
    }

    public function testDecryptInvalidBase64ReturnsNull(): void
    {
        $this->assertNull($this->service->decrypt('not-base64'));
    }

    public function testDecryptCorruptedCiphertextReturnsNull(): void
    {
        $plaintext = 'data';
        $encrypted = $this->service->encrypt($plaintext);
        // Corrupt last character
        $corrupted = substr($encrypted, 0, -1) . 'x';
        $this->assertNull($this->service->decrypt($corrupted));
    }
}
