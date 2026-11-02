<?php

declare(strict_types=1);

namespace App\News\Controller;

use App\News\Service\NewsService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/news')]
class NewsController extends BaseController
{
    public function __construct(private readonly NewsService $service) {}

    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) throw new InvalidArgumentException('Invalid JSON');
        return $dataOr400 ?? [];
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return $this->handleException('news', $e);
        }
    }

    // ==================== Endpoints ====================

    #[Route('/articles', name: 'news_create_article', methods: ['POST'])]
    public function createArticle(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createArticleAction($request));
    }

    private function createArticleAction(Request $request): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId();
        $data = $this->getJsonData($request);
        $this->validateArticleData($data);
        $article = $this->service->createArticle(
            (int)$userId,
            $this->sanitizeString($data['title']),
            $this->sanitizeString($data['content']),
            $this->sanitizeString($data['summary'] ?? ''),
            $this->sanitizeString($data['category'])
        );
        if (isset($data['tags']) && is_array($data['tags'])) {
            $article->setTags(array_map([$this, 'sanitizeString'], $data['tags']));
        }
        $this->logAction('article_created', ['article_id' => $article->getId(), 'author_id' => $userId]);
        $this->incrementMetric('news_articles_created_total');
        return $this->json(['data' => $article], Response::HTTP_CREATED);
    }

    #[Route('/articles', name: 'news_get_articles', methods: ['GET'])]
    public function getArticles(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getArticlesAction($request));
    }

    private function getArticlesAction(Request $request): JsonResponse
    {
        $category = $request->query->get('category');
        $authorId = $request->query->get('author_id');
        if ($authorId !== null) {
            $articles = $this->service->getAuthorArticles((int)$authorId);
        } else {
            $articles = $this->service->getPublishedArticles($category !== null ? $category : null);
        }
        $this->logAction('articles_retrieved', ['count' => count($articles)]);
        $this->incrementMetric('news_articles_listed_total');
        return $this->json(['data' => array_values($articles)]);
    }

    #[Route('/articles/search', name: 'news_search_articles', methods: ['GET'])]
    public function searchArticles(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->searchArticlesAction($request));
    }

    private function searchArticlesAction(Request $request): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        if ($query === '') throw new InvalidArgumentException('Search query "q" is required');
        $articles = $this->service->searchArticles($query);
        $this->logAction('articles_searched', ['query' => $query, 'count' => count($articles)]);
        $this->incrementMetric('news_articles_searched_total');
        return $this->json(['data' => array_values($articles)]);
    }

    #[Route('/articles/{id}', name: 'news_get_article', methods: ['GET'])]
    public function getArticle(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getArticleAction($id, $request));
    }

    private function getArticleAction(int $id, Request $request): JsonResponse
    {
        $article = $this->service->getArticle($id);
        if (!$article) throw new NotFoundHttpException('Article not found');
        $this->service->incrementViewCount($id);
        $this->logAction('article_viewed', ['article_id' => $id]);
        $this->incrementMetric('news_article_viewed_total');
        return $this->json(['data' => $article]);
    }

    #[Route('/articles/{id}', name: 'news_update_article', methods: ['PUT'])]
    public function updateArticle(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateArticleAction($id, $request));
    }

    private function updateArticleAction(int $id, Request $request): JsonResponse
    {
        $userId = (int)$this->getAuthenticatedUserId();
        $data = $this->getJsonData($request);
        $article = $this->service->updateArticle($id, $data, $userId);
        if (!$article) throw new NotFoundHttpException('Article not found');
        $this->logAction('article_updated', ['article_id' => $id, 'user_id' => $userId]);
        $this->incrementMetric('news_articles_updated_total');
        return $this->json(['data' => $article]);
    }

    #[Route('/articles/{id}', name: 'news_delete_article', methods: ['DELETE'])]
    public function deleteArticle(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->deleteArticleAction($id, $request));
    }

    private function deleteArticleAction(int $id, Request $request): JsonResponse
    {
        $userId = (int)$this->getAuthenticatedUserId();
        $deleted = $this->service->deleteArticle($id, $userId);
        if (!$deleted) throw new NotFoundHttpException('Article not found');
        $this->logAction('article_deleted', ['article_id' => $id, 'user_id' => $userId]);
        $this->incrementMetric('news_articles_deleted_total');
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/articles/{id}/comments', name: 'news_get_comments', methods: ['GET'])]
    public function getComments(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getCommentsAction($id));
    }

    private function getCommentsAction(int $id): JsonResponse
    {
        $comments = $this->service->getArticleComments($id);
        $this->logAction('comments_retrieved', ['article_id' => $id, 'count' => count($comments)]);
        return $this->json(['data' => array_values($comments)]);
    }

    #[Route('/comments', name: 'news_add_comment', methods: ['POST'])]
    public function addComment(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->addCommentAction($request));
    }

    private function addCommentAction(Request $request): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId();
        $data = $this->getJsonData($request);
        $articleId = $data['article_id'] ?? null;
        $content = $data['content'] ?? '';
        $parentId = isset($data['parent_id']) ? (int)$data['parent_id'] : null;
        if ($articleId === null) throw new InvalidArgumentException('Article ID is required');
        if (empty(trim($content))) throw new InvalidArgumentException('Comment content is required');
        $comment = $this->service->addComment((int)$articleId, (int)$userId, $this->sanitizeString($content), $parentId);
        $this->logAction('comment_added', ['comment_id' => $comment->getId(), 'article_id' => $articleId, 'user_id' => $userId]);
        $this->incrementMetric('news_comments_created_total');
        return $this->json(['data' => $comment], Response::HTTP_CREATED);
    }

    #[Route('/comments/{id}', name: 'news_delete_comment', methods: ['DELETE'])]
    public function deleteComment(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->deleteCommentAction($id, $request));
    }

    private function deleteCommentAction(int $id, Request $request): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId();
        $deleted = $this->service->deleteComment($id, (int)$userId);
        if (!$deleted) throw new NotFoundHttpException('Comment not found');
        $this->logAction('comment_deleted', ['comment_id' => $id, 'user_id' => $userId]);
        $this->incrementMetric('news_comments_deleted_total');
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    // ========== PRIVATE HELPERS ==========

    private function getAuthenticatedUserId(): string
    {
        $user = $this->getUser();
        if (!$user) throw new AccessDeniedException('Authentication required');
        return $user->getId();
    }

    private function sanitizeString(?string $value): string
    {
        return $value !== null ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : '';
    }

    private function validateArticleData(array $data): void
    {
        if (empty(trim($data['title'] ?? ''))) throw new InvalidArgumentException('Title is required');
        if (empty(trim($data['content'] ?? ''))) throw new InvalidArgumentException('Content is required');
        if (empty(trim($data['category'] ?? ''))) throw new InvalidArgumentException('Category is required');
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("NewsController::{$context} error: " . $e->getMessage());
        $this->logAction("{$context}_error", ['error' => $e->getMessage()]);
        $this->incrementMetric("{$context}_errors");
        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function logAction(string $action, array $context = []): void
    {
        $userId = $this->getUser()?->getUserIdentifier() ?? 'anonymous';
        $timestamp = new DateTimeImmutable();
        error_log(json_encode([
            'timestamp' => $timestamp->format(DateTimeImmutable::ATOM),
            'action' => $action,
            'userId' => $userId,
            'module' => 'News',
            'context' => $context
        ]));
    }

    private function incrementMetric(string $metric): void
    {
        error_log(json_encode([
            'metric' => $metric,
            'timestamp' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM)
        ]));
    }
}