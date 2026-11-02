<?php

declare(strict_types=1);

namespace App\Conversation\Controller;

use App\Conversation\Service\ConversationService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use DateTimeImmutable;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Conversation Controller for managing conversation-related operations.
 * 
 * TL;DR: RESTful API controller for conversation management with proper validation, 
 * error handling, and dependency injection.
 */
#[Route('/api/conversations')]
class ConversationController extends BaseController
{
    private ConversationService $service;

    public function __construct(ConversationService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    private function requireAuth(): JsonResponse|true
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        return true;
    }

    private function getAuthenticatedUserId(): int
    {
        $user = $this->getUser();
        if (!$user) {
            throw new \LogicException('Authentication required – requireAuth not called');
        }
        return (int) $user->getUserIdentifier();
    }

    // ==================== Auth & JSON Helpers ====================

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        $userId = $user->getUserIdentifier();
        if ($userId === null) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        return $userId;
    }

    #[Route('', name: 'conversation_create', methods: ['POST'])]
    public function createConversation(Request $request): JsonResponse
    {
        try {
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (string) $userIdOr401;
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;
            $this->validateConversationData($data);
            $conversation = $this->service->createConversation($data['participant_ids'], $data['type'] ?? 'direct', $data['title'] ?? null);
            if (isset($data['created_by'])) $conversation->setCreatedBy((int)$data['created_by']);
            $this->logAction('create_conversation', ['id'=>$conversation->getId()]);
            $this->incrementMetric('conversations_created');
            return $this->json(['data'=>$conversation], JsonResponse::HTTP_CREATED);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error'=>$e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('create_conversation', $e);
        }
    }

    #[Route('/{id}', name: 'conversation_get', methods: ['GET'])]
    public function getConversation(int $id, Request $request): JsonResponse
    {
        try {
            $this->validatePositiveInt($id, 'conversation ID');
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (int) $userIdOr401;
            $conversation = $this->service->getConversation($id);
            if (!$conversation) return $this->json(['error'=>'Conversation not found'], JsonResponse::HTTP_NOT_FOUND);
            if (!$conversation->hasParticipant($userId)) return $this->json(['error'=>'Access denied'], JsonResponse::HTTP_FORBIDDEN);
            return $this->json(['data'=>$conversation]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error'=>$e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('get_conversation', $e);
        }
    }

    #[Route('/user/{userId}', name: 'conversation_get_user', methods: ['GET'])]
    public function getUserConversations(int $userId, Request $request): JsonResponse
    {
        try {
            $this->validatePositiveInt($userId, 'user ID');
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $currentUserId = (int) $userIdOr401;
            if ($userId !== $currentUserId) return $this->json(['error'=>'Access denied'], JsonResponse::HTTP_FORBIDDEN);
            $conversations = $this->service->getUserConversations($userId);
            return $this->json(['data'=>array_values($conversations)]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error'=>$e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('get_user_conversations', $e);
        }
    }

    #[Route('/{id}/title', name: 'conversation_update_title', methods: ['PUT'])]
    public function updateTitle(int $id, Request $request): JsonResponse
    {
        try {
            $this->validatePositiveInt($id, 'conversation ID');
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (int) $userIdOr401;
            $conversation = $this->service->getConversation($id);
            if (!$conversation) return $this->json(['error'=>'Conversation not found'], JsonResponse::HTTP_NOT_FOUND);
            if (!$conversation->hasParticipant($userId)) return $this->json(['error'=>'Access denied'], JsonResponse::HTTP_FORBIDDEN);
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;
            $this->validateTitleData($data);
            $conversation = $this->service->updateTitle($id, $data['title']);
            if (!$conversation) return $this->json(['error'=>'Conversation not found'], JsonResponse::HTTP_NOT_FOUND);
            $this->logAction('update_title', ['id'=>$id]);
            return $this->json(['data'=>$conversation]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error'=>$e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('update_title', $e);
        }
    }

    #[Route('/{id}/avatar', name: 'conversation_update_avatar', methods: ['PUT'])]
    public function updateAvatar(int $id, Request $request): JsonResponse
    {
        try {
            $this->validatePositiveInt($id, 'conversation ID');
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (int) $userIdOr401;
            $conversation = $this->service->getConversation($id);
            if (!$conversation) return $this->json(['error'=>'Conversation not found'], JsonResponse::HTTP_NOT_FOUND);
            if (!$conversation->hasParticipant($userId)) return $this->json(['error'=>'Access denied'], JsonResponse::HTTP_FORBIDDEN);
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;
            $this->validateAvatarData($data);
            $conversation = $this->service->updateAvatar($id, $data['avatar']);
            if (!$conversation) return $this->json(['error'=>'Conversation not found'], JsonResponse::HTTP_NOT_FOUND);
            return $this->json(['data'=>$conversation]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error'=>$e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('update_avatar', $e);
        }
    }

    #[Route('/{id}/participants', name: 'conversation_add_participant', methods: ['POST'])]
    public function addParticipant(int $id, Request $request): JsonResponse
    {
        try {
            $this->validatePositiveInt($id, 'conversation ID');
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (int) $userIdOr401;
            $conversation = $this->service->getConversation($id);
            if (!$conversation) return $this->json(['error'=>'Conversation not found'], JsonResponse::HTTP_NOT_FOUND);
            if (!$conversation->hasParticipant($userId)) return $this->json(['error'=>'Access denied'], JsonResponse::HTTP_FORBIDDEN);
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;
            $this->validateParticipantData($data);
            $conversation = $this->service->addParticipant($id, (int)$data['user_id']);
            $this->logAction('add_participant', ['conversation_id'=>$id, 'user_id'=>$data['user_id']]);
            return $this->json(['data'=>$conversation]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error'=>$e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('add_participant', $e);
        }
    }

    #[Route('/{id}/participants/{userId}', name: 'conversation_remove_participant', methods: ['DELETE'])]
    public function removeParticipant(int $id, int $userId, Request $request): JsonResponse
    {
        try {
            $this->validatePositiveInt($id, 'conversation ID');
            $this->validatePositiveInt($userId, 'user ID');
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $currentUserId = (int) $userIdOr401;
            $conversation = $this->service->getConversation($id);
            if (!$conversation) return $this->json(['error'=>'Conversation not found'], JsonResponse::HTTP_NOT_FOUND);
            if (!$conversation->hasParticipant($currentUserId)) return $this->json(['error'=>'Access denied'], JsonResponse::HTTP_FORBIDDEN);
            $conversation = $this->service->removeParticipant($id, $userId);
            $this->logAction('remove_participant', ['conversation_id'=>$id, 'user_id'=>$userId]);
            return $this->json(['data'=>$conversation]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error'=>$e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('remove_participant', $e);
        }
    }

    #[Route('/{id}', name: 'conversation_delete', methods: ['DELETE'])]
    public function deleteConversation(int $id, Request $request): JsonResponse
    {
        try {
            $this->validatePositiveInt($id, 'conversation ID');
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (int) $userIdOr401;
            $conversation = $this->service->getConversation($id);
            if (!$conversation) return $this->json(['error'=>'Conversation not found'], JsonResponse::HTTP_NOT_FOUND);
            if (!$conversation->hasParticipant($userId)) return $this->json(['error'=>'Access denied'], JsonResponse::HTTP_FORBIDDEN);
            $this->service->deleteConversation($id);
            $this->logAction('delete_conversation', ['id'=>$id]);
            return $this->json([], JsonResponse::HTTP_NO_CONTENT);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error'=>$e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('delete_conversation', $e);
        }
    }

    #[Route('/user/{userId}/search', name: 'conversation_search', methods: ['GET'])]
    public function searchConversations(int $userId, Request $request): JsonResponse
    {
        try {
            $this->validatePositiveInt($userId, 'user ID');
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $currentUserId = (int) $userIdOr401;
            if ($userId !== $currentUserId) return $this->json(['error'=>'Access denied'], JsonResponse::HTTP_FORBIDDEN);
            $query = $request->query->get('q', '');
            $conversations = $this->service->searchConversations($userId, $query);
            return $this->json(['data'=>array_values($conversations)]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error'=>$e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('search_conversations', $e);
        }
    }

    // ========== PRIVATE METHODS ==========

    private function validateConversationData(array $data): void
    {
        $participantIds = $data['participant_ids'] ?? [];
        $this->validateParticipantIds($participantIds);

        if (isset($data['type'])) {
            $this->validateConversationType($data['type']);
        }
    }

    private function validateParticipantIds(array $participantIds): void
    {
        if (count($participantIds) === 0) {
            throw new InvalidArgumentException('At least one participant ID is required');
        }

        foreach ($participantIds as $participantId) {
            // Accept both integer and string IDs, but must be non-empty
            if (is_int($participantId) && $participantId < 1) {
                throw new InvalidArgumentException('Invalid participant ID format');
            }
            if (is_string($participantId) && trim($participantId) === '') {
                throw new InvalidArgumentException('Invalid participant ID format');
            }
            if (!is_int($participantId) && !is_string($participantId)) {
                throw new InvalidArgumentException('Invalid participant ID format');
            }
        }
    }

    private function validateConversationType(string $type): void
    {
        if (!in_array($type, ['direct', 'group'])) {
            throw new InvalidArgumentException('Conversation type must be either direct or group');
        }
    }

    private function validateTitleData(array $data): void
    {
        if (!isset($data['title']) || !is_string($data['title'])) {
            throw new InvalidArgumentException('Title is required and must be a string');
        }
        
        if (strlen($data['title']) > 255) {
            throw new InvalidArgumentException('Title must not exceed 255 characters');
        }
    }

    private function validateAvatarData(array $data): void
    {
        if (!isset($data['avatar']) || !is_string($data['avatar'])) {
            throw new InvalidArgumentException('Avatar is required and must be a string');
        }
        
        if (!preg_match('/^https?:\/\/.*$/', $data['avatar'])) {
            throw new InvalidArgumentException('Avatar must be a valid URL');
        }
    }

    private function validateParticipantData(array $data): void
    {
        if (!isset($data['user_id']) || !is_numeric($data['user_id']) || (int) $data['user_id'] < 1) {
            throw new InvalidArgumentException('Valid user ID is required');
        }
    }

    private function validatePositiveInt(int $value, string $fieldName, int $min = 1): void
    {
        if ($value < $min) {
            throw new InvalidArgumentException("$fieldName must be at least $min");
        }
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("ConversationController::{$context} error: " . $e->getMessage());
        $this->logAction("{$context}_error", ['error' => $e->getMessage()]);
        $this->incrementMetric("{$context}_errors");
        return $this->json(['error' => 'Internal server error'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function logAction(string $action, array $context = []): void
    {
        $logEntry = [
            'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'action' => $action,
            'context' => $context,
        ];
        error_log(json_encode($logEntry));
    }

    private function incrementMetric(string $metricName): void
    {
        error_log(json_encode([
            'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'type' => 'metric',
            'name' => $metricName,
            'value' => 1,
        ]));
    }
}
