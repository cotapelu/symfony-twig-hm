<?php

declare(strict_types=1);

namespace App\User\Mailer;

use App\User\Entity\User;

/**
 * Interface for user-related email notifications.
 * Implementations can use SendGrid, AWS SES, Symfony Mailer, etc.
 */
interface UserMailerInterface
{
    public function sendVerificationEmail(User $user, string $token): void;
    
    public function sendPasswordResetEmail(User $user, string $token): void;
    
    public function sendWelcomeEmail(User $user): void;
}
