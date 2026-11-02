<?php

declare(strict_types=1);

namespace App\Shared\Service;

use SodiumException;

class EncryptionService
{
    private string $key;

    public function __construct(string $encryptionKey)
    {
        // Ensure key is 32 bytes for AES-256-GCM
        $this->key = hash('sha256', $encryptionKey, true);
    }

    /**
     * Encrypt plaintext string. Returns base64-encoded ciphertext with IV and auth tag.
     */
    public function encrypt(string $plaintext)
    {
        $iv = random_bytes(SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES);
        $ciphertext = sodium_crypto_aead_aes256gcm_encrypt(
            $plaintext,
            '',
            $iv,
            $this->key
        );
        // Store IV + ciphertext (which includes tag) as base64
        return base64_encode($iv . $ciphertext);
    }

    /**
     * Decrypt base64-encoded ciphertext (IV + ciphertext).
     * Returns plaintext string or throws SodiumException on failure.
     */
    public function decrypt(string $ciphertextBase64)
    {
        $data = base64_decode($ciphertextBase64, true);
        if (false === $data) {
            throw new SodiumException('Invalid base64 ciphertext');
        }
        $ivLen = SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES;
        if (strlen($data) < $ivLen) {
            throw new SodiumException('Ciphertext too short');
        }
        $iv = substr($data, 0, $ivLen);
        $ciphertext = substr($data, $ivLen);
        $plaintext = sodium_crypto_aead_aes256gcm_decrypt(
            $ciphertext,
            '',
            $iv,
            $this->key
        );
        if (false === $plaintext) {
            throw new SodiumException('Decryption failed - invalid key or corrupted data');
        }
        return $plaintext;
    }
}
