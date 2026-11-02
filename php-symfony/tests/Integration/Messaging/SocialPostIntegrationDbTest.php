<?php

declare(strict_types=1);

namespace App\Tests\Integration\Messaging;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Core\Entity\User;
use App\Messaging\Entity\SocialPost;

#[AllowMockObjectsWithoutExpectations]
class SocialPostIntegrationDbTest extends DatabaseIntegrationTestCase
{
    private ?User $user = null;

    private function createUser(): User
    {
        $email = 'test-' . bin2hex(random_bytes(8)) . '@example.com';
        $user = new User();
        $user->setEmail($email);
        $user->setPasswordHash(password_hash('password', PASSWORD_BCRYPT));
        $em = self::getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
    }

    protected function tearDown(): void
    {
        $em = self::getEntityManager();
        // Remove social posts first
        $posts = $em->getRepository(SocialPost::class)->findBy(['user' => $this->user]);
        foreach ($posts as $post) {
            $em->remove($post);
        }
        $em->remove($this->user);
        $em->flush();
        parent::tearDown();
    }

    public function testCreateAndRetrieveSocialPost(): void
    {
        $post = new SocialPost($this->user, SocialPost::TYPE_POST);
        $post->setContent('Hello world!');
        $post->setMedia(['image' => 'img.jpg']);
        $post->setLikesCount(5);

        $em = self::getEntityManager();
        $em->persist($post);
        $em->flush();

        $this->assertNotNull($post->getId());

        $repo = $em->getRepository(SocialPost::class);
        $found = $repo->find($post->getId());

        $this->assertNotNull($found);
        $this->assertSame($this->user->getId(), $found->getUser()->getId());
        $this->assertSame(SocialPost::TYPE_POST, $found->getType());
        $this->assertSame('Hello world!', $found->getContent());
        $this->assertSame(['image' => 'img.jpg'], $found->getMedia());
        $this->assertSame(5, $found->getLikesCount());
    }

    public function testFindByUser(): void
    {
        $post1 = new SocialPost($this->user, SocialPost::TYPE_POST);
        $post1->setContent('First post');
        $post2 = new SocialPost($this->user, SocialPost::TYPE_STORY);
        $post2->setContent('Second post');

        $em = self::getEntityManager();
        $em->persist($post1);
        $em->persist($post2);
        $em->flush();

        $repo = $em->getRepository(SocialPost::class);
        $byUser = $repo->findBy(['user' => $this->user->getId()]);

        $this->assertCount(2, $byUser);
        $contents = array_map(fn($p) => $p->getContent(), $byUser);
        $this->assertContains('First post', $contents);
        $this->assertContains('Second post', $contents);
    }

    public function testUpdateSocialPost(): void
    {
        $post = new SocialPost($this->user, SocialPost::TYPE_POST);
        $post->setContent('Original');
        $post->setLikesCount(0);

        $em = self::getEntityManager();
        $em->persist($post);
        $em->flush();

        $id = $post->getId();

        $post->setContent('Updated');
        $post->setLikesCount(10);
        $em->flush();

        $found = $em->getRepository(SocialPost::class)->find($id);
        $this->assertNotNull($found);
        $this->assertSame('Updated', $found->getContent());
        $this->assertSame(10, $found->getLikesCount());
    }

    public function testDeleteSocialPost(): void
    {
        $post = new SocialPost($this->user, SocialPost::TYPE_POST);
        $post->setContent('Delete me');

        $em = self::getEntityManager();
        $em->persist($post);
        $em->flush();

        $id = $post->getId();

        $em->remove($post);
        $em->flush();

        $deleted = $em->getRepository(SocialPost::class)->find($id);
        $this->assertNull($deleted);
    }
}
