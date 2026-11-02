<?php declare(strict_types=1);

namespace App\Tests\Unit\Maps\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Maps\Entity\Place;
use App\Maps\Service\MapsService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Result;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MapsServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private QueryBuilder $qb;
    private \Doctrine\ORM\Query $query;
    private MapsService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(\Doctrine\ORM\Query::class);

        $this->em->method('createQueryBuilder')
            ->willReturn($this->qb);

        // Common fluent stubs
        $this->qb->method('select')->willReturnSelf();
        $this->qb->method('from')->willReturnSelf();
        $this->qb->method('where')->willReturnSelf();
        $this->qb->method('andWhere')->willReturnSelf();
        $this->qb->method('orWhere')->willReturnSelf();
        $this->qb->method('setParameter')->willReturnSelf();
        $this->qb->method('setMaxResults')->willReturnSelf();
        $this->qb->method('getQuery')->willReturn($this->query);

        $this->service = new MapsService($this->em);
    }

    private function createPlaceMock(
        string $id,
        float $lat,
        float $lon,
        ?string $category = null,
        ?string $name = null,
        ?string $address = null
    ): Place {
        $place = $this->createMock(Place::class);
        $place->method('getId')->willReturn($id);
        $place->method('getLatitude')->willReturn($lat);
        $place->method('getLongitude')->willReturn($lon);
        $place->method('getCategory')->willReturn($category);
        $place->method('getName')->willReturn($name ?? 'Place '.$id);
        $place->method('getAddress')->willReturn($address ?? 'Address '.$id);
        $place->method('getPlaceType')->willReturn(null);
        $place->method('getPhone')->willReturn(null);
        $place->method('getRating')->willReturn(null);
        $place->method('getReviewCount')->willReturn(null);
        $place->method('getDescription')->willReturn(null);
        $place->method('getMetadata')->willReturn(null);
        $date = new \DateTime();
        $place->method('getCreatedAt')->willReturn($date);
        return $place;
    }

    // ================= findNearbyPlaces =================

    public function testFindNearbyPlacesValidatesLatitudeBounds(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid latitude');
        $this->service->findNearbyPlaces(-100, 10);
    }

    public function testFindNearbyPlacesValidatesLongitudeBounds(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid longitude');
        $this->service->findNearbyPlaces(10, 200);
    }

    public function testFindNearbyPlacesValidatesRadiusPositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Radius must be between');
        $this->service->findNearbyPlaces(10, 10, 0);
    }

    public function testFindNearbyPlacesValidatesRadiusMax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Radius must be between');
        $this->service->findNearbyPlaces(10, 10, 200000);
    }

    // ================= findNearbyPlaces Category Filter =================

    public function testFindNearbyPlacesAppliesCategoryFilter(): void
    {
        $lat = 10.0;
        $lon = 20.0;
        $radius = 1000;
        $category = 'restaurant';

        $place = $this->createPlaceMock('1', 10.008, 20.0, $category, 'Near');

        // Capture setParameter calls to verify category
        $setParamCalls = [];
        $this->qb->expects($this->atLeastOnce())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$setParamCalls) {
                $setParamCalls[] = ['name' => $name, 'value' => $value];
                return $this->qb;
            });

        $this->qb->expects($this->once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('from')
            ->with(Place::class, 'p')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([$place]);

        $result = $this->service->findNearbyPlaces($lat, $lon, $radius, $category);

        $this->assertCount(1, $result);
        $this->assertSame('Near', $result[0]['name']);
        // Verify category parameter was set
        $this->assertTrue(in_array(['name' => 'category', 'value' => $category], $setParamCalls, true));
    }

    public function testFindNearbyPlacesFiltersByExactDistanceUsingHaversine(): void
    {
        $lat = 10.0;
        $lon = 20.0;
        $radius = 1000;

        $place1 = $this->createPlaceMock('1', 10.008, 20.0, null, 'Near'); // ~900m
        $place2 = $this->createPlaceMock('2', 10.02, 20.0, null, 'Far'); // ~2.2km

        $this->qb->expects($this->once())->method('getQuery')->willReturn($this->query);
        $this->query->expects($this->once())->method('getResult')->willReturn([$place1, $place2]);

        $result = $this->service->findNearbyPlaces($lat, $lon, $radius);
        $this->assertCount(1, $result);
        $this->assertSame('Near', $result[0]['name']);
    }

    public function testFindNearbyPlacesSortsByDistanceAscending(): void
    {
        $lat = 10.0;
        $lon = 20.0;
        $radius = 5000;

        // Both places within radius; near is closer
        $placeNear = $this->createPlaceMock('n', 10.001, 20.0, null, 'Near');
        $placeFar = $this->createPlaceMock('f', 10.002, 20.0, null, 'Far');

        $this->qb->expects($this->once())->method('getQuery')->willReturn($this->query);
        $this->query->expects($this->once())->method('getResult')->willReturn([$placeNear, $placeFar]);

        $result = $this->service->findNearbyPlaces($lat, $lon, $radius);
        // Sorting should put Near first, Far second
        $this->assertSame('Near', $result[0]['name']);
        $this->assertSame('Far', $result[1]['name']);
        $this->assertLessThan($result[1]['distance'], $result[0]['distance']);
    }

    public function testFindNearbyPlacesIncludesDistanceInSerialization(): void
    {
        $lat = 10.0;
        $lon = 20.0;
        $radius = 1000;

        $place = $this->createPlaceMock('p1', 10.008, 20.0);
        $this->qb->expects($this->once())->method('getQuery')->willReturn($this->query);
        $this->query->expects($this->once())->method('getResult')->willReturn([$place]);

        $result = $this->service->findNearbyPlaces($lat, $lon, $radius);
        $this->assertArrayHasKey('distance', $result[0]);
        $this->assertIsFloat($result[0]['distance']);
    }

    // ================= getPlaceById =================

    public function testGetPlaceByIdReturnsNullWhenNotFound(): void
    {
        $this->em->method('find')->with(Place::class, 'nonexistent')->willReturn(null);
        $result = $this->service->getPlaceById('nonexistent');
        $this->assertNull($result);
    }

    public function testGetPlaceByIdReturnsSerializedPlaceWhenFound(): void
    {
        $place = $this->createPlaceMock('pid', 10.0, 20.0, 'cat', 'Place Name', 'Addr');
        $this->em->method('find')->with(Place::class, 'pid')->willReturn($place);
        $result = $this->service->getPlaceById('pid');
        $this->assertIsArray($result);
        $this->assertSame('pid', $result['id']);
        $this->assertSame('Place Name', $result['name']);
    }

    // ================= getDirections =================

    public function testGetDirectionsValidatesOriginFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be in "lat,lon" format');
        $this->service->getDirections('invalid', '30,40');
    }

    public function testGetDirectionsParsesCoordinates(): void
    {
        $result = $this->service->getDirections('10.0,20.0', '30.0,40.0');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('distance_meters', $result);
        $this->assertArrayHasKey('duration_seconds', $result);
        $this->assertArrayHasKey('steps', $result);
        $this->assertGreaterThan(0, $result['distance_meters']);
    }

    public function testGetDirectionsThrowsForInvalidLatitude(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getDirections('100,20', '30,40');
    }

    public function testGetDirectionsThrowsForInvalidLongitude(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getDirections('10,200', '30,40');
    }

    public function testGetDirectionsCalculatesDistanceCorrectly(): void
    {
        $result = $this->service->getDirections('0,0', '0,1');
        $distance = $result['distance_meters'];
        // ~111 km per degree at equator
        $this->assertGreaterThan(100000, $distance);
        $this->assertLessThan(113000, $distance);
    }

    // ================= calculateDistance =================

    public function testCalculateDistanceKnownPoints(): void
    {
        $dist = $this->service->calculateDistance(0, 0, 0, 1);
        $this->assertEqualsWithDelta(111319, $dist, 200);
    }

    public function testCalculateDistanceAntipodal(): void
    {
        $dist = $this->service->calculateDistance(0, 0, 0, 180);
        $this->assertEqualsWithDelta(20037508, $dist, 50000);
    }

    // ================= searchPlaces (partial) =================

    public function testSearchPlacesValidatesQueryNotEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');
        $this->service->searchPlaces('   ');
    }

    // ================= searchPlaces =================

    public function testSearchPlacesClampsLimitToMax(): void
    {
        $query = 'test';
        $limit = 200; // exceeds MAX_SEARCH_LIMIT 100

        // Verify limit is clamped to MAX_SEARCH_LIMIT (100)
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with(100)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->service->searchPlaces($query, $limit);
    }

    public function testSearchPlacesClampsLimitToMinimum(): void
    {
        $query = 'test';
        $limit = -5; // below minimum 1

        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with(1) // clamped to minimum 1
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->service->searchPlaces($query, $limit);
    }

    public function testSearchPlacesBuildsLikeQuery(): void
    {
        $query = 'coffee';
        $limit = 20;

        $place = $this->createPlaceMock('p1', 10.0, 20.0, null, 'Coffee Shop', '123 Main St');

        // Capture setParameter to verify binding with wildcards
        $setParamCalls = [];
        $this->qb->expects($this->atLeastOnce())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$setParamCalls) {
                $setParamCalls[] = ['name' => $name, 'value' => $value];
                return $this->qb;
            });

        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([$place]);

        $result = $this->service->searchPlaces($query, $limit);

        $this->assertCount(1, $result);
        $this->assertSame('Coffee Shop', $result[0]['name']);
        // Verify query parameter was bound with wildcards
        $this->assertTrue(in_array(['name' => 'q', 'value' => '%coffee%'], $setParamCalls, true));
    }

    public function testSearchPlacesReturnsSerializedPlaces(): void
    {
        $query = 'test';
        $limit = 10;

        $place = $this->createPlaceMock('p1', 10.0, 20.0, 'cat', 'Test Place', 'Test Address');

        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([$place]);

        $result = $this->service->searchPlaces($query, $limit);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('p1', $result[0]['id']);
        $this->assertSame('Test Place', $result[0]['name']);
        $this->assertSame('Test Address', $result[0]['address']);
        // searchPlaces does not compute distance; ensure no distance key
        $this->assertArrayNotHasKey('distance', $result[0]);
    }

    // ================= Serialization =================

    public function testSerializationIncludesAllFields(): void
    {
        // Create a dedicated mock with all fields set
        $place = $this->createMock(Place::class);
        $place->method('getId')->willReturn('abc');
        $place->method('getName')->willReturn('My Place');
        $place->method('getAddress')->willReturn('Addy');
        $place->method('getLatitude')->willReturn(10.0);
        $place->method('getLongitude')->willReturn(20.0);
        $place->method('getCategory')->willReturn('cat');
        $place->method('getPlaceType')->willReturn('type1');
        $place->method('getPhone')->willReturn('+123456789');
        $place->method('getRating')->willReturn(4.5);
        $place->method('getReviewCount')->willReturn(123);
        $place->method('getDescription')->willReturn('Desc');
        $place->method('getMetadata')->willReturn(['key' => 'val']);
        $date = new \DateTime('2024-01-01 12:00:00');
        $place->method('getCreatedAt')->willReturn($date);

        $this->em->method('find')->with(Place::class, 'abc')->willReturn($place);
        $result = $this->service->getPlaceById('abc');

        $this->assertSame('abc', $result['id']);
        $this->assertSame('My Place', $result['name']);
        $this->assertSame('Addy', $result['address']);
        $this->assertSame(10.0, $result['latitude']);
        $this->assertSame(20.0, $result['longitude']);
        $this->assertSame('cat', $result['category']);
        $this->assertSame('type1', $result['place_type']);
        $this->assertSame('+123456789', $result['phone']);
        $this->assertSame(4.5, $result['rating']);
        $this->assertSame(123, $result['review_count']);
        $this->assertSame('Desc', $result['description']);
        $this->assertSame(['key' => 'val'], $result['metadata']);
        $this->assertSame('2024-01-01T12:00:00+00:00', $result['created_at']);
    }
}
