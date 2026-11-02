<?php declare(strict_types=1);

namespace App\Tests\Service\Common;

use App\Entity\Common\User;
use App\Service\Common\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private UserService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new UserService($this->em, $this->logger);
    }

    public function testCreateSuccess(): void
    {
        $email = 'test@example.com';
        $passwordHash = password_hash('secret', PASSWORD_DEFAULT);
        $userId = '123e4567-e89b-12d3-a456-426614174000';

        $user = new User($userId, $email, $passwordHash);

        $repository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $u) use ($email, $userId) {
                return $u->getEmail() === $email && $u->getId() === $userId;
            }));

        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('User created', ['id' => $userId, 'email' => $email]);

        $result = $this->service->create($email, $passwordHash);
        $this->assertInstanceOf(User::class, $result);
        $this->assertNotEmpty($result->getId());
    }

    public function testCreateFailsOnInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');
        $this->service->create('invalid-email', 'hash');
    }

    public function testCreateFailsOnEmptyPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password hash cannot be empty');
        $this->service->create('test@example.com', '');
    }

    public function testGetWhenNotFoundReturnsNull(): void
    {
        $id = 'nonexistent';
        $repository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $result = $this->service->get($id);
        $this->assertNull($result);
    }

    public function testGetByEmailSuccess(): void
    {
        $email = 'test@example.com';
        $user = new User('id', $email, 'hash');

        $repository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($user);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $result = $this->service->getByEmail($email);
        $this->assertSame($user, $result);
    }

    public function testUpdateSuccess(): void
    {
        $id = 'id123';
        $user = new User($id, 'old@example.com', 'oldhash');
        $user->setPhone('1234567890');

        $repository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($user);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);
        $this->em->expects($this->once())
            ->method('flush');

        $newEmail = 'new@example.com';
        $result = $this->service->update($id, ['email' => $newEmail]);
        $this->assertSame($user, $result);
        $this->assertSame($newEmail, $user->getEmail());
    }

    public function testUpdateThrowsWhenUserNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not found');

        $repository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $this->service->update('nonexistent', []);
    }

    public function testDeleteSuccess(): void
    {
        $id = 'id123';
        $user = new User($id, 'test@example.com', 'hash');

        $repository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($user);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($user);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->delete($id);
        $this->assertTrue($result);
    }

    public function testDeleteReturnsFalseWhenNotFound(): void
    {
        $repository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $result = $this->service->delete('nonexistent');
        $this->assertFalse($result);
    }

    public function testExistsByEmailTrue(): void
    {
        $email = 'test@example.com';
        $user = new User('id', $email, 'hash');

        $repository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($user);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $result = $this->service->existsByEmail($email);
        $this->assertTrue($result);
    }

    public function testExistsByEmailFalse(): void
    {
        $repository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $result = $this->service->existsByEmail('missing@example.com');
        $this->assertFalse($result);
    }
}
