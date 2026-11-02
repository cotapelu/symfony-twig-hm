<?php

declare(strict_types=1);

namespace App\FoodDelivery\Service;

use App\FoodDelivery\Entity\Restaurant;
use App\FoodDelivery\Entity\Product;
use App\FoodDelivery\Repository\RestaurantRepository;
use App\FoodDelivery\Entity\Order;
use App\FoodDelivery\Repository\ProductRepository;

class RestaurantService
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_ACTIVE = 'active';
    private const STATUS_SUSPENDED = 'suspended';
    private RestaurantRepository $restaurantRepository;
    private ProductRepository $productRepository;

    public function __construct(
        RestaurantRepository $restaurantRepository,
        ProductRepository $productRepository
    ) {
        $this->restaurantRepository = $restaurantRepository;
        $this->productRepository = $productRepository;
    }

    public function createRestaurant(array $data)
    {
        $restaurant = new Restaurant();
        $restaurant->setOwnerId($data['ownerId']);
        $restaurant->setName($data['name']);
        $restaurant->setDescription($data['description'] ?? null);
        $restaurant->setAddress($data['address']);
        $restaurant->setLatitude((float) $data['latitude']);
        $restaurant->setLongitude((float) $data['longitude']);
        $restaurant->setCuisine($data['cuisine'] ?? null);
        $restaurant->setPhone($data['phone'] ?? null);
        $restaurant->setImageUrl($data['imageUrl'] ?? null);
        $restaurant->setDeliveryFee((float) ($data['deliveryFee'] ?? 0));
        $restaurant->setDeliveryTime((int) ($data['deliveryTime'] ?? 30));
        $restaurant->setStatus(self::STATUS_PENDING);

        $this->restaurantRepository->save($restaurant);

        return $restaurant;
    }

    public function updateRestaurant(int $restaurantId, array $data)
    {
        $restaurant = $this->restaurantRepository->find($restaurantId);
        if (!$restaurant) {
            return null;
        }

        $fieldSetters = [
            'name' => fn($v) => $restaurant->setName($v),
            'description' => fn($v) => $restaurant->setDescription($v),
            'address' => fn($v) => $restaurant->setAddress($v),
            'latitude' => fn($v) => $restaurant->setLatitude((float)$v),
            'longitude' => fn($v) => $restaurant->setLongitude((float)$v),
            'cuisine' => fn($v) => $restaurant->setCuisine($v),
            'phone' => fn($v) => $restaurant->setPhone($v),
            'imageUrl' => fn($v) => $restaurant->setImageUrl($v),
            'deliveryFee' => fn($v) => $restaurant->setDeliveryFee((float)$v),
            'deliveryTime' => fn($v) => $restaurant->setDeliveryTime((int)$v),
        ];

        foreach ($fieldSetters as $field => $setter) {
            if (isset($data[$field])) {
                $setter($data[$field]);
            }
        }

        $this->restaurantRepository->save($restaurant);
        return $restaurant;
    }

    public function approveRestaurant(int $restaurantId)
    {
        $restaurant = $this->restaurantRepository->find($restaurantId);
        if (!$restaurant) {
            return null;
        }

        $restaurant->setStatus(self::STATUS_ACTIVE);
        $this->restaurantRepository->save($restaurant);

        return $restaurant;
    }

    public function suspendRestaurant(int $restaurantId)
    {
        $restaurant = $this->restaurantRepository->find($restaurantId);
        if (!$restaurant) {
            return null;
        }

        $restaurant->setStatus(self::STATUS_SUSPENDED);
        $this->restaurantRepository->save($restaurant);

        return $restaurant;
    }

    public function getRestaurant(int $restaurantId)
    {
        return $this->restaurantRepository->find($restaurantId);
    }

    public function getRestaurantsByOwner(string $ownerId)
    {
        return $this->restaurantRepository->findByOwner($ownerId);
    }

    public function addProduct(int $restaurantId, array $data)
    {
        $restaurant = $this->restaurantRepository->find($restaurantId);
        if (!$restaurant) {
            throw new \Exception('Restaurant not found');
        }

        $product = new Product();
        $product->setRestaurant($restaurant);
        $product->setName($data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice((float) $data['price']);
        $product->setOriginalPrice(isset($data['originalPrice']) ? (float) $data['originalPrice'] : null);
        $product->setCategory($data['category']);
        $product->setImageUrl($data['imageUrl'] ?? null);
        $product->setPreparationTime($data['preparationTime'] ?? null);
        $product->setPopular($data['isPopular'] ?? false);
        $product->setNew($data['isNew'] ?? false);
        $product->setStatus(Product::STATUS_ACTIVE);

        $this->productRepository->save($product);

        return $product;
    }

    public function getProducts(int $restaurantId)
    {
        return $this->productRepository->findByRestaurant($restaurantId);
    }

    public function getPopularProducts(int $restaurantId)
    {
        return $this->productRepository->findPopularByRestaurant($restaurantId);
    }

    public function getProductCategories(int $restaurantId)
    {
        return $this->productRepository->getCategoriesByRestaurant($restaurantId);
    }

    public function updateProductRating(int $restaurantId)
    {
        $restaurant = $this->restaurantRepository->find($restaurantId);
        if (!$restaurant) {
            return;
        }

        $orders = $this->restaurantRepository->findBy(['id' => $restaurantId]);
        $totalRating = 0;
        $count = 0;

        foreach ($orders as $order) {
            if ($order->getStatus() === Order::STATUS_DELIVERED && $order->getDriverRating()) {
                $totalRating += $order->getDriverRating();
                $count++;
            }
        }

        if ($count > 0) {
            $restaurant->setRating($totalRating / $count);
            $restaurant->setTotalReviews($count);
            $this->restaurantRepository->save($restaurant);
        }
    }
}
