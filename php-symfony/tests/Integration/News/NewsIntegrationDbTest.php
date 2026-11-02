<?php declare(strict_types=1);

namespace App\Tests\Integration\News;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\News\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class NewsIntegrationDbTest extends DatabaseIntegrationTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = self::getEntityManager();
    }

    public function testCreateArticleAndPersist(): void
    {
        $article = new Article();
        $article->setTitle('Test News Title');
        $article->setContent('This is the content of the news article.');
        $article->setSummary('Short summary');
        $article->setCategory('general');
        $article->setAuthor('user-100');

        $this->em->persist($article);
        $this->em->flush();

        $this->assertNotNull($article->getId());
        $this->assertSame('Test News Title', $article->getTitle());
        $this->assertSame('user-100', $article->getAuthor());
        $this->assertSame('published', $article->getStatus());
        $this->assertSame(0, $article->getViewCount());
    }

    public function testFindArticleById(): void
    {
        $article = new Article();
        $article->setTitle('Another News');
        $article->setContent('More content');
        $article->setSummary('Summary');
        $article->setCategory('technology');
        $article->setAuthor('user-200');
        $this->em->persist($article);
        $this->em->flush();

        $id = $article->getId();
        $repo = $this->em->getRepository(Article::class);
        $found = $repo->find($id);
        $this->assertNotNull($found);
        $this->assertSame('Another News', $found->getTitle());
        $this->assertSame('technology', $found->getCategory());
    }

    public function testUpdateArticle(): void
    {
        $article = new Article();
        $article->setTitle('Original Title');
        $article->setContent('Original content');
        $article->setSummary('Original summary');
        $article->setCategory('health');
        $article->setAuthor('user-300');
        $this->em->persist($article);
        $this->em->flush();

        $article->setTitle('Updated Title');
        $article->setSummary('Updated summary');
        $article->setViewCount(42);
        $this->em->flush();

        $repo = $this->em->getRepository(Article::class);
        $updated = $repo->find($article->getId());
        $this->assertSame('Updated Title', $updated->getTitle());
        $this->assertSame('Updated summary', $updated->getSummary());
        $this->assertSame(42, $updated->getViewCount());
    }

    public function testDeleteArticle(): void
    {
        $article = new Article();
        $article->setTitle('To Delete');
        $article->setContent('Will be deleted');
        $article->setSummary('Delete me');
        $article->setCategory('sports');
        $article->setAuthor('user-400');
        $this->em->persist($article);
        $this->em->flush();

        $id = $article->getId();
        $this->em->remove($article);
        $this->em->flush();

        $repo = $this->em->getRepository(Article::class);
        $this->assertNull($repo->find($id));
    }
}
