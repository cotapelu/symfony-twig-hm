<?php

namespace App\News\Service;

use App\News\Entity\NewsArticle;
use App\News\Entity\NewsComment;
use InvalidArgumentException;

class NewsService
{
    private array $articles = [];
    private array $comments = [];
    private int $nextArticleId = 1;
    private int $nextCommentId = 1;

    public function createArticle(int $authorId, string $title, string $content, string $summary, string $category)
    {
        if ($authorId <= 0) {
            throw new InvalidArgumentException('Invalid author ID');
        }
        $article = new NewsArticle($authorId, $title, $content, $summary, $category);
        $article->setId($this->nextArticleId++);
        $this->articles[$article->getId()] = $article;
        return $article;
    }

    public function getArticle(int $id)
    {
        return $this->articles[$id] ?? null;
    }

    public function incrementViewCount(int $id)
    {
        if (isset($this->articles[$id])) {
            $this->articles[$id]->incrementViewCount();
        }
    }

    public function getPublishedArticles(?string $category = null)
    {
        $articles = array_filter($this->articles, fn($a) => $a->getStatus() === 'published');
        if ($category) {
            $articles = array_filter($articles, fn($a) => $a->getCategory() === $category);
        }
        return array_values($articles);
    }

    public function getAuthorArticles(int $authorId)
    {
        return array_filter($this->articles, fn($a) => $a->getAuthorId() === $authorId);
    }

    public function updateArticle(int $id, array $data, int $requesterId)
    {
        $article = $this->getArticle($id);
        if (!$article) {
            return null;
        }
        if ($article->getAuthorId() !== $requesterId) {
            throw new InvalidArgumentException('Unauthorized: not the author');
        }

        if (isset($data['title'])) {
            $article->setTitle($data['title']);
        }
        if (isset($data['content'])) {
            $article->setContent($data['content']);
        }
        if (isset($data['tags'])) {
            $article->setTags($data['tags']);
        }
        if (isset($data['status'])) {
            $allowed = ['draft', 'published', 'archived'];
            if (!in_array($data['status'], $allowed, true)) {
                throw new InvalidArgumentException('Invalid status');
            }
            $article->setStatus($data['status']);
        }
        if (isset($data['featured_image'])) {
            $article->setFeaturedImage($data['featured_image']);
        }

        return $article;
    }

    public function deleteArticle(int $id, int $requesterId)
    {
        $article = $this->getArticle($id);
        if (!$article) {
            return false;
        }
        if ($article->getAuthorId() !== $requesterId) {
            throw new InvalidArgumentException('Unauthorized: not the author');
        }
        unset($this->articles[$id]);
        // Also delete comments? Probably cascade; for simplicity, remove comments for this article
        foreach ($this->comments as $cid => $comment) {
            if ($comment->getArticleId() === $id) {
                unset($this->comments[$cid]);
            }
        }
        return true;
    }

    public function searchArticles(string $query)
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        return array_values(array_filter($this->articles, function($a) use ($query) {
            return stripos($a->getTitle(), $query) !== false || stripos($a->getContent(), $query) !== false;
        }));
    }

    public function addComment(int $articleId, int $userId, string $content, ?int $parentId = null)
    {
        if (!isset($this->articles[$articleId])) {
            throw new InvalidArgumentException('Article not found');
        }
        $comment = new NewsComment($articleId, $userId, $content);
        $comment->setId($this->nextCommentId++);
        $comment->setParentId($parentId);
        $this->comments[$comment->getId()] = $comment;
        return $comment;
    }

    public function getArticleComments(int $articleId)
    {
        return array_filter($this->comments, fn($c) => $c->getArticleId() === $articleId);
    }

    public function deleteComment(int $id, int $requesterId)
    {
        if (!isset($this->comments[$id])) {
            return false;
        }
        $comment = $this->comments[$id];
        // Only the comment author can delete
        if ($comment->getUserId() !== $requesterId) {
            throw new InvalidArgumentException('Unauthorized: not the comment author');
        }
        unset($this->comments[$id]);
        return true;
    }
}
