<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;

/**
 * Service for managing notifications
 */
class NotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    /**
     * Generate unique UUIDv4
     */
    private function generateId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    /**
     * Validate required notification fields
     */
    private function validateNotification(string $userId, string $type, string $title, string $body)
    {
        if (empty(trim($userId))) {
            throw new \InvalidArgumentException('User ID is required');
        }
        if (empty(trim($type))) {
            throw new \InvalidArgumentException('Notification type is required');
        }
        if (empty(trim($title))) {
            throw new \InvalidArgumentException('Notification title is required');
        }
        if (empty(trim($body))) {
            throw new \InvalidArgumentException('Notification body is required');
        }
    }

    /**
     * Create a new notification
     */
    public function create(
        string $userId,
        string $type,
        string $title,
        string $body,
        ?array $data = null
    ) {
        $this->validateNotification($userId, $type, $title, $body);
        $notification = $this->buildNotificationEntity($userId, $type, $title, $body, $data);
        $this->persistAndLog($notification, ['user_id' => $userId, 'type' => $type]);
        return $notification;
    }

    private function persistAndLog(Notification $notification, array $context): void
    {
        try {
            $this->em->persist($notification);
            $this->em->flush();
            $this->logger->info('Notification created', array_merge($context, ['id' => $notification->getId()]));
        } catch (ORMException $e) {
            $this->logger->error('Failed to create notification', array_merge($context, ['error' => $e->getMessage()]));
            throw new \RuntimeException('Unable to create notification', 0, $e);
        }
    }

    /**
     * Find notification by ID
     */
    public function get(string $id)
    {
        return $this->em->getRepository(Notification::class)->find($id);
    }

    /**
     * Get notifications for a user
     */
    public function getUserNotifications(string $userId, ?int $limit = null, ?int $offset = null)
    {
        $qb = $this->em->getRepository(Notification::class)->createQueryBuilder('n');
        $qb->where('n.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('n.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(string $notificationId)
    {
        $notification = $this->get($notificationId);
        if ($notification === null) {
            return false;
        }

        $notification->setReadAt(new \DateTime());
        $this->em->flush();

        $this->logger->info('Notification marked as read', ['id' => $notificationId]);

        return true;
    }

    /**
     * Delete notification
     */
    public function delete(string $notificationId)
    {
        $notification = $this->get($notificationId);
        if ($notification === null) {
            return false;
        }

        $this->em->remove($notification);
        $this->em->flush();

        $this->logger->info('Notification deleted', ['id' => $notificationId]);

        return true;
    }

    /**
     * Count unread notifications for a user
     */
    public function countUnread(string $userId)
    {
        return $this->em->getRepository(Notification::class)
            ->count(['userId' => $userId, 'readAt' => null]);
    }

    /**
     * Build notification entity (helper)
     */
    private function buildNotificationEntity(
        string $userId,
        string $type,
        string $title,
        string $body,
        ?array $data
    ): Notification {
        $notification = new Notification(
            $this->generateId(),
            $userId,
            $type,
            $title,
            $body
        );
        $notification->setData($data);
        return $notification;
    }
}
