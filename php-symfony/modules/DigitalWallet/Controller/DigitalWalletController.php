<?php
declare(strict_types=1);

namespace App\DigitalWallet\Controller;

use App\DigitalWallet\Service\DigitalWalletService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use DateTimeImmutable;

/**
 * Digital Wallet Controller for managing digital wallet operations.
 * 
 * TL;DR: RESTful API controller for digital wallet management with proper validation, 
 * error handling, and dependency injection.
 */
class DigitalWalletController extends BaseController
{
    private DigitalWalletService $walletService;
    private const SUPPORTED_CURRENCIES = ['VND', 'USD', 'EUR'];
    private const SUPPORTED_WALLET_TYPES = ['main', 'savings', 'business'];
    private const MIN_AMOUNT = 1000;
    private const MAX_AMOUNT = 100000000;

    public function __construct(DigitalWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => 'Access denied'], JsonResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->handleException('controller', $e);
        }
    }

    #[Route('/create', name: 'wallet_create', methods: ['POST'])]
    public function createWallet(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
            $this->validateWalletCreationData($data);
            
            $wallet = $this->walletService->createWallet(
                $userId,
                $data['walletType'] ?? 'main',
                $data['currency'] ?? 'VND'
            );

            $result = [
                'id' => $wallet->getId(),
                'userId' => $wallet->getUserId(),
                'walletType' => $wallet->getWalletType(),
                'balance' => $wallet->getBalance(),
                'currency' => $wallet->getCurrency(),
            ];

            return $this->json($result, JsonResponse::HTTP_CREATED);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => 'Access denied'], JsonResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->handleException('create_wallet', $e);
        }
    }

    #[Route('/{walletId}/deposit', name: 'wallet_deposit', methods: ['POST'])]
    public function deposit(string $walletId, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->depositAction($walletId, $request));
    }

    private function depositAction(string $walletId, Request $request): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId();
        $this->validateWalletOwnership($walletId);
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        $this->validateTransactionData($data);

        $amount = $data['amount'];
        $description = $data['description'] ?? 'Deposit';
        $idempotencyKey = $request->headers->get('Idempotency-Key');

        $transaction = $this->walletService->deposit($walletId, $amount, $description, $idempotencyKey, $userId);

        return $this->json([
            'success' => true,
            'data' => [
                'transactionId' => $transaction->getId(),
                'amount' => $transaction->getAmount(),
                'status' => $transaction->getStatus(),
            ]
        ]);
    }

    #[Route('/{walletId}/withdraw', name: 'wallet_withdraw', methods: ['POST'])]
    public function withdraw(string $walletId, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->withdrawAction($walletId, $request));
    }

    private function withdrawAction(string $walletId, Request $request): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId();
        $this->validateWalletOwnership($walletId);
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        $this->validateTransactionData($data);

        $amount = $data['amount'];
        $description = $data['description'] ?? 'Withdrawal';
        $idempotencyKey = $request->headers->get('Idempotency-Key');

        $transaction = $this->walletService->withdraw($walletId, $amount, $description, $idempotencyKey, $userId);

        return $this->json([
            'success' => true,
            'data' => [
                'transactionId' => $transaction->getId(),
                'amount' => abs($transaction->getAmount()),
                'status' => $transaction->getStatus(),
            ]
        ]);
    }

    #[Route('/{fromWalletId}/transfer', name: 'wallet_transfer', methods: ['POST'])]
    public function transfer(string $fromWalletId, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->transferAction($fromWalletId, $request));
    }

    private function transferAction(string $fromWalletId, Request $request): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId();
        $this->validateWalletOwnership($fromWalletId);
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        $this->validateTransferData($data);

        $toWalletId = $data['toWalletId'];
        $amount = $data['amount'];
        $description = $data['description'] ?? 'Transfer';
        $idempotencyKey = $request->headers->get('Idempotency-Key');

        if (!$this->walletService->walletExists($toWalletId)) {
            throw new InvalidArgumentException('Recipient wallet not found');
        }

        $transactions = $this->walletService->transfer($fromWalletId, $toWalletId, $amount, $description, $idempotencyKey, $userId);

        return $this->json([
            'success' => true,
            'data' => $this->buildTransferResponse($transactions)
        ]);
    }

    private function buildTransferResponse(array $transactions): array
    {
        return [
            'fromTransaction' => [
                'id' => $transactions[0]->getId(),
                'amount' => abs($transactions[0]->getAmount()),
                'fee' => $transactions[0]->getFee(),
            ],
            'toTransaction' => [
                'id' => $transactions[1]->getId(),
                'amount' => $transactions[1]->getAmount(),
            ]
        ];
    }

    #[Route('/{walletId}/balance', name: 'wallet_balance', methods: ['GET'])]
    public function getBalance(string $walletId): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $balance = $this->walletService->getBalance($walletId, $userId);

            return $this->json([
                'success' => true,
                'data' => [
                    'walletId' => $walletId,
                    'balance' => $balance,
                ]
            ]);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => 'Access denied'], JsonResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->handleException('get_balance', $e);
        }
    }

    #[Route('/{walletId}/transactions', name: 'wallet_transactions', methods: ['GET'])]
    public function getTransactions(string $walletId, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getTransactionsAction($walletId, $request));
    }

    private function getTransactionsAction(string $walletId, Request $request): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId();
        $pagination = $this->extractPagination($request);
        
        $transactions = $this->walletService->getTransactionHistory(
            $walletId, 
            $pagination['limit'], 
            $pagination['offset'],
            $userId
        );

        return $this->json([
            'success' => true,
            'data' => array_map(function ($t) {
                return [
                    'id' => $t->getId(),
                    'type' => $t->getTransactionType(),
                    'amount' => $t->getAmount(),
                    'description' => $t->getDescription(),
                    'createdAt' => $t->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }, $transactions)
        ]);
    }

    // ========== PRIVATE METHODS ==========

    private function getAuthenticatedUserId(): string
    {
        // Get authenticated user from security context
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Authentication required');
        }
        return $user->getId();
    }




    private function validateWalletCreationData(array $data): void
    {
        $walletType = $data['walletType'] ?? 'main';
        $currency = $data['currency'] ?? 'VND';
        
        if (!in_array($walletType, self::SUPPORTED_WALLET_TYPES, true)) {
            throw new InvalidArgumentException(
                'Wallet type must be one of: ' . implode(', ', self::SUPPORTED_WALLET_TYPES)
            );
        }
        
        if (!in_array($currency, self::SUPPORTED_CURRENCIES, true)) {
            throw new InvalidArgumentException(
                'Currency must be one of: ' . implode(', ', self::SUPPORTED_CURRENCIES)
            );
        }
    }

    private function validateTransactionData(array $data): void
    {
        if (!isset($data['amount']) || !is_numeric($data['amount'])) {
            throw new InvalidArgumentException('Valid amount is required');
        }
        
        $amount = (float) $data['amount'];
        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            throw new InvalidArgumentException(
                sprintf('Amount must be between %d and %d', self::MIN_AMOUNT, self::MAX_AMOUNT)
            );
        }
        
        if (isset($data['description']) && !is_string($data['description'])) {
            throw new InvalidArgumentException('Description must be a string');
        }
        
        if (isset($data['description']) && strlen($data['description']) > 500) {
            throw new InvalidArgumentException('Description must not exceed 500 characters');
        }
    }

    private function validateTransferData(array $data): void
    {
        $this->validateTransactionData($data);
        
        if (!isset($data['toWalletId']) || trim($data['toWalletId']) === '') {
            throw new InvalidArgumentException('Recipient wallet ID is required');
        }
    }

    private function extractPagination(Request $request): array
    {
        return [
            'limit' => $this->validatePositiveInt(
                (int) $request->query->get('limit', 50), 
                'limit', 
                1, 
                100
            ),
            'offset' => $this->validatePositiveInt(
                (int) $request->query->get('offset', 0), 
                'offset', 
                0
            )
        ];
    }

    private function validatePositiveInt(int $value, string $fieldName, int $min = 1, ?int $max = null): int
    {
        if ($value < $min) {
            throw new InvalidArgumentException("$fieldName must be at least $min");
        }
        if ($max !== null && $value > $max) {
            throw new InvalidArgumentException("$fieldName must not exceed $max");
        }
        return $value;
    }

    private function validateWalletOwnership(string $walletId): void
    {
        $userId = $this->getAuthenticatedUserId();
        if (!$this->walletService->isWalletOwnedBy($walletId, $userId)) {
            throw new AccessDeniedException('Access denied to this wallet');
        }
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("DigitalWalletController::{$context} error: " . $e->getMessage());
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