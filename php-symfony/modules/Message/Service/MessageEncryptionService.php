<?php

declare(strict_types=1);

namespace App\Message\Service;

class MessageEncryptionService
{
    private string $encryptionKey;

    public function __construct(?string $encryptionKey = null)
    {
        $this->encryptionKey = $encryptionKey ?? getenv('MESSAGE_ENCRYPTION_KEY');
        if (!$this->encryptionKey) {
            throw new \RuntimeException('MESSAGE_ENCRYPTION_KEY environment variable must be set for message encryption');
        }
    }

    /**
     * Encrypt a message.
     */
    public function encryptMessage(string $message, string $userId)
    {
        if (empty($message) || empty($userId)) {
            throw new \InvalidArgumentException('Message and user ID are required');
        }

        $key = $this->deriveKey($userId);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($message, 'AES-256-CBC', $key, 0, $iv);

        if (false === $encrypted) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode($iv.$encrypted);
    }

    public function decryptMessage(string $encryptedMessage, string $userId)
    {
        if (empty($encryptedMessage) || empty($userId)) {
            throw new \InvalidArgumentException('Encrypted message and user ID are required');
        }

        $key = $this->deriveKey($userId);
        $data = base64_decode($encryptedMessage);

        if (strlen($data) < 16) {
            throw new \InvalidArgumentException('Invalid encrypted message format');
        }

        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);

        if (false === $decrypted) {
            // Log the error but don't reveal specific failure reason to prevent timing attacks
            error_log("Decryption failed for user {$userId}");
            throw new \RuntimeException('Decryption failed');
        }

        return $decrypted;
    }

    /**
     * Derive encryption key from user ID.
     */
    private function deriveKey(string $userId)
    {
        return hash_hmac('sha256', $userId, $this->encryptionKey, true);
    }

    /**
     * Generate key pair for end-to-end encryption.
     */
    public function generateKeyPair()
    {
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privateKey);

        $publicKey = openssl_pkey_get_details($res)['key'];

        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey,
        ];
    }

    /**
     * Encrypt with public key.
     */
    public function encryptWithPublicKey(string $data, string $publicKey)
    {
        openssl_public_encrypt($data, $encrypted, $publicKey);

        return base64_encode($encrypted);
    }

    /**
     * Decrypt with private key.
     */
    public function decryptWithPrivateKey(string $encryptedData, string $privateKey)
    {
        $data = base64_decode($encryptedData);
        openssl_private_decrypt($data, $decrypted, $privateKey);

        return $decrypted;
    }
}
