<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\EcommerceTransaction;
use App\Repository\Php\EcommerceTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

class EcommerceTransactionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EcommerceTransactionRepository $repository
    ) {}

    public function createTransaction(string $orderId, string $customerId, string $paymentMethod, int $amountCents, ?string $externalTransactionId = null, ?string $status = 'PENDING', ?string $currency = 'USD', ?array $paymentDetails = null, ?array $metadata = null)
    {
        $this->validateCreateInput($orderId, $customerId, $paymentMethod, $amountCents);
        $transaction = $this->buildTransactionEntity($orderId, $customerId, $paymentMethod, $amountCents, $externalTransactionId, $status, $currency, $paymentDetails, $metadata);
        $this->em->persist($transaction); $this->em->flush();
        return $transaction;
    }

    public function getTransaction(string $id)
    {
        $transaction = $this->repository->find($id);
        if (!$transaction) throw new \RuntimeException('EcommerceTransaction not found');
        return $transaction;
    }

    public function updateTransaction(string $id, array $data)
    {
        $transaction = $this->getTransaction($id);
        $this->applyTransactionFields($transaction, $data);
        $this->em->flush();
        return $transaction;
    }

    public function deleteTransaction(string $id)
    {
        $transaction = $this->getTransaction($id);
        $this->em->remove($transaction);
        $this->em->flush();
    }

    public function findByOrderId(string $orderId)
    {
        return $this->repository->findBy(['orderId' => $orderId]);
    }

    public function findByCustomerId(string $customerId)
    {
        return $this->repository->findBy(['customerId' => $customerId]);
    }

    public function findByStatus(string $status)
    {
        return $this->repository->findBy(['status' => $status]);
    }

    public function findByPaymentMethod(string $paymentMethod)
    {
        return $this->repository->findBy(['paymentMethod' => $paymentMethod]);
    }

    private function validateCreateInput(string $orderId, string $customerId, string $paymentMethod, int $amountCents): void
    {
        if (empty(trim($orderId))) throw new \InvalidArgumentException('Order ID is required');
        if (empty(trim($customerId))) throw new \InvalidArgumentException('Customer ID is required');
        if (empty(trim($paymentMethod))) throw new \InvalidArgumentException('Payment method is required');
        if ($amountCents <= 0) throw new \InvalidArgumentException('Amount must be positive');
    }

    private function buildTransactionEntity(string $orderId, string $customerId, string $paymentMethod, int $amountCents, ?string $externalTransactionId = null, ?string $status = 'PENDING', ?string $currency = 'USD', ?array $paymentDetails = null, ?array $metadata = null): EcommerceTransaction
    {
        $transaction = new EcommerceTransaction(bin2hex(random_bytes(16)), $orderId, $customerId, $paymentMethod, $amountCents);
        $transaction->setExternalTransactionId($externalTransactionId)->setStatus($status)->setCurrency($currency)->setPaymentDetails($paymentDetails)->setMetadata($metadata);
        return $transaction;
    }

    private function applyTransactionFields($transaction, array $data): void
    {
        $this->applyOrderId($transaction, $data);
        $this->applyCustomerId($transaction, $data);
        $this->applyPaymentMethod($transaction, $data);
        $this->applyAmountCents($transaction, $data);
        $this->applyExternalTransactionId($transaction, $data);
        $this->applyStatus($transaction, $data);
        $this->applyCurrency($transaction, $data);
        $this->applyPaymentDetails($transaction, $data);
        $this->applyMetadata($transaction, $data);
    }

    private function applyOrderId($transaction, array $data): void
    {
        if (!array_key_exists('orderId', $data)) return;
        $orderId = trim($data['orderId']);
        if ($orderId === '') throw new \InvalidArgumentException('Order ID cannot be empty');
        $transaction->setOrderId($orderId);
    }

    private function applyCustomerId($transaction, array $data): void
    {
        if (!array_key_exists('customerId', $data)) return;
        $customerId = trim($data['customerId']);
        if ($customerId === '') throw new \InvalidArgumentException('Customer ID cannot be empty');
        $transaction->setCustomerId($customerId);
    }

    private function applyPaymentMethod($transaction, array $data): void
    {
        if (!array_key_exists('paymentMethod', $data)) return;
        $method = trim($data['paymentMethod']);
        if ($method === '') throw new \InvalidArgumentException('Payment method cannot be empty');
        $transaction->setPaymentMethod($method);
    }

    private function applyAmountCents($transaction, array $data): void
    {
        if (!array_key_exists('amountCents', $data)) return;
        $amount = (int)$data['amountCents'];
        if ($amount <= 0) throw new \InvalidArgumentException('Amount must be positive');
        $transaction->setAmountCents($amount);
    }

    private function applyExternalTransactionId($transaction, array $data): void
    {
        if (array_key_exists('externalTransactionId', $data)) {
            $transaction->setExternalTransactionId($data['externalTransactionId']);
        }
    }

    private function applyStatus($transaction, array $data): void
    {
        if (!array_key_exists('status', $data)) return;
        $status = trim($data['status']);
        if ($status === '') throw new \InvalidArgumentException('Status cannot be empty');
        $transaction->setStatus($status);
    }

    private function applyCurrency($transaction, array $data): void
    {
        if (!array_key_exists('currency', $data)) return;
        $curr = trim($data['currency']);
        if ($curr === '') throw new \InvalidArgumentException('Currency cannot be empty');
        $transaction->setCurrency($curr);
    }

    private function applyPaymentDetails($transaction, array $data): void
    {
        if (array_key_exists('paymentDetails', $data)) {
            $transaction->setPaymentDetails($data['paymentDetails']);
        }
    }

    private function applyMetadata($transaction, array $data): void
    {
        if (array_key_exists('metadata', $data)) {
            $transaction->setMetadata($data['metadata']);
        }
    }
}
