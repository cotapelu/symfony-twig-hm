<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\EventListener;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\EventListener\UserEncryptionListener;
use App\Shared\Service\EncryptionService;
use App\User\Entity\User;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]

class UserEncryptionListenerTest extends TestCase
{
    private EncryptionService&MockObject $encryptionMock;
    private UserEncryptionListener $listener;

    protected function setUp(): void
    {
        $this->encryptionMock = $this->createMock(EncryptionService::class);
        $this->listener = new UserEncryptionListener($this->encryptionMock);
    }

    private function createUserMock(?string $plain = null, ?string $encrypted = null): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getBiometricDataPlain')->willReturn($plain);
        $user->method('getBiometricDataEncrypted')->willReturn($encrypted);
        return $user;
    }

    private function createLifecycleEventArgs(User $user): LifecycleEventArgs
    {
        $em = $this->createMock(\Doctrine\Persistence\ObjectManager::class);
        return new LifecycleEventArgs($user, $em);
    }

    public function testPrePersistEncryptsBiometricData(): void
    {
        $user = $this->createUserMock('plain_biometric');
        $this->encryptionMock->expects($this->once())
            ->method('encrypt')
            ->with('plain_biometric')
            ->willReturn('encrypted_data');

        $user->expects($this->once())
            ->method('setBiometricDataEncrypted')
            ->with('encrypted_data');

        $args = $this->createLifecycleEventArgs($user);
        $this->listener->prePersist($user, $args);
    }

    public function testPrePersistDoesNothingWhenPlainIsNull(): void
    {
        $user = $this->createUserMock(null);
        $this->encryptionMock->expects($this->never())->method('encrypt');
        $user->expects($this->never())->method('setBiometricDataEncrypted');

        $args = $this->createLifecycleEventArgs($user);
        $this->listener->prePersist($user, $args);
    }

    public function testPreUpdateEncryptsBiometricData(): void
    {
        $user = $this->createUserMock('updated_plain');
        $this->encryptionMock->expects($this->once())
            ->method('encrypt')
            ->with('updated_plain')
            ->willReturn('updated_encrypted');

        $user->expects($this->once())
            ->method('setBiometricDataEncrypted')
            ->with('updated_encrypted');

        $args = $this->createLifecycleEventArgs($user);
        $this->listener->preUpdate($user, $args);
    }

    public function testPostLoadDecryptsBiometricData(): void
    {
        $user = $this->createUserMock(null, 'encrypted_biometric');
        $this->encryptionMock->expects($this->once())
            ->method('decrypt')
            ->with('encrypted_biometric')
            ->willReturn('decrypted_plain');

        $user->expects($this->once())
            ->method('setBiometricDataPlain')
            ->with('decrypted_plain');

        $args = $this->createLifecycleEventArgs($user);
        $this->listener->postLoad($user, $args);
    }

    public function testPostLoadDoesNothingWhenEncryptedIsNull(): void
    {
        $user = $this->createUserMock();
        $this->encryptionMock->expects($this->never())->method('decrypt');
        $user->expects($this->never())->method('setBiometricDataPlain');

        $args = $this->createLifecycleEventArgs($user);
        $this->listener->postLoad($user, $args);
    }

    public function testPostLoadHandlesDecryptionException(): void
    {
        $user = $this->createUserMock(null, 'bad_encrypted');
        $this->encryptionMock->expects($this->once())
            ->method('decrypt')
            ->with('bad_encrypted')
            ->willThrowException(new \Exception('Decryption failed'));

        $user->expects($this->never())->method('setBiometricDataPlain');

        $args = $this->createLifecycleEventArgs($user);
        $this->listener->postLoad($user, $args);
        // Should not throw; leaves plain as null
        $this->assertTrue(true);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = $this->listener->getSubscribedEvents();
        $this->assertContains(Events::prePersist, $events);
        $this->assertContains(Events::preUpdate, $events);
        $this->assertContains(Events::postLoad, $events);
    }

}
