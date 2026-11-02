<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\User\Repository\UserRepositoryInterface;
use App\Message\Repository\MessageRepositoryInterface;
use App\Conversation\Repository\ConversationRepositoryInterface;
use DateTimeImmutable;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use InvalidArgumentException;

/**
 * Admin controller for managing administrative functions.
 * 
 * TL;DR: RESTful API controller for admin dashboard and user management with proper validation and error handling.
 */
#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends BaseController
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MessageRepositoryInterface $messageRepository,
        private ConversationRepositoryInterface $conversationRepository,
    ) {
    }

    #[Route('/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        try {
            $stats = $this->getDashboardStats();
            $recentData = $this->getRecentActivity();
            
            $this->logAction('dashboard_accessed', [
                'total_users' => $stats['total_users'],
                'total_messages' => $stats['total_messages'],
                'total_conversations' => $stats['total_conversations']
            ]);
            $this->incrementMetric('dashboard_requests');

            return $this->json([
                'stats' => $stats,
                'recent_users' => $recentData['users'],
                'recent_messages' => $recentData['messages'],
            ]);
        } catch (\Exception $e) {
            return $this->handleException('dashboard', $e);
        }
    }

    #[Route('/users', name: 'admin_users', methods: ['GET'])]
    public function users(): JsonResponse
    {
        try {
            $filters = $this->extractUserFilters(); $this->validateUserFilters($filters);
            $users = $this->userRepository->findUsersPaginated($filters['page'], $filters['limit'], $filters['search'], $filters['status']);
            $this->logAction('users_list_accessed', ['page'=>$filters['page'],'limit'=>$filters['limit'],'search'=>$filters['search'],'status'=>$filters['status'],'total_results'=>$users['total']]);
            return $this->json(['users'=>$users['data'],'total'=>$users['total'],'page'=>$filters['page'],'limit'=>$filters['limit'],'pages'=>(int)ceil($users['total'] / $filters['limit'])]);
        } catch (\Exception $e) {
            return $this->handleException('users_list', $e);
        }
    }

    #[Route('/users/{id}', name: 'admin_user_details', methods: ['GET'])]
    public function userDetails(string $id): JsonResponse
    {
        try {
            $this->validateStringNotEmpty($id, 'user ID');

            $user = $this->userRepository->findById($id);
            if (!$user) {
                return $this->json(['error' => 'User not found'], 404);
            }

            $this->logAction('user_details_accessed', ['user_id' => $id]);
            $this->incrementMetric('user_details_requests');

            return $this->json($this->formatUserData($user));
        } catch (\Exception $e) {
            return $this->handleException('user_details', $e);
        }
    }

    #[Route('/users/{id}/status', name: 'admin_user_status', methods: ['PUT'])]
    public function updateUserStatus(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateStringNotEmpty($id, 'user ID');

            $user = $this->userRepository->findById($id);
            if (!$user) {
                return $this->json(['error' => 'User not found'], 404);
            }

            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
            if (isset($data['isActive'])) {
                $this->validateBoolean($data['isActive'], 'isActive');
                $user->setIsActive((bool) $data['isActive']);
                $this->userRepository->save($user);
            }

            return $this->json([
                'success' => true,
                'isActive' => $user->isActive(),
            ]);
        } catch (\Exception $e) {
            return $this->handleException('user_status', $e);
        }
    }

    #[Route('/conversations', name: 'admin_conversations', methods: ['GET'])]
    public function conversations(): JsonResponse
    {
        try {
            $filters = $this->extractConversationFilters(); $this->validateConversationFilters($filters);
            $result = $this->conversationRepository->findConversationsPaginated($filters['page'], $filters['limit'], $filters['type']);
            $this->logAction('conversations_list_accessed', ['page'=>$filters['page'],'limit'=>$filters['limit'],'type'=>$filters['type'],'total_results'=>$result['total']]);
            return $this->json(['conversations'=>$result['data'],'total'=>$result['total'],'page'=>$filters['page'],'limit'=>$filters['limit'],'pages'=>(int)ceil($result['total'] / $filters['limit'])]);
        } catch (\Exception $e) {
            return $this->handleException('conversations_list', $e);
        }
    }

    #[Route('/messages', name: 'admin_messages', methods: ['GET'])]
    public function messages(): JsonResponse
    {
        try {
            $filters = $this->extractMessageFilters();
            $this->validateMessageFilters($filters);
            
            $messages = $this->messageRepository->findMessagesPaginated(
                $filters['page'],
                $filters['limit'],
                $filters['conversationId']
            );

            return $this->json([
                'messages' => $messages['data'],
                'total' => $messages['total'],
                'page' => $filters['page'],
                'limit' => $filters['limit'],
                'pages' => (int)ceil($messages['total'] / $filters['limit']),
            ]);
        } catch (\Exception $e) {
            return $this->handleException('messages_list', $e);
        }
    }

    #[Route('/analytics', name: 'admin_analytics', methods: ['GET'])]
    public function analytics(): JsonResponse
    {
        try {
            $dateRange = $this->extractDateRange();
            $this->validateDateRange($dateRange);
            
            $analytics = [
                'user_growth' => $this->userRepository->getUserGrowth(
                    $dateRange['startDate'], 
                    $dateRange['endDate']
                ),
                'message_volume' => $this->messageRepository->getMessageVolume(
                    $dateRange['startDate'], 
                    $dateRange['endDate']
                ),
                'conversation_activity' => $this->conversationRepository->getConversationActivity(
                    $dateRange['startDate'], 
                    $dateRange['endDate']
                ),
                'peak_hours' => $this->messageRepository->getPeakHours(
                    $dateRange['startDate'], 
                    $dateRange['endDate']
                ),
            ];

            return $this->json($analytics);
        } catch (\Exception $e) {
            return $this->handleException('analytics', $e);
        }
    }

    private function getDashboardStats(): array
    {
        return [
            'total_users' => $this->userRepository->countAll(),
            'total_messages' => $this->messageRepository->countAll(),
            'total_conversations' => $this->conversationRepository->countAll(),
            'active_users_today' => $this->userRepository->countActiveUsersToday(),
            'new_users_today' => $this->userRepository->countNewUsersToday(),
        ];
    }

    private function getRecentActivity(): array
    {
        return [
            'users' => $this->userRepository->findRecent(10),
            'messages' => $this->messageRepository->findRecent(10),
        ];
    }

    private function formatUserData(object $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
            'bio' => $user->getBio(),
            'avatar' => $user->getAvatarUrl(),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'lastLoginAt' => $user->getLastLoginAt()?->format('c'),
            'isActive' => $user->isActive(),
            'phoneNumber' => $user->getPhoneNumber(),
            'preferredLanguage' => $user->getPreferredLanguage(),
            'timezone' => $user->getTimezone(),
            'notificationSettings' => $user->getNotificationSettings(),
            'privacySettings' => $user->getPrivacySettings(),
        ];
    }

    private function extractUserFilters(): array
    {
        $request = $this->getRequest();
        return [
            'page' => max(1, (int) $request->query->get('page', 1)),
            'limit' => max(1, min(100, (int) $request->query->get('limit', 20))),
            'search' => $this->sanitizeSearchString($request->query->get('search', '')),
            'status' => $this->sanitizeStatusString($request->query->get('status', 'all')),
        ];
    }

    private function extractConversationFilters(): array
    {
        $request = $this->getRequest();
        return [
            'page' => max(1, (int) $request->query->get('page', 1)),
            'limit' => max(1, min(100, (int) $request->query->get('limit', 20))),
            'type' => $this->sanitizeConversationType($request->query->get('type', 'all')),
        ];
    }

    private function extractMessageFilters(): array
    {
        $request = $this->getRequest();
        return [
            'page' => max(1, (int) $request->query->get('page', 1)),
            'limit' => max(1, min(100, (int) $request->query->get('limit', 20))),
            'conversationId' => $this->sanitizeConversationId($request->query->get('conversationId')),
        ];
    }

    private function extractDateRange(): array
    {
        $request = $this->getRequest();
        return [
            'startDate' => $this->sanitizeDateString($request->query->get('startDate')),
            'endDate' => $this->sanitizeDateString($request->query->get('endDate')),
        ];
    }

    private function validateUserFilters(array $filters): void
    {
        $this->validatePositiveInt($filters['page'], 'page', 1);
        $this->validatePositiveInt($filters['limit'], 'limit', 1, 100);
        $this->validateStringLength($filters['search'], 'search', 100);
        $validStatuses = ['all', 'active', 'inactive', 'pending'];
        $this->validateInArray($filters['status'], 'status', $validStatuses);
    }

    private function validateConversationFilters(array $filters): void
    {
        $this->validatePositiveInt($filters['page'], 'page', 1);
        $this->validatePositiveInt($filters['limit'], 'limit', 1, 100);
        $validTypes = ['all', 'direct', 'group'];
        $this->validateInArray($filters['type'], 'type', $validTypes);
    }

    private function validateMessageFilters(array $filters): void
    {
        $this->validatePositiveInt($filters['page'], 'page', 1);
        $this->validatePositiveInt($filters['limit'], 'limit', 1, 100);
        if ($filters['conversationId'] !== null) {
            $this->validateStringNotEmpty($filters['conversationId'], 'conversation ID');
        }
    }

    private function validateDateRange(array $dateRange): void
    {
        if ($dateRange['startDate'] !== null) {
            $this->validateStringNotEmpty($dateRange['startDate'], 'start date');
        }
        if ($dateRange['endDate'] !== null) {
            $this->validateStringNotEmpty($dateRange['endDate'], 'end date');
        }
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("AdminController::{$context} error: " . $e->getMessage());
        $this->logAction("{$context}_error", ['error' => $e->getMessage()]);
        return $this->json(['error' => 'Internal server error'], 500);
    }

    private function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    /**
     * Log controller actions for audit and debugging purposes.
     */
    private function logAction(string $action, array $context = []): void
    {
        $logEntry = [
            'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'action' => $action,
            'context' => $context,
        ];
        error_log(json_encode($logEntry));
    }

    /**
     * Sanitize search string to prevent potential injection attacks.
     * Removes special characters that could be used for SQL/NoSQL injection.
     *
     * @param string $search The search string to sanitize
     * @return string The sanitized search string
     */
    private function sanitizeSearchString(string $search): string
    {
        // Remove potentially dangerous characters for search
        // Allow alphanumeric, spaces, and basic punctuation
        return preg_replace('/[^\w\s\-@.]/', '', $search);
    }

    /**
     * Sanitize status string to prevent parameter manipulation.
     * Ensures the status is one of the allowed values.
     *
     * @param string $status The status string to sanitize
     * @return string The sanitized status string (defaults to 'all' if invalid)
     */
    private function sanitizeStatusString(string $status): string
    {
        $validStatuses = ['all', 'active', 'inactive', 'pending'];
        return in_array($status, $validStatuses, true) ? $status : 'all';
    }

    /**
     * Sanitize conversation type to prevent parameter manipulation.
     * Ensures the type is one of the allowed values.
     *
     * @param string $type The conversation type to sanitize
     * @return string The sanitized conversation type (defaults to 'all' if invalid)
     */
    private function sanitizeConversationType(string $type): string
    {
        $validTypes = ['all', 'direct', 'group'];
        return in_array($type, $validTypes, true) ? $type : 'all';
    }

    /**
     * Sanitize conversation ID to prevent injection attacks.
     * Ensures the ID is a valid UUID or numeric ID.
     *
     * @param string|null $conversationId The conversation ID to sanitize
     * @return string|null The sanitized conversation ID or null if invalid
     */
    private function sanitizeConversationId(?string $conversationId): ?string
    {
        if ($conversationId === null) {
            return null;
        }
        // Allow only alphanumeric characters, hyphens, and underscores
        // This covers UUIDs and most numeric ID formats
        return preg_match('/^[\w\-]+$/', $conversationId) ? $conversationId : null;
    }

    /**
     * Sanitize date string to prevent injection attacks.
     * Ensures the date string matches a safe format (YYYY-MM-DD).
     *
     * @param string|null $dateString The date string to sanitize
     * @return string|null The sanitized date string or null if invalid
     */
    private function sanitizeDateString(?string $dateString): ?string
    {
        if ($dateString === null) {
            return null;
        }
        // Validate YYYY-MM-DD format
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString) ? $dateString : null;
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

    /**
     * Validate that a value is a positive integer within an optional range.
     *
     * @param int $value The value to validate
     * @param string $fieldName The field name for error messages
     * @param int $min The minimum allowed value (inclusive)
     * @param int|null $max The maximum allowed value (inclusive), null for no maximum
     * @throws InvalidArgumentException If validation fails
     */
    private function validatePositiveInt(int $value, string $fieldName, int $min = 1, ?int $max = null): void
    {
        if ($value < $min) {
            throw new InvalidArgumentException("$fieldName must be at least $min");
        }
        if ($max !== null && $value > $max) {
            throw new InvalidArgumentException("$fieldName must not exceed $max");
        }
    }

    /**
     * Validate that a string is not empty (after trimming).
     *
     * @param string $value The string to validate
     * @param string $fieldName The field name for error messages
     * @throws InvalidArgumentException If the string is empty
     */
    private function validateStringNotEmpty(string $value, string $fieldName): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("$fieldName must not be empty");
        }
    }

    /**
     * Validate that a string does not exceed a maximum length.
     *
     * @param string $value The string to validate
     * @param string $fieldName The field name for error messages
     * @param int $maxLength The maximum allowed length
     * @throws InvalidArgumentException If the string is too long
     */
    private function validateStringLength(string $value, string $fieldName, int $maxLength): void
    {
        if (strlen($value) > $maxLength) {
            throw new InvalidArgumentException("$fieldName must not exceed $maxLength characters");
        }
    }

    /**
     * Validate that a value is in an array of allowed values.
     *
     * @param mixed $value The value to validate
     * @param string $fieldName The field name for error messages
     * @param array $allowedValues The array of allowed values
     * @throws InvalidArgumentException If the value is not in the allowed values
     */
    private function validateInArray(mixed $value, string $fieldName, array $allowedValues): void
    {
        if (!in_array($value, $allowedValues, true)) {
            $allowedValuesList = implode(', ', $allowedValues);
            throw new InvalidArgumentException("$fieldName must be one of: $allowedValuesList");
        }
    }

    /**
     * Validate that a value is a boolean.
     *
     * @param mixed $value The value to validate
     * @param string $fieldName The field name for error messages
     * @throws InvalidArgumentException If the value is not a boolean
     */
    private function validateBoolean(mixed $value, string $fieldName): void
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException("$fieldName must be a boolean");
        }
    }
}