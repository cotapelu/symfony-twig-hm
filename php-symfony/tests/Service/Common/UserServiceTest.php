<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\User;
use App\Service\Common\UserService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class UserServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private EntityRepository $userRepo;
    private LoggerInterface $logger;
    private UserService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        // Use generic EntityRepository because UserRepository may not exist as a distinct class
        $this->userRepo = $this->createMock(EntityRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->method('getRepository')
            ->willReturnMap([
                [User::class, $this->userRepo],
            ]);
    }

    public function testCreateUser(): void
    {
        $email = 'test@example.com';
        $passwordHash = 'hashedpass';
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $phone = '1234567890';
        $profile = ['name' => 'Test'];
        $captured = null;

        $this->userRepo->expects($this->never())->method('find');
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $u) use (&$captured, $email, $passwordHash, $roles, $phone, $profile) {
                $captured = $u;
                return $captured !== null
                    && $captured->getEmail() === $email
                    && $captured->getPasswordHash() === $passwordHash
                    && $captured->getRoles() === $roles
                    && $captured->getPhone() === $phone
                    && $captured->getProfile() === $profile;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('User created', $this->arrayHasKey('id'));

        $this->service = new UserService($this->em, $this->logger);
        $result = $this->service->create($email, $passwordHash, $roles, $phone, $profile);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($email, $result->getEmail());
        $this->assertNotNull($result->getId());
    }

    public function testCreateUserMinimal(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())->method('info');

        $this->service = new UserService($this->em, $this->logger);
        $result = $this->service->create('minimal@example.com', 'hash');
        $this->assertInstanceOf(User::class, $result);
    }

    public function testCreateUserInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->service = new UserService($this->em, $this->logger);
        $this->service->create('invalid', 'hash');
    }

    public function testCreateUserEmptyPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password hash cannot be empty');

        $this->service = new UserService($this->em, $this->logger);
        $this->service->create('test@example.com', '');
    }

    public function testCreateUserEmptyRoles(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one role is required');

        $this->service = new UserService($this->em, $this->logger);
        $this->service->create('test@example.com', 'hash', []);
    }

    public function testCreateUserInvalidRoles(): void
    {
        $this->expectException(\TypeError::class);

        $this->service = new UserService($this->em, $this->logger);
        $this->service->create('test@example.com', 'hash', 'not-an-array');
    }

    public function testCreateUserPersistenceException(): void
    {
        $this->userRepo->expects($this->never())->method('find');
        $this->em->expects($this->once())->method('persist');
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to create user', $this->arrayHasKey('error'));

        $this->service = new UserService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to create user');
        $this->service->create('test@example.com', 'hash');
    }

    public function testGetUser(): void
    {
        $id = 'user-1';
        $user = $this->createMock(User::class);
        $this->userRepo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($user);

        $this->service = new UserService($this->em, $this->logger);
        $result = $this->service->get($id);
        $this->assertSame($user, $result);
    }

    public function testGetByEmail(): void
    {
        $email = 'user@example.com';
        $user = $this->createMock(User::class);
        $this->userRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($user);

        $this->service = new UserService($this->em, $this->logger);
        $result = $this->service->getByEmail($email);
        $this->assertSame($user, $result);
    }

    public function testGetByEmailInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->service = new UserService($this->em, $this->logger);
        $this->service->getByEmail('bad-email');
    }

    public function testUpdateUser(): void
    {
        $id = 'user-2';
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('setEmail')->with('new@example.com');
        $user->expects($this->once())->method('setPasswordHash')->with('newhash');
        $user->expects($this->once())->method('setPhone')->with('9876543210');
        $user->expects($this->never())->method('setRoles'); // not provided
        $user->expects($this->never())->method('setProfile');
        $user->expects($this->never())->method('setStatus');

        $this->userRepo->expects($this->once())->method('find')->with($id)->willReturn($user);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('User updated', ['id' => $id]);

        $this->service = new UserService($this->em, $this->logger);
        $result = $this->service->update($id, email: 'new@example.com', passwordHash: 'newhash', phone: '9876543210');
        $this->assertSame($user, $result);
    }

    public function testUpdateUserWithOptionalFields(): void
    {
        $id = 'user-3';
        $user = $this->createMock(User::class);
        $user->expects($this->never())->method('setEmail');
        $user->expects($this->never())->method('setPasswordHash');
        $user->expects($this->once())->method('setRoles')->with(['ROLE_ADMIN']);
        $user->expects($this->once())->method('setProfile')->with(['bio' => 'hi']);
        $user->expects($this->never())->method('setStatus');

        $this->userRepo->expects($this->once())->method('find')->with($id)->willReturn($user);
        $this->em->expects($this->once())->method('flush');

        $this->service = new UserService($this->em, $this->logger);
        $result = $this->service->update($id, roles: ['ROLE_ADMIN'], profile: ['bio' => 'hi']);
        $this->assertSame($user, $result);
    }

    public function testUpdateUserNotFound(): void
    {
        $this->userRepo->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new UserService($this->em, $this->logger);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User not found');
        $this->service->update('missing', email: 'new@example.com');
    }

    public function testUpdateUserInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        $user = $this->createMock(User::class);
        $this->userRepo->expects($this->once())->method('find')->with('id')->willReturn($user);
        $this->em->expects($this->never())->method('flush');

        $this->service = new UserService($this->em, $this->logger);
        $this->service->update('id', email: 'invalid');
    }

    public function testUpdateUserEmptyPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password hash cannot be empty');

        $user = $this->createMock(User::class);
        $this->userRepo->expects($this->once())->method('find')->with('id')->willReturn($user);
        $this->em->expects($this->never())->method('flush');

        $this->service = new UserService($this->em, $this->logger);
        $this->service->update('id', passwordHash: '');
    }

    public function testUpdateUserInvalidRoles(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one role is required');

        $user = $this->createMock(User::class);
        $this->userRepo->expects($this->once())->method('find')->with('id')->willReturn($user);
        $this->em->expects($this->never())->method('flush');

        $this->service = new UserService($this->em, $this->logger);
        $this->service->update('id', roles: []);
    }

    public function testUpdateUserPersistenceException(): void
    {
        $id = 'user-4';
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('setEmail')->with('new@example.com');
        $this->userRepo->expects($this->once())->method('find')->with($id)->willReturn($user);
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to update user', $this->arrayHasKey('error'));

        $this->service = new UserService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to update user');
        $this->service->update($id, email: 'new@example.com');
    }

    public function testDeleteUser(): void
    {
        $id = 'user-5';
        $user = $this->createMock(User::class);

        $this->userRepo->expects($this->once())->method('find')->with($id)->willReturn($user);
        $this->em->expects($this->once())->method('remove')->with($user);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('User deleted', ['id' => $id]);

        $this->service = new UserService($this->em, $this->logger);
        $result = $this->service->delete($id);
        $this->assertTrue($result);
    }

    public function testDeleteUserNotFound(): void
    {
        $this->userRepo->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->em->expects($this->never())->method('remove');

        $this->service = new UserService($this->em, $this->logger);
        $result = $this->service->delete('missing');
        $this->assertFalse($result);
    }

    public function testDeleteUserPersistenceException(): void
    {
        $id = 'user-6';
        $user = $this->createMock(User::class);
        $this->userRepo->expects($this->once())->method('find')->with($id)->willReturn($user);
        $this->em->expects($this->once())->method('remove')->with($user);
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to delete user', $this->arrayHasKey('error'));

        $this->service = new UserService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to delete user');
        $this->service->delete($id);
    }

    public function testGetByEmailTrue(): void
    {
        $email = 'exists@example.com';
        $user = $this->createMock(User::class);
        $this->userRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($user);

        $this->service = new UserService($this->em, $this->logger);
        $result = $this->service->getByEmail($email);
        $this->assertSame($user, $result);
    }

    public function testGetByEmailFalse(): void
    {
        $this->userRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->service = new UserService($this->em, $this->logger);
        $result = $this->service->getByEmail('nope@example.com');
        $this->assertNull($result);
    }
}
