<?php

declare(strict_types=1);

namespace App\Shared\EventListener;

use App\Shared\Service\EncryptionService;
use App\User\Entity\User;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;

class UserEncryptionListener implements EventSubscriber
{
    public function __construct(private EncryptionService $encryption) {}

    public function getSubscribedEvents(): array
    {
        return [Events::prePersist, Events::preUpdate, Events::postLoad];
    }

    public function prePersist(User $user, LifecycleEventArgs $args): void
    {
        $this->encryptUser($user);
    }

    public function preUpdate(User $user, LifecycleEventArgs $args): void
    {
        $this->encryptUser($user);
    }

    public function postLoad(User $user, LifecycleEventArgs $args): void
    {
        $this->decryptUser($user);
    }

    private function encryptUser(User $user): void
    {
        $plain = $user->getBiometricDataPlain();
        if ($plain !== null) {
            $encrypted = $this->encryption->encrypt($plain);
            $user->setBiometricDataEncrypted($encrypted);
        }
    }

    private function decryptUser(User $user): void
    {
        $encrypted = $user->getBiometricDataEncrypted();
        if ($encrypted !== null) {
            try {
                $plain = $this->encryption->decrypt($encrypted);
                $user->setBiometricDataPlain($plain);
            } catch (\Exception $e) {
                // Log error if needed; leave plain null.
            }
        }
    }
}
