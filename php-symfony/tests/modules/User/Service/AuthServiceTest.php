<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\DomainEvent\UserRegistered;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\AuthService;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Shared\DomainEvent\DomainEventPublisherInterface;
use App\Shared\Service\UuidService;

#[AllowMockObjectsWithoutExpectations]
class AuthServiceTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private UuidService&MockObject $uuidService;
    private DomainEventPublisherInterface&MockObject $eventPublisher;
    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->uuidService = $this->createMock(UuidService::class);
        $this->eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $this->service = new AuthService(
            $this->userRepository,
            $this->entityManager,
            $this->passwordHasher,
            $this->uuidService,
            $this->eventPublisher
        );
    }

    public function testRegisterThrowsWhenMissingRequiredFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name, email and password are required');

        $this->service->register(['email' => 'test@example.com']); // missing name and password
    }

    public function testRegisterThrowsWhenEmailInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->service->register([
            'email' => 'invalid-email',
            'password' => 'password123',
            'name' => 'John'
        ]);
    }

    public function testRegisterThrowsWhenPasswordTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        $this->service->register([
            'email' => 'test@example.com',
            'password' => 'short',
            'name' => 'John'
        ]);
    }

    public function testRegisterThrowsWhenUserAlreadyExists(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn(new User('user-1', 'test@example.com'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User already exists');

        $this->service->register([
            'email' => 'test@example.com',
            'password' => 'password123',
            'name' => 'John'
        ]);
    }

    public function testRegisterSuccess(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn(null);

        $this->uuidService->expects($this->once())
            ->method('generateWithPrefix')
            ->with('user_')
            ->willReturn('user_123');

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($this->callback(function (User $user) {
                return $user->getEmail() === 'test@example.com';
            }))
            ->willReturn('hashed_password');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $user) {
                return $user->getId() === 'user_123'
                    && $user->getEmail() === 'test@example.com'
                    && $user->getPassword() === 'hashed_password';
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->eventPublisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(UserRegistered::class));

        $user = $this->service->register([
            'email' => 'test@example.com',
            'password' => 'password123',
            'name' => 'John',
            'phoneNumber' => '0123456789'
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('user_123', $user->getId());
    }

    public function testRegisterPublishesUserRegisteredEvent(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        $this->uuidService->expects($this->once())
            ->method('generateWithPrefix')
            ->willReturn('user_123');

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        // Capture the published event
        $publishedEvent = null;
        $this->eventPublisher->expects($this->once())
            ->method('publish')
            ->willReturnCallback(function ($event) use (&$publishedEvent) {
                $publishedEvent = $event;
            });

        $this->service->register([
            'email' => 'test@example.com',
            'password' => 'password123',
            'name' => 'John'
        ]);

        $this->assertInstanceOf(UserRegistered::class, $publishedEvent);
        $this->assertSame('user_123', $publishedEvent->userId());
        $this->assertSame('test@example.com', $publishedEvent->email());
        $this->assertSame('John', $publishedEvent->name());
    }
}
