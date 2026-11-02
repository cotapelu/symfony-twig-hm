<?php

declare(strict_types=1);

namespace App\Investment\Controller;

use App\Investment\Service\InvestmentService;
use App\Investment\Entity\Investment;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use DateTimeImmutable;

#[Route('/api/investments')]
class InvestmentController extends BaseController
{
    private const VALID_INVESTMENT_TYPES = ['stock', 'bond', 'mutual_fund', 'etf', 'real_estate', 'crypto', 'savings', 'fixed_deposit'];
    private const VALID_STATUSES = ['active', 'matured', 'cancelled'];

    public function __construct(
        private InvestmentService $investmentService,
    ) {
    }

    private function getAuthenticatedUserId(): int
    {
        $user = $this->getUser();
        if (!$user) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('Authentication required');
        }
        return (int) $user->getUserIdentifier();
    }

    #[Route('', methods: ['POST'])]
    public function createInvestment(Request $request): JsonResponse
    {
        try {
            $authUserId = $this->getAuthenticatedUserId(); $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400; else $data = $dataOr400;
            $this->validateInvestmentType($data); $this->validateName($data); $this->validatePrincipal($data);
            $purchaseDate = $this->parseDate($data['purchaseDate'] ?? null); $maturityDate = $this->parseDate($data['maturityDate'] ?? null);
            $this->validateDateOrder($purchaseDate, $maturityDate);
            return $this->executeCreateInvestment($authUserId, $data, $purchaseDate, $maturityDate);
        } catch (InvalidArgumentException $e) { return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST); }
        catch (AccessDeniedException $e) { return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN); }
        catch (\Exception $e) { return $this->handleException('create_investment', $e); }
    }

    private function executeCreateInvestment(int $authUserId, array $data, ?DateTimeImmutable $purchaseDate, ?DateTimeImmutable $maturityDate): JsonResponse
    {
        $principal = (float)$data['principal']; $currentValue = isset($data['currentValue']) ? (float)$data['currentValue'] : $principal; $investmentType = trim($data['investmentType']); $name = trim($data['name']);
        $investment = $this->investmentService->createInvestment(userId: (string)$authUserId, investmentType: $investmentType, name: $name, principal: $principal, currentValue: $currentValue, purchaseDate: $purchaseDate ? new \DateTime($purchaseDate->format('Y-m-d')) : null, maturityDate: $maturityDate ? new \DateTime($maturityDate->format('Y-m-d')) : null);
        $this->logAction('investment_created', ['investment_id' => $investment->getId(), 'user_id' => $authUserId, 'type' => $investmentType]); $this->incrementMetric('investments_created');
        return $this->json(['investmentId' => $investment->getId(),'name' => $investment->getName(),'type' => $investment->getInvestmentType(),'principal' => $investment->getPrincipal(),'currentValue' => $investment->getCurrentValue()], Response::HTTP_CREATED);
    }

    private function validateInvestmentType(array $data): void
    {
        if (!isset($data['investmentType']) || empty(trim($data['investmentType']))) {
            throw new InvalidArgumentException('Investment type is required');
        }
    }

    private function validateName(array $data): void
    {
        if (!isset($data['name']) || empty(trim($data['name']))) {
            throw new InvalidArgumentException('Investment name is required');
        }
    }

    private function validatePrincipal(array $data): void
    {
        if (!isset($data['principal']) || !is_numeric($data['principal'])) {
            throw new InvalidArgumentException('Principal amount is required');
        }
    }

    private function parseDate(?string $dateString): ?DateTimeImmutable
    {
        if ($dateString === null || $dateString === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($dateString);
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Invalid date format');
        }
    }

    private function validateDateOrder(?DateTimeImmutable $purchaseDate, ?DateTimeImmutable $maturityDate): void
    {
        if ($purchaseDate && $maturityDate && $maturityDate <= $purchaseDate) {
            throw new InvalidArgumentException('Maturity date must be after purchase date');
        }
    }

    private function handle(callable $action, string $context = ''): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => 'Access denied'], 403);
        } catch (\Exception $e) {
            if ($context) {
                return $this->handleException($context, $e);
            }
            error_log("InvestmentController error: ".$e->getMessage());
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('', methods: ['GET'])]
    public function getInvestments(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getInvestmentsAction($request), 'get_investments');
    }

    private function getInvestmentsAction(Request $request): JsonResponse
    {
        $authUserId = $this->getAuthenticatedUserId();
        $type = $request->query->get('type');
        $status = $request->query->get('status');
        if ($type && !in_array($type, self::VALID_INVESTMENT_TYPES, true)) {
            throw new InvalidArgumentException('Invalid investment type filter');
        }
        if ($status && !in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException('Invalid status filter');
        }
        $investments = $this->investmentService->getUserInvestments((string)$authUserId, $type, $status);
        $this->logAction('investments_fetched', ['user_id' => $authUserId, 'count' => count($investments)]);
        return $this->json($this->buildInvestmentsList($investments));
    }

    private function buildInvestmentsList(array $investments): array
    {
        return [
            'investments' => array_map(fn(Investment $i) => [
                'id' => $i->getId(),
                'investmentType' => $i->getInvestmentType(),
                'name' => $i->getName(),
                'principal' => $i->getPrincipal(),
                'currentValue' => $i->getCurrentValue(),
                'returnRate' => $i->getReturnRate(),
                'profit' => $i->getProfit(),
                'status' => $i->getStatus(),
                'purchaseDate' => $i->getPurchaseDate()->format('c'),
            ], $investments),
            'total' => count($investments)
        ];
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getInvestment(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getInvestmentAction($id), 'get_investment');
    }

    private function getInvestmentAction(int $id): JsonResponse
    {
        if ($id <= 0) { throw new InvalidArgumentException('Investment ID must be positive'); }
        $authUserId = $this->getAuthenticatedUserId();
        $investment = $this->investmentService->getInvestment($id);
        if (!$investment) { return $this->json(['error' => 'Investment not found'], Response::HTTP_NOT_FOUND); }
        if ($investment->getUserId() !== (string)$authUserId) { return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN); }
        return $this->json($this->buildInvestmentData($investment));
    }

    private function buildInvestmentData(Investment $investment): array
    {
        return [
            'id' => $investment->getId(),
            'userId' => $investment->getUserId(),
            'investmentType' => $investment->getInvestmentType(),
            'name' => $investment->getName(),
            'principal' => $investment->getPrincipal(),
            'currentValue' => $investment->getCurrentValue(),
            'returnRate' => $investment->getReturnRate(),
            'currency' => $investment->getCurrency(),
            'profit' => $investment->getProfit(),
            'purchaseDate' => $investment->getPurchaseDate()->format('c'),
            'maturityDate' => $investment->getMaturityDate()?->format('c'),
            'status' => $investment->getStatus(),
        ];
    }

    #[Route('/{id}/update-value', methods: ['POST'])]
    public function updateInvestmentValue(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateInvestmentValueAction($id, $request), 'update_investment_value');
    }

    private function updateInvestmentValueAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) { throw new InvalidArgumentException('Investment ID must be positive'); }
        $authUserId = $this->getAuthenticatedUserId();
        $investment = $this->investmentService->getInvestment($id);
        if (!$investment) { return $this->json(['error' => 'Investment not found'], Response::HTTP_NOT_FOUND); }
        if ($investment->getUserId() !== (string)$authUserId) { return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN); }
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) { return $dataOr400; }
        $data = $dataOr400;
        $currentValue = (float)$data['currentValue'];
        if ($currentValue < 0) { throw new InvalidArgumentException('Current value cannot be negative'); }
        $updated = $this->investmentService->updateInvestmentValue($id, $currentValue);
        if (!$updated) { return $this->json(['error' => 'Failed to update investment value'], Response::HTTP_BAD_REQUEST); }
        $this->logAction('investment_value_updated', ['investment_id' => $id, 'user_id' => $authUserId, 'new_value' => $currentValue]);
        $this->incrementMetric('investment_values_updated');
        return $this->json(['success'=>true,'currentValue'=>$updated->getCurrentValue(),'returnRate'=>$updated->getReturnRate(),'profit'=>$updated->getProfit()]);
    }

    #[Route('/{id}/mature', methods: ['POST'])]
    public function markAsMature(int $id): JsonResponse
    {
        try {
            if ($id <= 0) {
                throw new InvalidArgumentException('Investment ID must be positive');
            }
            $authUserId = $this->getAuthenticatedUserId();

            $investment = $this->investmentService->getInvestment($id);
            if (!$investment) {
                return $this->json(['error' => 'Investment not found'], Response::HTTP_NOT_FOUND);
            }
            if ($investment->getUserId() !== (string)$authUserId) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $result = $this->investmentService->markAsMature($id);
            if (!$result) {
                return $this->json(['error' => 'Failed to mark investment as matured'], Response::HTTP_BAD_REQUEST);
            }

            $this->logAction('investment_matured', ['investment_id' => $id, 'user_id' => $authUserId]);
            $this->incrementMetric('investments_matured');

            return $this->json(['success' => true, 'message' => 'Investment marked as matured']);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('mark_as_mature', $e);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteInvestment(int $id): JsonResponse
    {
        try {
            if ($id <= 0) {
                throw new InvalidArgumentException('Investment ID must be positive');
            }
            $authUserId = $this->getAuthenticatedUserId();

            $investment = $this->investmentService->getInvestment($id);
            if (!$investment) {
                return $this->json(['error' => 'Investment not found'], Response::HTTP_NOT_FOUND);
            }
            if ($investment->getUserId() !== (string)$authUserId) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $deleted = $this->investmentService->deleteInvestment($id);
            if (!$deleted) {
                return $this->json(['error' => 'Failed to delete investment'], Response::HTTP_BAD_REQUEST);
            }

            $this->logAction('investment_deleted', ['investment_id' => $id, 'user_id' => $authUserId]);
            $this->incrementMetric('investments_deleted');

            return $this->json(['success' => true], Response::HTTP_NO_CONTENT);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('delete_investment', $e);
        }
    }

    #[Route('/summary', methods: ['GET'])]
    public function getInvestmentSummary(Request $request): JsonResponse
    {
        try {
            $authUserId = $this->getAuthenticatedUserId();

            $summary = $this->investmentService->getUserInvestmentSummary((string)$authUserId);

            $this->logAction('investment_summary_fetched', ['user_id' => $authUserId]);

            return $this->json($summary);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->handleException('get_investment_summary', $e);
        }
    }

    #[Route('/types', methods: ['GET'])]
    public function getInvestmentTypes(): JsonResponse
    {
        return $this->json([
            'types' => array_combine(self::VALID_INVESTMENT_TYPES, array_map(fn($t) => ucwords(str_replace('_', ' ', $t)), self::VALID_INVESTMENT_TYPES))
        ]);
    }

    // ========== PRIVATE METHODS ==========

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("InvestmentController::{$context} error: " . $e->getMessage());
        $this->logAction("{$context}_error", ['error' => $e->getMessage()]);
        $this->incrementMetric("{$context}_errors");

        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
