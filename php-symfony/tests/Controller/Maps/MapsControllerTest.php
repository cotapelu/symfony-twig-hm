<?php

declare(strict_types=1);

namespace App\Tests\Controller\Maps;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Maps\Controller\MapsController;
use App\Maps\Service\MapsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class MapsControllerTest extends TestCase
{
    private MapsController $controller;
    private MapsService|MockObject $mapsService;

    protected function setUp(): void
    {
        $this->mapsService = $this->createMock(MapsService::class);
        $this->controller = new MapsController($this->mapsService);
    }

    public function testFindNearbySuccess(): void
    {
        $request = new Request(['lat' => '21.0285', 'lon' => '105.8542', 'radius' => '1000', 'category' => 'restaurant']);

        $places = [
            ['id' => 'place1', 'name' => 'Restaurant A', 'lat' => 21.0286, 'lon' => 105.8543, 'distance' => 100],
            ['id' => 'place2', 'name' => 'Restaurant B', 'lat' => 21.0287, 'lon' => 105.8544, 'distance' => 200],
        ];

        $this->mapsService
            ->expects($this->once())
            ->method('findNearbyPlaces')
            ->with(21.0285, 105.8542, 1000, 'restaurant')
            ->willReturn($places);

        $result = $this->controller->findNearby($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('places', $data);
        $this->assertCount(2, $data['places']);
        $this->assertEquals('Restaurant A', $data['places'][0]['name']);
    }

    public function testFindNearbyDefaultRadius(): void
    {
        $request = new Request(['lat' => '21.0285', 'lon' => '105.8542']);

        $places = [];

        $this->mapsService
            ->expects($this->once())
            ->method('findNearbyPlaces')
            ->with(21.0285, 105.8542, 1000, null)
            ->willReturn($places);

        $result = $this->controller->findNearby($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testFindNearbyWithoutCategory(): void
    {
        $request = new Request(['lat' => '21.0285', 'lon' => '105.8542', 'radius' => '2000']);

        $places = [['id' => 'place1', 'name' => 'Place A']];

        $this->mapsService
            ->expects($this->once())
            ->method('findNearbyPlaces')
            ->with(21.0285, 105.8542, 2000, null)
            ->willReturn($places);

        $result = $this->controller->findNearby($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testFindNearbyReturnsCorrectStructure(): void
    {
        $request = new Request(['lat' => '21.0285', 'lon' => '105.8542']);

        $places = [
            ['id' => 'place1', 'name' => 'Test Place', 'lat' => 21.03, 'lon' => 105.85, 'distance' => 500, 'category' => 'test'],
        ];

        $this->mapsService
            ->expects($this->once())
            ->method('findNearbyPlaces')
            ->willReturn($places);

        $result = $this->controller->findNearby($request);

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('places', $data);
        $this->assertIsArray($data['places']);
    }

    public function testSearchSuccess(): void
    {
        $request = new Request(['q' => 'coffee shop', 'limit' => '10']);

        $places = [
            ['id' => 'place1', 'name' => 'Coffee Shop A', 'address' => '123 Main St'],
            ['id' => 'place2', 'name' => 'Coffee Shop B', 'address' => '456 Oak Ave'],
        ];

        $this->mapsService
            ->expects($this->once())
            ->method('searchPlaces')
            ->with('coffee shop', 10)
            ->willReturn($places);

        $result = $this->controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('places', $data);
        $this->assertCount(2, $data['places']);
        $this->assertEquals('Coffee Shop A', $data['places'][0]['name']);
    }

    public function testSearchDefaultLimit(): void
    {
        $request = new Request(['q' => 'restaurant']);

        $places = [];

        $this->mapsService
            ->expects($this->once())
            ->method('searchPlaces')
            ->with('restaurant', 20)
            ->willReturn($places);

        $result = $this->controller->search($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testSearchEmptyQuery(): void
    {
        $request = new Request(['q' => '']);

        $places = [];

        $this->mapsService
            ->expects($this->once())
            ->method('searchPlaces')
            ->with('', 20)
            ->willReturn($places);

        $result = $this->controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('places', $data);
        $this->assertIsArray($data['places']);
    }

    public function testSearchNoQueryParameter(): void
    {
        $request = new Request();

        $places = [];

        $this->mapsService
            ->expects($this->once())
            ->method('searchPlaces')
            ->with('', 20)
            ->willReturn($places);

        $result = $this->controller->search($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testSearchReturnsCorrectStructure(): void
    {
        $request = new Request(['q' => 'test']);

        $places = [
            ['id' => 'place1', 'name' => 'Test Place', 'lat' => 21.03, 'lon' => 105.85],
        ];

        $this->mapsService
            ->expects($this->once())
            ->method('searchPlaces')
            ->willReturn($places);

        $result = $this->controller->search($request);

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('places', $data);
        $this->assertIsArray($data['places']);
    }

    public function testGetDirectionsSuccess(): void
    {
        $request = new Request(['origin' => '21.0285,105.8542', 'destination' => '21.0300,105.8600']);

        $directions = [
            'distance' => 1000,
            'duration' => 300,
            'steps' => [
                ['instruction' => 'Head north', 'distance' => 500],
                ['instruction' => 'Turn right', 'distance' => 500],
            ],
        ];

        $this->mapsService
            ->expects($this->once())
            ->method('getDirections')
            ->with('21.0285,105.8542', '21.0300,105.8600')
            ->willReturn($directions);

        $result = $this->controller->getDirections($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('directions', $data);
        $this->assertEquals(1000, $data['directions']['distance']);
        $this->assertEquals(300, $data['directions']['duration']);
        $this->assertCount(2, $data['directions']['steps']);
    }

    public function testGetDirectionsWithoutParameters(): void
    {
        $request = new Request();

        $directions = [];

        $this->mapsService
            ->expects($this->once())
            ->method('getDirections')
            ->with(null, null)
            ->willReturn($directions);

        $result = $this->controller->getDirections($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('directions', $data);
    }

    public function testGetDirectionsReturnsCorrectStructure(): void
    {
        $request = new Request(['origin' => '21.0285,105.8542', 'destination' => '21.0300,105.8600']);

        $directions = ['distance' => 1000, 'duration' => 300];

        $this->mapsService
            ->expects($this->once())
            ->method('getDirections')
            ->willReturn($directions);

        $result = $this->controller->getDirections($request);

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('directions', $data);
        $this->assertIsArray($data['directions']);
    }

    public function testFindNearbyWithMultipleCoordinates(): void
    {
        $request = new Request(['lat' => '10.7769', 'lon' => '106.7009', 'radius' => '500', 'category' => 'hospital']);

        $places = [
            ['id' => 'hosp1', 'name' => 'Hospital A', 'distance' => 200],
        ];

        $this->mapsService
            ->expects($this->once())
            ->method('findNearbyPlaces')
            ->with(10.7769, 106.7009, 500, 'hospital')
            ->willReturn($places);

        $result = $this->controller->findNearby($request);

        $this->assertEquals(200, $result->StatusCode());
    }

    public function testSearchWithDifferentQuery(): void
    {
        $request = new Request(['q' => 'pharmacy', 'limit' => '5']);

        $places = [['id' => 'pharm1', 'name' => 'Pharmacy A']];

        $this->mapsService
            ->expects($this->once())
            ->method('searchPlaces')
            ->with('pharmacy', 5)
            ->willReturn($places);

        $result = $this->controller->search($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetDirectionsDifferentLocations(): void
    {
        $request = new Request(['origin' => '10.7769,106.7009', 'destination' => '10.7800,106.7100']);

        $directions = [
            'distance' => 2000,
            'duration' => 600,
            'steps' => [],
        ];

        $this->mapsService
            ->expects($this->once())
            ->method('getDirections')
            ->with('10.7769,106.7009', '10.7800,106.7100')
            ->willReturn($directions);

        $result = $this->controller->getDirections($request);

        $this->assertEquals(200, $result->getStatusCode());
    }
}
