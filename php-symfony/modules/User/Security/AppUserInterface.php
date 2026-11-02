<?php

declare(strict_types=1);

namespace App\User\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Extended UserInterface that includes getId() method.
 * This interface is used throughout the application for type hinting
 * authenticated users that have an ID property.
 */
interface AppUserInterface extends UserInterface
{
    /**
     * Returns the user ID.
     */
    public function getId(): string;
}
