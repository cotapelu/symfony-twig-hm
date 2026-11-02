<?php

declare(strict_types=1);

namespace App\RideHailing\Controller;

use App\RideHailing\Service\RideHailingService;
use App\RideHailing\Service\DriverService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/ride-hailing')]
class RideHailingController extends BaseController
{
    private const MAX_ADDRESS_LENGTH = 500;
    private const MAX_REASON_LENGTH = 500;
    private const MAX_REVIEW_LENGTH = 1000;
    private const MAX_RADIUS = 50.0;
    private const MIN_RATING = 1.0;
    private const MAX_RATING = 5.0;
    private const ALLOWED_RIDE_TYPES = ['standard', 'premium', 'economy'];
    private const ALLOWED_PAYMENT_METHODS = ['cash', 'wallet', 'card'];

    public function __construct(
        private RideHailingService $rideService,
        private DriverService $driverService,
        private ?TokenStorageInterface $tokenStorage = null,
        ?AuthorizationCheckerInterface $authChecker = null
    ) {
        parent::__construct();
        $this->authChecker = $authChecker;
    }

    // ==================== Existing helpers ====================
    private function getCurrentUserId(): string
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Authentication required');
        }
        return $user->getUserIdentifier();
    }

    private function ensureRideOwnership(\App\RideHailing\Entity\Ride $ride, string $userId): void
    {
        $isRider = $ride->getRiderId() === $userId;
        $driver = $ride->getDriver();
        $isDriver = $driver && $driver->getUserId() === $userId;
        if (!$isRider && !$isDriver) {
            throw new AccessDeniedHttpException('Access denied to this ride');
        }
    }

    private function getDriverByCurrentUser(): ?\App\RideHailing\Entity\Driver
    {
        $userId = $this->getCurrentUserId();
        return $this->driverService->getDriverByUserId($userId);
    }

    private function logAction(string $action, array $context = []): void
    {
        $logData = [
            'timestamp' => (new DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
            'action' => $action,
            'context' => $context,
        ];
        error_log(json_encode($logData, JSON_UNESCAPED_UNICODE));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        $metricData = [
            'metric' => $metric,
            'tags' => $tags,
            'timestamp' => (new DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        ];
        error_log(json_encode($metricData));
    }

    private function sanitizeString(?string $value): string
    {
        return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function isAuthenticated(): bool
    {
        if ($this->tokenStorage === null || $this->authChecker === null) {
            return false;
        }
        return $this->tokenStorage->getToken() !== null
            && $this->authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }
        return (string) $user->getId();
    }

    private function requireAuth(): JsonResponse|true
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        return true;
    }

    private function validateCoordinate(float $lat, float $lng): ?string
    {
        if ($lat < -90 || $lat > 90) {
            return 'Invalid latitude (must be between -90 and 90)';
        }
        if ($lng < -180 || $lng > 180) {
            return 'Invalid longitude (must be between -180 and 180)';
        }
        return null;
    }

    private function validateRating(float $rating): ?string
    {
        if ($rating < self::MIN_RATING || $rating > self::MAX_RATING) {
            return 'Rating must be between ' . self::MIN_RATING . ' and ' . self::MAX_RATING;
        }
        return null;
    }

    // ==================== New Infrastructure ====================
    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            throw new InvalidArgumentException('Invalid JSON');
        }
        return $dataOr400 ?? [];
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->handleException('ride_hailing', $e);
        }
    }

    private function requireAuthOr401AsString(): string
    {
        $result = $this->requireAuthOr401();
        if ($result instanceof JsonResponse) {
            throw new AccessDeniedHttpException('Authentication required');
        }
        return $result;
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("RideHailingController::{$context} error: " . $e->getMessage());
        $this->logAction("{$context}_error", ['error' => $e->getMessage()]);
        $this->incrementMetric("{$context}_errors");
        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    // ==================== Validation Helpers ====================
    private function validateRequestRideData(array $data): void
    {
        $required = ['pickupLatitude', 'pickupLongitude', 'pickupAddress', 'estimatedPrice', 'estimatedDuration'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing field: $field");
            }
        }
        $error = $this->validateCoordinate((float)$data['pickupLatitude'], (float)$data['pickupLongitude']);
        if ($error) {
            throw new InvalidArgumentException($error);
        }
        if (strlen($data['pickupAddress']) > self::MAX_ADDRESS_LENGTH) {
            throw new InvalidArgumentException('Pickup address too long');
        }
        if (!empty($data['dropoffAddress']) && strlen($data['dropoffAddress']) > self::MAX_ADDRESS_LENGTH) {
            throw new InvalidArgumentException('Dropoff address too long');
        }
        if ($data['estimatedPrice'] < 0 || $data['estimatedDuration'] < 0) {
            throw new InvalidArgumentException('Price and duration must be non-negative');
        }
    }

    private function validateRateRideData(array $data): void
    {
        if (!isset($data['driverRating'])) {
            throw new InvalidArgumentException('Missing driverRating');
        }
        $error = $this->validateRating((float)$data['driverRating']);
        if ($error) {
            throw new InvalidArgumentException($error);
        }
        if (isset($data['riderRating'])) {
            $error = $this->validateRating((float)$data['riderRating']);
            if ($error) {
                throw new InvalidArgumentException($error);
            }
        }
        if (!empty($data['review']) && strlen($data['review']) > self::MAX_REVIEW_LENGTH) {
            throw new InvalidArgumentException('Review too long');
        }
    }

    // ==================== Endpoints (thin wrappers) ====================
    #[Route('/request', name: 'ride_request', methods: ['POST'])]
    public function requestRide(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->requestRideAction($request));
    }

    #[Route('/{id}/accept', name: 'ride_accept', methods: ['POST'])]
    public function acceptRide(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->acceptRideAction($id));
    }

    #[Route('/{id}/arrive', name: 'ride_arrive', methods: ['POST'])]
    public function driverArrived(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->driverArrivedAction($id));
    }

    #[Route('/{id}/start', name: 'ride_start', methods: ['POST'])]
    public function startRide(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->startRideAction($id));
    }

    #[Route('/{id}/complete', name: 'ride_complete', methods: ['POST'])]
    public function completeRide(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->completeRideAction($id, $request));
    }

    #[Route('/{id}/cancel', name: 'ride_cancel', methods: ['POST'])]
    public function cancelRide(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->cancelRideAction($id, $request));
    }

    #[Route('/{id}/rate', name: 'ride_rate', methods: ['POST'])]
    public function rateRide(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->rateRideAction($request, $id));
    }

    #[Route('/drivers/nearby', name: 'drivers_nearby', methods: ['GET'])]
    public function nearbyDrivers(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->nearbyDriversAction($request));
    }

    #[Route('/history/{userId}', name: 'ride_history', methods: ['GET'])]
    public function getRideHistory(string $userId): JsonResponse
    {
        return $this->handle(fn() => $this->getRideHistoryAction($userId));
    }

    #[Route('/active/{userId}', name: 'ride_active', methods: ['GET'])]
    public function getActiveRide(string $userId): JsonResponse
    {
        return $this->handle(fn() => $this->getActiveRideAction($userId));
    }

    #[Route('/driver/online', name: 'driver_online', methods: ['POST'])]
    public function setDriverOnline(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->setDriverOnlineAction($request));
    }

    #[Route('/driver/offline', name: 'driver_offline', methods: ['POST'])]
    public function setDriverOffline(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->setDriverOfflineAction($request));
    }

    #[Route('/driver/location', name: 'driver_location', methods: ['POST'])]
    public function updateDriverLocation(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateDriverLocationAction($request));
    }

    // ==================== Private Actions ====================
    private function requestRideAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $data = $this->getJsonData($request);
        if (empty($data)) {
            throw new InvalidArgumentException('Invalid JSON payload');
        }
        $this->validateRequestRideData($data);
        $data['riderId'] = $userId;
        $ride = $this->rideService->requestRide($data);
        $this->logAction('ridehailing.ride.requested', ['rideId' => $ride->getId(), 'riderId' => $userId]);
        $this->incrementMetric('ridehailing.ride.requested');
        return $this->json([
            'success' => true,
            'ride' => $this->serializeRide($ride)
        ]);
    }

    private function acceptRideAction(int $id): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $driver = $this->getDriverByCurrentUser();
        if (!$driver) {
            throw new NotFoundHttpException('Driver not registered');
        }

        $ride = $this->rideService->acceptRide($id, $driver->getId());
        if (!$ride) {
            throw new InvalidArgumentException('Unable to accept ride');
        }

        $this->logAction('ridehailing.ride.accepted', ['rideId' => $id, 'driverId' => $driver->getId()]);
        $this->incrementMetric('ridehailing.ride.accepted');

        return $this->json(['success' => true, 'ride' => $this->serializeRide($ride)]);
    }

    private function driverArrivedAction(int $id): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $ride = $this->rideService->driverArrived($id);
        if (!$ride) {
            throw new InvalidArgumentException('Ride not found or invalid status');
        }

        $driver = $ride->getDriver();
        if (!$driver || $driver->getUserId() !== $userId) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $this->logAction('ridehailing.ride.arrived', ['rideId' => $id]);

        return $this->json(['success' => true, 'ride' => $this->serializeRide($ride)]);
    }

    private function startRideAction(int $id): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $ride = $this->rideService->startRide($id);
        if (!$ride) {
            throw new InvalidArgumentException('Ride not found or invalid status');
        }

        $driver = $ride->getDriver();
        if (!$driver || $driver->getUserId() !== $userId) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $this->logAction('ridehailing.ride.started', ['rideId' => $id]);

        return $this->json(['success' => true, 'ride' => $this->serializeRide($ride)]);
    }

    private function completeRideAction(int $id, Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $data = $this->getJsonData($request);

        $required = ['actualPrice', 'actualDuration', 'distance'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing field: $field");
            }
        }

        if ($data['actualPrice'] < 0 || $data['actualDuration'] < 0 || $data['distance'] < 0) {
            throw new InvalidArgumentException('Price, duration and distance must be non-negative');
        }

        $ride = $this->rideService->completeRide($id, (float)$data['actualPrice'], (int)$data['actualDuration'], (int)$data['distance']);
        if (!$ride) {
            throw new InvalidArgumentException('Ride not found or invalid status');
        }

        $this->ensureRideOwnership($ride, $userId);

        $this->logAction('ridehailing.ride.completed', ['rideId' => $id, 'price' => $data['actualPrice']]);
        $this->incrementMetric('ridehailing.ride.completed');

        return $this->json([
            'success' => true,
            'ride' => $this->serializeRide($ride),
            'payment' => $this->serializePayment($ride->getPayment())
        ]);
    }

    private function cancelRideAction(int $id, Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $data = $this->getJsonData($request);
        $reason = $data['reason'] ?? 'Cancelled by user';
        if (strlen($reason) > self::MAX_REASON_LENGTH) {
            throw new InvalidArgumentException('Reason too long');
        }

        $ride = $this->rideService->cancelRide($id, $this->sanitizeString($reason));
        if (!$ride) {
            throw new InvalidArgumentException('Ride not found or cannot be cancelled');
        }

        if ($ride->getRiderId() !== $userId) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $this->logAction('ridehailing.ride.cancelled', ['rideId' => $id, 'reason' => $reason]);
        $this->incrementMetric('ridehailing.ride.cancelled');

        return $this->json(['success' => true, 'ride' => $this->serializeRide($ride)]);
    }

    private function rateRideAction(Request $request, int $id): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $data = $this->getJsonData($request);
        if (empty($data)) {
            throw new InvalidArgumentException('Invalid JSON payload');
        }
        $this->validateRateRideData($data);
        $ride = $this->rideService->rateRide(
            $id,
            (float) $data['driverRating'],
            $data['review'] ?? null,
            isset($data['riderRating']) ? (float) $data['riderRating'] : null
        );
        if (!$ride) {
            throw new InvalidArgumentException('Ride not found or not completed');
        }
        $this->ensureRideOwnership($ride, $userId);
        $this->logAction('ridehailing.ride.rated', ['rideId' => $id]);
        return $this->json(['success' => true, 'ride' => $this->serializeRide($ride)]);
    }

    private function nearbyDriversAction(Request $request): JsonResponse
    {
        $latitude = (float) $request->query->get('latitude');
        $longitude = (float) $request->query->get('longitude');
        $radius = (float) $request->query->get('radius', 5.0);
        $vehicleType = $request->query->get('vehicleType');

        if (!$latitude || !$longitude) {
            throw new InvalidArgumentException('Missing latitude or longitude');
        }

        $error = $this->validateCoordinate($latitude, $longitude);
        if ($error) {
            throw new InvalidArgumentException($error);
        }

        if ($radius < 0 || $radius > self::MAX_RADIUS) {
            throw new InvalidArgumentException('Radius must be between 0 and ' . self::MAX_RADIUS);
        }

        $drivers = $this->rideService->findNearbyDrivers($latitude, $longitude, $radius, $vehicleType);
        if ($drivers === null) {
            $drivers = [];
        }

        return $this->json([
            'success' => true,
            'drivers' => array_map(fn($d) => $this->serializeDriver($d), $drivers)
        ]);
    }

    private function getRideHistoryAction(string $userId): JsonResponse
    {
        $currentUserId = $this->requireAuthOr401AsString();
        if ($userId !== $currentUserId) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $rides = $this->rideService->getRideHistory($userId);

        return $this->json([
            'success' => true,
            'rides' => array_map(fn($r) => $this->serializeRide($r), $rides)
        ]);
    }

    private function getActiveRideAction(string $userId): JsonResponse
    {
        $currentUserId = $this->requireAuthOr401AsString();
        if ($userId !== $currentUserId) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $ride = $this->rideService->getActiveRide($userId);

        return $this->json([
            'success' => true,
            'ride' => $ride ? $this->serializeRide($ride) : null
        ]);
    }

    private function setDriverOnlineAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $driver = $this->getDriverByCurrentUser();
        if (!$driver) {
            throw new NotFoundHttpException('Driver not registered');
        }

        $data = $this->getJsonData($request);
        if (empty($data)) {
            throw new InvalidArgumentException('Invalid JSON payload');
        }

        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            throw new InvalidArgumentException('Missing latitude or longitude');
        }

        $error = $this->validateCoordinate((float)$data['latitude'], (float)$data['longitude']);
        if ($error) {
            throw new InvalidArgumentException($error);
        }

        $driver = $this->driverService->setOnline(
            $driver->getId(),
            (float) $data['latitude'],
            (float) $data['longitude']
        );

        if (!$driver) {
            throw new \Exception('Failed to set driver online');
        }

        return $this->json(['success' => true, 'driver' => $this->serializeDriver($driver)]);
    }

    private function setDriverOfflineAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $driver = $this->getDriverByCurrentUser();
        if (!$driver) {
            throw new NotFoundHttpException('Driver not registered');
        }

        $driver = $this->driverService->setOffline($driver->getId());
        if (!$driver) {
            throw new \Exception('Failed to set driver offline');
        }

        return $this->json(['success' => true]);
    }

    private function updateDriverLocationAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $driver = $this->getDriverByCurrentUser();
        if (!$driver) {
            throw new NotFoundHttpException('Driver not registered');
        }

        $data = $this->getJsonData($request);
        if (empty($data)) {
            throw new InvalidArgumentException('Invalid JSON payload');
        }

        $required = ['latitude', 'longitude'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing field: $field");
            }
        }

        $error = $this->validateCoordinate((float)$data['latitude'], (float)$data['longitude']);
        if ($error) {
            throw new InvalidArgumentException($error);
        }

        $this->rideService->updateDriverLocation(
            $driver->getId(),
            (float) $data['latitude'],
            (float) $data['longitude']
        );

        return $this->json(['success' => true]);
    }

    // ==================== Serialization Helpers ====================
    private function serializeRide($ride): array
    {
        return [
            'id' => $ride->getId(),
            'rideNumber' => $ride->getRideNumber(),
            'status' => $ride->getStatus(),
            'rideType' => $ride->getRideType(),
            'pickupAddress' => $ride->getPickupAddress(),
            'dropoffAddress' => $ride->getDropoffAddress(),
            'estimatedPrice' => $ride->getEstimatedPrice(),
            'estimatedDuration' => $ride->getEstimatedDuration(),
            'distance' => $ride->getDistance(),
            'driverRating' => $ride->getDriverRating(),
            'createdAt' => $ride->getRequestedAt()->format('c'),
        ];
    }

    private function serializeDriver($driver): array
    {
        return [
            'id' => $driver->getId(),
            'userId' => $driver->getUserId(),
            'vehicleType' => $driver->getVehicleType(),
            'vehicleColor' => $driver->getVehicleColor(),
            'rating' => $driver->getRating(),
            'status' => $driver->getStatus(),
            'latitude' => $driver->getCurrentLatitude(),
            'longitude' => $driver->getCurrentLongitude(),
        ];
    }

    private function serializePayment($payment): ?array
    {
        if (!$payment) {
            return null;
        }
        return [
            'transactionId' => $payment->getTransactionId(),
            'amount' => $payment->getAmount(),
            'status' => $payment->getStatus(),
            'driverEarning' => $payment->getDriverEarning(),
        ];
    }
}