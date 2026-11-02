<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class TOTPService
{
    private const SECRET_LENGTH = 32;
    private const CODE_VALIDITY = 30;
    private const BACKUP_CODES = 10;
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function generateSecret(string $userId)
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        $secret = bin2hex(random_bytes($this::SECRET_LENGTH / 2));

        $user->setTotpSecret($secret);

        $this->entityManager->flush();

        return $secret;
    }

    public function generateBackupCodes(string $userId)
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        $backupCodes = [];
        for ($i = 0; $i < $this::BACKUP_CODES; $i++) {
            $backupCodes[] = $this->generateCode(10);
        }

        $user->setTotpBackupCodes($backupCodes);

        $this->entityManager->flush();

        return $backupCodes;
    }

    public function verifyCode(string $userId, string $code)
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return false;
        }

        $secret = $user->getTotpSecret();
        if (!$secret) {
            return false;
        }

        if ($this->isValidBackupCode($user, $code)) {
            $this->consumeBackupCode($user, $code);
            return true;
        }

        $expectedCode = $this->generateTOTP($secret);

        if ($this->verifyAttemptsExceeded($user)) {
            throw new \Exception('Too many verification attempts. Please try again later.');
        }

        if ($this->codeMatches($code, $expectedCode)) {
            $user->setTotpAttempts(0);
            $this->entityManager->flush();
            return true;
        }

        $user->setTotpAttempts($user->getTotpAttempts() + 1);
        $this->entityManager->flush();

        return false;
    }

    public function enableTOTP(string $userId, string $code)
    {
        if (!$this->verifyCode($userId, $code)) {
            return false;
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return false;
        }

        $user->setTotpEnabled(true);

        $this->entityManager->flush();

        return true;
    }

    public function disableTOTP(string $userId)
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return false;
        }

        $user->setTotpEnabled(false);
        $user->setTotpSecret(null);
        $user->setTotpBackupCodes([]);

        $this->entityManager->flush();

        return true;
    }

    private function generateTOTP(string $secret)
    {
        $time = floor(time() / $this::CODE_VALIDITY);
        $timeBytes = pack('N', $time);
        $timeBytes = str_pad($timeBytes, 8, "\x00", STR_PAD_RIGHT);

        $secretBytes = $this->base32Decode($secret);

        $hash = hash_hmac('sha1', $timeBytes, $secretBytes, true);
        $offset = ord($hash[19]) & 0x0f;

        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    private function generateCode(int $length = 6)
    {
        $digits = '0123456789';
        $code = '';
        $max = strlen($digits) - 1;
        for ($i = 0; $i < $length; $i++) {
            $code .= $digits[random_int(0, $max)];
        }
        return $code;
    }

    private function isValidBackupCode(object $user, string $code)
    {
        $backupCodes = $user->getTotpBackupCodes();
        if (!is_array($backupCodes)) {
            return false;
        }

        return in_array($code, $backupCodes, true);
    }

    private function consumeBackupCode(object $user, string $code)
    {
        $backupCodes = $user->getTotpBackupCodes();
        if (is_array($backupCodes)) {
            $backupCodes = array_filter($backupCodes, fn ($c) => $c !== $code);
            $user->setTotpBackupCodes(array_values($backupCodes));
            $this->entityManager->flush();
        }
    }

    private function verifyAttemptsExceeded(object $user)
    {
        return $user->getTotpAttempts() >= $this::MAX_ATTEMPTS;
    }

    private function codeMatches(string $code1, string $code2)
    {
        return hash_equals($code1, $code2);
    }

    private function base32Decode(string $secret)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer = 0;
        $bufferBits = 0;
        $output = '';

        for ($i = 0; $i < strlen($secret); $i++) {
            $char = $secret[$i];
            $index = strpos($alphabet, $char);

            if ($index === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $index;
            $bufferBits += 5;

            if ($bufferBits >= 8) {
                $output .= chr(($buffer >> ($bufferBits - 8)) & 0xff);
                $bufferBits -= 8;
            }
        }

        return $output;
    }

    public function getQRCodeUrl(string $secret, string $accountName)
    {
        $issuer = 'QCoder Platform';
        $label = rawurlencode($issuer . ':' . $accountName);
        $secretRaw = rawurlencode($secret);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s',
            $label,
            $secretRaw,
            rawurlencode($issuer)
        );
    }
}
