<?php declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;
use App\User\Mailer\UserMailerInterface;
use App\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class UserVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepositoryInterface $userRepository,
        private ?UserMailerInterface $mailer = null,
    ) {
    }

    public function sendEmailVerification(User $user)
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTime('+24 hours');

        $user->setVerificationToken($token);
        $user->setVerificationTokenExpiresAt($expiresAt);

        $this->entityManager->flush();

        if ($this->mailer) {
            $this->mailer->sendVerificationEmail($user, $token);
        } else {
            error_log("Email verification token for {$user->getEmail()}: {$token}");
        }
    }

    public function verifyEmail(string $token)
    {
        $user = $this->userRepository->findByVerificationToken($token);

        if (!$user) {
            return false;
        }

        if ($user->getVerificationTokenExpiresAt() < new \DateTime()) {
            return false;
        }

        $user->setEmailVerifiedAt(new \DateTime());
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);
        $user->setVerificationLevel('email');

        $this->entityManager->flush();

        return true;
    }

    public function sendPhoneVerification(User $user)
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->setVerificationToken($code);
        $user->setVerificationTokenExpiresAt(new \DateTime('+10 minutes'));

        $this->entityManager->flush();

        return $code;
    }

    public function verifyPhone(string $phoneNumber, string $code)
    {
        $user = $this->userRepository->findByPhoneAndVerification($phoneNumber, $code);

        if (!$user) {
            return false;
        }

        if ($user->getVerificationTokenExpiresAt() < new \DateTime()) {
            return false;
        }

        $user->setPhoneVerifiedAt(new \DateTime());
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);

        if ($user->getEmailVerifiedAt()) {
            $user->setVerificationLevel('phone');
        }

        $this->entityManager->flush();

        return true;
    }

    public function registerBiometric(User $user, string $biometricData)
    {
        $user->setBiometricData($biometricData);
        $user->setBiometricVerifiedAt(new \DateTime());
        $user->setVerificationLevel('biometric');

        $this->entityManager->flush();
    }

    public function verifyBiometric(string $email, string $biometricData)
    {
        $user = $this->userRepository->findByEmailAndBiometric($email, $biometricData);

        if ($user && $user->getBiometricVerifiedAt()) {
            $user->setLastLoginAt(new \DateTime());
            $this->entityManager->flush();

            return $user;
        }

        return null;
    }

    public function getVerificationStatus(User $user)
    {
        return [
            'email_verified' => null !== $user->getEmailVerifiedAt(),
            'phone_verified' => null !== $user->getPhoneVerifiedAt(),
            'biometric_verified' => null !== $user->getBiometricVerifiedAt(),
            'level' => $user->getVerificationLevel(),
        ];
    }
}
