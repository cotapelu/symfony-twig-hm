<?php

namespace App\Discover\Service;

use App\Discover\Entity\DiscoveryItem;
use App\Discover\Entity\Action;

class DiscoverService
{
    private array $items = [];
    private int $nextId = 1;

    public function createItem(int $userId, string $type, string $title)
    {
        $item = new DiscoveryItem($userId, $type, $title);
        $item->setId($this->nextId++);
        $this->items[$item->getId()] = $item;
        return $item;
    }

    public function getItem(int $id)
    {
        return $this->items[$id] ?? null;
    }

    public function getItemsByType(string $type)
    {
        return array_filter($this->items, fn($i) => $i->getType() === $type);
    }

    public function getNearbyItems(float $lat, float $lng, float $radiusKm)
    {
        return array_filter($this->items, fn($i) => 
            $i->getLatitude() && $i->getLongitude() &&
            $this->calculateDistance($lat, $lng, $i->getLatitude(), $i->getLongitude()) <= $radiusKm
        );
    }

    public function searchItems(string $query)
    {
        $query = strtolower($query);
        return array_filter($this->items, fn($i) => 
            stripos($i->getTitle(), $query) !== false ||
            stripos($i->getDescription() ?? '', $query) !== false
        );
    }

    public function getTrendingItems(int $limit = 10)
    {
        $items = $this->items;
        usort($items, fn($a, $b) => $b->getScore() <=> $a->getScore());
        return array_slice($items, 0, $limit);
    }

    public function updateItem(int $id, array $data)
    {
        $item = $this->getItem($id);
        if (!$item) return null;

        if (isset($data['description'])) $item->setDescription($data['description']);
        if (isset($data['image_url'])) $item->setImageUrl($data['image_url']);
        if (isset($data['address'])) $item->setAddress($data['address']);
        if (isset($data['latitude']) && isset($data['longitude'])) {
            $item->setLocation($data['latitude'], $data['longitude']);
        }
        if (isset($data['tags'])) $item->setTags($data['tags']);

        return $item;
    }

    public function incrementScore(int $id)
    {
        $item = $this->getItem($id);
        if ($item) {
            $item->incrementScore();
        }
        return $item;
    }

    public function getUserItems(int $userId)
    {
        return array_filter($this->items, fn($i) => $i->getUserId() === $userId);
    }

    public function deleteItem(int $id)
    {
        if (isset($this->items[$id])) {
            unset($this->items[$id]);
            return true;
        }
        return false;
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2)
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }

    public function getTrendingContent(int $limit, ?string $type = null): array
    {
        if ($type) {
            $items = $this->getItemsByType($type);
            usort($items, fn($a,$b)=>$b->getScore()<=>$a->getScore());
            return array_slice($items, 0, $limit);
        }
        return $this->getTrendingItems($limit);
    }

    public function getTrendingByType(string $type, int $limit = 10): array
    {
        $items = $this->getItemsByType($type);
        usort($items, fn($a,$b)=>$b->getScore()<=>$a->getScore());
        return array_slice($items, 0, $limit);
    }

    public function getRecommendedContent(string $userId, int $limit = 20): array
    {
        return [];
    }

    public function trackAction(string $userId, string $action_type, string $content_type, ?int $content_id = null): Action|false
    {
        $action = new Action($userId, $action_type, $content_type, $content_id);
        return $action;
    }

    public function incrementViewCount(string $content_type, int $content_id): bool
    {
        return true;
    }

    public function getUserDiscoverHistory(string $userId, int $limit = 20): array
    {
        return [];
    }

    public function refreshRecommendations(string $userId): bool
    {
        return true;
    }

    public function searchContent(string $query, ?string $content_type = null, int $limit = 20): array
    {
        return [];
    }

}
