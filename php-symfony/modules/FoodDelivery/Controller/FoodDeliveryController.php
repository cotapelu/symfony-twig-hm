<?php

declare(strict_types=1);

namespace App\FoodDelivery\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use App\FoodDelivery\Service\FoodDeliveryService;
use App\Shared\Controller\BaseController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use InvalidArgumentException;
use DateTimeImmutable;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/food')]
class FoodDeliveryController extends BaseController
{
    private FoodDeliveryService $foodDeliveryService;

    private const MIN_ORDER_AMOUNT = 10000;
    private const MAX_ORDER_AMOUNT = 50000000;
    private const MAX_ITEMS_PER_ORDER = 50;

    public function __construct(FoodDeliveryService $foodDeliveryService)
    {
        parent::__construct();
        $this->foodDeliveryService = $foodDeliveryService;
    }

    private function getAuthenticatedUserId(): string
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('Authentication required');
        }
        return $user->getId();
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

    private function validateOwnership(int $orderId, string $userId): void
    {
        $order = $this->foodDeliveryService->getOrder($orderId);
        if (!$order || $order->getCustomerId() !== $userId) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Access denied to this order');
        }
    }

    private function validateOrderInput(array $data): void
    {
        if (empty($data['restaurantId'])) {
            throw new \InvalidArgumentException('Restaurant ID is required');
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            throw new \InvalidArgumentException('Order items are required');
        }

        if (count($data['items']) > self::MAX_ITEMS_PER_ORDER) {
            throw new \InvalidArgumentException('Maximum ' . self::MAX_ITEMS_PER_ORDER . ' items per order');
        }

        if (empty($data['deliveryAddress'])) {
            throw new \InvalidArgumentException('Delivery address is required');
        }

        $total = $data['total'] ?? 0;
        if ($total < self::MIN_ORDER_AMOUNT) {
            throw new \InvalidArgumentException('Minimum order amount is ' . number_format(self::MIN_ORDER_AMOUNT) . ' VND');
        }

        if ($total > self::MAX_ORDER_AMOUNT) {
            throw new \InvalidArgumentException('Maximum order amount is ' . number_format(self::MAX_ORDER_AMOUNT) . ' VND');
        }
    }

    private function validatePagination(Request $request): array
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        return [$page, $limit];
    }

    #[Route('/restaurants', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function listRestaurants(Request $request): JsonResponse
    {
        try {
            [$page, $limit] = $this->validatePagination($request);
            $cuisine = $request->query->get('cuisine'); $latitude = $request->query->get('latitude'); $longitude = $request->query->get('longitude'); $radius = $request->query->get('radius', 5.0);
            $offset = ($page - 1) * $limit;
            $data = $this->getRestaurantsList($cuisine, $latitude, $longitude, $radius, $limit, $offset);
            $restaurantsData = array_map(fn($r)=>['id'=>$r->getId(),'name'=>$r->getName(),'cuisine'=>$r->getCuisine(),'rating'=>$r->getRating(),'deliveryTime'=>$r->getDeliveryTime(),'minOrder'=>$r->getMinOrder(),'image'=>$r->getImage()], $data['restaurants']);
            return new JsonResponse(['data'=>$restaurantsData, 'pagination'=>['page'=>$page,'limit'=>$limit,'total'=>$data['total'],'totalPages'=>ceil($data['total']/$limit)]]);
        } catch (\Exception $e) {
            return new JsonResponse(['error'=>$e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    
    #[Route('/restaurants/{id}', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getRestaurant(Request $request, int $id): JsonResponse
    {
        try {
            $restaurant = $this->foodDeliveryService->getRestaurant($id);
            if (!$restaurant) {
                return new JsonResponse(['error' => 'Restaurant not found'], Response::HTTP_NOT_FOUND);
            }

            // Build restaurant array with expected fields
            $restaurantData = [
                'id' => $restaurant->getId(),
                'name' => $restaurant->getName(),
                'cuisine' => $restaurant->getCuisine(),
                'rating' => $restaurant->getRating(),
                'deliveryTime' => $restaurant->getDeliveryTime(),
                'minOrder' => $restaurant->getMinOrder(),
                'image' => $restaurant->getImage(),
                'menu' => $restaurant->getMenu(),
            ];
            return new JsonResponse($restaurantData);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    
    #[Route('/orders', methods: ['POST'])]
    public function createOrder(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400 ?? [];
            $data['customerId'] = $userId;
            $this->validateOrderData($data);
            $order = $this->foodDeliveryService->createOrder($data);
            $this->logAction('order_created', ['order_id' => $order->getId(), 'customer_id' => $userId]);
            $this->incrementMetric('orders_created');
            return $this->json($this->buildOrderResponse($order), Response::HTTP_CREATED);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logAction('create_order_failed', ['error' => $e->getMessage()]);
            return $this->json(['success' => false, 'error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function validateOrderData(array $data): void
    {
        $this->requireRestaurantId($data);
        $this->requireItems($data);
        $this->requireDeliveryAddress($data);
        $this->validateOrderAmount($data);
    }

    private function requireRestaurantId(array $data): void
    {
        if (empty($data['restaurantId'])) {
            throw new \InvalidArgumentException('Missing required field: restaurantId');
        }
    }

    private function requireItems(array $data): void
    {
        if (empty($data['items']) || !is_array($data['items'])) {
            throw new \InvalidArgumentException('Order items are required');
        }
        if (count($data['items']) > self::MAX_ITEMS_PER_ORDER) {
            throw new \InvalidArgumentException('Maximum ' . self::MAX_ITEMS_PER_ORDER . ' items per order');
        }
    }

    private function requireDeliveryAddress(array $data): void
    {
        $deliveryAddress = $data['deliveryAddress'] ?? $data['address'] ?? null;
        if (empty($deliveryAddress)) {
            throw new \InvalidArgumentException('Delivery address is required');
        }
    }

    private function validateOrderAmount(array $data): void
    {
        $total = $data['total'] ?? 0;
        if ($total < self::MIN_ORDER_AMOUNT) {
            throw new \InvalidArgumentException('Minimum order amount is ' . number_format(self::MIN_ORDER_AMOUNT) . ' VND');
        }
        if ($total > self::MAX_ORDER_AMOUNT) {
            throw new \InvalidArgumentException('Maximum order amount is ' . number_format(self::MAX_ORDER_AMOUNT) . ' VND');
        }
    }

    private function buildOrderResponse($order): array
    {
        return [
            'id' => $order->getId(),
            'orderNumber' => $order->getOrderNumber(),
            'restaurantId' => is_object($order->getRestaurant()) ? $order->getRestaurant()->getId() : null,
            'items' => array_map(fn($item) => [
                'productId' => $item->getProduct()->getId(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getUnitPrice()
            ], (array) $order->getItems()),
            'total' => $order->getTotal(),
            'status' => $order->getStatus(),
            'createdAt' => $order->getCreatedAt() ? $order->getCreatedAt()->format('Y-m-d H:i:s') : null,
        ];
    }
    
    #[Route('/orders/{id}', methods: ['GET'])]
    public function getOrder(Request $request, int $id): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $order = $this->foodDeliveryService->getOrder($id);
            
            if (!$order) {
                return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
            }
            
            $this->validateOwnership($id, $userId);

            $estimated = $order->getEstimatedDeliveryTime();
            if ($estimated instanceof \DateTimeInterface) {
                $estimated = $estimated->format('H:i');
            }
            return new JsonResponse([
                'id' => $order->getId(),
                'status' => $order->getStatus(),
                'estimatedDelivery' => $estimated,
            ]);
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        } catch (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    #[Route('/orders/{id}/track', methods: ['GET'])]
    public function trackOrder(Request $request, int $id): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $order = $this->foodDeliveryService->getOrder($id);
            if (!$order) {
                return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
            }
            $this->validateOwnership($id, $userId);

            $loc = $this->formatDriverLocation($order->getDriverLocation());
            $estimatedArrival = $this->formatEstimatedArrival($order->getEstimatedDeliveryTime());

            return new JsonResponse([
                'orderId' => $order->getId(),
                'status' => $order->getStatus(),
                'driverLocation' => $loc,
                'estimatedArrival' => $estimatedArrival,
            ]);
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        } catch (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function formatDriverLocation($driverLocation): array
    {
        if (is_array($driverLocation)) {
            return ['lat' => isset($driverLocation['lat']) ? (float)$driverLocation['lat'] : null, 'lng' => isset($driverLocation['lng']) ? (float)$driverLocation['lng'] : null];
        }
        if (is_object($driverLocation) && method_exists($driverLocation, 'getLat')) {
            return ['lat' => (float)$driverLocation->getLat(), 'lng' => (float)$driverLocation->getLng()];
        }
        return ['lat' => null, 'lng' => null];
    }

    private function formatEstimatedArrival($estimated): ?string
    {
        if (is_int($estimated)) {
            return $estimated . ' phút';
        }
        if ($estimated instanceof \DateTimeInterface) {
            return $estimated->format('H:i');
        }
        return null;
    }

    #[Route('/orders/my', methods: ['GET'])]
    public function getMyOrders(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId(); [$page, $limit] = $this->validatePagination($request); $offset = ($page - 1) * $limit;
            $total = $this->foodDeliveryService->getCustomerOrdersCount($userId); $orders = $this->foodDeliveryService->getCustomerOrders($userId, $limit, $offset);
            return $this->json(['data'=>array_map(fn($o)=>['id'=>$o->getId(),'orderNumber'=>$o->getOrderNumber(),'restaurantName'=>$o->getRestaurant()->getName(),'status'=>$o->getStatus(),'total'=>$o->getTotal(),'createdAt'=>$o->getCreatedAt()->format('Y-m-d H:i:s')], $orders), 'pagination'=>['page'=>$page,'limit'=>$limit,'total'=>$total,'totalPages'=>ceil($total/$limit)]]);
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            return $this->json(['error'=>$e->getMessage()], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->json(['error'=>$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getRestaurantsList(?string $cuisine, ?string $latitude, ?string $longitude, float $radius, int $limit, int $offset): array
    {
        if ($latitude && $longitude) {
            $total = $this->foodDeliveryService->countNearbyRestaurants((float)$latitude, (float)$longitude, (float)$radius);
            $restaurants = $this->foodDeliveryService->getNearbyRestaurants((float)$latitude, (float)$longitude, (float)$radius, $limit, $offset);
        } elseif ($cuisine) {
            $total = $this->foodDeliveryService->countRestaurantsByCuisine($cuisine);
            $restaurants = $this->foodDeliveryService->getRestaurantsByCuisine($cuisine, $limit, $offset);
        } else {
            $total = $this->foodDeliveryService->countActiveRestaurants();
            $restaurants = $this->foodDeliveryService->getActiveRestaurants($limit, $offset);
        }
        return ['total'=>$total, 'restaurants'=>$restaurants];
    }
}
