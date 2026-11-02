<?php

declare(strict_types=1);

namespace App\Social\Controller;

use App\Social\Service\PostService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/api/social')]
class SocialController extends BaseController
{
    private const MAX_CONTENT_LENGTH = 5000;
    private const MAX_COMMENT_LENGTH = 1000;
    private const MAX_URL_LENGTH = 500;
    private const ALLOWED_MEDIA_TYPES = ['image', 'video', 'audio', 'document', 'link'];
    private const MAX_OFFSET = 10000;
    private const MAX_LIMIT = 100;

    public function __construct(
        private PostService $postService,
        private ?TokenStorageInterface $tokenStorage = null,
        ?AuthorizationCheckerInterface $authChecker = null
    ) {
        parent::__construct();
        $this->authChecker = $authChecker;
    }

    private function logAction(string $action, array $context = []): void
    {
        $logData = [
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
            'action' => $action,
            'context' => $context,
        ];
        error_log(json_encode($logData, JSON_UNESCAPED_UNICODE));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        $metricData = [
            'metric' => $metric,
            'tags' => $tags,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        ];
        error_log(json_encode($metricData));
    }

    private function sanitizeString(?string $value): string
    {
        return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function isAuthenticated(): bool
    {
        return $this->getUser() !== null;
    }

    private function getCurrentUserId(): ?string
    {
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getId')) {
            return null;
        }
        return (string) $user->getId();
    }

    private function requireAuth(): JsonResponse|true
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        return true;
    }

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        return $userId;
    }

    private function validateContent(?string $content): ?string
    {
        if ($content !== null && strlen($content) > self::MAX_CONTENT_LENGTH) {
            return 'Content too long (max ' . self::MAX_CONTENT_LENGTH . ' chars)';
        }
        return null;
    }

    private function validateMediaUrl(?string $url): ?string
    {
        if ($url !== null && strlen($url) > self::MAX_URL_LENGTH) {
            return 'Media URL too long';
        }
        if ($url !== null && !filter_var($url, FILTER_VALIDATE_URL)) {
            return 'Invalid media URL format';
        }
        return null;
    }

    private function validateMediaType(?string $type): ?string
    {
        if ($type !== null && !in_array($type, self::ALLOWED_MEDIA_TYPES)) {
            return 'Invalid media type';
        }
        return null;
    }

    private function validateCreatePostData(array $data): array
    {
        $errors = [];
        $errors = array_merge($errors, $this->ensurePostHasContentOrMedia($data));
        $errors = array_merge($errors, $this->ensureContentIsString($data));
        $errors = array_merge($errors, $this->ensureValidMediaUrl($data));
        $errors = array_merge($errors, $this->ensureValidMediaType($data));
        $errors = array_merge($errors, $this->ensureIsPublicBool($data));
        if (isset($data['content'])) {
            $contentError = $this->validateContent($data['content']);
            if ($contentError !== null) {
                $errors[] = $contentError;
            }
        }
        return $errors;
    }

    private function ensurePostHasContentOrMedia(array $data): array
    {
        if (empty($data['content']) && empty($data['media_url'])) {
            return ['Post must have content or media'];
        }
        return [];
    }

    private function ensureContentIsString(array $data): array
    {
        if (isset($data['content']) && !is_string($data['content'])) {
            return ['Content must be a string'];
        }
        return [];
    }

    private function ensureValidMediaUrl(array $data): array
    {
        if (isset($data['media_url']) && !filter_var($data['media_url'], FILTER_VALIDATE_URL)) {
            return ['Invalid media URL'];
        }
        return [];
    }

    private function ensureValidMediaType(array $data): array
    {
        if (isset($data['media_type']) && !in_array($data['media_type'], self::ALLOWED_MEDIA_TYPES)) {
            return ['Invalid media type'];
        }
        return [];
    }

    private function ensureIsPublicBool(array $data): array
    {
        if (isset($data['is_public']) && !is_bool($data['is_public'])) {
            return ['is_public must be boolean'];
        }
        return [];
    }

    #[Route('/posts', methods: ['POST'])]
    public function createPost(Request $request): JsonResponse
    {
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) {
            return $userIdOr401;
        }
        $userId = $userIdOr401;

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;

        $errors = $this->validateCreatePostData($data);
        if (!empty($errors)) {
            return $this->json(['error' => 'Validation failed', 'errors' => $errors], 400);
        }

        $post = $this->postService->createPost(
            userId: $userId,
            content: $data['content'] ?? null,
            mediaUrl: $data['media_url'] ?? null,
            mediaType: $data['media_type'] ?? null,
            metadata: $data['metadata'] ?? [],
            isPublic: $data['is_public'] ?? true,
        );

        return $this->json($post->toArray(), 201);
    }

    #[Route('/posts', methods: ['GET'])]
    public function getPosts(Request $request): JsonResponse
    {
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) {
            return $userIdOr401;
        }
        $userId = $userIdOr401;

        $offset = $request->query->getInt('offset', 0);
        $limit = $request->query->getInt('limit', 20);

        // Validate pagination (clamp to defaults if out of range)
        if ($offset < 0 || $offset > self::MAX_OFFSET) {
            $offset = 0;
        }
        if ($limit < 1 || $limit > self::MAX_LIMIT) {
            $limit = 20;
        }

        if ($request->query->get('public')) {
            $posts = $this->postService->getPublicFeed($offset, $limit);
        } else {
            $posts = $this->postService->getFeedForUser($userId, $offset, $limit);
        }

        return $this->json($posts);
    }

    #[Route('/posts/{id}', methods: ['GET'])]
    public function getPost(string $id): JsonResponse
    {
        $postDetail = $this->postService->getPostDetail($id);

        if (!$postDetail) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        return $this->json($postDetail);
    }

    #[Route('/posts/{id}/like', methods: ['POST'])]
    public function likePost(string $id): JsonResponse
    {
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) {
            return $userIdOr401;
        }
        $userId = $userIdOr401;

        try {
            $this->postService->likePost($id, $userId);
            return $this->json(['message' => 'Post liked/unliked successfully']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[Route('/posts/{id}/comments', methods: ['POST'])]
    public function commentPost(Request $request, string $id): JsonResponse
    {
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) {
            return $userIdOr401;
        }
        $userId = $userIdOr401;

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;

        $content = $data['content'] ?? '';

        if (empty($content)) {
            return $this->json(['error' => 'Comment content is required'], 400);
        }

        if (strlen($content) > self::MAX_COMMENT_LENGTH) {
            return $this->json(['error' => 'Comment too long (max ' . self::MAX_COMMENT_LENGTH . ' chars)'], 400);
        }

        try {
            $this->postService->commentPost($id, $userId, $content);
            return $this->json(['message' => 'Comment added successfully'], 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[Route('/posts/{id}/share', methods: ['POST'])]
    public function sharePost(string $id): JsonResponse
    {
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) {
            return $userIdOr401;
        }
        $userId = $userIdOr401;

        try {
            $this->postService->sharePost($id, $userId);
            return $this->json(['message' => 'Post shared successfully']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[Route('/posts/{id}', methods: ['DELETE'])]
    public function deletePost(string $id): JsonResponse
    {
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) {
            return $userIdOr401;
        }
        $userId = $userIdOr401;

        try {
            $this->postService->deletePost($id, $userId);
            return $this->json(['message' => 'Post deleted successfully']);
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'not found') !== false || stripos($msg, 'Post not found') !== false) {
                return $this->json(['error' => $msg], 404);
            }
            return $this->json(['error' => $msg], 403);
        }
    }

    #[Route('/posts/{id}/comments', methods: ['GET'])]
    public function getPostComments(string $id, Request $request): JsonResponse
    {
        $postDetail = $this->postService->getPostDetail($id);

        if (!$postDetail) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        return $this->json($postDetail['comments']);
    }

    #[Route('/posts/{id}/likes', methods: ['GET'])]
    public function getPostLikes(string $id, Request $request): JsonResponse
    {
        $postDetail = $this->postService->getPostDetail($id);

        if (!$postDetail) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        return $this->json($postDetail['likes']);
    }
}
