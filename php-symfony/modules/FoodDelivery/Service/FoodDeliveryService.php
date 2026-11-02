<?php

declare(strict_types=1);

namespace App\FoodDelivery\Service;

use App\FoodDelivery\Entity\Order;
use App\FoodDelivery\Entity\OrderItem;
use App\FoodDelivery\Entity\Payment;
use App\FoodDelivery\Entity\Restaurant;
use App\FoodDelivery\Entity\Product;
use App\FoodDelivery\Repository\OrderRepository;
use App\FoodDelivery\Repository\RestaurantRepository;
use App\FoodDelivery\Repository\ProductRepository;
use App\FoodDelivery\Repository\PaymentRepository;

class FoodDeliveryService
{
    private OrderRepository $orderRepository;
    private RestaurantRepository $restaurantRepository;
    private ProductRepository $productRepository;
    private PaymentRepository $paymentRepository;

    private const PLATFORM_FEE_RATE = 0.15;
    private const DELIVERY_FEE_RATE = 0.10;

    public function __construct(
        OrderRepository $orderRepository,
        RestaurantRepository $restaurantRepository,
        ProductRepository $productRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->restaurantRepository = $restaurantRepository;
        $this->productRepository = $productRepository;
        $this->paymentRepository = $paymentRepository;
    }

    public function createOrder(array $data)
    {
        $restaurant = $this->getActiveRestaurant($data['restaurantId']);
        [$items, $subtotal] = $this->buildOrderItems($data['items']);
        $pricing = $this->calculatePricing($subtotal, $restaurant, $data);

        $order = new Order();
        $this->populateOrder($order, $restaurant, $items, $pricing, $data);

        $this->orderRepository->save($order);
        return $order;
    }

    private function getActiveRestaurant(int $restaurantId)
    {
        $restaurant = $this->restaurantRepository->find($restaurantId);
        if (!$restaurant || !$restaurant->isActive()) {
            throw new \Exception('Restaurant not found or inactive');
        }
        return $restaurant;
    }

    private function buildOrderItems(array $itemsData): array
    {
        $items = [];
        $subtotal = 0;
        foreach ($itemsData as $itemData) {
            $product = $this->productRepository->find($itemData['productId']);
            if (!$product) {
                throw new \Exception('Product not found: ' . $itemData['productId']);
            }
            $item = new OrderItem();
            $item->setProduct($product);
            $item->setQuantity($itemData['quantity']);
            $item->setUnitPrice($product->getPrice());
            $item->setTotalPrice($product->getPrice() * $itemData['quantity']);
            $item->setSpecialInstructions($itemData['specialInstructions'] ?? null);
            $items[] = $item;
            $subtotal += $item->getTotalPrice();
        }
        return [$items, $subtotal];
    }

    private function calculatePricing(float $subtotal, $restaurant, array $data): array
    {
        $deliveryFee = $restaurant->getDeliveryFee();
        $serviceFee = $subtotal * 0.02;
        $tax = $subtotal * 0.08;
        $discount = $data['discount'] ?? 0;
        $total = $subtotal + $deliveryFee + $serviceFee + $tax - $discount;
        return [
            'subtotal' => $subtotal,
            'deliveryFee' => $deliveryFee,
            'serviceFee' => $serviceFee,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
        ];
    }

    private function populateOrder(Order $order, $restaurant, array $items, array $pricing, array $data): void
    {
        $order->setRestaurant($restaurant);
        $order->setCustomerId($data['customerId']);
        $order->setSubtotal($pricing['subtotal']);
        $order->setDeliveryFee($pricing['deliveryFee']);
        $order->setServiceFee($pricing['serviceFee']);
        $order->setTax($pricing['tax']);
        $order->setDiscount($pricing['discount']);
        $order->setTotal($pricing['total']);
        $order->setDeliveryAddress($data['deliveryAddress']);
        $order->setDeliveryLatitude((float) $data['deliveryLatitude']);
        $order->setDeliveryLongitude((float) $data['deliveryLongitude']);
        $order->setDeliveryInstructions($data['deliveryInstructions'] ?? null);
        $order->setCustomerPhone($data['customerPhone'] ?? null);
        $order->setCustomerName($data['customerName'] ?? null);
        $order->setEstimatedDeliveryTime($restaurant->getDeliveryTime() + 15);
        $order->setStatus(Order::STATUS_PENDING);

        foreach ($items as $item) {
            $item->setOrder($order);
            $order->addItem($item);
        }
    }

    public function confirmOrder(int $orderId)
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order || $order->getStatus() !== Order::STATUS_PENDING) {
            return null;
        }

        $order->setStatus(Order::STATUS_CONFIRMED);
        $order->setConfirmedAt(new \DateTime());
        $order->setEstimatedDeliveryTime($order->getRestaurant()->getDeliveryTime() + 15);

        $this->orderRepository->save($order);

        return $order;
    }

    public function prepareOrder(int $orderId)
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order || $order->getStatus() !== Order::STATUS_CONFIRMED) {
            return null;
        }

        $order->setStatus(Order::STATUS_PREPARING);
        $order->setPreparingAt(new \DateTime());

        $this->orderRepository->save($order);

        return $order;
    }

    public function readyOrder(int $orderId)
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order || $order->getStatus() !== Order::STATUS_PREPARING) {
            return null;
        }

        $order->setStatus(Order::STATUS_READY);
        $order->setReadyAt(new \DateTime());

        $this->orderRepository->save($order);

        return $order;
    }

    public function cancelOrder(int $orderId, string $reason)
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order || !$order->isActive()) {
            return null;
        }

        $order->setStatus(Order::STATUS_CANCELLED);

        $this->orderRepository->save($order);

        return $order;
    }

    public function getOrder(int $orderId)
    {
        return $this->orderRepository->find($orderId);
    }

    public function getCustomerOrders(string $customerId, int $limit = 50, int $offset = 0)
    {
        return $this->orderRepository->findByCustomer($customerId, $limit, $offset);
    }

    public function getCustomerOrdersCount(string $customerId)
    {
        return $this->orderRepository->countByCustomer($customerId);
    }

    public function getRestaurantOrders(int $restaurantId)
    {
        return $this->orderRepository->findByRestaurant($restaurantId);
    }

    public function getActiveRestaurantOrders(int $restaurantId)
    {
        return $this->orderRepository->findActiveOrdersByRestaurant($restaurantId);
    }

    public function getPendingDeliveryOrders()
    {
        return $this->orderRepository->findPendingDeliveryOrders();
    }

    public function getActiveOrderByCustomer(string $customerId)
    {
        return $this->orderRepository->findActiveOrderByCustomer($customerId);
    }

    public function processPayment(int $orderId, array $paymentData)
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order) {
            return null;
        }

        $payment = new Payment();
        $payment->setOrder($order);
        $payment->setAmount($order->getTotal());
        $payment->setPaymentMethod($paymentData['method'] ?? Payment::METHOD_CASH);
        $payment->setPaymentProvider($paymentData['provider'] ?? null);

        $earnings = $this->calculateEarnings($order);
        $payment->setPlatformFee($earnings['platformFee']);
        $payment->setRestaurantEarning($earnings['restaurantEarning']);
        $payment->setDeliveryFee($earnings['deliveryFee']);
        $payment->setDriverEarning($earnings['driverEarning']);

        $this->paymentRepository->save($payment);
        $order->setPayment($payment);

        $payment->setStatus(Payment::STATUS_COMPLETED);
        $payment->setPaidAt(new \DateTime());
        $this->paymentRepository->save($payment);

        $this->orderRepository->save($order);

        return $payment;
    }

    private function calculateEarnings(Order $order): array
    {
        $platformFee = $order->getSubtotal() * self::PLATFORM_FEE_RATE;
        $deliveryFee = $order->getDeliveryFee();
        $restaurantEarning = $order->getSubtotal() - $platformFee;
        $driverEarning = $deliveryFee * (1 - self::DELIVERY_FEE_RATE);
        return [
            'platformFee' => $platformFee,
            'restaurantEarning' => $restaurantEarning,
            'deliveryFee' => $deliveryFee,
            'driverEarning' => $driverEarning,
        ];
    }

    public function getActiveRestaurants(int $limit = 50, int $offset = 0)
    {
        return $this->restaurantRepository->findActiveRestaurants($limit, $offset);
    }

    public function countActiveRestaurants()
    {
        return $this->restaurantRepository->countActiveRestaurants();
    }

    public function getRestaurantsByCuisine(string $cuisine, int $limit = 50, int $offset = 0)
    {
        return $this->restaurantRepository->findByCuisine($cuisine, $limit, $offset);
    }

    public function countRestaurantsByCuisine(string $cuisine)
    {
        return $this->restaurantRepository->countByCuisine($cuisine);
    }

    public function getNearbyRestaurants(float $latitude, float $longitude, float $radius = 5.0, int $limit = 50, int $offset = 0)
    {
        return $this->restaurantRepository->findNearbyRestaurants($latitude, $longitude, $radius, $limit, $offset);
    }

    public function countNearbyRestaurants(float $latitude, float $longitude, float $radius = 5.0)
    {
        return $this->restaurantRepository->countNearbyRestaurants($latitude, $longitude, $radius);
    }

    public function searchRestaurants(string $keyword, int $limit = 50, int $offset = 0)
    {
        return $this->restaurantRepository->searchRestaurants($keyword, $limit, $offset);
    }

    public function countSearchRestaurants(string $keyword)
    {
        return $this->restaurantRepository->countSearchRestaurants($keyword);
    }

    public function getRestaurant(int $id)
    {
        return $this->restaurantRepository->find($id);
    }

    public function getOrderByIdempotencyKey(string $key)
    {
        return $this->orderRepository->findByIdempotencyKey($key);
    }
}
