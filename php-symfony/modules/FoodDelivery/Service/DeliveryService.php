<?php

declare(strict_types=1);

namespace App\FoodDelivery\Service;

use App\FoodDelivery\Entity\DeliveryPerson;
use App\FoodDelivery\Entity\Order;
use App\FoodDelivery\Repository\DeliveryPersonRepository;
use App\FoodDelivery\Repository\OrderRepository;

class DeliveryService
{
    private DeliveryPersonRepository $deliveryPersonRepository;
    private OrderRepository $orderRepository;

    public function __construct(
        DeliveryPersonRepository $deliveryPersonRepository,
        OrderRepository $orderRepository
    ) {
        $this->deliveryPersonRepository = $deliveryPersonRepository;
        $this->orderRepository = $orderRepository;
    }

    public function registerDeliveryPerson(array $data)
    {
        $deliveryPerson = new DeliveryPerson();
        $deliveryPerson->setUserId($data['userId']);
        $deliveryPerson->setName($data['name']);
        $deliveryPerson->setPhone($data['phone'] ?? null);
        $deliveryPerson->setVehicleType($data['vehicleType']);
        $deliveryPerson->setLicensePlate($data['licensePlate'] ?? null);
        $deliveryPerson->setStatus(DeliveryPerson::STATUS_OFFLINE);
        $deliveryPerson->setCurrentLatitude(0);
        $deliveryPerson->setCurrentLongitude(0);

        $this->deliveryPersonRepository->save($deliveryPerson);

        return $deliveryPerson;
    }

    public function setOnline(int $deliveryPersonId, float $latitude, float $longitude)
    {
        $deliveryPerson = $this->deliveryPersonRepository->find($deliveryPersonId);
        if (!$deliveryPerson) {
            return null;
        }

        $deliveryPerson->setStatus(DeliveryPerson::STATUS_ONLINE);
        $deliveryPerson->setCurrentLatitude($latitude);
        $deliveryPerson->setCurrentLongitude($longitude);
        $deliveryPerson->setLastActiveAt(new \DateTime());

        $this->deliveryPersonRepository->save($deliveryPerson);

        return $deliveryPerson;
    }

    public function setOffline(int $deliveryPersonId)
    {
        $deliveryPerson = $this->deliveryPersonRepository->find($deliveryPersonId);
        if (!$deliveryPerson) {
            return null;
        }

        $deliveryPerson->setStatus(DeliveryPerson::STATUS_OFFLINE);
        $this->deliveryPersonRepository->save($deliveryPerson);

        return $deliveryPerson;
    }

    public function updateLocation(int $deliveryPersonId, float $latitude, float $longitude)
    {
        $deliveryPerson = $this->deliveryPersonRepository->find($deliveryPersonId);
        if ($deliveryPerson) {
            $deliveryPerson->setCurrentLatitude($latitude);
            $deliveryPerson->setCurrentLongitude($longitude);
            $deliveryPerson->setLastActiveAt(new \DateTime());
            $this->deliveryPersonRepository->save($deliveryPerson);
        }
    }

    public function assignOrder(int $orderId, int $deliveryPersonId)
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order || $order->getStatus() !== Order::STATUS_READY) {
            return null;
        }

        $deliveryPerson = $this->deliveryPersonRepository->find($deliveryPersonId);
        if (!$deliveryPerson || !$deliveryPerson->isAvailable()) {
            return null;
        }

        $order->setDeliveryPerson($deliveryPerson);
        $order->setStatus(Order::STATUS_PICKED_UP);
        $order->setPickedUpAt(new \DateTime());

        $deliveryPerson->setStatus(DeliveryPerson::STATUS_DELIVERING);
        $this->deliveryPersonRepository->save($deliveryPerson);
        $this->orderRepository->save($order);

        return $order;
    }

    public function completeDelivery(int $orderId)
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order || $order->getStatus() !== Order::STATUS_PICKED_UP) {
            return null;
        }

        $order->setStatus(Order::STATUS_DELIVERED);
        $order->setDeliveredAt(new \DateTime());

        if ($order->getDeliveryPerson()) {
            $deliveryPerson = $order->getDeliveryPerson();
            $deliveryPerson->setStatus(DeliveryPerson::STATUS_ONLINE);
            $deliveryPerson->setCompletedDeliveries($deliveryPerson->getCompletedDeliveries() + 1);
            $deliveryPerson->setTotalDeliveries($deliveryPerson->getTotalDeliveries() + 1);
            $this->deliveryPersonRepository->save($deliveryPerson);
        }

        $this->orderRepository->save($order);

        return $order;
    }

    public function findNearbyDeliveryPersons(float $latitude, float $longitude, float $radius = 5.0)
    {
        return $this->deliveryPersonRepository->findNearbyDeliveryPersons($latitude, $longitude, $radius);
    }

    public function getAvailableDeliveryPersons()
    {
        return $this->deliveryPersonRepository->findAvailableDeliveryPersons();
    }

    public function getDeliveryPerson(int $deliveryPersonId)
    {
        return $this->deliveryPersonRepository->find($deliveryPersonId);
    }

    public function getDeliveryPersonByUserId(string $userId)
    {
        return $this->deliveryPersonRepository->findByUserId($userId);
    }

    public function getDeliveryPersonOrders(int $deliveryPersonId)
    {
        return $this->orderRepository->findByDeliveryPerson($deliveryPersonId);
    }

    public function rateDelivery(int $orderId, float $rating, ?string $review = null)
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order || !$order->isCompleted()) {
            return null;
        }

        $order->setDriverRating($rating);
        $order->setDriverReview($review);

        $this->orderRepository->save($order);

        if ($order->getDeliveryPerson()) {
            $this->updateDeliveryPersonRating($order->getDeliveryPerson());
        }

        return $order;
    }

    private function updateDeliveryPersonRating(DeliveryPerson $deliveryPerson)
    {
        $orders = $this->orderRepository->findByDeliveryPerson($deliveryPerson->getId());

        if (empty($orders)) {
            return;
        }

        $totalRating = 0;
        $count = 0;

        foreach ($orders as $order) {
            if ($order->getDriverRating()) {
                $totalRating += $order->getDriverRating();
                $count++;
            }
        }

        if ($count > 0) {
            $deliveryPerson->setRating($totalRating / $count);
            $this->deliveryPersonRepository->save($deliveryPerson);
        }
    }
}
