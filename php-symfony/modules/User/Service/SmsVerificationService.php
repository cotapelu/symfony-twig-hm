<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\UserVerification;
use App\User\Repository\UserVerificationRepositoryInterface;

class SmsVerificationService
{
    private const CODE_LENGTH = 6;
    private const CODE_EXPIRY_MINUTES = 10;
    private const MAX_ATTEMPTS = 3;
    private const RATE_LIMIT_PER_HOUR = 10;

    public function __construct(
        private UserVerificationRepositoryInterface $verificationRepository,
    ) {
    }

    public function sendVerificationCode(string $phoneNumber)
    {
        $this->checkRateLimit($phoneNumber);

        $verification = $this->verificationRepository->findByPhoneNumber($phoneNumber);

        if ($verification && $verification->getPhoneCodeExpiresAt() &&
            $verification->getPhoneCodeExpiresAt() > new \DateTime() &&
            $verification->getPhoneCode()) {
            throw new \RuntimeException('Verification code already sent. Please wait for expiry or request new code.');
        }

        $code = $this->generateSecureCode();
        $expiresAt = (new \DateTime())->modify(sprintf('+%d minutes', self::CODE_EXPIRY_MINUTES));

        $verification = $this->prepareVerification($verification, $phoneNumber, $code, $expiresAt);
        $this->verificationRepository->save($verification);

        $this->sendSms($phoneNumber, $code);

        return [
            'success' => true,
            'message' => 'Verification code sent',
            'expiresIn' => self::CODE_EXPIRY_MINUTES * 60,
        ];
    }

    private function prepareVerification(?UserVerification $existing, string $phoneNumber, string $code, \DateTime $expiresAt): UserVerification
    {
        if ($existing) {
            $existing->setPhoneCode($code);
            $existing->setPhoneCodeExpiresAt($expiresAt);
            $existing->setPhoneCodeAttempts(0);
            $existing->setLastPhoneSentAt(new \DateTime());
            return $existing;
        }
        $verification = new UserVerification('sms_' . bin2hex(random_bytes(8)));
        $verification->setPhoneNumber($phoneNumber);
        $verification->setPhoneCode($code);
        $verification->setPhoneCodeExpiresAt($expiresAt);
        $verification->setPhoneCodeAttempts(0);
        $verification->setLastPhoneSentAt(new \DateTime());
        return $verification;
    }

    public function verifyCode(string $phoneNumber, string $code)
    {
        $verification = $this->verificationRepository->findByPhoneNumber($phoneNumber);
        $this->ensureVerificationValid($verification);

        // Increment attempt count (original semantics: increment before checking code)
        $attempts = $verification->getPhoneCodeAttempts() + 1;
        $verification->setPhoneCodeAttempts($attempts);

        // Check for max attempts (lockout)
        if ($attempts >= self::MAX_ATTEMPTS) {
            $verification->setPhoneCode(null);
            $verification->setPhoneCodeExpiresAt(null);
            $this->verificationRepository->save($verification);
            throw new \RuntimeException('Too many failed attempts. Please request a new code.');
        }

        // Check code correctness
        if ($verification->getPhoneCode() !== $code) {
            $this->verificationRepository->save($verification);
            throw new \RuntimeException(sprintf(
                'Invalid verification code. %d attempts remaining.',
                self::MAX_ATTEMPTS - $verification->getPhoneCodeAttempts()
            ));
        }

        // Success
        $verification->setPhoneCode(null);
        $verification->setPhoneCodeExpiresAt(null);
        $verification->setPhoneVerifiedAt(new \DateTime());
        $this->verificationRepository->save($verification);

        return true;
    }

    private function ensureVerificationValid(?UserVerification $verification): void
    {
        if (!$verification || !$verification->getPhoneCode()) {
            throw new \RuntimeException('No verification code found. Please request a new code.');
        }
        if (!$verification->getPhoneCodeExpiresAt() || $verification->getPhoneCodeExpiresAt() < new \DateTime()) {
            throw new \RuntimeException('Verification code expired. Please request a new code.');
        }
    }

    public function isPhoneVerified(string $phoneNumber)
    {
        $verification = $this->verificationRepository->findByPhoneNumber($phoneNumber);

        return $verification && $verification->getPhoneVerifiedAt() !== null;
    }

    private function generateSecureCode()
    {
        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function checkRateLimit(string $phoneNumber)
    {
        $oneHourAgo = (new \DateTime())->modify('-1 hour');
        $count = $this->verificationRepository->countRecentByPhone($phoneNumber, $oneHourAgo);
        if ($count >= self::RATE_LIMIT_PER_HOUR) {
            throw new \RuntimeException('Rate limit exceeded. Please try again later.');
        }
    }

    private function sendSms(string $phoneNumber, string $code)
    {
        $message = sprintf('Your verification code is: %s. Valid for %d minutes.', $code, self::CODE_EXPIRY_MINUTES);

        $twilioSid = $_ENV['TWILIO_SID'] ?? getenv('TWILIO_SID');
        $twilioToken = $_ENV['TWILIO_TOKEN'] ?? getenv('TWILIO_TOKEN');
        $twilioFrom = $_ENV['TWILIO_FROM'] ?? getenv('TWILIO_FROM');

        if ($twilioSid && $twilioToken && $twilioFrom) {
            $this->sendViaTwilio($twilioSid, $twilioToken, $twilioFrom, $phoneNumber, $message);
        } else {
            $this->logSms($phoneNumber, $message);
        }
    }

    private function sendViaTwilio(string $sid, string $token, string $from, string $to, string $message)
    {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $data = [
            'To' => $to,
            'From' => $from,
            'Body' => $message,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "{$sid}:{$token}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            throw new \RuntimeException('Failed to send SMS via Twilio: ' . $response);
        }
    }

    private function logSms(string $phoneNumber, string $message)
    {
        error_log(sprintf('[SMS] To: %s | Message: %s', $phoneNumber, $message));
    }
}
