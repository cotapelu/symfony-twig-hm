<?php
$file = 'modules/Ecommerce/Service/EcommerceService.php';
$code = file_get_contents($file);
$append = <<<'PHP'

    public function createOrder(array $data): \App\Ecommerce\Entity\EcommerceOrder
    {
        $userId = $data['user_id'];
        $productId = $data['product_id'];
        $quantity = (int) ($data['quantity'] ?? 1);
        $shippingAddress = $data['shipping_address'] ?? null;
        $idempotencyKey = $data['idempotency_key'] ?? null;
        return $this->orderService->createOrder($userId, $productId, $quantity, $shippingAddress, $idempotencyKey);
    }

    public function getOrder(string $orderId, string $userId): ?\App\Ecommerce\Entity\EcommerceOrder
    {
        return $this->orderService->getOrder($orderId, $userId);
    }

    public function confirmOrder(string $orderId, string $userId): \App\Ecommerce\Entity\EcommerceOrder
    {
        return $this->orderService->confirmOrder($orderId, $userId);
    }

    public function cancelOrder(string $orderId, string $userId): \App\Ecommerce\Entity\EcommerceOrder
    {
        return $this->orderService->cancelOrder($orderId, $userId);
    }

    public function getUserOrders(string $userId, int $limit = 20): array
    {
        return $this->orderService->getUserOrders($userId, $limit);
    }
PHP;
file_put_contents($file, $code . $append);
echo "Appended order methods.\n";