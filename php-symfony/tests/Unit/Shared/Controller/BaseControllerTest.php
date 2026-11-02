<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Controller;

use App\Shared\Controller\BaseController;
use App\User\Entity\User;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TestController extends BaseController
{
    // Concrete test subclass
}

#[AllowMockObjectsWithoutExpectations]
class BaseControllerTest extends TestCase
{
    public function testGetUserReturnsTestUserWhenSet(): void
    {
        $controller = new TestController();
        $user = $this->createMock(User::class);
        $controller->setTestUser($user);
        $this->assertSame($user, $controller->getUser());
    }

    public function testGetUserReturnsNullWhenNoTokenAndNoTestUser(): void
    {
        $controller = new TestController();
        $container = new ContainerBuilder();
        $controller->setContainer($container);
        $this->assertNull($controller->getUser());
    }

    public function testGetUserReturnsTokenUser(): void
    {
        $controller = new TestController();
        $container = new ContainerBuilder();
        $tokenStorage = new \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage();
        $user = $this->createMock(User::class);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage->setToken($token);
        $container->set('security.token_storage', $tokenStorage);
        $controller->setContainer($container);
        $this->assertSame($user, $controller->getUser());
    }
}
