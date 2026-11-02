<?php

declare(strict_types=1);

namespace App\BusinessAccounts\Controller;

use App\BusinessAccounts\Service\BusinessAccountsService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

#[Route('/api/business-accounts')]
class BusinessAccountsController extends BaseController
{
    private BusinessAccountsService $service;
    private LoggerInterface $logger;

    public function __construct(BusinessAccountsService $service, LoggerInterface $logger)
    {
        $this->service = $service;
        $this->logger = $logger;
    }

    private function requireAuth(): int
    {
        $user = $this->getUser();
        if (!$user) throw new InvalidArgumentException('Authentication required');
        return (int)$user->getId();
    }

    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) throw new InvalidArgumentException('Invalid JSON');
        return $dataOr400 ?? [];
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("BusinessAccountsController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['error' => 'Internal server error'], 500);
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'context'=>$context]));
    }

    private function incrementMetric(string $metric): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metric, 'value'=>1]));
    }

    // ==================== Endpoints ====================

    #[Route('', name: 'business_accounts_create', methods: ['POST'])]
    public function createAccount(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createAccountAction($request));
    }

    private function createAccountAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);
        $this->validateAccountCreationData($data);

        $account = $this->service->createAccount($userId, $data['business_name'], $data['business_type']);
        $this->applyAccountOptions($account, $data);

        $this->logAction('account_created', ['account_id'=>$account->getId(), 'user_id'=>$userId]);
        $this->incrementMetric('accounts_created');

        return $this->json(['data'=>$account], JsonResponse::HTTP_CREATED);
    }

    #[Route('', name: 'business_accounts_get_user', methods: ['GET'])]
    public function getUserAccounts(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getUserAccountsAction($request));
    }

    private function getUserAccountsAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $limit = max(1, min(100, (int)($request->query->get('limit', 20))));
        $offset = max(0, (int)($request->query->get('offset', 0)));

        $accounts = $this->service->getUserAccounts($userId, $limit, $offset);
        return $this->json(['data'=>array_values($accounts), 'meta'=>['limit'=>$limit, 'offset'=>$offset, 'count'=>count($accounts)]]);
    }

    #[Route('/{id}', name: 'business_accounts_get', methods: ['GET'])]
    public function getAccount(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getAccountAction($id, $request));
    }

    private function getAccountAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Account ID must be positive');
        $userId = $this->requireAuth();
        $account = $this->service->getAccount($id, $userId);
        return $this->json(['data'=>$account]);
    }

    #[Route('/{id}', name: 'business_accounts_update', methods: ['PUT'])]
    public function updateAccount(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateAccountAction($id, $request));
    }

    private function updateAccountAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Account ID must be positive');
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);
        $this->validateAccountUpdateData($data);

        // Apply updates via service
        if (isset($data['tax_id'])) $this->service->updateTaxId($id, $data['tax_id'], $userId);
        if (isset($data['phone']) || isset($data['email'])) $this->service->updateContact($id, $data['phone'] ?? null, $data['email'] ?? null, $userId);
        if (isset($data['whatsapp_business_id'])) $this->service->setWhatsappBusiness($id, $data['whatsapp_business_id'], $userId);
        if (isset($data['verify'])) $this->service->verifyAccount($id, $userId);

        $account = $this->service->getAccount($id, $userId);
        $this->logAction('account_updated', ['account_id'=>$id, 'user_id'=>$userId]);
        return $this->json(['data'=>$account]);
    }

    #[Route('/{id}', name: 'business_accounts_delete', methods: ['DELETE'])]
    public function deleteAccount(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->deleteAccountAction($id, $request));
    }

    private function deleteAccountAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Account ID must be positive');
        $userId = $this->requireAuth();
        $this->service->deleteAccount($id, $userId);
        $this->logAction('account_deleted', ['account_id'=>$id, 'user_id'=>$userId]);
        return $this->json([], JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/verify', name: 'business_accounts_verify', methods: ['POST'])]
    public function verifyAccount(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->verifyAccountAction($id, $request));
    }

    private function verifyAccountAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Account ID must be positive');
        $userId = $this->requireAuth();
        $account = $this->service->verifyAccount($id, $userId);
        $this->logAction('account_verified', ['account_id'=>$id, 'user_id'=>$userId]);
        return $this->json(['data'=>$account]);
    }

    #[Route('/search', name: 'business_accounts_search', methods: ['GET'])]
    public function searchByName(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->searchByNameAction($request));
    }

    private function searchByNameAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $query = trim($request->query->get('name', ''));
        $limit = max(1, min(100, (int)($request->query->get('limit', 20))));
        $offset = max(0, (int)($request->query->get('offset', 0)));

        if ($query === '') return $this->json(['data'=>[], 'meta'=>['count'=>0]]);

        $accounts = $this->service->searchUserAccounts($userId, $query, $limit, $offset);
        return $this->json(['data'=>array_values($accounts), 'meta'=>['query'=>$query, 'limit'=>$limit, 'offset'=>$offset, 'count'=>count($accounts)]]);
    }

    // ==================== Validation Helpers ====================

    private function applyAccountOptions(object $account, array $data): void
    {
        if (isset($data['tax_id'])) $account->setTaxId($data['tax_id']);
        if (isset($data['address'])) $account->setAddress($data['address']);
        if (isset($data['phone'])) $account->setPhone($data['phone']);
        if (isset($data['email'])) $account->setEmail($data['email']);
    }

    private function validateAccountCreationData(array $data): void
    {
        $this->validateBusinessNameRequired($data);
        $this->validateBusinessTypeRequired($data);
        $this->validateBusinessName($data);
        $this->validateBusinessType($data);
        $this->validateDescription($data);
        $this->validateUrlFields($data, ['logoUrl', 'coverUrl']);
        $this->validatePhone($data);
        $this->validateEmail($data);
        $this->validateWhatsappBusinessId($data);
        $this->validateTaxId($data);
    }

    private function validateBusinessNameRequired(array $data): void
    {
        if (empty(trim($data['business_name'] ?? ''))) throw new InvalidArgumentException('Business name is required');
    }

    private function validateBusinessTypeRequired(array $data): void
    {
        if (empty(trim($data['business_type'] ?? ''))) throw new InvalidArgumentException('Business type is required');
    }

    private function validateBusinessName(array $data): void
    {
        if (isset($data['business_name']) && $data['business_name'] !== '' && strlen($data['business_name']) > 200) {
            throw new InvalidArgumentException('Business name cannot exceed 200 characters');
        }
    }

    private function validateBusinessType(array $data): void
    {
        if (isset($data['business_type']) && $data['business_type'] !== '') {
            $validTypes = ['individual', 'company', 'ngo', 'government'];
            if (!in_array($data['business_type'], $validTypes)) {
                throw new InvalidArgumentException('Invalid business type. Valid types: individual, company, ngo, government');
            }
        }
    }

    private function validateDescription(array $data): void
    {
        if (isset($data['description']) && $data['description'] !== null && strlen($data['description']) > 500) {
            throw new InvalidArgumentException('Description cannot exceed 500 characters');
        }
    }

    private function validateUrlFields(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== null && $data[$field] !== '') {
                if (!filter_var($data[$field], FILTER_VALIDATE_URL)) {
                    throw new InvalidArgumentException("Invalid $field URL");
                }
            }
        }
    }

    private function validatePhone(array $data): void
    {
        if (isset($data['phone']) && $data['phone'] !== null && $data['phone'] !== '') {
            if (!preg_match('/^[\d\s\-\(\)\+]+$/', $data['phone'])) {
                throw new InvalidArgumentException('Phone number contains invalid characters');
            }
            if (strlen($data['phone']) > 20) {
                throw new InvalidArgumentException('Phone number cannot exceed 20 characters');
            }
        }
    }

    private function validateEmail(array $data): void
    {
        if (isset($data['email']) && $data['email'] !== null && $data['email'] !== '') {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email format');
            }
            if (strlen($data['email']) > 100) {
                throw new InvalidArgumentException('Email cannot exceed 100 characters');
            }
        }
    }

    private function validateWhatsappBusinessId(array $data): void
    {
        if (isset($data['whatsapp_business_id']) && $data['whatsapp_business_id'] !== null && $data['whatsapp_business_id'] !== '') {
            if (!preg_match('/^[a-zA-Z0-9\-]+$/', $data['whatsapp_business_id'])) {
                throw new InvalidArgumentException('WhatsApp Business ID contains invalid characters');
            }
            if (strlen($data['whatsapp_business_id']) > 100) {
                throw new InvalidArgumentException('WhatsApp Business ID cannot exceed 100 characters');
            }
        }
    }

    private function validateTaxId(array $data): void
    {
        if (isset($data['tax_id']) && $data['tax_id'] !== null && $data['tax_id'] !== '') {
            if (preg_match('/<[^>]*>/', $data['tax_id'])) {
                throw new InvalidArgumentException('Tax ID contains invalid characters');
            }
            if (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $data['tax_id'])) {
                throw new InvalidArgumentException('Tax ID contains invalid characters');
            }
            if (strlen($data['tax_id']) > 50) {
                throw new InvalidArgumentException('Tax ID cannot exceed 50 characters');
            }
        }
    }

    private function validateAccountData(array $data, bool $isCreation = true): void
    {
        if ($isCreation) {
            $this->validateBusinessNameRequired($data);
            $this->validateBusinessTypeRequired($data);
        }
        $this->validateBusinessName($data);
        $this->validateBusinessType($data);
        $this->validateDescription($data);
        $this->validateUrlFields($data, ['logoUrl', 'coverUrl']);
        $this->validatePhone($data);
        $this->validateEmail($data);
        $this->validateWhatsappBusinessId($data);
        $this->validateTaxId($data);
    }

    private function validateAccountUpdateData(array $data): void
    {
        if (empty($data)) throw new InvalidArgumentException('No data provided for update');
        $this->validateAccountData($data, false);
    }
}
