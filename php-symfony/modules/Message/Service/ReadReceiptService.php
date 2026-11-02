<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Entity\MessageReadReceipt;
use App\Message\Repository\MessageRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class ReadReceiptService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepositoryInterface $messageRepository,
    ) {
    }

    /**
     * Mark a message as read by a user.
     */
    public function markMessageAsRead(string $messageId, string $userId)
    {
        // Check if message exists
        $message = $this->messageRepository->findById($messageId);
        if (!$message) {
            return false;
        }

        // Check if user is participant in conversation
        if (!$this->messageRepository->isUserParticipant($message->getConversationId(), $userId)) {
            return false;
        }

        // Check if already read
        if ($this->isMessageReadByUser($messageId, $userId)) {
            return true; // Already read
        }

        // Create read receipt
        $readReceipt = new MessageReadReceipt($messageId, $userId);
        $this->entityManager->persist($readReceipt);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Mark multiple messages as read.
     */
    public function markMessagesAsRead(array $messageIds, string $userId)
    {
        $results = [];
        foreach ($messageIds as $messageId) {
            $results[$messageId] = $this->markMessageAsRead($messageId, $userId);
        }

        return $results;
    }

    /**
     * Mark all messages in conversation as read for user.
     */
    public function markConversationAsRead(string $conversationId, string $userId)
    {
        // Get all unread messages in conversation for user
        $unreadMessages = $this->getUnreadMessagesInConversation($conversationId, $userId);

        $count = 0;
        foreach ($unreadMessages as $message) {
            if ($this->markMessageAsRead($message->getId(), $userId)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Check if message is read by user.
     */
    public function isMessageReadByUser(string $messageId, string $userId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $count = $qb->select('COUNT(r.id)')
            ->from(MessageReadReceipt::class, 'r')
            ->where('r.messageId = :messageId')
            ->andWhere('r.userId = :userId')
            ->setParameter('messageId', $messageId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Get read status for messages.
     */
    public function getReadStatus(array $messageIds, string $userId)
    {
        if (empty($messageIds)) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();
        $readReceipts = $qb->select('r.messageId')
            ->from(MessageReadReceipt::class, 'r')
            ->where('r.messageId IN (:messageIds)')
            ->andWhere('r.userId = :userId')
            ->setParameter('messageIds', $messageIds)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getScalarResult();

        $readStatus = [];
        foreach ($readReceipts as $receipt) {
            $readStatus[$receipt['messageId']] = true;
        }

        // Fill in false for unread messages
        foreach ($messageIds as $messageId) {
            if (!isset($readStatus[$messageId])) {
                $readStatus[$messageId] = false;
            }
        }

        return $readStatus;
    }

    /**
     * Get unread messages in conversation for user.
     */
    public function getUnreadMessagesInConversation(string $conversationId, string $userId)
    {
        // Get all messages in conversation
        $messages = $this->messageRepository->findByConversationId($conversationId);

        // Filter out messages sent by the user themselves
        $messages = array_filter($messages, fn ($msg) => $msg->getSenderId() !== $userId);

        // Filter out already read messages
        $unreadMessages = [];
        foreach ($messages as $message) {
            if (!$this->isMessageReadByUser($message->getId(), $userId)) {
                $unreadMessages[] = $message;
            }
        }

        return $unreadMessages;
    }

    /**
     * Get read receipts for a message.
     */
    public function getMessageReadReceipts(string $messageId)
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('r')
            ->from(MessageReadReceipt::class, 'r')
            ->where('r.messageId = :messageId')
            ->setParameter('messageId', $messageId)
            ->orderBy('r.readAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get read count for a message.
     */
    public function getMessageReadCount(string $messageId)
    {
        $qb = $this->entityManager->createQueryBuilder();

        return (int) $qb->select('COUNT(r.id)')
            ->from(MessageReadReceipt::class, 'r')
            ->where('r.messageId = :messageId')
            ->setParameter('messageId', $messageId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
