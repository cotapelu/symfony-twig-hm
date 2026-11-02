<?php

declare(strict_types=1);

namespace App\Investment\Service;

use App\Investment\Entity\Investment;
use App\Investment\Repository\InvestmentRepository;

class InvestmentService
{
    public function __construct(
        private InvestmentRepository $investmentRepository,
    ) {
    }

    public function createInvestment(string $userId, string $investmentType, string $name, float $principal, ?float $currentValue = null, ?\DateTime $purchaseDate = null, ?\DateTime $maturityDate = null): Investment
    {
        if (empty(trim($userId))) throw new \InvalidArgumentException('User ID is required');
        if (strlen($userId) > 255) throw new \InvalidArgumentException('User ID cannot exceed 255 characters');
        $investment = new Investment();
        $investment->setUserId($userId)->setInvestmentType($investmentType)->setName($name)->setPrincipal($principal);
        $investment->setCurrentValue($currentValue ?? $principal);
        $investment->setPurchaseDate($purchaseDate ?? new \DateTime());
        $investment->setMaturityDate($maturityDate);
        $investment->calculateReturnRate();
        $this->investmentRepository->save($investment);
        return $investment;
    }

    public function getUserInvestments(string $userId, ?string $type = null, ?string $status = null)
    {
        if ($type) {
            return $this->investmentRepository->findByUserAndType($userId, $type);
        }
        
        return $this->investmentRepository->findByUser($userId);
    }

    public function getInvestment(int $id)
    {
        return $this->investmentRepository->find($id);
    }

    public function updateInvestmentValue(int $id, float $currentValue)
    {
        $investment = $this->investmentRepository->find($id);
        if (!$investment) {
            return null;
        }

        $investment->setCurrentValue($currentValue);
        $investment->calculateReturnRate();
        
        $this->investmentRepository->save($investment);
        return $investment;
    }

    public function markAsMature(int $id)
    {
        $investment = $this->investmentRepository->find($id);
        if (!$investment) {
            return false;
        }

        $investment->setStatus('matured');
        $this->investmentRepository->save($investment);
        return true;
    }

    public function deleteInvestment(int $id)
    {
        $investment = $this->investmentRepository->find($id);
        if (!$investment) {
            return false;
        }

        $this->investmentRepository->delete($investment);
        return true;
    }

    public function getUserInvestmentSummary(string $userId): array
    {
        $active = $this->investmentRepository->findActiveByUser($userId);
        $matured = $this->investmentRepository->findMatureByUser($userId);
        $totalPrincipal = $this->investmentRepository->getTotalPrincipalByUser($userId);
        $totalCurrentValue = $this->investmentRepository->getTotalCurrentValueByUser($userId);
        $totalProfit = $totalCurrentValue - $totalPrincipal;
        $returnRate = $totalPrincipal > 0 ? ($totalProfit / $totalPrincipal) * 100 : 0;
        $investmentsByType = $this->groupInvestmentsByType($active);
        return [
            'totalPrincipal' => $totalPrincipal,
            'totalCurrentValue' => $totalCurrentValue,
            'totalProfit' => $totalProfit,
            'returnRate' => round($returnRate, 2),
            'activeCount' => count($active),
            'maturedCount' => count($matured),
            'investmentsByType' => array_values($investmentsByType),
        ];
    }

    private function groupInvestmentsByType(array $activeInvestments): array
    {
        $byType = [];
        foreach ($activeInvestments as $investment) {
            $type = $investment->getInvestmentType();
            if (!isset($byType[$type])) {
                $byType[$type] = [
                    'type' => $type,
                    'count' => 0,
                    'totalPrincipal' => 0,
                    'totalCurrentValue' => 0,
                ];
            }
            $byType[$type]['count']++;
            $byType[$type]['totalPrincipal'] += $investment->getPrincipal();
            $byType[$type]['totalCurrentValue'] += $investment->getCurrentValue();
        }
        return $byType;
    }
}
