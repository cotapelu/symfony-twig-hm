<?php

namespace App\Conversation\Service;

use App\Conversation\Entity\Conversation;
use App\Conversation\Repository\ConversationRepositoryInterface;

class ConversationService
{
    public function __construct(
        private ConversationRepositoryInterface $repository
    ) {}

    public function createConversation(array $participantIds, string $type, ?string $title = null)
    {
        $conversation = new Conversation($participantIds, $type);
        if ($title) {
            $conversation->setTitle($title);
        }
        return $this->repository->save($conversation);
    }

    public function getConversation(int $id)
    {
        return $this->repository->findById($id);
    }

    public function getUserConversations(int $userId)
    {
        return $this->repository->findByUserId($userId);
    }

    public function addParticipant(int $conversationId, int $userId)
    {
        $conversation = $this->repository->findById($conversationId);
        if ($conversation) {
            $participants = $conversation->getParticipantIds();
            if (!in_array($userId, $participants)) {
                $participants[] = $userId;
                $conversation->setParticipantIds($participants);
                return $this->repository->save($conversation);
            }
        }
        return $conversation;
    }

    public function removeParticipant(int $conversationId, int $userId)
    {
        $conversation = $this->repository->findById($conversationId);
        if ($conversation) {
            $participants = array_filter($conversation->getParticipantIds(), fn($id) => $id !== $userId);
            $conversation->setParticipantIds($participants);
            return $this->repository->save($conversation);
        }
        return null;
    }

    public function updateTitle(int $conversationId, string $title)
    {
        $conversation = $this->repository->findById($conversationId);
        if ($conversation) {
            $conversation->setTitle($title);
            return $this->repository->save($conversation);
        }
        return null;
    }

    public function updateAvatar(int $conversationId, string $avatar)
    {
        $conversation = $this->repository->findById($conversationId);
        if ($conversation) {
            $conversation->setAvatar($avatar);
            return $this->repository->save($conversation);
        }
        return null;
    }

    public function deleteConversation(int $id)
    {
        return $this->repository->delete($id);
    }

    public function searchConversations(int $userId, string $query)
    {
        $userConvs = $this->repository->findByUserId($userId);
        return array_filter($userConvs, fn($c) =>
            $c->getTitle() && stripos($c->getTitle(), $query) !== false
        );
    }

    public function getDirectConversation(int $user1, int $user2)
    {
        return $this->repository->findDirectConversation($user1, $user2);
    }
}
