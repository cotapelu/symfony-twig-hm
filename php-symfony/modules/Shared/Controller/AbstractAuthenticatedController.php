<?php

declare(strict_types=1);

namespace App\Shared\Controller;

use App\User\Service\AuthService;
use App\Shared\Controller\BaseController;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractAuthenticatedController extends BaseController
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    protected function getAuthenticatedUserId(UserInterface $user): string
    {
        return $this->authService->getAuthenticatedUserId($user);
    }

    protected function getAuthenticatedUser(UserInterface $user): \App\User\Entity\User
    {
        return $this->authService->getAuthenticatedUser($user);
    }
}
