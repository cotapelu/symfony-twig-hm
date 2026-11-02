<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\EncryptionService;
use PHPUnit\Framework\TestCase;
use SodiumException;

#[AllowMockObjectsWithoutExpectations]
class EncryptionServiceTest extends TestCase
{
    private EncryptionService $service;

    protected function setUp(): void
    {
        $this->service = new EncryptionService('test-secret-key');
    }

    public function testEncryptDecryptRoundTrip(): void
    {
        $plaintext = 'Hello, World! 123';
        $ciphertext = $this->service->encrypt($plaintext);
        $this->assertIsString($ciphertext);
        $this->assertNotEmpty($ciphertext);
        $this->assertNotSame($plaintext, $ciphertext);

        $decrypted = $this->service->decrypt($ciphertext);
        $this->assertSame($plaintext, $decrypted);
    }

    public function testEncryptProducesDifferentCiphertextsDueToIV(): void
    {
        $plaintext = 'same plaintext';
        $ciphertext1 = $this->service->encrypt($plaintext);
        $ciphertext2 = $this->service->encrypt($plaintext);
        $this->assertNotSame($ciphertext1, $ciphertext2);

        // But both decrypt to same plaintext
        $this->assertSame($plaintext, $this->service->decrypt($ciphertext1));
        $this->assertSame($plaintext, $this->service->decrypt($ciphertext2));
    }

    public function testDecryptWithInvalidBase64ThrowsSodiumException(): void
    {
        $this->expectException(SodiumException::class);
        $this->service->decrypt('not-valid-base64!@#');
    }

    public function testDecryptWithShortCiphertextThrowsSodiumException(): void
    {
        $this->expectException(SodiumException::class);
        $this->service->decrypt(base64_encode('short')); // too short for IV
    }

    public function testDecryptWithWrongKeyThrowsSodiumException(): void
    {
        $plaintext = 'secret';
        $ciphertext = $this->service->encrypt($plaintext);

        $otherService = new EncryptionService('different-key');
        $this->expectException(SodiumException::class);
        $otherService->decrypt($ciphertext);
    }

    public function testEncryptWithEmptyPlaintext(): void
    {
        $plaintext = '';
        $ciphertext = $this->service->encrypt($plaintext);
        $decrypted = $this->service->decrypt($ciphertext);
        $this->assertSame($plaintext, $decrypted);
    }

    public function testEncryptWithSpecialCharacters(): void
    {
        $plaintext = " Unicode: 你好 🌟 \n\t\"'\\/";
        $ciphertext = $this->service->encrypt($plaintext);
        $decrypted = $this->service->decrypt($ciphertext);
        $this->assertSame($plaintext, $decrypted);
    }
}
