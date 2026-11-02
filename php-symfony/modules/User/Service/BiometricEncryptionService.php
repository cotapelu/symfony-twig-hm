<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;
use App\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class BiometricEncryptionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function generateKeyPair(string $userId)
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        $keyPair = sodium_crypto_box_keypair();
        $publicKey = sodium_crypto_box_publickey($keyPair);
        $secretKey = sodium_crypto_box_secretkey($keyPair);

        $publicKeyHex = sodium_bin2hex($publicKey);
        $privateKey = $this->encryptPrivateKey($secretKey, $userId);

        $user->setBiometricData(json_encode([
            'public_key' => $publicKeyHex,
            'private_key_encrypted' => $privateKey,
            'key_pair_version' => 1,
        ]));

        $this->entityManager->flush();

        sodium_memzero($secretKey);

        return [
            'public_key' => $publicKeyHex,
            'version' => 1,
        ];
    }

    public function encryptMessage(string $message, string $recipientPublicKey)
    {
        $recipientKeyHex = $this->hex2bin($recipientPublicKey);
        if ($recipientKeyHex === false) {
            throw new \InvalidArgumentException('Invalid recipient public key format');
        }
        if (strlen($recipientKeyHex) !== 32) {
            throw new \InvalidArgumentException('Invalid recipient public key length');
        }

        $ciphertext = sodium_crypto_box_seal($message, $recipientKeyHex);

        return $this->bin2base64($ciphertext);
    }

    public function decryptMessage(string $encryptedMessage, string $userId)
    {
        $biometricData = $this->loadBiometricData($userId);

        $privateKeySecret = $this->decryptPrivateKey($biometricData['private_key_encrypted'], $userId);
        $publicKey = $this->extractPublicKey($biometricData['public_key']);

        $keypair = $this->constructKeypair($privateKeySecret, $publicKey);
        $ciphertext = $this->decodeCiphertext($encryptedMessage);
        $decrypted = sodium_crypto_box_seal_open($ciphertext, $keypair);

        sodium_memzero($keypair);
        sodium_memzero($ciphertext);

        if ($decrypted === false) {
            throw new \Exception('Failed to decrypt message');
        }

        return $decrypted;
    }

    private function loadBiometricData(string $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }
        $biometricJson = $user->getBiometricData();
        if ($biometricJson === null) {
            throw new \Exception('Encryption keys not found');
        }
        $biometricData = json_decode($biometricJson, true);
        if (!is_array($biometricData) || !isset($biometricData['private_key_encrypted'])) {
            throw new \Exception('Encryption keys not found');
        }
        return $biometricData;
    }

    private function extractPublicKey(?string $publicKeyHex): string
    {
        if ($publicKeyHex === null) {
            throw new \Exception('Public key missing');
        }
        $publicKey = $this->hex2bin($publicKeyHex);
        if ($publicKey === false) {
            throw new \Exception('Invalid public key format');
        }
        return $publicKey;
    }

    private function constructKeypair(string $privateKeySecret, string $publicKey)
    {
        $keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey($privateKeySecret, $publicKey);
        if ($keypair === false) {
            throw new \Exception('Failed to construct keypair');
        }
        return $keypair;
    }

    private function decodeCiphertext(string $encryptedMessage)
    {
        $ciphertext = $this->base642bin($encryptedMessage);
        if ($ciphertext === false) {
            throw new \Exception('Invalid encrypted message format');
        }
        return $ciphertext;
    }

    public function rotateKeys(string $userId)
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        return $this->generateKeyPair($userId);
    }

    public function getPublicKey(string $userId)
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return null;
        }

        $biometricJson = $user->getBiometricData();
        if ($biometricJson === null) {
            return null;
        }

        $biometricData = json_decode($biometricJson, true);
        if (!is_array($biometricData)) {
            return null;
        }

        return $biometricData['public_key'] ?? null;
    }

    public function hasEncryptionEnabled(string $userId)
    {
        return $this->getPublicKey($userId) !== null;
    }

    private function encryptPrivateKey(string $privateKey, string $userId): string
    {
        $passwordHash = $this->getUserPasswordHash($userId);
        // Derive a 32-byte symmetric key from password hash
        $key = hash('sha256', $passwordHash, true);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($privateKey, $nonce, $key);
        sodium_memzero($privateKey);
        // Store as base64(nonce . ciphertext)
        return $this->bin2base64($nonce . $ciphertext);
    }

    private function decryptPrivateKey(string $encryptedPrivateKey, string $userId): string
    {
        $passwordHash = $this->getUserPasswordHash($userId);
        $key = hash('sha256', $passwordHash, true);
        $decoded = base64_decode($encryptedPrivateKey, true);
        if ($decoded === false || strlen($decoded) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \Exception('Invalid encrypted private key format');
        }
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        if ($decrypted === false) {
            throw new \Exception('Failed to decrypt private key');
        }
        return $decrypted;
    }

    private function getUserPasswordHash(string $userId)
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        return $user->getPassword();
    }

    private function hex2bin(string $hex)
    {
        return @hex2bin($hex);
    }

    private function bin2base64(string $binary)
    {
        return base64_encode($binary);
    }

    private function base642bin(string $base64)
    {
        return base64_decode($base64, true);
    }
}
