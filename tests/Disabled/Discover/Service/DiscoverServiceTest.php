<?php declare(strict_types=1);

namespace App\Tests\Unit\Discover\Service;

use App\Discover\Entity\DiscoveryItem;
use App\Discover\Service\DiscoverService;
use PHPUnit\Framework\TestCase;

class DiscoverServiceTest extends TestCase
{
    private DiscoverService $service;

    protected function setUp(): void
    {
        $this->service = new DiscoverService();
    }

    public function testCreateItem(): void
    {
        $item = $this->service->createItem(1, 'place', 'Coffee Shop');
        
        $this->assertInstanceOf(DiscoveryItem::class, $item);
        $this->assertEquals(1, $item->getUserId());
        $this->assertEquals('place', $item->getType());
        $this->assertEquals('Coffee Shop', $item->getTitle());
    }

    public function testGetItem(): void
    {
        $created = $this->service->createItem(1, 'place', 'Coffee Shop');
        
        $result = $this->service->getItem($created->getId());
        
        $this->assertSame($created, $result);
    }

    public function testGetItemNotFound(): void
    {
        $result = $this->service->getItem(999);
        
        $this->assertNull($result);
    }

    public function testGetItemsByType(): void
    {
        $this->service->createItem(1, 'place', 'Coffee Shop');
        $this->service->createItem(1, 'place', 'Restaurant');
        $this->service->createItem(2, 'event', 'Concert');
        
        $results = $this->service->getItemsByType('place');
        
        $this->assertCount(2, $results);
    }

    public function testGetNearbyItems(): void
    {
        $item1 = $this->service->createItem(1, 'place', 'Near Place');
        $item1->setLocation(10.7769, 106.7009);
        
        $item2 = $this->service->createItem(2, 'place', 'Far Place');
        $item2->setLocation(10.9000, 106.8000);
        
        $results = $this->service->getNearbyItems(10.7769, 106.7009, 5);
        
        $this->assertCount(1, $results);
    }

    public function testSearchItems(): void
    {
        $item = $this->service->createItem(1, 'place', 'Coffee Shop');
        $item->setDescription('Best coffee in town');
        
        $results = $this->service->searchItems('coffee');
        
        $this->assertCount(1, $results);
    }

    public function testSearchItemsByDescription(): void
    {
        $item = $this->service->createItem(1, 'place', 'Coffee Shop');
        $item->setDescription('Great espresso and pastries');
        
        $results = $this->service->searchItems('espresso');
        
        $this->assertCount(1, $results);
    }

    public function testGetTrendingItems(): void
    {
        $item1 = $this->service->createItem(1, 'place', 'Popular Place');
        $item1->incrementScore();
        $item1->incrementScore();
        
        $item2 = $this->service->createItem(2, 'place', 'Less Popular');
        
        $results = $this->service->getTrendingItems(1);
        
        $this->assertCount(1, $results);
    }

    public function testUpdateItem(): void
    {
        $item = $this->service->createItem(1, 'place', 'Coffee Shop');
        
        $result = $this->service->updateItem($item->getId(), [
            'description' => 'New description',
            'image_url' => 'https://example.com/image.jpg',
            'tags' => ['coffee', 'wifi']
        ]);
        
        $this->assertNotNull($result);
        $this->assertEquals('New description', $result->getDescription());
    }

    public function testUpdateItemNotFound(): void
    {
        $result = $this->service->updateItem(999, ['description' => 'Test']);
        
        $this->assertNull($result);
    }

    public function testIncrementScore(): void
    {
        $item = $this->service->createItem(1, 'place', 'Coffee Shop');
        
        $result = $this->service->incrementScore($item->getId());
        
        $this->assertNotNull($result);
        $this->assertEquals(1, $result->getScore());
    }

    public function testGetUserItems(): void
    {
        $this->service->createItem(1, 'place', 'Place 1');
        $this->service->createItem(1, 'event', 'Event 1');
        $this->service->createItem(2, 'place', 'Place 2');
        
        $results = $this->service->getUserItems(1);
        
        $this->assertCount(2, $results);
    }

    public function testDeleteItem(): void
    {
        $item = $this->service->createItem(1, 'place', 'Coffee Shop');
        
        $result = $this->service->deleteItem($item->getId());
        
        $this->assertTrue($result);
        $this->assertNull($this->service->getItem($item->getId()));
    }

    public function testDeleteItemNotFound(): void
    {
        $result = $this->service->deleteItem(999);
        
        $this->assertFalse($result);
    }
}
