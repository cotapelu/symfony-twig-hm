<?php

declare(strict_types=1);

namespace App\Maps\Service;

use App\Maps\Entity\Place;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class MapsService
{
    private const MAX_RADIUS_METERS = 100000; // 100km
    private const MAX_SEARCH_LIMIT = 100;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findNearbyPlaces(float $latitude, float $longitude, int $radiusMeters = 1000, ?string $category = null)
    {
        $this->validateLatitudeLongitude($latitude, $longitude);
        $this->validateRadius($radiusMeters);

        $qb = $this->createBaseQuery($latitude, $longitude, $radiusMeters, $category);
        $places = $qb->getQuery()->getResult();

        return $this->filterAndSort($places, $latitude, $longitude, $radiusMeters);
    }

    private function validateLatitudeLongitude(float $lat, float $lon): void
    {
        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException('Invalid latitude: must be between -90 and 90');
        }
        if ($lon < -180 || $lon > 180) {
            throw new InvalidArgumentException('Invalid longitude: must be between -180 and 180');
        }
    }

    private function validateRadius(int $radiusMeters): void
    {
        if ($radiusMeters <= 0 || $radiusMeters > self::MAX_RADIUS_METERS) {
            throw new InvalidArgumentException(sprintf('Radius must be between 1 and %d meters', self::MAX_RADIUS_METERS));
        }
    }

    private function createBaseQuery(float $latitude, float $longitude, int $radiusMeters, ?string $category)
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Place::class, 'p')
            ->where('p.latitude BETWEEN :lat_min AND :lat_max')
            ->andWhere('p.longitude BETWEEN :lon_min AND :lon_max')
            ->setParameter('lat_min', $latitude - ($radiusMeters / 111320))
            ->setParameter('lat_max', $latitude + ($radiusMeters / 111320))
            ->setParameter('lon_min', $longitude - ($radiusMeters / (111320 * cos(deg2rad($latitude)))))
            ->setParameter('lon_max', $longitude + ($radiusMeters / (111320 * cos(deg2rad($latitude)))));

        if ($category !== null) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        return $qb;
    }

    private function filterAndSort(array $places, float $latitude, float $longitude, int $radiusMeters): array
    {
        $result = [];
        foreach ($places as $place) {
            $distance = $this->calculateDistance($latitude, $longitude, $place->getLatitude(), $place->getLongitude());
            if ($distance <= $radiusMeters) {
                $result[] = $this->serializePlace($place, $distance);
            }
        }
        usort($result, fn($a, $b) => $a['distance'] <=> $b['distance']);
        return $result;
    }

    public function searchPlaces(string $query, int $limit = 20)
    {
        $this->validateSearchQuery($query);
        $limit = $this->clampLimit($limit);
        $qb = $this->buildSearchQuery($query, $limit);
        $places = $qb->getQuery()->getResult();
        return $this->serializePlaces($places);
    }

    private function validateSearchQuery(string $query): void
    {
        if (empty(trim($query))) {
            throw new InvalidArgumentException('Search query cannot be empty');
        }
    }

    private function clampLimit(int $limit): int
    {
        return min(self::MAX_SEARCH_LIMIT, max(1, $limit));
    }

    private function buildSearchQuery(string $query, int $limit)
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Place::class, 'p')
            ->where('p.name LIKE :q')
            ->orWhere('p.address LIKE :q')
            ->orWhere('p.description LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->setMaxResults($limit);
    }

    private function serializePlaces(array $places): array
    {
        $result = [];
        foreach ($places as $place) {
            $result[] = $this->serializePlace($place);
        }
        return $result;
    }

    public function getPlaceById(string $placeId)
    {
        $place = $this->entityManager->find(Place::class, $placeId);
        if (!$place) {
            return null;
        }
        return $this->serializePlace($place);
    }

    public function getDirections(string $origin, string $destination)
    {
        [$originCoord, $destCoord] = $this->parseCoordinates($origin, $destination);
        $this->validateCoordinates($originCoord, $destCoord);

        $distance = $this->calculateDistance($originCoord['lat'], $originCoord['lon'], $destCoord['lat'], $destCoord['lon']);
        $duration = $distance / 16.67;

        return [
            'distance_meters' => $distance,
            'duration_seconds' => $duration,
            'steps' => [
                [
                    'instruction' => 'Head from origin',
                    'distance' => $distance,
                    'duration' => $duration,
                    'start_lat' => $originCoord['lat'],
                    'start_lon' => $originCoord['lon'],
                    'end_lat' => $destCoord['lat'],
                    'end_lon' => $destCoord['lon']
                ]
            ]
        ];
    }

    private function parseCoordinates(string $origin, string $destination): array
    {
        $originParts = explode(',', $origin);
        $destParts = explode(',', $destination);

        if (count($originParts) !== 2 || count($destParts) !== 2) {
            throw new InvalidArgumentException('Origin and destination must be in "lat,lon" format');
        }

        return [
            $this->createCoordinate($originParts),
            $this->createCoordinate($destParts)
        ];
    }

    private function createCoordinate(array $parts): array
    {
        return [
            'lat' => (float) trim($parts[0]),
            'lon' => (float) trim($parts[1])
        ];
    }

    private function validateCoordinates(array $origin, array $dest): void
    {
        if ($this->isInvalidLat($origin['lat']) || $this->isInvalidLat($dest['lat'])) {
            throw new InvalidArgumentException('Invalid latitude in coordinates');
        }
        if ($this->isInvalidLon($origin['lon']) || $this->isInvalidLon($dest['lon'])) {
            throw new InvalidArgumentException('Invalid longitude in coordinates');
        }
    }

    private function isInvalidLat(float $lat): bool
    {
        return $lat < -90 || $lat > 90;
    }

    private function isInvalidLon(float $lon): bool
    {
        return $lon < -180 || $lon > 180;
    }

    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2)
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function serializePlace(Place $place, ?float $distance = null)
    {
        $data = [
            'id' => $place->getId(),
            'name' => $place->getName(),
            'address' => $place->getAddress(),
            'latitude' => (float) $place->getLatitude(),
            'longitude' => (float) $place->getLongitude(),
            'category' => $place->getCategory(),
            'place_type' => $place->getPlaceType(),
            'phone' => $place->getPhone(),
            'rating' => $place->getRating(),
            'review_count' => $place->getReviewCount(),
            'description' => $place->getDescription(),
            'metadata' => $place->getMetadata(),
            'created_at' => $place->getCreatedAt()->format('c'),
        ];
        if ($distance !== null) {
            $data['distance'] = $distance;
        }
        return $data;
    }
}
