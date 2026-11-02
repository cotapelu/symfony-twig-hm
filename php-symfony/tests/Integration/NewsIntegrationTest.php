<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\News\Entity\Article;
use App\News\Entity\Category;
use PHPUnit\Framework\TestCase;

/**
 * News Module Integration Test
 * 
 * Tests the News module as a real system.
 */
#[AllowMockObjectsWithoutExpectations]
class NewsIntegrationTest extends TestCase
{
    public function testArticleEntityCompleteWorkflow(): void
    {
        // Create article
        $article = new Article();
        
        $publishedDate = new \DateTimeImmutable('2026-02-25 08:00:00');
        
        $article->setTitle('Breaking: New Technology Revolution')
            ->setSummary('A brief summary of the revolutionary technology')
            ->setContent('Full detailed content of the article goes here. This is where the main story is covered in detail with all the facts, quotes, and analysis.')
            ->setCategory('technology')
            ->setAuthor('Jane Smith')
            ->setImageUrl('https://example.com/tech-news.jpg')
            ->setViewCount(500)
            ->setPublishedAt($publishedDate);

        // Verify all properties
        $this->assertEquals('Breaking: New Technology Revolution', $article->getTitle());
        $this->assertEquals('A brief summary of the revolutionary technology', $article->getSummary());
        $this->assertStringContainsString('Full detailed content', $article->getContent());
        $this->assertEquals('technology', $article->getCategory());
        $this->assertEquals('Jane Smith', $article->getAuthor());
        $this->assertEquals('https://example.com/tech-news.jpg', $article->getImageUrl());
        $this->assertEquals(500, $article->getViewCount());
        $this->assertEquals($publishedDate, $article->getPublishedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $article->getCreatedAt());
    }

    public function testArticleWithNullImageUrl(): void
    {
        $article = new Article();
        $article->setTitle('Text Only News')
            ->setSummary('No image for this article')
            ->setContent('Content without image')
            ->setCategory('local')
            ->setAuthor('Reporter');

        $this->assertNull($article->getImageUrl());
    }

    public function testArticleWithNullViewCount(): void
    {
        $article = new Article();
        $article->setTitle('New Article')
            ->setSummary('Summary')
            ->setContent('Content')
            ->setCategory('sports')
            ->setAuthor('Writer');

        // Default viewCount is 0 (not null)
        $this->assertSame(0, $article->getViewCount());
    }

    public function testCategoryEntityCompleteWorkflow(): void
    {
        // Create category
        $category = new Category();
        
        $category->setName('Business')
            ->setSlug('business')
            ->setArticleCount(100);

        // Verify all properties
        $this->assertEquals('Business', $category->getName());
        $this->assertEquals('business', $category->getSlug());
        $this->assertEquals(100, $category->getArticleCount());
    }

    public function testMultipleArticlesInCategory(): void
    {
        // Create category
        $category = new Category();
        $category->setName('Politics')
            ->setSlug('politics')
            ->setArticleCount(0);

        // Simulate adding articles
        $article1 = new Article();
        $article1->setTitle('Article 1')->setCategory('politics');
        
        $article2 = new Article();
        $article2->setTitle('Article 2')->setCategory('politics');
        
        $article3 = new Article();
        $article3->setTitle('Article 3')->setCategory('politics');

        // Update count
        $category->setArticleCount(3);

        $this->assertEquals(3, $category->getArticleCount());
    }

    public function testArticleCategories(): void
    {
        $categories = ['technology', 'business', 'sports', 'entertainment', 'politics', 'health', 'science', 'local'];
        
        foreach ($categories as $categoryName) {
            $article = new Article();
            $article->setTitle("News in $categoryName")
                ->setSummary("Summary for $categoryName")
                ->setContent("Content for $categoryName")
                ->setCategory($categoryName)
                ->setAuthor('Reporter');
            
            $this->assertEquals($categoryName, $article->getCategory());
        }
    }

    public function testArticlePublishedAtCanBeUpdated(): void
    {
        $article = new Article();
        
        $originalDate = new \DateTimeImmutable('2026-01-01 12:00:00');
        $newDate = new \DateTimeImmutable('2026-02-25 15:30:00');
        
        $article->setPublishedAt($originalDate);
        $this->assertEquals($originalDate, $article->getPublishedAt());
        
        $article->setPublishedAt($newDate);
        $this->assertEquals($newDate, $article->getPublishedAt());
    }

    public function testArticleViewCountIncrements(): void
    {
        $article = new Article();
        $article->setTitle('Popular Article')
            ->setSummary('Summary')
            ->setContent('Content')
            ->setCategory('trending')
            ->setAuthor('Author')
            ->setViewCount(0);

        // Simulate view count increment
        $currentViews = $article->getViewCount();
        $article->setViewCount($currentViews + 100);
        
        $this->assertEquals(100, $article->getViewCount());
        
        $article->setViewCount($article->getViewCount() + 250);
        $this->assertEquals(350, $article->getViewCount());
    }

    public function testCategoryDefaultValues(): void
    {
        $category = new Category();
        
        $this->assertEquals(0, $category->getArticleCount());
    }

    public function testNewsIntegrationFlow(): void
    {
        // Create category first
        $category = new Category();
        $category->setName('Technology')
            ->setSlug('technology')
            ->setArticleCount(0);

        // Create articles in this category
        $articles = [];
        for ($i = 1; $i <= 3; $i++) {
            $article = new Article();
            $article->setTitle("Tech News $i")
                ->setSummary("Summary for tech news $i")
                ->setContent("Full content for tech news $i")
                ->setCategory('technology')
                ->setAuthor('Tech Reporter')
                ->setViewCount(rand(100, 1000));
            
            $articles[] = $article;
        }

        // Update category count
        $category->setArticleCount(count($articles));

        // Verify integration
        $this->assertEquals(3, $category->getArticleCount());
        $this->assertEquals('technology', $category->getSlug());
        
        foreach ($articles as $article) {
            $this->assertEquals('technology', $article->getCategory());
            $this->assertGreaterThan(0, $article->getViewCount());
        }
    }
}
