<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Discover\Entity\DiscoveryItem;
use App\Discover\Service\DiscoverService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DiscoverIntegrationTest extends TestCase
{
    private DiscoverService $service;

    protected function setUp(): void
    {
        $this->service = new DiscoverService();
    }

    public function testFullItemLifecycle(): void
    {
        $item = $this->service->createItem(1, 'place', 'Coffee Shop');
        $this->assertNotNull($item->getId());
        
        $this->service->updateItem($item->getId(), [
            'description' => 'Best coffee in town',
            'latitude' => 10.7769,
            'longitude' => 106.7009
        ]);
        
        $retrieved = $this->service->getItem($item->getId());
        $this->assertNotNull($retrieved);
        $this->assertEquals('Best coffee in town', $retrieved->getDescription());
    }

    public function testItemSearch(): void
    {
        $item1 = $this->service->createItem(1, 'place', 'Coffee Shop');
        $item1->setDescription('Great coffee and pastries');
        
        $item2 = $this->service->createItem(2, 'place', 'Restaurant');
        
        $search = $this->service->searchItems('coffee');
        $this->assertCount(1, $search);
    }

    public function testNearbyItems(): void
    {
        $nearby = $this->service->createItem(1, 'place', 'Nearby Cafe');
        $nearby->setLocation(10.7769, 106.7009);
        
        $far = $this->service->createItem(2, 'place', 'Far Restaurant');
        $far->setLocation(10.9000, 106.9000);
        
        $results = $this->service->getNearbyItems(10.7769, 106.7009, 10);
        
        $this->assertCount(1, $results);
    }

    public function testTrendingItems(): void
    {
        $popular = $this->service->createItem(1, 'place', 'Popular Place');
        $popular->incrementScore();
        $popular->incrementScore();
        
        $unpopular = $this->service->createItem(2, 'place', 'Unknown Place');
        
        $trending = $this->service->getTrendingItems(1);
        
        $this->assertCount(1, $trending);
    }

    public function testItemScore(): void
    {
        $item = $this->service->createItem(1, 'place', 'Test Place');
        
        $this->service->incrementScore($item->getId());
        $this->service->incrementScore($item->getId());
        
        $retrieved = $this->service->getItem($item->getId());
        
        $this->assertEquals(2, $retrieved->getScore());
    }

    public function testUserItems(): void
    {
        $this->service->createItem(1, 'place', 'Place 1');
        $this->service->createItem(1, 'event', 'Event 1');
        $this->service->createItem(2, 'place', 'Place 2');
        
        $user1 = $this->service->getUserItems(1);
        
        $this->assertCount(2, $user1);
    }
}
