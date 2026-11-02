<?php

declare(strict_types=1);

namespace App\PharmacyDelivery\Service;

use App\PharmacyDelivery\Entity\{Order, OrderItem, Payment, Pharmacy, Medicine, DeliveryPerson};
use App\PharmacyDelivery\Repository\{OrderRepository, PharmacyRepository, MedicineRepository, DeliveryPersonRepository, PaymentRepository};
use Doctrine\DBAL\LockMode;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PharmacyDeliveryService
{
    private OrderRepository $orderRepo;
    private PharmacyRepository $pharmacyRepo;
    private MedicineRepository $medicineRepo;
    private DeliveryPersonRepository $dpRepo;
    private PaymentRepository $paymentRepo;
    private const PLATFORM_FEE = 0.12;

    public function __construct(OrderRepository $or, PharmacyRepository $pr, MedicineRepository $mr, DeliveryPersonRepository $dpr, PaymentRepository $par)
    {
        $this->orderRepo = $or;
        $this->pharmacyRepo = $pr;
        $this->medicineRepo = $mr;
        $this->dpRepo = $dpr;
        $this->paymentRepo = $par;
    }

    private function roundCents(float $value)
    {
        return (int)round($value * 100);
    }

    private function formatCents(int $cents)
    {
        return round($cents / 100, 2);
    }

    public function createOrder(array $data)
    {
        $this->validateCreateOrderBasicFields($data);
        [$lat, $lng] = $this->validateLatLng($data);
        $pharmacy = $this->validatePharmacy($data['pharmacyId']);
        $items = $this->validateAndBuildItems($data);
        $subtotalCents = $this->calculateItemsSubtotal($items);
        $fees = $this->calculateOrderFees($pharmacy, $subtotalCents, $data['discount'] ?? null);
        $prescriptionRequired = $this->determinePrescriptionRequired($items);
        $this->validatePrescription($prescriptionRequired, $data);
        $em = $this->orderRepo->getEntityManager();
        $em->beginTransaction();
        try {
            $order = $this->buildOrderEntity($data, $pharmacy, $lat, $lng, $items, $fees, $prescriptionRequired);
            $this->reserveStock($items);
            $this->orderRepo->save($order);
            $em->commit();
            return $order;
        } catch (\Throwable $e) {
            $em->rollback();
            throw $e;
        }
    }

    private function validateCreateOrderBasicFields(array $data): void
    {
        if (empty($data['customerId'])) {
            throw new InvalidArgumentException('Customer ID is required');
        }
        if (empty($data['pharmacyId'])) {
            throw new InvalidArgumentException('Pharmacy ID is required');
        }
        if (empty($data['items']) || !is_array($data['items'])) {
            throw new InvalidArgumentException('Items must be a non-empty array');
        }
        if (empty(trim($data['deliveryAddress'] ?? ''))) {
            throw new InvalidArgumentException('Delivery address is required');
        }
    }

    private function validateLatLng(array $data): array
    {
        if (!isset($data['lat'], $data['lng'])) {
            throw new InvalidArgumentException('Latitude and longitude are required');
        }
        $lat = (float)$data['lat'];
        $lng = (float)$data['lng'];
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw new InvalidArgumentException('Invalid latitude or longitude');
        }
        return [$lat, $lng];
    }

    private function validatePharmacy(string $pharmacyId): Pharmacy
    {
        $pharmacy = $this->pharmacyRepo->find($pharmacyId);
        if (!$pharmacy || !$pharmacy->isActive()) {
            throw new InvalidArgumentException('Pharmacy invalid or inactive');
        }
        return $pharmacy;
    }

    private function validateAndBuildItems(array $data): array
    {
        $items = [];
        foreach ($data['items'] as $it) {
            $items[] = $this->processItem($it);
        }
        return $items;
    }

    private function processItem(array $it): OrderItem
    {
        $this->validateItem($it);
        $m = $this->medicineRepo->find($it['medicineId']);
        $unitPriceCents = $this->roundCents($m->getPrice());
        $quantity = (int)$it['quantity'];
        $totalPriceCents = $unitPriceCents * $quantity;
        $i = new OrderItem();
        $i->setMedicine($m)
          ->setQuantity($quantity)
          ->setUnitPrice($this->formatCents($unitPriceCents))
          ->setTotalPrice($this->formatCents($totalPriceCents));
        return $i;
    }

    private function validateItem(array $it): void
    {
        if (!isset($it['medicineId'], $it['quantity']) || $it['quantity'] <= 0) {
            throw new InvalidArgumentException('Each item must have medicineId and positive quantity');
        }
        $m = $this->medicineRepo->find($it['medicineId']);
        if (!$m) {
            throw new InvalidArgumentException('Medicine not found: ' . $it['medicineId']);
        }
        if (!$m->isAvailable()) {
            throw new InvalidArgumentException('Medicine unavailable: ' . $m->getName());
        }
        if ($m->getQuantityInStock() < $it['quantity']) {
            throw new InvalidArgumentException('Insufficient stock for medicine: ' . $m->getName());
        }
    }

    private function calculateItemsSubtotal(array $items): int
    {
        $subtotalCents = 0;
        foreach ($items as $i) {
            $subtotalCents += $this->roundCents($i->getTotalPrice());
        }
        return $subtotalCents;
    }

    private function calculateOrderFees(Pharmacy $pharmacy, int $subtotalCents, ?float $discount = null): array
    {
        $subtotal = $this->formatCents($subtotalCents);
        $deliveryFee = $pharmacy->getDeliveryFee();
        $deliveryFeeCents = $this->roundCents($deliveryFee);
        $serviceFeeCents = (int)round($subtotalCents * 0.02);
        $taxCents = (int)round($subtotalCents * 0.05);
        $discountCents = isset($discount) ? $this->roundCents($discount) : 0;
        if ($discountCents < 0) {
            throw new InvalidArgumentException('Discount cannot be negative');
        }
        if ($discountCents > $subtotalCents) {
            throw new InvalidArgumentException('Discount cannot exceed subtotal');
        }
        $totalCents = $subtotalCents + $deliveryFeeCents + $serviceFeeCents + $taxCents - $discountCents;
        $total = $this->formatCents($totalCents);
        return [
            'subtotal' => $subtotal,
            'deliveryFee' => $this->formatCents($deliveryFeeCents),
            'serviceFee' => $this->formatCents($serviceFeeCents),
            'tax' => $this->formatCents($taxCents),
            'discount' => $this->formatCents($discountCents),
            'total' => $total,
        ];
    }

    private function determinePrescriptionRequired(array $items): bool
    {
        foreach ($items as $i) {
            if ($i->getMedicine()->requiresPrescription()) {
                return true;
            }
        }
        return false;
    }

    private function validatePrescription(bool $prescriptionRequired, array $data): void
    {
        if ($prescriptionRequired) {
            if (empty($data['prescriptionImageUrl'])) {
                throw new InvalidArgumentException('Prescription image is required for some medicines');
            }
            if (!filter_var($data['prescriptionImageUrl'], FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Prescription image URL is invalid');
            }
        }
    }

    private function buildOrderEntity(array $data, Pharmacy $pharmacy, float $lat, float $lng, array $items, array $fees, bool $prescriptionRequired): Order
    {
        $order = new Order();
        $order->setPharmacy($pharmacy)
              ->setCustomerId($data['customerId'])
              ->setSubtotal($fees['subtotal'])
              ->setDeliveryFee($fees['deliveryFee'])
              ->setServiceFee($fees['serviceFee'])
              ->setTax($fees['tax'])
              ->setDiscount($fees['discount'])
              ->setTotal($fees['total'])
              ->setDeliveryAddress($this->sanitizeString($data['deliveryAddress']))
              ->setDeliveryLatitude($lat)
              ->setDeliveryLongitude($lng)
              ->setStatus(Order::STATUS_PENDING)
              ->setEstimatedDeliveryTime($pharmacy->getEstimatedDeliveryTime() + 15);

        if ($prescriptionRequired) {
            $this->configurePrescription($order, $data);
        }

        $this->attachItemsToOrder($order, $items);

        return $order;
    }

    private function configurePrescription(Order $order, array $data): void
    {
        $order->setPrescriptionImageUrl($data['prescriptionImageUrl'])
              ->setPatientName($data['patientName'] ?? null)
              ->setPatientAge(isset($data['patientAge']) ? (int)$data['patientAge'] : null)
              ->setStatus(Order::STATUS_WAITING_PRESCRIPTION);
    }

    private function attachItemsToOrder(Order $order, array $items): void
    {
        foreach ($items as $i) {
            $i->setOrder($order);
            $order->addItem($i);
        }
    }

    private function reserveStock(array $items): void
    {
        foreach ($items as $i) {
            $medicine = $i->getMedicine();
            $newStock = $medicine->getQuantityInStock() - $i->getQuantity();
            $medicine->setQuantityInStock($newStock);
            $this->medicineRepo->save($medicine);
        }
    }

    private function sanitizeString(?string $value)
    {
        return $value !== null ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : '';
    }

    private function ensureOrderOwnership(Order $order, string $userId)
    {
        if ($order->getCustomerId() !== $userId) {
            throw new AccessDeniedException('Not authorized to access this order');
        }
    }

    public function confirmOrder(int $id, string $userId)
    {
        $o = $this->orderRepo->find($id);
        if (!$o) return null;
        $this->ensureOrderOwnership($o, $userId);
        if ($o->getStatus() === Order::STATUS_WAITING_PRESCRIPTION || $o->getStatus() === Order::STATUS_PENDING) {
            $o->setStatus(Order::STATUS_CONFIRMED)->setConfirmedAt(new \DateTime());
            $this->orderRepo->save($o);
            return $o;
        }
        return null;
    }

    public function prepareOrder(int $id)
    {
        $o = $this->orderRepo->find($id);
        if (!$o || $o->getStatus() !== Order::STATUS_CONFIRMED) return null;
        $o->setStatus(Order::STATUS_PREPARING)->setReadyAt(new \DateTime());
        $this->orderRepo->save($o);
        return $o;
    }

    public function readyOrder(int $id)
    {
        $o = $this->orderRepo->find($id);
        if (!$o || $o->getStatus() !== Order::STATUS_PREPARING) return null;
        $o->setStatus(Order::STATUS_READY)->setReadyAt(new \DateTime());
        $this->orderRepo->save($o);
        return $o;
    }

    public function assignDeliveryPerson($orderId, $deliveryPersonId)
    {
        $em = $this->orderRepo->getEntityManager();
        $em->beginTransaction();
        try {
            $o = $this->orderRepo->find($orderId);
            $dp = $this->dpRepo->find($deliveryPersonId);
            if (!$o || !$dp) {
                $em->rollback();
                return null;
            }
            $em->lock($dp, LockMode::PESSIMISTIC_WRITE);
            $em->lock($o, LockMode::PESSIMISTIC_WRITE);
            if (!$this->validateAssignmentAfterLock($o, $dp)) {
                $em->rollback();
                return null;
            }
            $this->applyAssignment($o, $dp);
            $em->commit();
            return $o;
        } catch (\Throwable $e) {
            $em->rollback();
            throw $e;
        }
    }

    private function validateAssignmentAfterLock($o, $dp): bool
    {
        return $dp->isAvailable() && $o->getStatus() === Order::STATUS_READY;
    }

    private function applyAssignment($o, $dp): void
    {
        $o->setDeliveryPerson($dp)->setStatus(Order::STATUS_PICKED_UP)->setPickedUpAt(new \DateTime());
        $dp->setStatus('busy')->setTotalDeliveries($dp->getTotalDeliveries() + 1);
        $this->dpRepo->save($dp);
        $this->orderRepo->save($o);
    }

    public function completeDelivery(int $id)
    {
        $em = $this->orderRepo->getEntityManager();
        $em->beginTransaction();
        try {
            $o = $this->orderRepo->find($id);
            if (!$o || $o->getStatus() !== Order::STATUS_PICKED_UP) {
                $em->rollback();
                return null;
            }
            $o->setStatus(Order::STATUS_DELIVERED)->setDeliveredAt(new \DateTime());
            if ($o->getDeliveryPerson()) {
                $dp = $o->getDeliveryPerson();
                $dp->setStatus('available');
                $dp->setTotalDeliveries($dp->getTotalDeliveries() + 1);
                $this->dpRepo->save($dp);
            }
            $this->orderRepo->save($o);
            $em->commit();
            return $o;
        } catch (\Throwable $e) {
            $em->rollback();
            throw $e;
        }
    }

    public function cancelOrder(int $id, string $userId)
    {
        $o = $this->orderRepo->find($id);
        if (!$o) return null;
        $this->ensureOrderOwnership($o, $userId);
        if (!$o->isActive()) {
            return null;
        }
        $o->setStatus(Order::STATUS_CANCELLED);
        // Optionally restock medicines
        foreach ($o->getItems() as $item) {
            $medicine = $item->getMedicine();
            $medicine->setQuantityInStock($medicine->getQuantityInStock() + $item->getQuantity());
            $this->medicineRepo->save($medicine);
        }
        $this->orderRepo->save($o);
        return $o;
    }

    public function processPayment(int $orderId, array $data, string $userId)
    {
        $o = $this->orderRepo->find($orderId);
        if (!$o) return null;
        $this->ensureOrderOwnership($o, $userId);
        if ($o->getPayment() && $o->getPayment()->getStatus() === Payment::STATUS_COMPLETED) {
            return $o->getPayment();
        }
        $em = $this->orderRepo->getEntityManager();
        $em->beginTransaction();
        try {
            $p = $this->createAndFinalizePayment($o, $data);
            $em->commit();
            return $p;
        } catch (\Throwable $e) {
            $em->rollback();
            throw $e;
        }
    }

    private function createAndFinalizePayment($o, array $data): Payment
    {
        $p = new Payment();
        $p->setOrder($o)
          ->setAmount($o->getTotal())
          ->setPaymentMethod($data['method'] ?? Payment::METHOD_CASH)
          ->setPlatformFee($this->formatCents($this->roundCents($o->getSubtotal() * self::PLATFORM_FEE)))
          ->setPharmacyEarning($this->formatCents($this->roundCents($o->getSubtotal() * (1 - self::PLATFORM_FEE))))
          ->setDriverEarning($this->formatCents($this->roundCents($o->getDeliveryFee() * 0.85)));
        $this->paymentRepo->save($p);
        $p->setStatus(Payment::STATUS_COMPLETED)->setPaidAt(new \DateTime());
        $this->paymentRepo->save($p);
        $o->setPayment($p);
        $this->orderRepo->save($o);
        return $p;
    }

    public function getNearbyPharmacies(float $lat, float $lng, float $r = 5)
    {
        return $this->pharmacyRepo->findNearbyPharmacies($lat, $lng, $r);
    }

    public function getPharmacyMedicines(int $pharmacyId)
    {
        return $this->medicineRepo->findByPharmacy($pharmacyId);
    }

    public function searchMedicines(string $query, ?int $pharmacyId = null)
    {
        return $this->medicineRepo->searchMedicines($query, $pharmacyId);
    }

    public function getCustomerOrders(string $uid)
    {
        return $this->orderRepo->findByCustomer($uid);
    }
}
