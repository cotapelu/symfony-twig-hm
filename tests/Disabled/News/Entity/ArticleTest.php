<?php declare(strict_types=1);

namespace App\Tests\Unit\News\Entity;

use App\News\Entity\Article;
use App\News\Entity\Category;
use PHPUnit\Framework\TestCase;

class ArticleTest extends TestCase
{
    private Article $article;

    protected function setUp(): void
    {
        $this->article = new Article();
    }

    public function testConstructorSetsCreatedAt(): void
    {
        $this->assertInstanceOf(\DateTime::class, $this->article->getCreatedAt());
    }

    public function testConstructorSetsPublishedAt(): void
    {
        $this->assertInstanceOf(\DateTime::class, $this->article->getPublishedAt());
    }

    public function testSetAndGetTitle(): void
    {
        $result = $this->article->setTitle('Breaking News Title');
        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals('Breaking News Title', $this->article->getTitle());
    }

    public function testSetAndGetSummary(): void
    {
        $result = $this->article->setSummary('This is a brief summary of the article');
        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals('This is a brief summary of the article', $this->article->getSummary());
    }

    public function testSetAndGetContent(): void
    {
        $content = 'Full article content goes here with detailed information...';
        $result = $this->article->setContent($content);
        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals($content, $this->article->getContent());
    }

    public function testSetAndGetCategory(): void
    {
        $result = $this->article->setCategory('technology');
        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals('technology', $this->article->getCategory());
    }

    public function testSetAndGetAuthor(): void
    {
        $result = $this->article->setAuthor('John Doe');
        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals('John Doe', $this->article->getAuthor());
    }

    public function testSetAndGetImageUrl(): void
    {
        $result = $this->article->setImageUrl('https://example.com/image.jpg');
        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals('https://example.com/image.jpg', $this->article->getImageUrl());
    }

    public function testImageUrlCanBeNull(): void
    {
        $this->assertNull($this->article->getImageUrl());
    }

    public function testSetAndGetViewCount(): void
    {
        $result = $this->article->setViewCount(1500);
        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals(1500, $this->article->getViewCount());
    }

    public function testViewCountCanBeNull(): void
    {
        $this->assertNull($this->article->getViewCount());
    }

    public function testSetAndGetPublishedAt(): void
    {
        $dateTime = new \DateTime('2026-02-25 10:00:00');
        $result = $this->article->setPublishedAt($dateTime);
        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals($dateTime, $this->article->getPublishedAt());
    }

    public function testFluentInterfaceChaining(): void
    {
        $result = $this->article
            ->setTitle('Test Title')
            ->setSummary('Test Summary')
            ->setContent('Test Content')
            ->setCategory('news')
            ->setAuthor('Test Author')
            ->setImageUrl('https://example.com/test.jpg')
            ->setViewCount(100)
            ->setPublishedAt(new \DateTime('2026-02-25'));

        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals('Test Title', $this->article->getTitle());
        $this->assertEquals('Test Summary', $this->article->getSummary());
        $this->assertEquals('Test Content', $this->article->getContent());
        $this->assertEquals('news', $this->article->getCategory());
        $this->assertEquals('Test Author', $this->article->getAuthor());
        $this->assertEquals('https://example.com/test.jpg', $this->article->getImageUrl());
        $this->assertEquals(100, $this->article->getViewCount());
    }
}

class CategoryTest extends TestCase
{
    private Category $category;

    protected function setUp(): void
    {
        $this->category = new Category();
    }

    public function testSetAndGetName(): void
    {
        $result = $this->category->setName('Technology');
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals('Technology', $this->category->getName());
    }

    public function testSetAndGetSlug(): void
    {
        $result = $this->category->setSlug('technology');
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals('technology', $this->category->getSlug());
    }

    public function testSetAndGetArticleCount(): void
    {
        $result = $this->category->setArticleCount(25);
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals(25, $this->category->getArticleCount());
    }

    public function testDefaultArticleCountIsZero(): void
    {
        $this->assertEquals(0, $this->category->getArticleCount());
    }

    public function testFluentInterfaceChaining(): void
    {
        $result = $this->category
            ->setName('Sports')
            ->setSlug('sports')
            ->setArticleCount(50);

        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals('Sports', $this->category->getName());
        $this->assertEquals('sports', $this->category->getSlug());
        $this->assertEquals(50, $this->category->getArticleCount());
    }
}
