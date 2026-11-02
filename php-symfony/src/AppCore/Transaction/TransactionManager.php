<?php

namespace App\AppCore\Transaction;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Throwable;

class TransactionManager
{
    private EntityManagerInterface $entityManager;
    private bool $inTransaction = false;
    private int $nestingLevel = 0;
    private array $savePoints = [];

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function begin(): void
    {
        if ($this->nestingLevel === 0) {
            $this->entityManager->beginTransaction();
            $this->inTransaction = true;
        }
        $this->nestingLevel++;
    }

    public function commit(): void
    {
        if ($this->nestingLevel === 0) return;
        $this->nestingLevel--;
        if ($this->nestingLevel === 0) {
            $this->entityManager->commit();
            $this->inTransaction = false;
            $this->clearSavePoints();
        }
    }

    public function rollback(): void
    {
        if ($this->nestingLevel === 0) return;
        $this->nestingLevel--;
        if ($this->nestingLevel === 0) {
            $this->entityManager->rollback();
            $this->inTransaction = false;
            $this->clearSavePoints();
        }
    }

    public function rollbackTo(string $savePointName): void
    {
        if (!$this->inTransaction || !isset($this->savePoints[$savePointName])) return;
        $this->entityManager->rollback($this->savePoints[$savePointName]);
        $this->removeSavePointsAfter($savePointName);
    }

    public function createSavePoint(string $name): string
    {
        if (!$this->inTransaction) throw new \RuntimeException('Cannot create save point outside of transaction');
        $this->savePoints[$name] = $name;
        return $name;
    }

    public function releaseSavePoint(string $name): void
    {
        if (!$this->inTransaction || !isset($this->savePoints[$name])) return;
        unset($this->savePoints[$name]);
    }

    public function execute(callable $operation): mixed
    {
        return $this->executeWithRetry($operation, 3);
    }

    public function executeWithRetry(callable $operation, int $maxRetries): mixed
    {
        $retries = 0;
        $lastException = null;

        while ($retries < $maxRetries) {
            try {
                return $this->executeOperation($operation);
            } catch (Throwable $e) {
                $lastException = $e;
                $retries++;
                if ($retries < $maxRetries) {
                    usleep(100000 * $retries);
                }
            }
        }

        throw $lastException;
    }

    public function isInTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function getNestingLevel(): int
    {
        return $this->nestingLevel;
    }

    public function getSavePoints(): array
    {
        return array_keys($this->savePoints);
    }

    private function executeOperation(callable $operation): mixed
    {
        $this->begin();
        try {
            $result = $operation();
            $this->commit();
            return $result;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    private function clearSavePoints(): void
    {
        $this->savePoints = [];
    }

    private function removeSavePointsAfter(string $savePointName): void
    {
        $found = false;
        $toRemove = [];
        foreach ($this->savePoints as $name => $point) {
            if ($name === $savePointName) {
                $found = true;
                continue;
            }
            if ($found) {
                $toRemove[] = $name;
            }
        }
        foreach ($toRemove as $name) {
            unset($this->savePoints[$name]);
        }
    }

    public function __destruct()
    {
        if ($this->inTransaction) {
            try {
                $this->rollback();
            } catch (Exception $e) {}
        }
    }
}
