<?php

declare(strict_types=1);

namespace App\GDPR\Service;

use App\User\Repository\UserRepositoryInterface;
use App\Message\Repository\MessageRepositoryInterface;

class GdprService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MessageRepositoryInterface $messageRepository
    ) {}

    /**
     * Export all user data for GDPR compliance
     */
    public function exportUserData(string $userId)
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return [];
        }

        // Basic user data
        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'phoneNumber' => $user->getPhoneNumber(),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'lastLoginAt' => $user->getLastLoginAt()?->format('c'),
            'isActive' => $user->isActive(),
            'preferredLanguage' => $user->getPreferredLanguage(),
            'timezone' => $user->getTimezone(),
            'roles' => $user->getRoles(),
        ];

        // Messages sent by user
        $messages = $this->messageRepository->findBySenderId($userId);
        $messageData = array_map(function ($m) {
            return [
                'id' => $m->getId(),
                'conversationId' => $m->getConversationId(),
                'content' => $m->getContent(),
                'type' => $m->getType(),
                'createdAt' => $m->getCreatedAt()->format('c'),
                'isEdited' => $m->isEdited(),
                'isDeleted' => $m->isDeleted(),
            ];
        }, $messages);

        return [
            'user' => $userData,
            'messages' => $messageData,
        ];
    }

    /**
     * Delete all user data (anonymize for GDPR)
     */
    public function deleteUserData(string $userId)
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return;
        }

        // Anonymize user PII
        $user->setEmail('deleted_'.$userId.'@deleted.com');
        $user->setPhoneNumber(null);
        $user->setBiometricData(null);
        $user->markAsDeleted();
        $user->setIsActive(false);
        $this->userRepository->save($user);

        // Note: In production, you might also delete or anonymize related data
        // e.g., delete notifications, messages, calls, etc.
        // However, to preserve audit trails, we might just soft-delete.
    }
}
