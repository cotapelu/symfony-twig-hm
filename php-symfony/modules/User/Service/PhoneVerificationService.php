<?php

declare(strict_types=1);

namespace App\User\Service;

use App\Shared\Service\CircuitBreakerService;
use App\User\Entity\User;
use App\User\Entity\UserVerification;
use App\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class PhoneVerificationService
{
    private const CODE_EXPIRY_MINUTES = 10;
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepositoryInterface $userRepository,
        
        private ?CircuitBreakerService $circuitBreaker = null,
    ) {
    }

    /**
     * Send verification code to phone number.
     */
    public function sendVerificationCode(string $userId, string $phoneNumber)
    {
        $user = $this->getUserOrFail($userId);
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        $verification = $this->getOrCreateVerification($user);
        $this->ensureResendCooldown($verification);
        $code = $this->generateVerificationCode();
        $this->populateVerificationForSend($verification, $phoneNumber, $code);
        $this->entityManager->flush();
        if (!$this->sendSms($phoneNumber, $code)) {
            throw new \RuntimeException('Failed to send verification code');
        }
        return $this->buildSendResponse($phoneNumber);
    }

    private function getUserOrFail(string $userId): User
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }
        return $user;
    }

    private function getOrCreateVerification(User $user): UserVerification
    {
        $verification = $user->getVerification();
        if (!$verification) {
            $verification = new UserVerification($user->getId());
            $user->setVerification($verification);
        }
        return $verification;
    }

    private function ensureResendCooldown(UserVerification $verification): void
    {
        if ($verification->getLastPhoneSentAt()) {
            $cooldown = time() - $verification->getLastPhoneSentAt()->getTimestamp();
            if ($cooldown < self::RESEND_COOLDOWN_SECONDS) {
                throw new \InvalidArgumentException('Please wait before requesting another code');
            }
        }
    }

    private function populateVerificationForSend(UserVerification $verification, string $phoneNumber, string $code): void
    {
        $verification->setPhoneCode(password_hash($code, PASSWORD_BCRYPT));
        $verification->setPhoneCodeExpiresAt(new \DateTime('+'.self::CODE_EXPIRY_MINUTES.' minutes'));
        $verification->setLastPhoneSentAt(new \DateTime());
        $verification->setPhoneNumber($phoneNumber);
    }

    private function buildSendResponse(string $phoneNumber): array
    {
        return [
            'success' => true,
            'message' => 'Verification code sent successfully',
            'phone_number' => $this->maskPhoneNumber($phoneNumber),
            'expires_in' => self::CODE_EXPIRY_MINUTES * 60,
        ];
    }

    /**
     * Verify phone number with code.
     */
    public function verifyPhoneNumber(string $userId, string $code)
    {
        $user = $this->getUserOrFail($userId);
        $verification = $user->getVerification();
        if (!$verification) {
            throw new \InvalidArgumentException('No verification code found');
        }
        $storedCode = $verification->getPhoneCode();
        if (!$storedCode) {
            throw new \InvalidArgumentException('No verification code found');
        }
        $expiresAt = $verification->getPhoneCodeExpiresAt();
        if ($expiresAt && $expiresAt < new \DateTime()) {
            throw new \InvalidArgumentException('Verification code has expired');
        }
        if (!password_verify($code, $storedCode)) {
            $this->handleFailedAttempt($verification);
        }
        $this->completePhoneVerification($user, $verification);
        return [
            'success' => true,
            'message' => 'Phone number verified successfully',
            'phone_number' => $this->maskPhoneNumber($verification->getPhoneNumber()),
        ];
    }

    private function handleFailedAttempt(UserVerification $verification): never
    {
        $attempts = $verification->getPhoneCodeAttempts() + 1;
        $verification->setPhoneCodeAttempts($attempts);
        if ($attempts >= 3) {
            $verification->setPhoneCode(null);
            $verification->setPhoneCodeAttempts(0);
            $this->entityManager->flush();
            throw new \InvalidArgumentException('Too many failed attempts. Please request a new code.');
        }
        $this->entityManager->flush();
        $remaining = 3 - $attempts;
        throw new \InvalidArgumentException("Invalid code. {$remaining} attempts remaining");
    }

    private function completePhoneVerification(User $user, UserVerification $verification): void
    {
        $user->setPhoneNumber($verification->getPhoneNumber());
        $user->setIsPhoneVerified(true);
        $verification->setPhoneCode(null);
        $verification->setPhoneCodeAttempts(0);
        $verification->setPhoneCodeExpiresAt(null);
        $this->entityManager->flush();
    }

    /**
     * Generate 6-digit verification code.
     */
    private function generateVerificationCode()
    {
        return sprintf('%06d', random_int(0, 999999));
    }

    /**
     * Format phone number to E.164 format.
     */
    private function formatPhoneNumber(string $phoneNumber)
    {
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        if (!str_starts_with($phoneNumber, '+')) {
            if (str_starts_with($phoneNumber, '84')) {
                $phoneNumber = '+'.$phoneNumber;
            } else {
                $phoneNumber = '+84'.ltrim($phoneNumber, '0');
            }
        }

        return $phoneNumber;
    }

    /**
     * Mask phone number for privacy.
     */
    private function maskPhoneNumber(string $phoneNumber)
    {
        if (strlen($phoneNumber) < 10) {
            return $phoneNumber;
        }

        return substr($phoneNumber, 0, -6).'******'.substr($phoneNumber, -2);
    }

    /**
     * Send SMS via provider (mock or real SMS API).
     */
    private function sendSms(string $phoneNumber, string $code)
    {
        $twilioSid = getenv('TWILIO_ACCOUNT_SID');
        $twilioToken = getenv('TWILIO_AUTH_TOKEN');
        $twilioFrom = getenv('TWILIO_PHONE_NUMBER');

        if ($twilioSid && $twilioToken && $twilioFrom) {
            return $this->sendViaTwilio($phoneNumber, $code, $twilioSid, $twilioToken, $twilioFrom);
        }

        return $this->sendMockSms($phoneNumber, $code);
    }

    /**
     * Send SMS via Twilio.
     */
    private function sendViaTwilio(string $phoneNumber, string $code, string $sid, string $token, string $from)
    {
        if (null === $this->circuitBreaker) {
            return $this->makeTwilioRequest($phoneNumber, $code, $sid, $token, $from);
        }

        return $this->circuitBreaker->execute(
            fn () => $this->makeTwilioRequest($phoneNumber, $code, $sid, $token, $from),
            'twilio_api',
            3,
            60,
            fn () => $this->sendMockSms($phoneNumber, $code)
        );
    }

    private function makeTwilioRequest(string $phoneNumber, string $code, string $sid, string $token, string $from)
    {
        $message = $this->buildTwilioMessage($code);
        $url = $this->buildTwilioUrl($sid);
        $ch = $this->initCurl($url, $phoneNumber, $message, $from, $sid, $token);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        $this->handleCurlError($error, $response, $httpCode);
        return true;
    }

    private function buildTwilioMessage(string $code): string
    {
        return "Your verification code is: {$code}. Valid for 10 minutes.";
    }

    private function buildTwilioUrl(string $sid): string
    {
        return "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
    }

    private function initCurl(string $url, string $phoneNumber, string $message, string $from, string $sid, string $token)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'From' => $from,
                'To' => $phoneNumber,
                'Body' => $message,
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_USERPWD => $sid.':'.$token,
            CURLOPT_TIMEOUT => 30,
        ]);
        return $ch;
    }

    private function handleCurlError(string $error, string $response, int $httpCode): void
    {
        if ($error) {
            throw new \Exception('Twilio cURL error: '.$error);
        }
        if (!in_array($httpCode, [200, 201], true)) {
            throw new \Exception('Twilio API error: HTTP '.$httpCode.' - '.$response);
        }
    }

    /**
     * Mock SMS sending for development.
     */
    private function sendMockSms(string $phoneNumber, string $code)
    {
        error_log("[MOCK SMS] To: {$phoneNumber}, Code: {$code}");
        return true;
    }
}
