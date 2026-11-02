<?php

declare(strict_types=1);

namespace App\PharmacyDelivery\Controller;

use App\PharmacyDelivery\Service\PharmacyDeliveryService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use InvalidArgumentException;

#[Route('/api/pharmacy')]
class PharmacyDeliveryController extends BaseController
{
    private PharmacyDeliveryService $service;

    public function __construct(PharmacyDeliveryService $service)
    {
        parent::__construct();
        $this->service = $service;
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
        $userId = $this->getUser()?->getUserIdentifier() ?? 'anonymous';
        $timestamp = new \DateTimeImmutable();
        error_log(json_encode([
            'timestamp' => $timestamp->format(\DateTimeImmutable::ATOM),
            'action' => $action,
            'userId' => $userId,
            'module' => 'PharmacyDelivery',
            'context' => $context
        ]));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode([
            'metric' => $metric,
            'tags' => $tags,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeImmutable::ATOM)
        ]));
    }

    private function sanitizeString(?string $value): string
    {
        return $value !== null ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : '';
    }

    #[Route('/orders', name: 'pharmacy_order_create', methods: ['POST'])]
    public function createOrder(Request $request): JsonResponse
    {
        try {
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400 ?? [];
            try { $userId = $this->getAuthenticatedUserId(); } catch (AccessDeniedException $e) { return $this->json(['success'=>false,'error'=>$e->getMessage()], 403); }
            try { $order = $this->service->createOrder($data); } catch (\Exception $e) { return $this->json(['success'=>false,'error'=>$e->getMessage()], 400); }
            $this->logAction('order_created', ['order_id' => $order->getId(), 'order_number' => $order->getOrderNumber()]);
            $this->incrementMetric('pharmacy_orders_created_total');
            return $this->json(['success' => true, 'orderId' => $order->getId(), 'orderNumber' => $order->getOrderNumber()]);
        } catch (\Throwable $e) {
            $this->logAction('create_order_failed', ['error' => $e->getMessage()]);
            return $this->json(['success' => false, 'error' => 'Internal server error'], 500);
        }
    }

    #[Route('/orders/{id}/confirm', name: 'pharmacy_order_confirm', methods: ['POST'])]
    public function confirmOrder(int $id): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $order = $this->service->confirmOrder($id, $userId);
            if (!$order) {
                return $this->json(['success' => false, 'error' => 'Order not found'], 404);
            }
            $this->logAction('order_confirmed', ['order_id' => $id]);
            $this->incrementMetric('pharmacy_orders_confirmed_total');
            return $this->json(['success' => true, 'order' => $id]);
        } catch (AccessDeniedException $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            $this->logAction('confirm_order_failed', ['order_id' => $id, 'error' => $e->getMessage()]);
            return $this->json(['success' => false, 'error' => 'Internal server error'], 500);
        }
    }

    #[Route('/orders/{id}/prepare', name: 'pharmacy_order_prepare', methods: ['POST'])]
    public function prepareOrder(int $id): JsonResponse
    {
        try {
            $order = $this->service->prepareOrder($id);
            if (!$order) {
                return $this->json(['success' => false, 'error' => 'Invalid order'], 400);
            }
            $this->logAction('order_prepared', ['order_id' => $id]);
            $this->incrementMetric('pharmacy_orders_prepared_total');
            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            $this->logAction('prepare_order_failed', ['order_id' => $id, 'error' => $e->getMessage()]);
            return $this->json(['success' => false, 'error' => 'Internal server error'], 500);
        }
    }

    #[Route('/orders/{id}/ready', name: 'pharmacy_order_ready', methods: ['POST'])]
    public function readyOrder(int $id): JsonResponse
    {
        try {
            $order = $this->service->readyOrder($id);
            if (!$order) {
                return $this->json(['success' => false, 'error' => 'Invalid order'], 400);
            }
            $this->logAction('order_ready', ['order_id' => $id]);
            $this->incrementMetric('pharmacy_orders_ready_total');
            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            $this->logAction('ready_order_failed', ['order_id' => $id, 'error' => $e->getMessage()]);
            return $this->json(['success' => false, 'error' => 'Internal server error'], 500);
        }
    }

    #[Route('/orders/{id}/assign', name: 'pharmacy_order_assign', methods: ['POST'])]
    public function assignDeliveryPerson(Request $request, int $id): JsonResponse
    {
        try {
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400 ?? [];
            $deliveryPersonId = $data['deliveryPersonId'] ?? null;
            if (!$deliveryPersonId) throw new InvalidArgumentException('deliveryPersonId is required');
            $order = $this->service->assignDeliveryPerson($id, $deliveryPersonId);
            if (!$order) return $this->json(['success'=>false,'error'=>'Assignment failed'], 400);
            $this->logAction('order_assigned_delivery', ['order_id'=>$id, 'delivery_person_id'=>$deliveryPersonId]);
            $this->incrementMetric('pharmacy_orders_assigned_total');
            return $this->json(['success'=>true]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['success'=>false,'error'=>$e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->logAction('assign_delivery_failed', ['order_id'=>$id, 'error'=>$e->getMessage()]);
            return $this->json(['success'=>false,'error'=>'Internal server error'], 500);
        }
    }

    #[Route('/orders/{id}/pay', name: 'pharmacy_order_pay', methods: ['POST'])]
    public function processPayment(Request $request, int $id): JsonResponse
    {
        try {
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400 ?? [];
            $userId = $this->getAuthenticatedUserId();
            try { $payment = $this->service->processPayment($id, $data, $userId); } catch (\Exception $e) { return $this->json(['success'=>false,'error'=>$e->getMessage()], 400); }
            if (!$payment) return $this->json(['success'=>false,'error'=>'Payment failed'], 400);
            $this->logAction('payment_processed', ['payment_id'=>$payment->getId(), 'order_id'=>$id]);
            $this->incrementMetric('pharmacy_payments_processed_total');
            return $this->json(['success'=>true, 'paymentId'=>$payment->getId()]);
        } catch (AccessDeniedException $e) {
            return $this->json(['success'=>false,'error'=>$e->getMessage()], 403);
        } catch (\Throwable $e) {
            $this->logAction('process_payment_failed', ['order_id'=>$id, 'error'=>$e->getMessage()]);
            return $this->json(['success'=>false,'error'=>'Internal server error'], 500);
        }
    }

    #[Route('/orders/{id}/complete', name: 'pharmacy_order_complete', methods: ['POST'])]
    public function completeDelivery(int $id): JsonResponse
    {
        try {
            $order = $this->service->completeDelivery($id);
            if (!$order) {
                return $this->json(['success' => false, 'error' => 'Invalid order'], 400);
            }
            $this->logAction('order_completed', ['order_id' => $id]);
            $this->incrementMetric('pharmacy_orders_completed_total');
            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            $this->logAction('complete_delivery_failed', ['order_id' => $id, 'error' => $e->getMessage()]);
            return $this->json(['success' => false, 'error' => 'Internal server error'], 500);
        }
    }

    #[Route('/orders/{id}/cancel', name: 'pharmacy_order_cancel', methods: ['POST'])]
    public function cancelOrder(int $id): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $order = $this->service->cancelOrder($id, $userId);
            if (!$order) {
                return $this->json(['success' => false, 'error' => 'Cancel failed'], 400);
            }
            $this->logAction('order_cancelled', ['order_id' => $id]);
            $this->incrementMetric('pharmacy_orders_cancelled_total');
            return $this->json(['success' => true]);
        } catch (AccessDeniedException $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            $this->logAction('cancel_order_failed', ['order_id' => $id, 'error' => $e->getMessage()]);
            return $this->json(['success' => false, 'error' => 'Internal server error'], 500);
        }
    }

    #[Route('/nearby', name: 'pharmacy_nearby', methods: ['GET'])]
    public function getNearbyPharmacies(Request $request): JsonResponse
    {
        try {
            $lat = $request->query->get('lat');
            $lng = $request->query->get('lng');
            $radius = $request->query->get('radius', 5);
            if ($lat === null || $lng === null) throw new InvalidArgumentException('Lat and lng are required');
            $latF = (float)$lat; $lngF = (float)$lng;
            if ($latF < -90 || $latF > 90 || $lngF < -180 || $lngF > 180) throw new InvalidArgumentException('Invalid latitude or longitude');
            $pharmacies = $this->service->getNearbyPharmacies($latF, $lngF, (float)$radius);
            $this->logAction('nearby_pharmacies_retrieved', ['lat'=>$latF, 'lng'=>$lngF, 'count'=>count($pharmacies)]);
            $this->incrementMetric('pharmacy_nearby_queries_total');
            $pharmaciesData = array_map(fn($p)=>['id'=>$p->getId(),'name'=>$p->getName(),'address'=>$p->getAddress(),'delivery_fee'=>$p->getDeliveryFee(),'estimated_delivery_time'=>$p->getEstimatedDeliveryTime(),'rating'=>$p->getRating(),'isOpen'=>$p->isOpenNow()], $pharmacies);
            return $this->json(['success'=>true, 'pharmacies'=>$pharmaciesData]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['success'=>false,'error'=>$e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->logAction('get_nearby_pharmacies_failed', ['error'=>$e->getMessage()]);
            return $this->json(['success'=>false,'error'=>'Internal server error'], 500);
        }
    }

    #[Route('/{pharmacyId}/medicines', name: 'pharmacy_medicines', methods: ['GET'])]
    public function getPharmacyMedicines(int $pharmacyId): JsonResponse
    {
        try {
            $medicines = $this->service->getPharmacyMedicines($pharmacyId);
            $this->logAction('pharmacy_medicines_retrieved', ['pharmacy_id' => $pharmacyId, 'count' => count($medicines)]);
            $medicinesData = array_map(function($m) {
                return [
                    'id' => $m->getId(),
                    'name' => $m->getName(),
                    'genericName' => $m->getGenericName(),
                    'price' => $m->getPrice(),
                    'category' => $m->getCategory(),
                    'requiresPrescription' => $m->requiresPrescription(),
                    'inStock' => $m->getQuantityInStock() > 0,
                ];
            }, $medicines);
            return $this->json(['success' => true, 'medicines' => $medicinesData]);
        } catch (\Throwable $e) {
            $this->logAction('get_pharmacy_medicines_failed', ['pharmacy_id' => $pharmacyId, 'error' => $e->getMessage()]);
            return $this->json(['success' => false, 'error' => 'Internal server error'], 500);
        }
    }

    #[Route('/medicines/search', name: 'pharmacy_medicines_search', methods: ['GET'])]
    public function searchMedicines(Request $request): JsonResponse
    {
        try {
            $query = $request->query->get('q', '');
            $pharmacyId = $request->query->get('pharmacyId');
            if (trim($query) === '') throw new InvalidArgumentException('Query parameter q is required');
            $medicines = $this->service->searchMedicines($query, $pharmacyId ? (int)$pharmacyId : null);
            $this->logAction('medicines_searched', ['query'=>$query, 'pharmacy_id'=>$pharmacyId, 'count'=>count($medicines)]);
            $this->incrementMetric('pharmacy_medicines_searched_total');
            $medicinesData = array_map(fn($m)=>['id'=>$m->getId(),'name'=>$m->getName(),'genericName'=>$m->getGenericName(),'price'=>$m->getPrice(),'category'=>$m->getCategory(),'requiresPrescription'=>$m->requiresPrescription(),'inStock'=>$m->getQuantityInStock()>0], $medicines);
            return $this->json(['success'=>true, 'medicines'=>$medicinesData]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['success'=>false,'error'=>$e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->logAction('search_medicines_failed', ['query'=>$query, 'error'=>$e->getMessage()]);
            return $this->json(['success'=>false,'error'=>'Internal server error'], 500);
        }
    }

    #[Route('/orders/customer/{customerId}', name: 'pharmacy_customer_orders', methods: ['GET'])]
    public function getCustomerOrders(string $customerId): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            if ($customerId !== $userId) throw new AccessDeniedException('Cannot view orders of another user');
            $orders = $this->service->getCustomerOrders($userId);
            $this->logAction('customer_orders_retrieved', ['user_id'=>$userId, 'count'=>count($orders)]);
            $this->incrementMetric('pharmacy_customer_orders_total');
            $ordersData = array_map(fn($o)=>['orderNumber'=>$o->getOrderNumber(),'status'=>$o->getStatus(),'total'=>$o->getTotal(),'createdAt'=>$o->getCreatedAt()? $o->getCreatedAt()->format('c'):null], $orders);
            return $this->json(['success'=>true, 'orders'=>$ordersData]);
        } catch (AccessDeniedException $e) {
            return $this->json(['success'=>false,'error'=>$e->getMessage()], 403);
        } catch (\Throwable $e) {
            $this->logAction('get_customer_orders_failed', ['error'=>$e->getMessage()]);
            return $this->json(['success'=>false,'error'=>'Internal server error'], 500);
        }
    }
}
