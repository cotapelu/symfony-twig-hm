<?php

declare(strict_types=1);

namespace App\Tests\Integration\Discover;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Discover\Entity\DiscoverContent;

#[AllowMockObjectsWithoutExpectations]
class DiscoverContentIntegrationDbTest extends DatabaseIntegrationTestCase
{
    public function testCreateAndRetrieveDiscoverContent(): void
    {
        $content = new DiscoverContent();
        $content->setTitle('Sample Article');
        $content->setType('article');
        $content->setDescription('A sample article description');
        $content->setMediaUrl('https://example.com/image.jpg');
        $content->setLinkUrl('https://example.com/article');
        $content->setUserId('user-123');
        $content->setMetadata(['tags' => ['news', 'sample']]);
        $content->setPublishedAt(new \DateTime());
        $content->setViewCount(0);
        $content->setLikeCount(0);

        $em = self::getEntityManager();
        $em->persist($content);
        $em->flush();

        $this->assertNotNull($content->getId());

        $found = $em->getRepository(DiscoverContent::class)->find($content->getId());

        $this->assertNotNull($found);
        $this->assertSame('Sample Article', $found->getTitle());
        $this->assertSame('article', $found->getType());
        $this->assertSame('A sample article description', $found->getDescription());
        $this->assertSame('https://example.com/image.jpg', $found->getMediaUrl());
        $this->assertSame('https://example.com/article', $found->getLinkUrl());
        $this->assertSame('user-123', $found->getUserId());
        $this->assertSame(['tags' => ['news', 'sample']], $found->getMetadata());
        $this->assertInstanceOf(\DateTimeInterface::class, $found->getPublishedAt());
        $this->assertSame(0, $found->getViewCount());
        $this->assertSame(0, $found->getLikeCount());
    }

    public function testUpdateDiscoverContent(): void
    {
        $content = new DiscoverContent();
        $content->setTitle('Old Title');
        $content->setType('video');
        $content->setPublishedAt(new \DateTime());
        $content->setViewCount(10);

        $em = self::getEntityManager();
        $em->persist($content);
        $em->flush();

        $content->setTitle('New Title');
        $content->setViewCount(20);
        $em->flush();

        $found = $em->getRepository(DiscoverContent::class)->find($content->getId());
        $this->assertSame('New Title', $found->getTitle());
        $this->assertSame(20, $found->getViewCount());
    }

    public function testDeleteDiscoverContent(): void
    {
        $content = new DiscoverContent();
        $content->setTitle('To Delete');
        $content->setType('post');
        $content->setPublishedAt(new \DateTime());

        $em = self::getEntityManager();
        $em->persist($content);
        $em->flush();

        $id = $content->getId();
        $em->remove($content);
        $em->flush();

        $found = $em->getRepository(DiscoverContent::class)->find($id);
        $this->assertNull($found);
    }

    public function testDiscoverContentIdIsUuid(): void
    {
        $content = new DiscoverContent();
        $content->setTitle('Test');
        $content->setType('article');
        $content->setPublishedAt(new \DateTime());

        $em = self::getEntityManager();
        $em->persist($content);
        $em->flush();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $content->getId());
    }
}
