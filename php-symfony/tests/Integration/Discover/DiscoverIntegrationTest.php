<?php

declare(strict_types=1);

namespace App\Tests\Integration\Discover;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Discover\Entity\DiscoverAction;
use App\User\Entity\User;
use App\User\Entity\UserVerification;

#[AllowMockObjectsWithoutExpectations]
class DiscoverIntegrationTest extends DatabaseIntegrationTestCase
{
    private ?User $testUser = null;

    private function createTestUser(): User
    {
        $id = 'user-' . bin2hex(random_bytes(8));
        $user = new User(
            id: $id,
            email: "{$id}@example.com",
            roles: ['ROLE_USER']
        );
        // Ensure verification exists to satisfy FK constraint
        $verification = new UserVerification($id);
        $user->setVerification($verification);

        $em = self::getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testCreateAndRetrieveDiscoverAction(): void
    {
        $user = $this->createTestUser();

        $action = new DiscoverAction(
            id: 'action-' . bin2hex(random_bytes(8)),
            userId: $user->getId(),
            user: $user,
            actionType: 'view',
            contentType: 'product',
            contentId: 'prod-123',
            metadata: ['source' => 'homepage']
        );

        $em = self::getEntityManager();
        $em->persist($action);
        $em->flush();

        $this->assertNotNull($action->getId());

        // Retrieve by ID
        $repo = $em->getRepository(DiscoverAction::class);
        $found = $repo->find($action->getId());

        $this->assertNotNull($found);
        $this->assertSame($action->getActionType(), $found->getActionType());
        $this->assertSame($action->getContentType(), $found->getContentType());
        $this->assertSame($action->getContentId(), $found->getContentId());
        $this->assertSame($action->getMetadata(), $found->getMetadata());
        $this->assertSame($user->getId(), $found->getUser()->getId());
    }

    public function testFindByUser(): void
    {
        $user = $this->createTestUser();

        $action1 = new DiscoverAction(
            id: 'action-' . bin2hex(random_bytes(8)),
            userId: $user->getId(),
            user: $user,
            actionType: 'click',
            contentType: 'article',
            contentId: 'article-456'
        );
        $action2 = new DiscoverAction(
            id: 'action-' . bin2hex(random_bytes(8)),
            userId: $user->getId(),
            user: $user,
            actionType: 'purchase',
            contentType: 'product',
            contentId: 'prod-789'
        );

        $em = self::getEntityManager();
        $em->persist($action1);
        $em->persist($action2);
        $em->flush();

        $repo = $em->getRepository(DiscoverAction::class);
        $byUser = $repo->findBy(['user' => $user->getId()]);

        $this->assertCount(2, $byUser);
        $ids = array_map(fn($a) => $a->getId(), $byUser);
        $this->assertContains($action1->getId(), $ids);
        $this->assertContains($action2->getId(), $ids);
    }
}
