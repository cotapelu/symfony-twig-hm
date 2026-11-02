<?php

declare(strict_types=1);

namespace App\Search\Controller;

use App\Search\Service\SearchService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use InvalidArgumentException;

#[Route('/api/search')]
class SearchController extends BaseController
{
    private const MAX_QUERY_LENGTH = 500;
    private const MAX_LIMIT = 100;
    private const ALLOWED_ENTITY_TYPES = ['user','post','product','service','event','news','all'];

    public function __construct(
        private SearchService $searchService,
        private ?TokenStorageInterface $tokenStorage = null,
        ?AuthorizationCheckerInterface $authChecker = null
    ) {
        parent::__construct();
        $this->authChecker = $authChecker;
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['action'=>$action,'context'=>$context], JSON_UNESCAPED_UNICODE));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode(['metric'=>$metric,'tags'=>$tags]));
    }

    private function sanitizeString(?string $value): string
    {
        return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function isAuthenticated(): bool
    {
        if ($this->tokenStorage === null) return false;
        $token = $this->tokenStorage->getToken();
        if ($token === null) return false;
        if ($this->authChecker === null) return false;
        return $this->authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }

    private function getAuthenticatedUserId(): ?string
    {
        if (!$this->isAuthenticated()) return null;
        $token = $this->tokenStorage !== null ? $this->tokenStorage->getToken() : null;
        if (!$token) return null;
        $user = $token->getUser();
        if (!$user || !method_exists($user, 'getId')) return null;
        return (string) $user->getId();
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['status'=>400,'error'=>$e->getMessage()], 400); }
        catch (AccessDeniedHttpException $e) { return $this->json(['status'=>403,'error'=>$e->getMessage()], 403); }
        catch (\Exception $e) { error_log('SearchController error: '.$e->getMessage()); return $this->json(['status'=>500,'error'=>'Internal server error'], 500); }
    }

    private function requireUserId(Request $request): string|JsonResponse
    {
        $auth = $this->getAuthenticatedUserId();
        $reqId = $request->query->get('userId');
        if ($reqId !== null && $auth !== null && $reqId !== $auth) {
            return $this->json(['error' => 'Access denied to other users search history'], 403);
        }
        $userId = $auth ?? $reqId;
        if ($userId === null) {
            return $this->json(['error' => 'Authentication required or userId parameter required'], 401);
        }
        return (string)$userId;
    }

    private function validateSearchParams(string $query, string $type, int &$limit): void
    {
        if (empty(trim($query))) throw new InvalidArgumentException('Search query is required');
        if (strlen($query) > self::MAX_QUERY_LENGTH) throw new InvalidArgumentException('Search query too long (max '.self::MAX_QUERY_LENGTH.' chars)');
        if (!in_array($type, self::ALLOWED_ENTITY_TYPES, true)) throw new InvalidArgumentException('Invalid entity type');
        if ($limit < 1 || $limit > self::MAX_LIMIT) $limit = 20;
    }

    #[Route('', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->searchAction($request));
    }

    private function searchAction(Request $request): JsonResponse
    {
        $userId = $this->requireUserId($request);
        if ($userId instanceof JsonResponse) return $userId;
        $query = $request->query->get('q', '');
        $type = $request->query->get('type', 'all');
        $limit = (int) $request->query->get('limit', 20);
        $this->validateSearchParams($query, $type, $limit);
        $sanitized = $this->sanitizeString($query);
        $results = $this->searchService->search($userId, $sanitized, $type, $limit);
        $this->searchService->saveSearchHistory($userId, $sanitized, count($results));
        $this->logAction('search.executed', ['userId'=>$userId,'query'=>$query,'entityType'=>$type,'results'=>count($results)]);
        $this->incrementMetric('search.executed', ['entityType'=>$type]);
        return $this->json(['query'=>$query,'results'=>array_map(fn($r)=>['id'=>$r->getId(),'entityType'=>$r->getEntityType(),'entityId'=>$r->getEntityId(),'score'=>$r->getScore()],$results),'total'=>count($results)]);
    }

    #[Route('/history', name: 'search_history', methods: ['GET'])]
    public function getHistory(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->historyAction($request));
    }

    private function historyAction(Request $request): JsonResponse
    {
        $userId = $this->requireUserId($request);
        if ($userId instanceof JsonResponse) return $userId;
        $limit = (int) $request->query->get('limit', 10);
        if ($limit < 1 || $limit > 100) $limit = 10;
        $history = $this->searchService->getRecentSearches($userId, $limit);
        return $this->json(['searches'=>array_map(fn($h)=>['id'=>$h->getId(),'query'=>$h->getQuery(),'resultCount'=>$h->getResultCount(),'createdAt'=>$h->getCreatedAt()->format('Y-m-d H:i:s')],$history)]);
    }

    #[Route('/trending', name: 'search_trending', methods: ['GET'])]
    public function getTrending(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->trendingAction($request));
    }

    private function trendingAction(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 10);
        if ($limit < 1 || $limit > 100) $limit = 10;
        return $this->json(['trending'=>$this->searchService->getTrendingQueries($limit)]);
    }
}