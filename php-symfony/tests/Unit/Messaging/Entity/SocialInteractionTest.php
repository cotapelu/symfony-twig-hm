<?php declare(strict_types=1);

namespace App\Tests\Unit\Messaging\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Messaging\Entity\SocialInteraction;
use App\Messaging\Entity\SocialPost;
use App\Core\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SocialInteractionTest extends TestCase
{
    private function createMockSocialPost(string $id = 'post-123'): MockObject&SocialPost
    {
        $post = $this->createMock(SocialPost::class);
        $post->method('getId')->willReturn($id);
        return $post;
    }

    private function createMockUser(string $id = 'user-123'): MockObject&User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        return $user;
    }

    public function testConstructor(): void
    {
        $post = $this->createMockSocialPost();
        $user = $this->createMockUser();
        $interaction = new SocialInteraction($post, $user, SocialInteraction::TYPE_LIKE);

        $this->assertIsString($interaction->getId());
        $this->assertSame($post, $interaction->getPost());
        $this->assertSame($user, $interaction->getUser());
        $this->assertSame(SocialInteraction::TYPE_LIKE, $interaction->getType());
        $this->assertNull($interaction->getContent());
        $this->assertInstanceOf(\DateTime::class, $interaction->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $post = $this->createMockSocialPost();
        $user = $this->createMockUser();
        $interaction = new SocialInteraction($post, $user, SocialInteraction::TYPE_COMMENT);
        $interaction->setContent('Nice post!');

        $this->assertSame('Nice post!', $interaction->getContent());
    }

    public function testToArray(): void
    {
        $post = $this->createMockSocialPost('post-456');
        $user = $this->createMockUser('user-789');
        $interaction = new SocialInteraction($post, $user, SocialInteraction::TYPE_SHARE);
        $interaction->setContent('Sharing with my network');
        $createdAt = new \DateTime('2025-01-02 14:30:00');
        $ref = new \ReflectionProperty($interaction, 'createdAt');
        $ref->setAccessible(true);
        $ref->setValue($interaction, $createdAt);

        $arr = $interaction->toArray();

        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertSame('post-456', $arr['postId']);
        $this->assertSame('user-789', $arr['userId']);
        $this->assertSame(SocialInteraction::TYPE_SHARE, $arr['type']);
        $this->assertSame('Sharing with my network', $arr['content']);
        $this->assertSame('2025-01-02 14:30:00', $arr['createdAt']);
    }
}
