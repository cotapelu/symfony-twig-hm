<?php

declare(strict_types=1);

namespace App\GroceryDelivery\Controller;

use App\GroceryDelivery\Service\GroceryDeliveryService;
use App\GroceryDelivery\Service\DeliveryService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/grocery-delivery')]
class GroceryDeliveryController extends BaseController
{
    private GroceryDeliveryService $gdService;
    private DeliveryService $dService;

    public function __construct(GroceryDeliveryService $gds, DeliveryService $ds)
    {
        parent::__construct();
        $this->gdService = $gds;
        $this->dService = $ds;
    }

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['error' => 'Authentication required'], 401);
        return (string) $user->getId();
    }

    private function validateLatitude(float $lat): void
    {
        if ($lat < -90 || $lat > 90) throw new InvalidArgumentException('Latitude must be between -90 and 90');
    }

    private function validateLongitude(float $lng): void
    {
        if ($lng < -180 || $lng > 180) throw new InvalidArgumentException('Longitude must be between -180 and 180');
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("GroceryDeliveryController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['error' => 'Internal server error'], 500);
    }

    private function logAction(string $action, array $ctx=[]): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'context'=>$ctx]));
    }

    private function incrementMetric(string $m): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$m, 'value'=>1]));
    }

    // ==================== Endpoints ====================

    #[Route('/stores', methods: ['GET'])]
    public function getStores(Request $r): JsonResponse
    {
        return $this->handle(fn() => $this->getStoresAction($r));
    }

    private function getStoresAction(Request $r): JsonResponse
    {
        $lat = $r->query->get('lat'); $lng = $r->query->get('lng');
        if ($lat === null || $lng === null) return $this->json(['success'=>true, 'stores'=>[]]);
        $lat = (float)$lat; $lng = (float)$lng;
        $this->validateLatitude($lat); $this->validateLongitude($lng);
        $stores = $this->gdService->getNearbyStores($lat, $lng);
        $this->logAction('stores_fetched', ['lat'=>$lat, 'lng'=>$lng, 'count'=>count($stores)]);
        return $this->json(['success'=>true, 'stores'=>array_map(fn($s)=>['id'=>$s->getId(),'name'=>$s->getName(),'address'=>$s->getAddress(),'rating'=>$s->getRating(),'deliveryFee'=>$s->getDeliveryFee(),'deliveryTime'=>$s->getDeliveryTime()], $stores)]);
    }

    #[Route('/orders', methods: ['POST'])]
    public function createOrder(Request $r): JsonResponse
    {
        return $this->handle(fn() => $this->createOrderAction($r));
    }

    private function createOrderAction(Request $r): JsonResponse
    {
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = (string) $userIdOr401;

        $dataOr400 = $this->decodeJsonOr400($r);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400 ?: [];

        try {
            $order = $this->gdService->createOrder($data);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $this->logAction('order_created', ['order_id'=>$order->getId(), 'customer_id'=>$userId]); $this->incrementMetric('orders_created');
        return $this->json(['success'=>true, 'order'=>['id'=>$order->getId(),'orderNumber'=>$order->getOrderNumber(),'total'=>$order->getTotal()]], 200);
    }

    #[Route('/orders/{id}/confirm', methods: ['POST'])]
    public function confirm(int $id, Request $r): JsonResponse
    {
        return $this->handle(fn() => $this->confirmAction($id, $r));
    }

    private function confirmAction(int $id, Request $r): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Order ID must be positive');
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = (string) $userIdOr401;
        $order = $this->gdService->getOrder($id);
        if (!$order) return $this->json(['error' => 'Order not found'], 404);
        if ($order->getCustomerId() !== $userId) return $this->json(['error' => 'Access denied'], 403);
        $o = $this->gdService->confirmOrder($id);
        if (!$o) return $this->json(['error' => 'Failed'], 400);
        $this->logAction('order_confirmed', ['order_id'=>$id, 'customer_id'=>$userId]); $this->incrementMetric('orders_confirmed');
        return $this->json(['success'=>true]);
    }

    #[Route('/orders/{id}/ready', methods: ['POST'])]
    public function ready(int $id, Request $r): JsonResponse
    {
        return $this->handle(fn() => $this->readyAction($id, $r));
    }

    private function readyAction(int $id, Request $r): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Order ID must be positive');
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = (string) $userIdOr401;
        $order = $this->gdService->getOrder($id);
        if (!$order) return $this->json(['error' => 'Order not found'], 404);
        if ($order->getCustomerId() !== $userId) return $this->json(['error' => 'Access denied'], 403);
        $o = $this->gdService->readyOrder($id);
        if (!$o) return $this->json(['error' => 'Failed'], 400);
        $this->logAction('order_ready', ['order_id'=>$id, 'customer_id'=>$userId]); $this->incrementMetric('orders_ready');
        return $this->json(['success'=>true]);
    }

    #[Route('/orders/{id}/complete', methods: ['POST'])]
    public function completeDelivery(int $id, Request $r): JsonResponse
    {
        return $this->handle(fn() => $this->completeDeliveryAction($id, $r));
    }

    private function completeDeliveryAction(int $id, Request $r): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Order ID must be positive');
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = (string) $userIdOr401;
        $order = $this->gdService->getOrder($id);
        if (!$order) return $this->json(['error' => 'Order not found'], 404);
        if ($order->getCustomerId() !== $userId) return $this->json(['error' => 'Access denied'], 403);
        $o = $this->gdService->completeDelivery($id);
        if (!$o) return $this->json(['error' => 'Failed'], 400);
        $this->logAction('order_completed', ['order_id'=>$id, 'customer_id'=>$userId]); $this->incrementMetric('orders_completed');
        return $this->json(['success'=>true]);
    }

    #[Route('/orders/{id}/pay', methods: ['POST'])]
    public function pay(Request $r, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->payAction($r, $id));
    }

    private function payAction(Request $r, int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Order ID must be positive');
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = (string) $userIdOr401;
        $order = $this->gdService->getOrder($id);
        if (!$order) return $this->json(['error' => 'Order not found'], 404);
        if ($order->getCustomerId() !== $userId) return $this->json(['error' => 'Access denied'], 403);
        $dataOr400 = $this->decodeJsonOr400($r);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        if (empty($data)) return $this->json(['error' => 'Invalid JSON'], 400);
        $p = $this->gdService->processPayment($id, $data);
        if (!$p) return $this->json(['error' => 'Failed'], 400);
        $this->logAction('payment_processed', ['order_id'=>$id, 'customer_id'=>$userId, 'method'=>$data['method']]); $this->incrementMetric('payments_processed');
        return $this->json(['success'=>true, 'payment'=>['transactionId'=>$p->getTransactionId(),'amount'=>$p->getAmount()]]);
    }

    #[Route('/customer/orders', methods: ['GET'])]
    public function customerOrders(Request $r): JsonResponse
    {
        return $this->handle(fn() => $this->customerOrdersAction($r));
    }

    private function customerOrdersAction(Request $r): JsonResponse
    {
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = (string) $userIdOr401;
        $orders = $this->gdService->getCustomerOrders($userId);
        $this->logAction('customer_orders_fetched', ['customer_id'=>$userId, 'count'=>count($orders)]);
        return $this->json(['success'=>true, 'orders'=>array_map(fn($o)=>['id'=>$o->getId(),'orderNumber'=>$o->getOrderNumber(),'status'=>$o->getStatus(),'total'=>$o->getTotal()], $orders)]);
    }

    #[Route('/delivery/online', methods: ['POST'])]
    public function deliveryOnline(Request $r): JsonResponse
    {
        return $this->handle(fn() => $this->deliveryOnlineAction($r));
    }

    private function deliveryOnlineAction(Request $r): JsonResponse
    {
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = (string) $userIdOr401; // not used but maybe for logging later

        $dataOr400 = $this->decodeJsonOr400($r);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $d = $dataOr400;
        if (empty($d)) return $this->json(['error' => 'Invalid JSON'], 400);
        $deliveryPersonId = $d['deliveryPersonId'] ?? null;
        if ($deliveryPersonId === null) throw new InvalidArgumentException('deliveryPersonId is required');
        $lat = isset($d['latitude']) ? (float)$d['latitude'] : 0;
        $lng = isset($d['longitude']) ? (float)$d['longitude'] : 0;
        if ($lat !== 0) $this->validateLatitude($lat);
        if ($lng !== 0) $this->validateLongitude($lng);

        $dp = $this->dService->setOnline($deliveryPersonId, $lat, $lng);
        if (!$dp) return $this->json(['error' => 'Failed to set delivery person online'], 400);

        $this->logAction('delivery_online', ['delivery_person_id'=>$deliveryPersonId]); $this->incrementMetric('delivery_online');
        return $this->json(['success'=>true]);
    }

    #[Route('/delivery/offline', methods: ['POST'])]
    public function deliveryOffline(Request $r): JsonResponse
    {
        return $this->handle(fn() => $this->deliveryOfflineAction($r));
    }

    private function deliveryOfflineAction(Request $r): JsonResponse
    {
        $dataOr400 = $this->decodeJsonOr400($r);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        if (empty($data)) return $this->json(['error' => 'Invalid JSON'], 400);
        $deliveryPersonId = $data['deliveryPersonId'] ?? null;
        if ($deliveryPersonId === null) throw new InvalidArgumentException('deliveryPersonId is required');

        $dp = $this->dService->setOffline($deliveryPersonId);
        if (!$dp) return $this->json(['error' => 'Failed to set delivery person offline'], 400);

        $this->logAction('delivery_offline', ['delivery_person_id'=>$deliveryPersonId]); $this->incrementMetric('delivery_offline');
        return $this->json(['success'=>true]);
    }

    #[Route('/delivery/nearby', methods: ['GET'])]
    public function nearbyDelivery(Request $r): JsonResponse
    {
        return $this->handle(fn() => $this->nearbyDeliveryAction($r));
    }

    private function nearbyDeliveryAction(Request $r): JsonResponse
    {
        $lat = $r->query->get('lat'); $lng = $r->query->get('lng');
        if ($lat === null || $lng === null) throw new InvalidArgumentException('Latitude and longitude are required');
        $lat = (float)$lat; $lng = (float)$lng;
        $this->validateLatitude($lat); $this->validateLongitude($lng);
        $dps = $this->dService->findNearby($lat, $lng);
        $this->logAction('nearby_delivery_fetched', ['lat'=>$lat, 'lng'=>$lng, 'count'=>count($dps)]);
        return $this->json(['success'=>true, 'deliveryPersons'=>array_map(fn($d)=>['id'=>$d->getId(),'name'=>$d->getName(),'rating'=>$d->getRating()], $dps)]);
    }

    #[Route('/orders/{id}/assign', methods: ['POST'])]
    public function assignOrder(Request $r, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->assignOrderAction($r, $id));
    }

    private function assignOrderAction(Request $r, int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Order ID must be positive');
        $dataOr400 = $this->decodeJsonOr400($r);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        if (empty($data)) return $this->json(['error' => 'Invalid JSON'], 400);
        $deliveryPersonId = $data['deliveryPersonId'] ?? null;
        if ($deliveryPersonId === null) throw new InvalidArgumentException('deliveryPersonId is required');
        $o = $this->dService->assignOrder($id, $deliveryPersonId);
        if (!$o) return $this->json(['error' => 'Failed'], 400);
        $this->logAction('order_assigned', ['order_id'=>$id, 'delivery_person_id'=>$deliveryPersonId]); $this->incrementMetric('orders_assigned');
        return $this->json(['success'=>true]);
    }
}
