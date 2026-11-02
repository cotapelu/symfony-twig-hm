<?php

declare(strict_types=1);

namespace App\Maps\Controller;

use App\Maps\Service\MapsService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/maps')]
class MapsController extends BaseController
{
    private MapsService $mapsService;

    public function __construct(MapsService $mapsService)
    {
        $this->mapsService = $mapsService;
    }

    private function logAction(string $action, array $context = []): void
    {
        $userId = $this->getUser()?->getUserIdentifier() ?? 'anonymous';
        $timestamp = new DateTimeImmutable();
        error_log(json_encode([
            'timestamp' => $timestamp->format(DateTimeImmutable::ATOM),
            'action' => $action,
            'userId' => $userId,
            'module' => 'Maps',
            'context' => $context
        ]));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode([
            'metric' => $metric,
            'tags' => $tags,
            'timestamp' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM)
        ]));
    }

    #[Route('/nearby', name: 'maps_find_nearby', methods: ['GET'])]
    public function findNearby(Request $request): JsonResponse
    {
        try {
            $lat = (float) $request->query->get('lat');
            $lon = (float) $request->query->get('lon');
            $radius = (int) $request->query->get('radius', 1000);
            $category = $request->query->get('category');

            $places = $this->mapsService->findNearbyPlaces($lat, $lon, $radius, $category);

            $this->logAction('nearby_places_searched', ['lat' => $lat, 'lon' => $lon, 'radius' => $radius, 'category' => $category, 'count' => count($places)]);
            $this->incrementMetric('maps_nearby_total', ['category' => $category ?? 'all']);

            return $this->json(['status' => 200, 'data' => $places]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['status' => 400, 'error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->logAction('find_nearby_failed', ['error' => $e->getMessage()]);
            return $this->json(['status' => 500, 'error' => 'Internal server error'], 500);
        }
    }

    #[Route('/search', name: 'maps_search_places', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        try {
            $query = trim($request->query->get('q', ''));
            $limit = (int) $request->query->get('limit', 20);

            if ($query === '') {
                throw new BadRequestHttpException('Query parameter "q" is required');
            }

            $places = $this->mapsService->searchPlaces($query, $limit);

            $this->logAction('places_searched', ['query' => $query, 'limit' => $limit, 'count' => count($places)]);
            $this->incrementMetric('maps_search_total');

            return $this->json(['status' => 200, 'data' => $places]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['status' => 400, 'error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->logAction('search_places_failed', ['error' => $e->getMessage()]);
            return $this->json(['status' => 500, 'error' => 'Internal server error'], 500);
        }
    }

    #[Route('/directions', name: 'maps_get_directions', methods: ['GET'])]
    public function getDirections(Request $request): JsonResponse
    {
        try {
            $origin = $request->query->get('origin');
            $destination = $request->query->get('destination');

            if (!$origin || !$destination) {
                throw new BadRequestHttpException('Origin and destination are required');
            }

            $directions = $this->mapsService->getDirections($origin, $destination);

            $this->logAction('directions_retrieved', ['origin' => $origin, 'destination' => $destination]);
            $this->incrementMetric('maps_directions_total');

            return $this->json(['status' => 200, 'data' => $directions]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['status' => 400, 'error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->logAction('get_directions_failed', ['error' => $e->getMessage()]);
            return $this->json(['status' => 500, 'error' => 'Internal server error'], 500);
        }
    }

    #[Route('/places/{id}', name: 'maps_get_place', methods: ['GET'])]
    public function getPlace(Request $request, string $id): JsonResponse
    {
        try {
            $place = $this->mapsService->getPlaceById($id);
            if (!$place) {
                throw new NotFoundHttpException('Place not found');
            }

            $this->logAction('place_viewed', ['place_id' => $id]);
            $this->incrementMetric('maps_place_view_total');

            return $this->json(['status' => 200, 'data' => $place]);
        } catch (NotFoundHttpException $e) {
            return $this->json(['status' => 404, 'error' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            $this->logAction('get_place_failed', ['error' => $e->getMessage()]);
            return $this->json(['status' => 500, 'error' => 'Internal server error'], 500);
        }
    }
}
