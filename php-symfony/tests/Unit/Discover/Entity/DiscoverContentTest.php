<?php declare(strict_types=1);

namespace App\Tests\Unit\Discover\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Discover\Entity\DiscoverContent;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DiscoverContentTest extends TestCase
{
    private function createContent(array $overrides = []): DiscoverContent
    {
        $content = new DiscoverContent();
        // Set required fields
        $content->setTitle('Explore the World');
        $content->setType('article');
        // Set publishedAt to current time
        $content->setPublishedAt(new \DateTime());
        foreach ($overrides as $prop => $value) {
            $setter = 'set' . ucfirst($prop);
            if (method_exists($content, $setter)) {
                $content->$setter($value);
            }
        }
        return $content;
    }

    public function testConstructorInitializesIdAndDefaults(): void
    {
        $content = $this->createContent([]);
        $this->assertIsString($content->getId());
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F-]{36}$/', $content->getId());
        $this->assertSame(0, $content->getViewCount());
        $this->assertSame(0, $content->getLikeCount());
        $this->assertNull($content->getDescription());
        $this->assertNull($content->getMediaUrl());
        $this->assertNull($content->getLinkUrl());
        $this->assertNull($content->getUserId());
        $this->assertNull($content->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $content->getPublishedAt());
        // By default, viewCount and likeCount are 0
        $this->assertSame(0, $content->getViewCount());
        $this->assertSame(0, $content->getLikeCount());
    }

    public function testGettersAndSetters(): void
    {
        $content = $this->createContent([]);

        $content->setTitle('New Title');
        $this->assertSame('New Title', $content->getTitle());

        $content->setType('video');
        $this->assertSame('video', $content->getType());

        $content->setDescription('A description');
        $this->assertSame('A description', $content->getDescription());

        $content->setMediaUrl('https://example.com/image.jpg');
        $this->assertSame('https://example.com/image.jpg', $content->getMediaUrl());

        $content->setLinkUrl('https://example.com/article');
        $this->assertSame('https://example.com/article', $content->getLinkUrl());

        $content->setUserId('user-123');
        $this->assertSame('user-123', $content->getUserId());

        $content->setMetadata(['tags' => ['travel', 'nature']]);
        $this->assertSame(['tags' => ['travel', 'nature']], $content->getMetadata());

        $content->setViewCount(50);
        $this->assertSame(50, $content->getViewCount());

        $content->setLikeCount(10);
        $this->assertSame(10, $content->getLikeCount());
    }

    public function testNullableSetters(): void
    {
        $content = $this->createContent([]);

        $content->setDescription(null);
        $this->assertNull($content->getDescription());

        $content->setMediaUrl(null);
        $this->assertNull($content->getMediaUrl());

        $content->setLinkUrl(null);
        $this->assertNull($content->getLinkUrl());

        $content->setUserId(null);
        $this->assertNull($content->getUserId());

        $content->setMetadata(null);
        $this->assertNull($content->getMetadata());
    }
}
