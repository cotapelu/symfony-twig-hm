<?php

declare(strict_types=1);

namespace App\Social\Service;

use App\Social\Entity\Post;
use App\Social\Entity\PostLike;
use App\Social\Entity\PostComment;
use App\Social\Repository\PostRepository;
use App\Shared\EventBus\EventBus;
use App\Social\Event\PostCreatedEvent;
use App\User\Service\AuthService;
use App\Shared\DomainEvent\DomainEventPublisherInterface;

class PostService
{
    public function __construct(
        private PostRepository $postRepository,
        private EventBus $eventBus,
        // @phpstan-ignore-next-line private AuthService $authService,
    ) {
    }

    public function createPost(
        $userId,
        ?string $content,
        ?string $mediaUrl,
        ?string $mediaType,
        array $metadata,
        bool $isPublic = true
    ) {
        $post = new Post(
            userId: $userId,
            content: $content,
            mediaUrl: $mediaUrl,
            mediaType: $mediaType,
            metadata: $metadata,
            isPublic: $isPublic,
        );

        $this->postRepository->save($post);

        $event = new PostCreatedEvent(
            postId: $post->getId(),
            userId: $userId,
            isPublic: $isPublic,
            createdAt: $post->getCreatedAt(),
        );

        $this->eventBus->dispatch($event);

        return $post;
    }

    public function likePost($postId, $userId)
    {
        $post = $this->postRepository->find($postId);

        if (!$post || $post->isDeleted()) {
            throw new \InvalidArgumentException('Post not found');
        }

        $existingLike = $this->postRepository->findLike($postId, $userId);

        if ($existingLike) {
            $this->postRepository->removeLike($existingLike->getId());
            $this->postRepository->decrementLikeCount($postId);
        } else {
            $like = new PostLike(
                postId: $postId,
                userId: $userId,
            );

            $this->postRepository->saveLike($like);
            $this->postRepository->incrementLikeCount($postId);
        }
    }

    public function commentPost($postId, $userId, $content)
    {
        $post = $this->postRepository->find($postId);

        if (!$post || $post->isDeleted()) {
            throw new \InvalidArgumentException('Post not found');
        }

        $comment = new PostComment(
            postId: $postId,
            userId: $userId,
            content: $content,
        );

        $this->postRepository->saveComment($comment);
        $this->postRepository->incrementCommentCount($postId);
    }

    public function sharePost($postId, $userId)
    {
        $post = $this->postRepository->find($postId);

        if (!$post || $post->isDeleted()) {
            throw new \InvalidArgumentException('Post not found');
        }

        $this->postRepository->incrementShareCount($postId);
    }

    public function deletePost($postId, $userId)
    {
        $post = $this->postRepository->find($postId);

        if (!$post || $post->getUserId() !== $userId) {
            throw new \InvalidArgumentException('Post not found or unauthorized');
        }

        $this->postRepository->delete($post);
    }

    public function getFeedForUser($userId, int $offset = 0, int $limit = 20)
    {
        return $this->postRepository->findFeedForUser($userId, $offset, $limit);
    }

    public function getPublicFeed(int $offset = 0, int $limit = 50)
    {
        return $this->postRepository->findPublicPosts($offset, $limit);
    }

    public function getPostDetail(string $postId)
    {
        $post = $this->postRepository->find($postId);

        if (!$post || $post->isDeleted()) {
            return null;
        }

        return $post;
    }
}
