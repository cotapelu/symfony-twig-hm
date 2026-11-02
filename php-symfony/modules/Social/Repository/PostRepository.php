<?php

declare(strict_types=1);

namespace App\Social\Repository;

use App\Social\Entity\Post;
use App\Social\Entity\PostLike;
use App\Social\Entity\PostComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function save(Post $post): void
    {
        $this->getEntityManager()->persist($post);
        $this->getEntityManager()->flush();
    }

    public function delete(Post $post): void
    {
        $post->setDeletedAt(new \DateTime());
        $this->getEntityManager()->flush();
    }

    public function findFeedForUser(string $userId, int $offset = 0, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('p.isPublic = true OR p.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPublicPosts(int $offset = 0, int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('p.isPublic = true')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findLikes(string $postId): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('l')
            ->from(PostLike::class, 'l')
            ->where('l.postId = :postId')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getResult();
    }

    public function findLike(string $postId, string $userId): ?PostLike
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('l')
            ->from(PostLike::class, 'l')
            ->where('l.postId = :postId AND l.userId = :userId')
            ->setParameter('postId', $postId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function saveLike(PostLike $like): void
    {
        $this->getEntityManager()->persist($like);
        $this->getEntityManager()->flush();
    }

    public function removeLike(string $likeId): void
    {
        $like = $this->getEntityManager()->find(PostLike::class, $likeId);
        if ($like) {
            $this->getEntityManager()->remove($like);
            $this->getEntityManager()->flush();
        }
    }

    public function incrementLikeCount(string $postId): void
    {
        $qb = $this->createQueryBuilder('p');
        $qb->update()
            ->set('p.likeCount', 'p.likeCount + 1')
            ->where('p.id = :postId')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->execute();
    }

    public function decrementLikeCount(string $postId): void
    {
        $qb = $this->createQueryBuilder('p');
        $qb->update()
            ->set('p.likeCount', 'p.likeCount - 1')
            ->where('p.id = :postId')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->execute();
    }

    public function findComments(string $postId): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('c')
            ->from(PostComment::class, 'c')
            ->where('c.postId = :postId')
            ->orderBy('c.createdAt', 'ASC')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getResult();
    }

    public function saveComment(PostComment $comment): void
    {
        $this->getEntityManager()->persist($comment);
        $this->getEntityManager()->flush();
    }

    public function incrementCommentCount(string $postId): void
    {
        $qb = $this->createQueryBuilder('p');
        $qb->update()
            ->set('p.commentCount', 'p.commentCount + 1')
            ->where('p.id = :postId')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->execute();
    }

    public function incrementShareCount(string $postId): void
    {
        $qb = $this->createQueryBuilder('p');
        $qb->update()
            ->set('p.shareCount', 'p.shareCount + 1')
            ->where('p.id = :postId')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->execute();
    }
}
