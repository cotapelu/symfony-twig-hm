<?php declare(strict_types=1);

namespace App\Tests\Unit\Discover\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Discover\Entity\DiscoveryItem;
use App\Discover\Service\DiscoverService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DiscoverServiceTest extends TestCase
{
    private DiscoverService $service;

    protected function setUp(): void
    {
        $this->service = new DiscoverService();
    }

    private function createItem(int $userId, string $type, string $title, array $extra = []): DiscoveryItem
    {
        $item = $this->service->createItem($userId, $type, $title);
        if (isset($extra['description'])) {
            $item->setDescription($extra['description']);
        }
        if (isset($extra['image_url'])) {
            $item->setImageUrl($extra['image_url']);
        }
        if (isset($extra['latitude']) && isset($extra['longitude'])) {
            $item->setLocation($extra['latitude'], $extra['longitude']);
        }
        if (isset($extra['address'])) {
            $item->setAddress($extra['address']);
        }
        if (isset($extra['tags'])) {
            $item->setTags($extra['tags']);
        }
        if (isset($extra['score'])) {
            for ($i = 0; $i < $extra['score']; $i++) {
                $item->incrementScore();
            }
        }
        return $item;
    }

    public function testCreateItem(): void
    {
        $item = $this->service->createItem(1, 'event', 'Concert');
        $this->assertInstanceOf(DiscoveryItem::class, $item);
        $this->assertSame(1, $item->getUserId());
        $this->assertSame('event', $item->getType());
        $this->assertSame('Concert', $item->getTitle());
        $this->assertSame(1, $item->getId());
    }

    public function testGetItemReturnsNullIfNotFound(): void
    {
        $this->assertNull($this->service->getItem(999));
    }

    public function testGetItemReturnsExisting(): void
    {
        $item = $this->createItem(1, 'place', 'Park');
        $found = $this->service->getItem($item->getId());
        $this->assertSame($item, $found);
    }

    public function testGetItemsByType(): void
    {
        $this->createItem(1, 'event', 'Event A');
        $this->createItem(2, 'place', 'Place B');
        $this->createItem(3, 'event', 'Event C');

        $events = $this->service->getItemsByType('event');
        $this->assertCount(2, $events);
        foreach ($events as $e) {
            $this->assertSame('event', $e->getType());
        }
    }

    public function testGetItemsByTypeReturnsEmptyIfNone(): void
    {
        $result = $this->service->getItemsByType('unknown');
        $this->assertEmpty($result);
    }

    public function testGetNearbyItemsFiltersByDistance(): void
    {
        // Create items with coordinates
        $nearby = $this->createItem(1, 'place', 'Nearby');
        $nearby->setLocation(10.0, 10.0);

        $far = $this->createItem(2, 'place', 'Far');
        $far->setLocation(20.0, 20.0);

        $result = $this->service->getNearbyItems(10.0, 10.0, 10.0); // radius 10 km
        $result = array_values($result); // reindex
        $this->assertCount(1, $result);
        $this->assertSame('Nearby', $result[0]->getTitle());
    }

    public function testGetNearbyItemsExcludesItemsWithoutCoordinates(): void
    {
        $this->createItem(1, 'place', 'Nowhere'); // no lat/lng
        $nearby = $this->service->getNearbyItems(0, 0, 100);
        $this->assertEmpty($nearby);
    }

    public function testSearchItemsMatchesTitleCaseInsensitive(): void
    {
        $this->createItem(1, 'event', 'Jazz Festival');
        $this->createItem(2, 'event', 'Rock Concert');

        $results = $this->service->searchItems('jazz');
        $results = array_values($results);
        $this->assertCount(1, $results);
        $this->assertSame('Jazz Festival', $results[0]->getTitle());
    }

    public function testSearchItemsMatchesDescription(): void
    {
        $this->createItem(1, 'event', 'Event X', ['description' => 'A wonderful evening']);
        $this->createItem(2, 'event', 'Event Y', ['description' => 'Nothing special']);

        $results = $this->service->searchItems('wonderful');
        $results = array_values($results);
        $this->assertCount(1, $results);
        $this->assertSame('Event X', $results[0]->getTitle());
    }

    public function testSearchItemsReturnsEmptyIfNoMatch(): void
    {
        $this->createItem(1, 'event', 'Concert');
        $results = $this->service->searchItems('unknown');
        $this->assertEmpty($results);
    }

    public function testSearchItemsAcceptsEmptyQuery(): void
    {
        $this->createItem(1, 'event', 'Concert');
        $results = $this->service->searchItems('');
        $this->assertCount(1, $results);
    }

    public function testGetTrendingItemsSortedByScoreDescending(): void
    {
        $low = $this->createItem(1, 'event', 'Low', ['score' => 5]);
        $medium = $this->createItem(2, 'event', 'Medium', ['score' => 10]);
        $high = $this->createItem(3, 'event', 'High', ['score' => 20]);

        $trending = $this->service->getTrendingItems(3);
        $this->assertCount(3, $trending);
        $this->assertSame('High', $trending[0]->getTitle());
        $this->assertSame('Medium', $trending[1]->getTitle());
        $this->assertSame('Low', $trending[2]->getTitle());
    }

    public function testGetTrendingItemsRespectsLimit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createItem(1, 'event', "Item $i", ['score' => $i]);
        }
        $trending = $this->service->getTrendingItems(3);
        $this->assertCount(3, $trending);
        $this->assertSame('Item 4', $trending[0]->getTitle());
        $this->assertSame('Item 3', $trending[1]->getTitle());
        $this->assertSame('Item 2', $trending[2]->getTitle());
    }

    public function testUpdateItemModifiesFields(): void
    {
        $item = $this->createItem(1, 'event', 'Old Title', ['description' => 'Old desc']);
        $updated = $this->service->updateItem($item->getId(), [
            'description' => 'New desc',
            'image_url' => 'http://example.com/img.jpg',
        ]);

        $this->assertSame($item, $updated);
        $this->assertSame('New desc', $item->getDescription());
        $this->assertSame('http://example.com/img.jpg', $item->getImageUrl());
        $this->assertSame('Old Title', $item->getTitle()); // unchanged
    }

    public function testUpdateItemReturnsNullIfNotFound(): void
    {
        $result = $this->service->updateItem(999, ['description' => 'x']);
        $this->assertNull($result);
    }

    public function testDeleteItemRemovesIt(): void
    {
        $item = $this->createItem(1, 'event', 'To delete');
        $id = $item->getId();
        $this->assertNotNull($this->service->getItem($id));

        $this->service->deleteItem($id);
        $this->assertNull($this->service->getItem($id));
    }

    public function testDeleteItemNonExistentDoesNotThrow(): void
    {
        // Should not throw
        $this->service->deleteItem(999);
        $this->assertTrue(true);
    }
}
