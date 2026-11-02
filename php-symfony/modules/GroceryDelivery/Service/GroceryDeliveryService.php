<?php

declare(strict_types=1);

namespace App\GroceryDelivery\Service;

use App\GroceryDelivery\Entity\{Order,OrderItem,Payment,Store,Product};
use App\GroceryDelivery\Repository\{OrderRepository,StoreRepository,ProductRepository,PaymentRepository};

class GroceryDeliveryService
{
    private OrderRepository $orderRepo;
    private StoreRepository $storeRepo;
    private ProductRepository $productRepo;
    private PaymentRepository $paymentRepo;
    private const PLATFORM_FEE = 0.15;

    public function __construct(OrderRepository $or, StoreRepository $sr, ProductRepository $pr, PaymentRepository $par)
    {
        $this->orderRepo = $or;
        $this->storeRepo = $sr;
        $this->productRepo = $pr;
        $this->paymentRepo = $par;
    }

    public function createOrder(array $data)
    {
        $store = $this->storeRepo->find($data['storeId']);
        if (!$store || !$store->isActive()) throw new \Exception('Store invalid');

        $items = []; $subtotal = 0;
        foreach ($data['items'] as $it) {
            $p = $this->productRepo->find($it['productId']);
            if (!$p) throw new \Exception('Product not found');
            $i = new OrderItem();
            $i->setProduct($p)->setQuantity($it['quantity'])->setUnitPrice($p->getPrice())->setTotalPrice($p->getPrice() * $it['quantity']);
            $items[] = $i;
            $subtotal += $i->getTotalPrice();
        }

        $order = new Order();
        $order->setStore($store)->setCustomerId($data['customerId'])
            ->setSubtotal($subtotal)->setDeliveryFee($store->getDeliveryFee())
            ->setServiceFee($subtotal * 0.02)->setTax($subtotal * 0.08)->setDiscount($data['discount'] ?? 0)
            ->setTotal($subtotal + $store->getDeliveryFee() + ($subtotal * 0.1) - ($data['discount'] ?? 0))
            ->setDeliveryAddress($data['deliveryAddress'])->setDeliveryLatitude((float)$data['lat'])
            ->setDeliveryLongitude((float)$data['lng'])->setStatus(Order::STATUS_PENDING);

        foreach ($items as $i) { $i->setOrder($order); $order->getItems()->add($i); }
        $this->orderRepo->save($order);
        return $order;
    }

    public function confirmOrder(int $id)
    {
        $o = $this->orderRepo->find($id);
        if (!$o || $o->getStatus() !== Order::STATUS_PENDING) return null;
        $o->setStatus(Order::STATUS_CONFIRMED)->setConfirmedAt(new \DateTime());
        $o->setEstimatedDeliveryTime($o->getStore()->getDeliveryTime() + 15);
        $this->orderRepo->save($o);
        return $o;
    }

    public function readyOrder(int $id)
    {
        $o = $this->orderRepo->find($id);
        if (!$o || $o->getStatus() !== Order::STATUS_CONFIRMED) return null;
        $o->setStatus(Order::STATUS_READY)->setReadyAt(new \DateTime());
        $this->orderRepo->save($o);
        return $o;
    }

    public function completeDelivery(int $id)
    {
        $o = $this->orderRepo->find($id);
        if (!$o || $o->getStatus() !== Order::STATUS_PICKED_UP) return null;
        $o->setStatus(Order::STATUS_DELIVERED)->setDeliveredAt(new \DateTime());
        $this->orderRepo->save($o);
        return $o;
    }

    public function cancelOrder(int $id)
    {
        $o = $this->orderRepo->find($id);
        if (!$o || !$o->isActive()) return null;
        $o->setStatus(Order::STATUS_CANCELLED);
        $this->orderRepo->save($o);
        return $o;
    }

    public function processPayment(int $orderId, array $data)
    {
        $o = $this->orderRepo->find($orderId);
        if (!$o) return null;
        $p = new Payment();
        $p->setOrder($o)->setAmount($o->getTotal())->setPaymentMethod($data['method'] ?? Payment::METHOD_CASH)
            ->setPlatformFee($o->getSubtotal() * self::PLATFORM_FEE)
            ->setStoreEarning($o->getSubtotal() * (1 - self::PLATFORM_FEE))
            ->setDriverEarning($o->getDeliveryFee() * 0.9);
        $this->paymentRepo->save($p);
        $p->setStatus(Payment::STATUS_COMPLETED)->setPaidAt(new \DateTime());
        $this->paymentRepo->save($p);
        $o->setPayment($p);
        $this->orderRepo->save($o);
        return $p;
    }

     public function getNearbyStores(float $lat, float $lng, float $r = 5)
     {
         return $this->storeRepo->findNearbyStores($lat, $lng, $r);
     }

     public function getCustomerOrders(string $uid)
     {
         return $this->orderRepo->findByCustomer($uid);
     }

     public function getOrder(int $id)
     {
         return $this->orderRepo->find($id);
     }
}
