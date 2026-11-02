<?php

declare(strict_types=1);

namespace App\Ecommerce\Controller;

use App\Ecommerce\Service\EcommerceService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use DateTimeImmutable;

/**
 * Ecommerce Controller for managing product catalog and orders.
 *
 * TL;DR: RESTful API controller with DRY error handling, validation, and ownership checks.
 */
#[Route('/api/v1/ecommerce')]
class EcommerceController extends BaseController
{
    private const SUPPORTED_CURRENCIES = ['USD', 'EUR', 'GBP', 'VND'];
    private const MIN_PRICE = 0.01;
    private const MAX_PRICE = 999999.99;
    private const MAX_STOCK = 999999;

    public function __construct(
        private readonly EcommerceService $service
    ) { parent::__construct(); }

    // ==================== Auth & JSON Helpers ====================

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }
        return (string) $user->getId();
    }

    // ========== PUBLIC API ENDPOINTS ==========

    #[Route('/products', name: 'ecommerce_create_product', methods: ['POST'])]
    public function createProduct(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $userIdOr401 = $this->requireAuthOr401(); if ($userIdOr401 instanceof JsonResponse) return $userIdOr401; $sellerId = (string)$userIdOr401;
            $dataOr400 = $this->decodeJsonOr400($request); if ($dataOr400 instanceof JsonResponse) return $dataOr400; $data = $dataOr400;
            $this->validateProductCreationData($data);
            $productData = ['seller_id'=>$sellerId,'name'=>$data['name'],'price'=>(float)$data['price']];
            if (isset($data['description'])) $productData['description'] = $data['description'];
            if (isset($data['category'])) $productData['category'] = $data['category'];
            $product = $this->service->createProduct($productData);
            $productId = method_exists($product,'getId') ? $product->getId() : null;
            $this->logAction('product_created', ['product_id'=>$productId,'seller_id'=>$sellerId]);
            $this->incrementMetric('products_created');
            if (method_exists($product,'toArray')) $product = $product->toArray();
            return $this->json($product, Response::HTTP_CREATED);
        });
    }

    #[Route('/products', name: 'ecommerce_get_products', methods: ['GET'])]
    public function getProducts(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $limit = $this->validatePositiveInt((int) $request->query->get('limit', 20), 'limit', 1, 100);
            $total = $this->service->countActiveProducts();
            $products = $this->service->getProducts($limit);

            return $this->json($products);
        });
    }

    #[Route('/products/{id}', name: 'ecommerce_get_product', methods: ['GET'])]
    public function getProduct(string $id): JsonResponse
    {
        return $this->handle(function() use ($id) {
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) {
                return $userIdOr401;
            }
            if (empty($id)) throw new InvalidArgumentException('Product ID is required');
            $product = $this->service->getProduct($id);
            if (!$product) throw new InvalidArgumentException('Product not found', Response::HTTP_NOT_FOUND);
            if (method_exists($product, 'toArray')) {
                $product = $product->toArray();
            }
            return $this->json($product);
        });
    }

    #[Route('/products/{id}', name: 'ecommerce_update_product', methods: ['PUT'])]
    public function updateProduct(Request $request, string $id): JsonResponse
    {
        return $this->handle(function() use ($id, $request) {
            if ($id === '') throw new InvalidArgumentException('Product ID is required');
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) {
                return $userIdOr401;
            }
            $sellerId = (string) $userIdOr401;

            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
            $data['seller_id'] = $sellerId;
            $this->validateProductUpdateData($data);
            $updated = $this->service->updateProduct($id, $data);
            $this->logAction('product_updated', ['product_id' => $id, 'seller_id' => $sellerId]);

            return $this->json(['success' => true, 'message' => 'Product updated successfully']);
        });
    }

    #[Route('/products/{id}', name: 'ecommerce_delete_product', methods: ['DELETE'])]
    public function deleteProduct(string $id): JsonResponse
    {
        return $this->handle(function() use ($id) {
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) {
                return $userIdOr401;
            }
            $sellerId = (string) $userIdOr401;
            if (empty($id)) throw new InvalidArgumentException('Product ID is required');
            $product = $this->service->getProduct($id);
            if (!$product) throw new InvalidArgumentException('Product not found', Response::HTTP_NOT_FOUND);
            if ($product->getSellerId() !== $sellerId) throw new AccessDeniedException('Not product owner');

            $this->service->deleteProduct($id, $sellerId);
            $this->logAction('product_deleted', ['product_id' => $id, 'seller_id' => $sellerId]);

            return $this->json(['success' => true, 'message' => 'Product deleted'], Response::HTTP_NO_CONTENT);
        });
    }

    #[Route('/orders', name: 'ecommerce_create_order', methods: ['POST'])]
    public function createOrder(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $userIdOr401 = $this->requireAuthOr401(); if ($userIdOr401 instanceof JsonResponse) return $userIdOr401; $userId = (string)$userIdOr401;
            $dataOr400 = $this->decodeJsonOr400($request); if ($dataOr400 instanceof JsonResponse) return $dataOr400; $data = $dataOr400;
            $orderData = $this->buildOrderData($userId, $data, $request);
            $order = $this->service->createOrder($orderData);
            $orderId = method_exists($order,'getId') ? $order->getId() : null;
            $this->logAction('order_created', ['order_id'=>$orderId,'product_id'=>$orderData['product_id'],'quantity'=>$orderData['quantity'],'user_id'=>$userId]);
            $this->incrementMetric('orders_created');
            if (method_exists($order,'toArray')) $order = $order->toArray();
            return $this->json($order, Response::HTTP_CREATED);
        });
    }

    #[Route('/orders/{orderId}', name: 'ecommerce_get_order', methods: ['GET'])]
    public function getOrder(string $orderId): JsonResponse
    {
        return $this->handle(function() use ($orderId) {
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) {
                return $userIdOr401;
            }
            $userId = (string) $userIdOr401;
            $order = $this->service->getOrder($orderId, $userId);
            if (!$order) throw new InvalidArgumentException('Order not found', Response::HTTP_NOT_FOUND);
            $orderData = method_exists($order, 'toArray') ? $order->toArray() : $order;
            return $this->json($orderData);
        });
    }

    #[Route('/orders/user/me', name: 'ecommerce_get_my_orders', methods: ['GET'])]
    public function getMyOrders(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) {
                return $userIdOr401;
            }
            $userId = (string) $userIdOr401;
            $limit = $this->validatePositiveInt((int) $request->query->get('limit', 20), 'limit', 1, 100);
            $orders = $this->service->getUserOrders($userId, $limit);
            return $this->json(array_values($orders));
        });
    }

    #[Route('/orders/{orderId}/confirm', name: 'ecommerce_confirm_order', methods: ['POST'])]
    public function confirmOrder(string $orderId): JsonResponse
    {
        return $this->handle(function() use ($orderId) {
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) {
                return $userIdOr401;
            }
            $userId = (string) $userIdOr401;
            $order = $this->service->confirmOrder($orderId, $userId);
            $this->logAction('order_confirmed', ['order_id' => $orderId, 'user_id' => $userId]);
            $this->incrementMetric('orders_confirmed');
            $orderData = method_exists($order, 'toArray') ? $order->toArray() : $order;
            return $this->json(array_merge($orderData, ['message' => 'Order confirmed']));
        });
    }

    #[Route('/orders/{orderId}/cancel', name: 'ecommerce_cancel_order', methods: ['POST'])]
    public function cancelOrder(string $orderId): JsonResponse
    {
        return $this->handle(function() use ($orderId) {
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) {
                return $userIdOr401;
            }
            $userId = (string) $userIdOr401;
            $order = $this->service->cancelOrder($orderId, $userId);
            $this->logAction('order_cancelled', ['order_id' => $orderId, 'user_id' => $userId]);
            $this->incrementMetric('orders_cancelled');
            $orderData = method_exists($order, 'toArray') ? $order->toArray() : $order;
            return $this->json(array_merge($orderData, ['message' => 'Order cancelled']));
        });
    }

    #[Route('/products/seller/{sellerId}', name: 'ecommerce_get_seller_products', methods: ['GET'])]
    public function getSellerProducts(string $sellerId, Request $request): JsonResponse
    {
        return $this->handle(function() use ($sellerId, $request) {
            $userId = $this->getAuthenticatedUserId();
            if ($userId !== $sellerId) throw new AccessDeniedException('Cannot view other sellers\' products');

            $limit = $this->validatePositiveInt((int) $request->query->get('limit', 20), 'limit', 1, 100);
            $products = $this->service->getSellerProducts($sellerId, $limit);
            return $this->json(array_values($products));
        });
    }

    #[Route('/products/seller/me', name: 'ecommerce_get_my_products', methods: ['GET'])]
    public function getMyProducts(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $sellerId = $this->getAuthenticatedUserId();
            $limit = $this->validatePositiveInt((int) $request->query->get('limit', 20), 'limit', 1, 100);
            $products = $this->service->getSellerProducts($sellerId, $limit);
            return $this->json(['success' => true, 'data' => $products]);
        });
    }

    #[Route('/products/category/{category}', name: 'ecommerce_get_products_by_category', methods: ['GET'])]
    public function getProductsByCategory(string $category, Request $request): JsonResponse
    {
        return $this->handle(function() use ($category, $request) {
            if (empty($category)) throw new InvalidArgumentException('Category required');
            $limit = $this->validatePositiveInt((int) $request->query->get('limit', 20), 'limit', 1, 100);
            $products = $this->service->getProductsByCategory($category, $limit);
            return $this->json(array_values($products));
        });
    }

    #[Route('/products/search', name: 'ecommerce_search_products', methods: ['GET'])]
    public function searchProducts(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $query = $request->query->get('q', '');
            if (empty($query)) {
                return $this->json([]);
            }
            if (strlen($query) < 2) throw new InvalidArgumentException('Query must be ≥2 chars');
            $limit = $this->validatePositiveInt((int) $request->query->get('limit', 20), 'limit', 1, 100);
            $products = $this->service->searchProducts($query, $limit);
            $this->logAction('products_searched', ['query' => $query, 'results' => count($products)]);
            return $this->json(array_values($products));
        });
    }

    #[Route('/products/featured', name: 'ecommerce_get_featured_products', methods: ['GET'])]
    public function getFeaturedProducts(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $limit = $this->validatePositiveInt((int)$request->query->get('limit', 10), 'limit', 1, 100);
            $products = $this->service->getFeaturedProducts($limit);
            // getFeaturedProducts internally uses getProducts, which returns raw data/objects
            return $this->json(['success' => true, 'data' => array_values($products)]);
        });
    }

    #[Route('/products/{id}/stock', name: 'ecommerce_update_stock', methods: ['POST'])]
    public function updateStock(string $id, Request $request): JsonResponse
    {
        return $this->handle(function() use ($id, $request) {
            if (empty($id)) throw new InvalidArgumentException('Product ID is required');
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;
            $sellerId = $this->getAuthenticatedUserId();

            $this->validateStockUpdateData($data);
            $product = $this->service->getProduct($id);
            if (!$product) throw new InvalidArgumentException('Product not found', Response::HTTP_NOT_FOUND);
            if ($product->getSellerId() !== $sellerId) throw new AccessDeniedException('Not product owner');

            $newStock = (int) $data['quantity'];
            if ($newStock < 0) throw new InvalidArgumentException('Stock cannot be negative');

            $updated = $this->service->updateStock($id, $newStock, $sellerId);
            $this->logAction('stock_updated', ['product_id' => $id, 'new_quantity' => $newStock, 'seller_id' => $sellerId]);

            return $this->json(['success' => true, 'data' => $updated]);
        });
    }

    // ========== PRIVATE HELPERS ==========


    private function getAuthenticatedUserId(): string
    {
        $user = $this->getUser();
        if (!$user) throw new InvalidArgumentException('Authentication required');
        return (string) $user->getId();
    }

    private function validateProductCreationData(array $data): void
    {
        $this->ensureNameAndPrice($data);
        $this->validateName($data);
        $this->validatePrice($data);
        $this->validateCurrency($data);
        $this->validateStock($data);
        $this->validateDescription($data);
    }

    private function ensureNameAndPrice(array $data): void
    {
        if (empty($data['name']) || !isset($data['price'])) {
            throw new InvalidArgumentException('Name and price are required');
        }
    }

    private function validateName(array $data): void
    {
        $name = trim($data['name']);
        if ($name === '') {
            throw new InvalidArgumentException('Name and price are required');
        }
        if (strlen($name) > 200) {
            throw new InvalidArgumentException('Name ≤200 chars');
        }
    }

    private function validatePrice(array $data): void
    {
        if (!is_numeric($data['price'])) {
            throw new InvalidArgumentException('Valid price required');
        }
        $price = (float) $data['price'];
        if ($price < self::MIN_PRICE || $price > self::MAX_PRICE) {
            throw new InvalidArgumentException('Price range invalid');
        }
    }

    private function validateCurrency(array $data): void
    {
        if (isset($data['currency']) && !in_array($data['currency'], self::SUPPORTED_CURRENCIES, true)) {
            throw new InvalidArgumentException('Invalid currency');
        }
    }

    private function validateStock(array $data): void
    {
        if (isset($data['stock'])) {
            $stock = (int) $data['stock'];
            if ($stock < 0 || $stock > self::MAX_STOCK) {
                throw new InvalidArgumentException('Stock range invalid');
            }
        }
    }

    private function validateDescription(array $data): void
    {
        if (isset($data['description']) && strlen($data['description']) > 5000) {
            throw new InvalidArgumentException('Description ≤5000 chars');
        }
    }

    private function validateProductUpdateData(array $data): void
    {
        if (isset($data['name'])) {
            $this->validateUpdateName($data);
        }
        if (isset($data['price'])) {
            $this->validateUpdatePrice($data);
        }
        if (isset($data['stock'])) {
            $this->validateUpdateStock($data);
        }
        if (isset($data['description'])) {
            $this->validateUpdateDescription($data);
        }
        if (isset($data['status'])) {
            $this->validateUpdateStatus($data);
        }
    }

    private function validateUpdateName(array $data): void
    {
        if (trim($data['name']) === '' || strlen($data['name']) > 200) {
            throw new InvalidArgumentException('Name must be 1-200 chars');
        }
    }

    private function validateUpdatePrice(array $data): void
    {
        if (!is_numeric($data['price'])) {
            throw new InvalidArgumentException('Price must be number');
        }
        $price = (float) $data['price'];
        if ($price < self::MIN_PRICE || $price > self::MAX_PRICE) {
            throw new InvalidArgumentException('Price range invalid');
        }
    }

    private function validateUpdateStock(array $data): void
    {
        $stock = (int) $data['stock'];
        if ($stock < 0 || $stock > self::MAX_STOCK) {
            throw new InvalidArgumentException('Stock range invalid');
        }
    }

    private function validateUpdateDescription(array $data): void
    {
        if (strlen($data['description']) > 5000) {
            throw new InvalidArgumentException('Description ≤5000 chars');
        }
    }

    private function validateUpdateStatus(array $data): void
    {
        if (!in_array($data['status'], ['active','inactive','draft'], true)) {
            throw new InvalidArgumentException('Invalid status');
        }
    }

    private function validateStockUpdateData(array $data): void
    {
        if (!isset($data['quantity'])) throw new InvalidArgumentException('Quantity required');
        if (!is_numeric($data['quantity'])) throw new InvalidArgumentException('Quantity must be number');
        $qty = (int) $data['quantity'];
        if ($qty < 0) throw new InvalidArgumentException('Quantity cannot be negative');
        if ($qty > self::MAX_STOCK) throw new InvalidArgumentException('Quantity exceeds max');
    }

    private function extractPagination(Request $request): array
    {
        return [
            'limit' => $this->validatePositiveInt((int)$request->query->get('limit', 20), 'limit', 1, 100),
            'offset' => $this->validatePositiveInt((int)$request->query->get('offset', 0), 'offset', 0)
        ];
    }

    private function validatePositiveInt(int $value, string $fieldName, int $min = 1, ?int $max = null): int
    {
        if ($value < $min) throw new InvalidArgumentException("$fieldName ≥$min");
        if ($max !== null && $value > $max) throw new InvalidArgumentException("$fieldName ≤$max");
        return $value;
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            $status = $this->getHttpStatusFromException($e);
            return $this->json(['success' => false, 'error' => $e->getMessage()], $status);
        } catch (AccessDeniedException $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            $this->logAction('controller_error', ['error' => $e->getMessage()]);
            $this->incrementMetric('controller_errors');
            return $this->json(['success' => false, 'error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getHttpStatusFromException(InvalidArgumentException $e): int
    {
        $msg = $e->getMessage();
        if (str_contains(strtolower($msg), 'not found')) return Response::HTTP_NOT_FOUND;
        if (str_contains($msg, 'Not authorized') || str_contains($msg, 'unauthorized') || str_contains($msg, 'Forbidden')) return Response::HTTP_FORBIDDEN;
        return Response::HTTP_BAD_REQUEST;
    }

    private function logAction(string $action, array $context = []): void
    {
        $logEntry = ['timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action' => $action, 'context' => $context];
        error_log(json_encode($logEntry));
    }

    private function buildOrderData(string $userId, array $data, Request $request): array
    {
        $productId = $data['productId'] ?? $data['product_id'] ?? null;
        if (!$productId) throw new InvalidArgumentException('Product ID is required');
        $quantity = (int) ($data['quantity'] ?? 1);
        $shippingAddress = $data['shipping_address'] ?? null;
        $idempotencyKey = $request->headers->get('Idempotency-Key');
        $orderData = ['user_id'=>$userId,'product_id'=>$productId,'quantity'=>$quantity];
        if ($shippingAddress !== null) $orderData['shipping_address'] = $shippingAddress;
        if ($idempotencyKey !== null) $orderData['idempotency_key'] = $idempotencyKey;
        return $orderData;
    }

    private function incrementMetric(string $metricName): void
    {
        error_log(json_encode(['timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type' => 'metric', 'name' => $metricName, 'value' => 1]));
    }
}
