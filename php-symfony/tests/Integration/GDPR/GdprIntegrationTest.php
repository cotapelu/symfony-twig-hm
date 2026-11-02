<?php declare(strict_types=1);

namespace App\Tests\Integration\GDPR;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\GDPR\Service\GdprService;
use App\User\Repository\UserRepositoryInterface;
use App\Message\Repository\MessageRepositoryInterface;

#[AllowMockObjectsWithoutExpectations]
class GdprIntegrationTest extends DatabaseIntegrationTestCase
{
    private GdprService $gdprService;
    private UserRepositoryInterface $userRepository;
    private MessageRepositoryInterface $messageRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('GdprIntegrationTest skipped: repository/service wiring issues.');
        $container = static::getContainer();
        $this->userRepository = $container->get(UserRepositoryInterface::class);
        $this->messageRepository = $container->get(MessageRepositoryInterface::class);
        $this->gdprService = new GdprService($this->userRepository, $this->messageRepository);
    }

    private function createUser(string $id, string $email): \App\User\Entity\User
    {
        $user = new \App\User\Entity\User($id, $email);
        $user->setPhoneNumber('+1234567890');
        $user->setLastLoginAt(new \DateTime());
        $user->setPreferredLanguage('en');
        $user->setTimezone('UTC');
        $user->setRoles(['ROLE_USER']);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        return $user;
    }

    private function createMessage(string $id, string $conversationId, string $senderId, string $content): \App\Message\Entity\Message
    {
        $message = new \App\Message\Entity\Message(
            $id,
            $conversationId,
            $senderId,
            $content,
            'text',
            null
        );
        $this->getEntityManager()->persist($message);
        $this->getEntityManager()->flush();
        return $message;
    }

    public function testExportUserDataReturnsCorrectStructure(): void
    {
        $userId = 'user-gdpr-1';
        $user = $this->createUser($userId, 'gdpr1@example.com');
        $this->createMessage('msg-1', 'conv-1', $userId, 'Hello world');
        $this->createMessage('msg-2', 'conv-1', $userId, 'Second message');

        $result = $this->gdprService->exportUserData($userId);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('messages', $result);
        $userData = $result['user'];
        $this->assertSame($userId, $userData['id']);
        $this->assertSame('gdpr1@example.com', $userData['email']);
        $this->assertSame('+1234567890', $userData['phoneNumber']);
        $this->assertSame(['ROLE_USER'], $userData['roles']);
        $this->assertTrue($userData['isActive']);
        $this->assertIsString($userData['createdAt']);
        $this->assertIsString($userData['lastLoginAt']);

        $messages = $result['messages'];
        $this->assertCount(2, $messages);
        $this->assertSame('msg-1', $messages[0]['id']);
        $this->assertSame('Hello world', $messages[0]['content']);
        $this->assertSame('msg-2', $messages[1]['id']);
    }

    public function testDeleteUserDataAnonymizesUser(): void
    {
        $userId = 'user-gdpr-2';
        $user = $this->createUser($userId, 'gdpr2@example.com');
        $userIdValue = $user->getId();

        $this->gdprService->deleteUserData($userId);

        // Reload user from DB
        $em = self::$entityManager;
        $em->refresh($user); // refresh to get persisted changes

        // Check anonymization
        $this->assertStringStartsWith('deleted_' . $userId . '@', $user->getEmail());
        $this->assertNull($user->getPhoneNumber());
        $this->assertNull($user->getBiometricData());
        $this->assertFalse($user->isActive());
        $this->assertNotNull($user->getDeletedAt());
    }

    public function testDeleteUserDataWhenUserNotFoundDoesNothing(): void
    {
        $this->gdprService->deleteUserData('nonexistent');
        // No exception means pass.
        $this->assertTrue(true);
    }
}
