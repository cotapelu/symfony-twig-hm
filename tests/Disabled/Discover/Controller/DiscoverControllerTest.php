<?php declare(strict_types=1);

namespace App\Tests\Unit\Discover\Controller;

use App\Discover\Controller\DiscoverController;
use App\Discover\Entity\DiscoveryItem;
use App\Discover\Service\DiscoverService;
use PHPUnit\Framework\TestCase;

class DiscoverControllerTest extends TestCase
{
    private DiscoverController $controller;
    private DiscoverService $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(DiscoverService::class);
        $reflection = new \ReflectionClass(DiscoverController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $reflectionProperty = $reflection->getProperty('service');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($controller, $this->service);
        $this->controller = $controller;
    }

    private function createItem(): DiscoveryItem
    {
        $item = new DiscoveryItem(1, 'place', 'Coffee Shop');
        $item->setId(1);
        return $item;
    }

    public function testCreateItemSuccess(): void
    {
        $item = $this->createItem();
        
        $this->service->expects($this->once())
            ->method('createItem')
            ->with(1, 'place', 'Coffee Shop')
            ->willReturn($item);

        $request = [
            'user_id' => 1,
            'body' => [
                'type' => 'place',
                'title' => 'Coffee Shop'
            ]
        ];

        $result = $this->controller->createItem($request);

        $this->assertEquals(201, $result['status']);
    }

    public function testGetItems(): void
    {
        $item = $this->createItem();
        
        $this->service->expects($this->once())
            ->method('getTrendingItems')
            ->with(50)
            ->willReturn([$item]);

        $request = [];

        $result = $this->controller->getItems($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetItemsByType(): void
    {
        $item = $this->createItem();
        
        $this->service->expects($this->once())
            ->method('getItemsByType')
            ->with('place')
            ->willReturn([$item]);

        $request = ['query' => ['type' => 'place']];

        $result = $this->controller->getItems($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetItemFound(): void
    {
        $item = $this->createItem();
        
        $this->service->expects($this->once())
            ->method('getItem')
            ->with(1)
            ->willReturn($item);

        $request = ['params' => ['id' => 1]];

        $result = $this->controller->getItem($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetItemNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('getItem')
            ->with(999)
            ->willReturn(null);

        $request = ['params' => ['id' => 999]];

        $result = $this->controller->getItem($request);

        $this->assertEquals(404, $result['status']);
    }

    public function testUpdateItemSuccess(): void
    {
        $item = $this->createItem();
        
        $this->service->expects($this->once())
            ->method('updateItem')
            ->with(1, ['description' => 'New description'])
            ->willReturn($item);

        $request = [
            'params' => ['id' => 1],
            'body' => ['description' => 'New description']
        ];

        $result = $this->controller->updateItem($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testUpdateItemNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('updateItem')
            ->with(999, [])
            ->willReturn(null);

        $request = [
            'params' => ['id' => 999],
            'body' => []
        ];

        $result = $this->controller->updateItem($request);

        $this->assertEquals(404, $result['status']);
    }

    public function testDeleteItemSuccess(): void
    {
        $this->service->expects($this->once())
            ->method('deleteItem')
            ->with(1)
            ->willReturn(true);

        $request = ['params' => ['id' => 1]];

        $result = $this->controller->deleteItem($request);

        $this->assertEquals(204, $result['status']);
    }

    public function testDeleteItemNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('deleteItem')
            ->with(999)
            ->willReturn(false);

        $request = ['params' => ['id' => 999]];

        $result = $this->controller->deleteItem($request);

        $this->assertEquals(404, $result['status']);
    }

    public function testGetNearbyItemsSuccess(): void
    {
        $item = $this->createItem();
        
        $this->service->expects($this->once())
            ->method('getNearbyItems')
            ->with(10.7769, 106.7009, 5.0)
            ->willReturn([$item]);

        $request = ['query' => ['lat' => 10.7769, 'lng' => 106.7009, 'radius' => 5]];

        $result = $this->controller->getNearbyItems($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetNearbyItemsMissingParams(): void
    {
        $request = ['query' => []];

        $result = $this->controller->getNearbyItems($request);

        $this->assertEquals(400, $result['status']);
    }

    public function testSearchItems(): void
    {
        $item = $this->createItem();
        
        $this->service->expects($this->once())
            ->method('searchItems')
            ->with('coffee')
            ->willReturn([$item]);

        $request = ['query' => ['q' => 'coffee']];

        $result = $this->controller->searchItems($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetTrendingItems(): void
    {
        $item = $this->createItem();
        
        $this->service->expects($this->once())
            ->method('getTrendingItems')
            ->with(10)
            ->willReturn([$item]);

        $request = ['query' => ['limit' => 10]];

        $result = $this->controller->getTrendingItems($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testIncrementScoreSuccess(): void
    {
        $item = $this->createItem();
        
        $this->service->expects($this->once())
            ->method('incrementScore')
            ->with(1)
            ->willReturn($item);

        $request = ['params' => ['id' => 1]];

        $result = $this->controller->incrementScore($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testIncrementScoreNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('incrementScore')
            ->with(999)
            ->willReturn(null);

        $request = ['params' => ['id' => 999]];

        $result = $this->controller->incrementScore($request);

        $this->assertEquals(404, $result['status']);
    }
}
