<?php

declare(strict_types=1);

namespace App\Message\Controller;

use App\Exception\ValidationException;
use App\Message\Repository\MessageRepositoryInterface;
use App\Message\Service\AIService;
use App\Message\Service\TranslationService;
use App\Message\Service\ConversationInsightsService;
use App\Message\Service\MessageServiceInterface;
use App\Conversation\Service\ConversationService;
use App\Shared\Infrastructure\RateLimitService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use InvalidArgumentException;

#[Route('/api')]
class MessageController extends BaseController
{
    public function __construct(
        private MessageServiceInterface $messageService,
        private ConversationService $conversationService,
        private AIService $aiService,
        private TranslationService $translationService,
        private ConversationInsightsService $insightsService,
        private RateLimitService $rateLimitService,
        private MessageRepositoryInterface $messageRepository,
        private ?TokenStorageInterface $tokenStorage = null,
        ?AuthorizationCheckerInterface $authChecker = null
    ) {
        parent::__construct();
        $this->authChecker = $authChecker;
    }

    private function requireUserOr401(): JsonResponse|string
    {
        if (null !== $this->tokenStorage) {
            $token = $this->tokenStorage->getToken();
            if (null !== $token) {
                $user = $token->getUser();
                if ($user instanceof UserInterface) {
                    return $user->getId();
                }
            }
        }
        return $this->json(['error' => 'Authentication required'], 401);
    }

    private function validateSendMessageData(array $data): array
    {
        $errors = [];
        if (!isset($data['conversationId']) || !isset($data['content'])) {
            $errors[] = 'Conversation ID and content are required';
        } else {
            $cid = $data['conversationId'];
            if (is_int($cid) ? $cid <= 0 : !is_string($cid) || trim($cid) === '') {
                $errors[] = 'Invalid conversation ID';
            }
            if (!is_string($data['content']) || trim($data['content']) === '') {
                $errors[] = 'Content must be a non-empty string';
            }
        }
        return $errors;
    }

    private function serializeMessage(\App\Message\Entity\Message $message): array
    {
        return [
            'id' => $message->getId(),
            'conversationId' => $message->getConversationId(),
            'senderId' => $message->getSenderId(),
            'content' => $message->getContent(),
            'type' => $message->getType(),
            'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            'is_edited' => false,
            'reply_to_message_id' => $message->getReplyToMessageId(),
        ];
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new \DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'context'=>$context], JSON_UNESCAPED_UNICODE));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode(['timestamp'=>(new \DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metric, 'tags'=>$tags, 'value'=>1]));
    }

    private function handle(callable $action): Response
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("MessageController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['error' => 'Internal server error'], 500);
    }

    // ==================== Endpoints ====================

    #[Route('/messages', name: 'api_messages_list', methods: ['GET'])]
    public function getMessages(Request $request): Response
    {
        return $this->handle(fn() => $this->getMessagesAction($request));
    }

    private function getMessagesAction(Request $request): Response
    {
        $clientIp = $request->getClientIp() ?? 'unknown';
        if (!$this->rateLimitService->checkRateLimit($clientIp, 'messages_list')) {
            $resetTime = $this->rateLimitService->getResetTime($clientIp, 'messages_list');
            return $this->json(['error'=>'Rate limit exceeded','retry_after'=>$resetTime['minute']], 429, ['X-RateLimit-Reset'=>$resetTime['minute'], 'Retry-After'=>$resetTime['minute']-time()]);
        }

        $userIdOr401 = $this->requireUserOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = $userIdOr401;

        $page = max(1, (int)($request->query->get('page', 1)));
        $perPage = max(1, min(100, (int)($request->query->get('perPage', 20))));
        $conversationId = $request->query->get('conversation_id');

        $result = $this->messageRepository->findMessagesPaginated($page, $perPage, $conversationId ?: null);
        $messages = $result['data'] ?? [];
        $total = $result['total'] ?? count($messages);
        $messagesData = array_map([$this, 'serializeMessage'], $messages);

        return $this->json([
            'messages' => $messagesData,
            'pagination' => ['page'=>$page, 'per_page'=>$perPage, 'total'=>$total, 'total_pages'=>(int)ceil($total/$perPage)],
        ]);
    }

    #[Route('/messages', name: 'api_messages_send', methods: ['POST'])]
    public function sendMessage(Request $request): Response
    {
        return $this->handle(fn() => $this->sendMessageAction($request));
    }

    private function sendMessageAction(Request $request): Response
    {
        $clientIp = $request->getClientIp() ?? 'unknown';
        if (!$this->rateLimitService->checkRateLimit($clientIp, 'messages_send')) {
            $resetTime = $this->rateLimitService->getResetTime($clientIp, 'messages_send');
            return $this->json(['error'=>'Rate limit exceeded','retry_after'=>$resetTime['minute']], 429, ['X-RateLimit-Reset'=>$resetTime['minute'], 'Retry-After'=>$resetTime['minute']-time()]);
        }

        $userIdOr401 = $this->requireUserOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = $userIdOr401;

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;

        $errors = $this->validateSendMessageData($data);
        if (!empty($errors)) return $this->json(['error'=>'Validation failed','errors'=>$errors], 400);

        $messageData = ['conversationId'=>$data['conversationId'], 'content'=>trim($data['content']), 'files'=>$data['files']??null];
        $message = $this->messageService->sendMessage($messageData, $userId);

        return $this->json([
            'id'=>$message->getId(), 'conversationId'=>$message->getConversationId(), 'senderId'=>$message->getSenderId(),
            'content'=>$message->getContent(), 'type'=>$message->getType(), 'created_at'=>$message->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/messages/{conversationId}', name: 'api_messages_conversation', methods: ['GET'])]
    public function getConversationMessages(Request $request, string $conversationId): Response
    {
        return $this->handle(fn() => $this->getConversationMessagesAction($request, $conversationId));
    }

    private function getConversationMessagesAction(Request $request, string $conversationId): Response
    {
        $userIdOr401 = $this->requireUserOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = $userIdOr401;

        $page = (int)($request->query->get('page', 1));
        $limit = (int)($request->query->get('limit', 50));
        $cursor = $request->query->get('cursor');
        $useCursor = $request->query->get('use_cursor') === 'true';

        if ($limit < 1 || $limit > 200) throw new InvalidArgumentException('Limit must be between 1 and 200');

        if ($useCursor && $cursor !== null) {
            $result = $this->messageService->getConversationMessagesCursor($conversationId, $userId, $cursor, $limit);
        } else {
            $messages = $this->messageService->getConversationMessages($conversationId, $userId, $page, $limit);
            $result = ['data'=>$messages, 'pagination'=>['page'=>$page, 'limit'=>$limit]];
        }

        $messagesData = array_map([$this, 'serializeMessage'], $result['data']);
        $response = ['messages'=>$messagesData, 'pagination'=>$result['pagination'] ?? ['next_cursor'=>$result['next_cursor']??null, 'has_more'=>$result['has_more']??false]];

        return $this->json($response);
    }

    #[Route('/messages/{messageId}/translate', name: 'api_messages_translate', methods: ['POST'])]
    public function translateMessage(Request $request, string $messageId): Response
    {
        return $this->handle(fn() => $this->translateMessageAction($request, $messageId));
    }

    private function translateMessageAction(Request $request, string $messageId): Response
    {
        $userIdOr401 = $this->requireUserOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;

        if (!isset($data['content'])) throw new InvalidArgumentException('Content is required');
        $content = $data['content'];
        $fromLang = $data['from_lang'] ?? 'auto';
        $toLang = $data['to_lang'] ?? 'en';

        $detectedLang = $fromLang === 'auto' ? $this->translationService->detectLanguage($content) : $fromLang;
        $translated = $this->translationService->translate($content, $detectedLang, $toLang);

        return $this->json([
            'messageId'=>$messageId, 'original'=>$content, 'translated'=>$translated, 'target_language'=>$toLang, 'detected_language'=>$detectedLang
        ]);
    }

    #[Route('/messages/smart-reply', name: 'api_messages_smart_reply', methods: ['POST'])]
    public function generateSmartReply(Request $request): Response
    {
        return $this->handle(fn() => $this->generateSmartReplyAction($request));
    }

    private function generateSmartReplyAction(Request $request): Response
    {
        $userIdOr401 = $this->requireUserOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;

        if (!isset($data['message'])) throw new InvalidArgumentException('Message is required');

        $smartReplies = $this->aiService->generateSmartReplies($data['message']);

        return $this->json(['originalMessage'=>$data['message'], 'smartReplies'=>$smartReplies, 'generated'=>true]);
    }

    #[Route('/messages/moderate', name: 'api_messages_moderate', methods: ['POST'])]
    public function moderateContent(Request $request): Response
    {
        return $this->handle(fn() => $this->moderateContentAction($request));
    }

    private function moderateContentAction(Request $request): Response
    {
        $userIdOr401 = $this->requireUserOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;

        if (!isset($data['content'])) throw new InvalidArgumentException('Content is required');

        $moderation = $this->aiService->moderateContent($data['content']);

        return $this->json(['content'=>$data['content'], 'moderation'=>$moderation, 'approved'=>$moderation['severity']==='low']);
    }

    #[Route('/messages/{messageId}/reactions', name: 'api_messages_add_reaction', methods: ['POST'])]
    public function addReaction(Request $request, string $messageId): Response
    {
        return $this->handle(fn() => $this->addReactionAction($request, $messageId));
    }

    private function addReactionAction(Request $request, string $messageId): Response
    {
        $userIdOr401 = $this->requireUserOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = $userIdOr401;

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;

        if (!isset($data['reaction'])) throw new InvalidArgumentException('Reaction is required');

        return $this->json(['messageId'=>$messageId, 'reaction'=>$data['reaction'], 'userId'=>$userId, 'added'=>true]);
    }

    #[Route('/messages/{messageId}/read', name: 'api_messages_mark_read', methods: ['PUT'])]
    public function markMessageRead(Request $request, string $messageId): Response
    {
        return $this->handle(fn() => $this->markMessageReadAction($request, $messageId));
    }

    private function markMessageReadAction(Request $request, string $messageId): Response
    {
        $userIdOr401 = $this->requireUserOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = $userIdOr401;

        return $this->json(['messageId'=>$messageId, 'userId'=>$userId, 'readAt'=>date('Y-m-d H:i:s')]);
    }

    #[Route('/typing/status/{conversationId}', name: 'api_typing_status', methods: ['GET'])]
    public function getTypingStatus(string $conversationId): Response
    {
        return $this->handle(fn() => $this->getTypingStatusAction($conversationId));
    }

    private function getTypingStatusAction(string $conversationId): Response
    {
        return $this->json(['conversationId'=>$conversationId, 'typingUsers'=>[]]);
    }

    #[Route('/conversations/{conversationId}/insights', name: 'api_conversation_insights', methods: ['GET'])]
    public function getConversationInsights(Request $request, string $conversationId): Response
    {
        return $this->handle(fn() => $this->getConversationInsightsAction($request, $conversationId));
    }

    private function getConversationInsightsAction(Request $request, string $conversationId): Response
    {
        $userIdOr401 = $this->requireUserOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = $userIdOr401;

        $insights = $this->insightsService->getConversationInsights($conversationId, $userId);
        return $this->json($insights);
    }
}
