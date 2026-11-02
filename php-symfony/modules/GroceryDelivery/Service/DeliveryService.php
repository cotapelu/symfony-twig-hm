<?php

declare(strict_types=1);

namespace App\GroceryDelivery\Service;

use App\GroceryDelivery\Entity\DeliveryPerson;
use App\GroceryDelivery\Entity\Order;
use App\GroceryDelivery\Repository\DeliveryPersonRepository;
use App\GroceryDelivery\Repository\OrderRepository;

class DeliveryService
{
    private DeliveryPersonRepository $dpRepo;
    private OrderRepository $orderRepo;

    public function __construct(DeliveryPersonRepository $dpr, OrderRepository $or)
    {
        $this->dpRepo = $dpr;
        $this->orderRepo = $or;
    }

    public function setOnline($dpId, float $lat, float $lng)
    {
        $dp = $this->dpRepo->find($dpId);
        if (!$dp) return null;
        $dp->setStatus(DeliveryPerson::STATUS_ONLINE)->setCurrentLatitude($lat)->setCurrentLongitude($lng)->setLastActiveAt(new \DateTime());
        $this->dpRepo->save($dp);
        return $dp;
    }

    public function setOffline($dpId)
    {
        $dp = $this->dpRepo->find($dpId);
        if (!$dp) return null;
        $dp->setStatus(DeliveryPerson::STATUS_OFFLINE);
        $this->dpRepo->save($dp);
        return $dp;
    }

    public function assignOrder($orderId, $dpId)
    {
        $o = $this->orderRepo->find($orderId);
        $dp = $this->dpRepo->find($dpId);
        if (!$o || !$dp || $o->getStatus() !== Order::STATUS_READY || !$dp->isAvailable()) return null;
        $o->setDeliveryPerson($dp)->setStatus(Order::STATUS_PICKED_UP)->setPickedUpAt(new \DateTime());
        $dp->setStatus(DeliveryPerson::STATUS_BUSY);
        $this->dpRepo->save($dp);
        $this->orderRepo->save($o);
        return $o;
    }

    public function completeDelivery(int $orderId)
    {
        $o = $this->orderRepo->find($orderId);
        if (!$o || $o->getStatus() !== Order::STATUS_PICKED_UP) return null;
        $o->setStatus(Order::STATUS_DELIVERED)->setDeliveredAt(new \DateTime());
        if ($o->getDeliveryPerson()) {
            $dp = $o->getDeliveryPerson();
            $dp->setStatus(DeliveryPerson::STATUS_ONLINE)->setCompletedDeliveries($dp->getCompletedDeliveries() + 1);
            $this->dpRepo->save($dp);
        }
        $this->orderRepo->save($o);
        return $o;
    }

    public function findNearby(float $lat, float $lng, float $r = 5)
    {
        return $this->dpRepo->findNearby($lat, $lng, $r);
    }
}
