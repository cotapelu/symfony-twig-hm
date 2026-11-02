<?php

declare(strict_types=1);

namespace App\Shared\Service;

final class BiometricEncryptionService
{
    private string $encryptionKey;
    private string $cipherMethod = 'aes-256-cbc';

    public function __construct(?string $encryptionKey = null)
    {
        $this->encryptionKey = $encryptionKey ?? getenv('BIOMETRIC_ENCRYPTION_KEY') ?? $this->generateKey();
        $this->validateKey();
    }

    public function encrypt(?string $data)
    {
        if (null === $data) {
            return null;
        }

        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, $this->cipherMethod, $this->encryptionKey, 0, $iv);

        if ($encrypted === false) {
            return null;
        }

        return base64_encode($iv . $encrypted);
    }

    public function decrypt(?string $encryptedData)
    {
        if (null === $encryptedData) {
            return null;
        }

        $decoded = base64_decode($encryptedData, true);
        if ($decoded === false) {
            return null;
        }

        $iv = mb_substr($decoded, 0, 16, '8bit');
        $encrypted = mb_substr($decoded, 16, null, '8bit');

        $decrypted = openssl_decrypt($encrypted, $this->cipherMethod, $this->encryptionKey, 0, $iv);

        return $decrypted ?: null;
    }

    private function generateKey()
    {
        return bin2hex(random_bytes(32));
    }

    private function validateKey()
    {
        if (empty($this->encryptionKey)) {
            throw new \RuntimeException('Encryption key cannot be empty');
        }
    }
}
