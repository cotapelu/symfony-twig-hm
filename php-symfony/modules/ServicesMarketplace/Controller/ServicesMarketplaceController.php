<?php declare(strict_types=1);

namespace App\ServicesMarketplace\Controller;

use App\Shared\Controller\BaseController;
use App\ServicesMarketplace\Service\ServicesMarketplaceService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use InvalidArgumentException;

#[Route('/api/services-marketplace')]
class ServicesMarketplaceController extends BaseController
{
    private const MAX_NAME_LENGTH = 255;
    private const MAX_DESCRIPTION_LENGTH = 1000;
    private const MAX_ADDRESS_LENGTH = 500;
    private const MAX_PHONE_LENGTH = 20;
    private const MAX_PRICE = 100000000;
    private const MAX_COMMENT_LENGTH = 1000;
    private const MAX_RADIUS = 50.0;
    private const MAX_LIMIT = 100;
    private const MIN_RATING = 1.0;
    private const MAX_RATING = 5.0;
    private const ALLOWED_PAYMENT_METHODS = ['cash', 'wallet', 'card'];
    private const ALLOWED_STATUSES = ['pending', 'active', 'suspended'];
    private const ALLOWED_BOOKING_STATUSES = ['PENDING', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'];
    private const ALLOWED_PAYMENT_STATUSES = ['PENDING', 'PAID', 'FAILED', 'REFUNDED'];

    public function __construct(
        private \App\ServicesMarketplace\Service\ServicesMarketplaceService $service,
        private ?TokenStorageInterface $tokenStorage = null,
        ?AuthorizationCheckerInterface $authChecker = null
    ) {
        parent::__construct();
        $this->authChecker = $authChecker;
    }

    // === Existing helpers (unchanged) ===
    private function logAction(string $action, array $context = []): void
    {
        $logData = [
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
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
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
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

    private function getAuthenticatedUserId(): ?int
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return null;
        }
        $user = $token->getUser();
        if (!$user || !method_exists($user, 'getId')) {
            return null;
        }
        return (int) $user->getId();
    }

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
        }
        return (string) $user->getId();
    }

    private function requireAuth(): JsonResponse|true
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['success' => false, 'error' => 'Authentication required'], 401);
        }
        return true;
    }

    private function validateCoordinate(?float $lat, ?float $lng): ?string
    {
        if ($lat !== null && ($lat < -90 || $lat > 90)) {
            return 'Invalid latitude';
        }
        if ($lng !== null && ($lng < -180 || $lng > 180)) {
            return 'Invalid longitude';
        }
        return null;
    }

    private function validateRating(?float $rating): ?string
    {
        if ($rating !== null && ($rating < self::MIN_RATING || $rating > self::MAX_RATING)) {
            return 'Rating must be between ' . self::MIN_RATING . ' and ' . self::MAX_RATING;
        }
        return null;
    }

    private function validateRequiredFields(array $data, array $required): array
    {
        $errors = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $errors[] = "Missing required field: $field";
            }
        }
        return $errors;
    }

    private function validateRegisterProviderData(array $data): array
    {
        return array_merge(
            $this->validateRequiredFields($data, ['businessName', 'displayName', 'category', 'address', 'phone', 'startingPrice']),
            $this->validateBusinessNameLength($data),
            $this->validateDisplayNameLength($data),
            $this->validateDescriptionLength($data),
            $this->validateCoordinates($data),
            $this->validateStartingPrice($data),
            $this->validatePhoneLength($data)
        );
    }

    private function validateCreateBookingData(array $data): array
    {
        return array_merge(
            $this->validateRequiredFields($data, ['providerId', 'userId', 'address', 'scheduledAt', 'price']),
            $this->validateAddressLength($data),
            $this->validateCoordinates($data),
            $this->validatePrice($data),
            $this->validateScheduledAt($data),
            $this->validatePaymentMethod($data)
        );
    }

    // === Extracted validators ===
    private function validateBusinessNameLength(array $data): array
    {
        if (isset($data['businessName']) && strlen($data['businessName']) > self::MAX_NAME_LENGTH) {
            return ['Business name too long (max ' . self::MAX_NAME_LENGTH . ' characters)'];
        }
        return [];
    }

    private function validateDisplayNameLength(array $data): array
    {
        if (isset($data['displayName']) && strlen($data['displayName']) > self::MAX_NAME_LENGTH) {
            return ['Display name too long (max ' . self::MAX_NAME_LENGTH . ' characters)'];
        }
        return [];
    }

    private function validateDescriptionLength(array $data): array
    {
        if (isset($data['description']) && strlen($data['description']) > self::MAX_DESCRIPTION_LENGTH) {
            return ['Description too long (max ' . self::MAX_DESCRIPTION_LENGTH . ' characters)'];
        }
        return [];
    }

    private function validateCoordinates(array $data): array
    {
        if (isset($data['latitude']) || isset($data['longitude'])) {
            $coordError = $this->validateCoordinate($data['latitude'] ?? null, $data['longitude'] ?? null);
            if ($coordError) {
                return [$coordError];
            }
        }
        return [];
    }

    private function validateStartingPrice(array $data): array
    {
        if (isset($data['startingPrice'])) {
            $price = $data['startingPrice'];
            if ($price < 0 || $price > self::MAX_PRICE) {
                return ['Invalid starting price'];
            }
        }
        return [];
    }

    private function validatePhoneLength(array $data): array
    {
        if (isset($data['phone']) && strlen($data['phone']) > self::MAX_PHONE_LENGTH) {
            return ['Phone number too long (max ' . self::MAX_PHONE_LENGTH . ' characters)'];
        }
        return [];
    }

    private function validateAddressLength(array $data): array
    {
        if (isset($data['address']) && strlen($data['address']) > self::MAX_ADDRESS_LENGTH) {
            return ['Address too long (max ' . self::MAX_ADDRESS_LENGTH . ' characters)'];
        }
        return [];
    }

    private function validatePrice(array $data): array
    {
        if (isset($data['price'])) {
            $price = $data['price'];
            if ($price < 0 || $price > self::MAX_PRICE) {
                return ['Invalid price'];
            }
        }
        return [];
    }

    private function validateScheduledAt(array $data): array
    {
        if (isset($data['scheduledAt'])) {
            try {
                $scheduledAt = new \DateTime($data['scheduledAt']);
                if ($scheduledAt <= new \DateTime()) {
                    return ['Scheduled time must be in the future'];
                }
            } catch (\Exception $e) {
                return ['Invalid scheduledAt format'];
            }
        }
        return [];
    }

    private function validatePaymentMethod(array $data): array
    {
        if (isset($data['paymentMethod']) && !in_array($data['paymentMethod'], self::ALLOWED_PAYMENT_METHODS)) {
            return ['Invalid payment method'];
        }
        return [];
    }

    private function validateReviewInput(array $data): void
    {
        $required = ['bookingId', 'providerId', 'rating'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Missing field: $field");
            }
        }
        $ratingError = $this->validateRating((float)$data['rating']);
        if ($ratingError) {
            throw new InvalidArgumentException($ratingError);
        }
        if (!empty($data['comment']) && strlen($data['comment']) > self::MAX_COMMENT_LENGTH) {
            throw new InvalidArgumentException('Comment too long');
        }
    }

    // === New infrastructure ===
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
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->handleException('servicesmarketplace', $e);
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
        error_log("ServicesMarketplaceController::{$context} error: " . $e->getMessage());
        $this->logAction("{$context}_error", ['error' => $e->getMessage()]);
        $this->incrementMetric("{$context}_errors");
        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    // === Endpoints (thin wrappers) ===
    #[Route('/categories', name: 'sm_categories', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        return $this->handle(fn() => $this->getCategoriesAction());
    }

    #[Route('/providers/nearby', name: 'sm_nearby_providers', methods: ['GET'])]
    public function getNearbyProviders(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getNearbyProvidersAction($request));
    }

    #[Route('/providers/category/{category}', name: 'sm_providers_by_category', methods: ['GET'])]
    public function getProvidersByCategory(string $category): JsonResponse
    {
        return $this->handle(fn() => $this->getProvidersByCategoryAction($category));
    }

    #[Route('/providers/search', name: 'sm_search_providers', methods: ['GET'])]
    public function searchProviders(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->searchProvidersAction($request));
    }

    #[Route('/providers/{id}', name: 'sm_provider_detail', methods: ['GET'])]
    public function getProvider(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getProviderAction($id));
    }

    #[Route('/providers/register', name: 'sm_register_provider', methods: ['POST'])]
    public function registerProvider(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->registerProviderAction($request));
    }

    #[Route('/bookings', name: 'sm_create_booking', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createBookingAction($request));
    }

    #[Route('/bookings/{id}', name: 'sm_booking_detail', methods: ['GET'])]
    public function getBooking(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getBookingAction($id));
    }

    #[Route('/bookings/{id}/confirm', name: 'sm_confirm_booking', methods: ['POST'])]
    public function confirmBooking(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->confirmBookingAction($id));
    }

    #[Route('/bookings/{id}/start', name: 'sm_start_booking', methods: ['POST'])]
    public function startBooking(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->startBookingAction($id));
    }

    #[Route('/bookings/{id}/complete', name: 'sm_complete_booking', methods: ['POST'])]
    public function completeBooking(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->completeBookingAction($id));
    }

    #[Route('/bookings/{id}/cancel', name: 'sm_cancel_booking', methods: ['POST'])]
    public function cancelBooking(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->cancelBookingAction($id, $request));
    }

    #[Route('/customer/{userId}/bookings', name: 'sm_customer_bookings', methods: ['GET'])]
    public function getCustomerBookings(string $userId): JsonResponse
    {
        return $this->handle(fn() => $this->getCustomerBookingsAction($userId));
    }

    #[Route('/provider/{providerId}/bookings', name: 'sm_provider_bookings', methods: ['GET'])]
    public function getProviderBookings(int $providerId): JsonResponse
    {
        return $this->handle(fn() => $this->getProviderBookingsAction($providerId));
    }

    #[Route('/provider/{providerId}/stats', name: 'sm_provider_stats', methods: ['GET'])]
    public function getProviderStats(int $providerId): JsonResponse
    {
        return $this->handle(fn() => $this->getProviderStatsAction($providerId));
    }

    #[Route('/reviews', name: 'sm_add_review', methods: ['POST'])]
    public function addReview(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->addReviewAction($request));
    }

    // === Private Actions ===
    private function getCategoriesAction(): JsonResponse
    {
        $categories = $this->service->getCategories() ?? [];
        return $this->json([
            'success' => true,
            'categories' => array_map(fn($c) => [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'slug' => $c->getSlug(),
                'description' => $c->getDescription(),
                'iconUrl' => $c->getIconUrl(),
            ], $categories)
        ]);
    }

    private function getNearbyProvidersAction(Request $request): JsonResponse
    {
        $lat = (float) $request->query->get('latitude', 0);
        $lng = (float) $request->query->get('longitude', 0);
        $radius = (float) $request->query->get('radius', 10);

        if ($lat == 0 && $lng == 0) {
            throw new InvalidArgumentException('Latitude and longitude required');
        }

        $coordError = $this->validateCoordinate($lat, $lng);
        if ($coordError) {
            throw new InvalidArgumentException($coordError);
        }

        if ($radius < 0 || $radius > self::MAX_RADIUS) {
            $radius = 10;
        }

        $providers = $this->service->getNearbyProviders($lat, $lng, $radius) ?? [];
        return $this->json([
            'success' => true,
            'providers' => array_map(fn($p) => $this->serializeProvider($p), $providers)
        ]);
    }

    private function getProvidersByCategoryAction(string $category): JsonResponse
    {
        $providers = $this->service->getProvidersByCategory($category) ?? [];
        return $this->json([
            'success' => true,
            'providers' => array_map(fn($p) => $this->serializeProvider($p), $providers)
        ]);
    }

    private function searchProvidersAction(Request $request): JsonResponse
    {
        $keyword = $request->query->get('q', '');
        if (strlen($keyword) < 2) {
            throw new InvalidArgumentException('Search term too short');
        }
        if (strlen($keyword) > self::MAX_NAME_LENGTH) {
            throw new InvalidArgumentException('Search term too long');
        }
        $providers = $this->service->searchProviders($keyword) ?? [];
        return $this->json([
            'success' => true,
            'providers' => array_map(fn($p) => $this->serializeProvider($p), $providers)
        ]);
    }

    private function getProviderAction(int $id): JsonResponse
    {
        $provider = $this->service->getProvider($id);
        if (!$provider) {
            throw new NotFoundHttpException('Provider not found');
        }
        return $this->json([
            'success' => true,
            'provider' => $this->serializeProvider($provider)
        ]);
    }

    private function registerProviderAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $data = $this->getJsonData($request);
        if (empty($data)) {
            throw new InvalidArgumentException('Invalid JSON payload');
        }
        $errors = $this->validateRegisterProviderData($data);
        if (!empty($errors)) {
            throw new InvalidArgumentException('Validation failed: ' . implode(', ', $errors));
        }
        try {
            $provider = $this->service->registerProvider($data);
        } catch (\Exception $e) {
            throw new InvalidArgumentException($e->getMessage(), 0, $e);
        }
        return $this->json([
            'success' => true,
            'provider' => $this->serializeProvider($provider)
        ]);
    }

    private function createBookingAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $data = $this->getJsonData($request);
        if (empty($data)) {
            throw new InvalidArgumentException('Invalid JSON payload');
        }
        $data['userId'] = $userId; // inject authenticated user
        $errors = $this->validateCreateBookingData($data);
        if (!empty($errors)) {
            throw new InvalidArgumentException('Validation failed: ' . implode(', ', $errors));
        }
        try {
            $booking = $this->service->createBooking($data);
        } catch (\Exception $e) {
            throw new InvalidArgumentException($e->getMessage(), 0, $e);
        }
        $this->logAction('servicesmarketplace.booking.created', ['bookingId' => $booking->getId(), 'providerId' => $data['providerId']]);
        $this->incrementMetric('servicesmarketplace.booking.created');
        return $this->json([
            'success' => true,
            'booking' => $this->serializeBooking($booking)
        ]);
    }

    private function getBookingAction(int $id): JsonResponse
    {
        $this->requireAuthOr401AsString();
        $booking = $this->service->getBooking($id);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }
        return $this->json([
            'success' => true,
            'booking' => $this->serializeBooking($booking)
        ]);
    }

    private function confirmBookingAction(int $id): JsonResponse
    {
        $this->requireAuthOr401AsString();
        $booking = $this->service->confirmBooking($id);
        if (!$booking) {
            throw new InvalidArgumentException('Cannot confirm booking');
        }
        return $this->json([
            'success' => true,
            'booking' => $this->serializeBooking($booking)
        ]);
    }

    private function startBookingAction(int $id): JsonResponse
    {
        $this->requireAuthOr401AsString();
        $booking = $this->service->startBooking($id);
        if (!$booking) {
            throw new InvalidArgumentException('Cannot start booking');
        }
        return $this->json([
            'success' => true,
            'booking' => $this->serializeBooking($booking)
        ]);
    }

    private function completeBookingAction(int $id): JsonResponse
    {
        $this->requireAuthOr401AsString();
        $booking = $this->service->completeBooking($id);
        if (!$booking) {
            throw new InvalidArgumentException('Cannot complete booking');
        }
        $this->logAction('servicesmarketplace.booking.completed', ['bookingId' => $id]);
        $this->incrementMetric('servicesmarketplace.booking.completed');
        return $this->json([
            'success' => true,
            'booking' => $this->serializeBooking($booking)
        ]);
    }

    private function cancelBookingAction(int $id, Request $request): JsonResponse
    {
        $this->requireAuthOr401AsString();
        $data = $this->getJsonData($request);
        $reason = $data['reason'] ?? 'Cancelled';
        $booking = $this->service->cancelBooking($id, $reason);
        if (!$booking) {
            throw new InvalidArgumentException('Cannot cancel booking');
        }
        return $this->json([
            'success' => true,
            'booking' => $this->serializeBooking($booking)
        ]);
    }

    private function getCustomerBookingsAction(string $userId): JsonResponse
    {
        $authId = $this->requireAuthOr401AsString();
        if ($authId !== $userId) {
            throw new AccessDeniedHttpException('Access denied');
        }
        $bookings = $this->service->getUserBookings($userId) ?? [];
        return $this->json([
            'success' => true,
            'bookings' => array_map(fn($b) => $this->serializeBooking($b), $bookings)
        ]);
    }

    private function getProviderBookingsAction(int $providerId): JsonResponse
    {
        $this->requireAuthOr401AsString();
        $bookings = $this->service->getProviderBookings($providerId) ?? [];
        return $this->json([
            'success' => true,
            'bookings' => array_map(fn($b) => $this->serializeBooking($b), $bookings)
        ]);
    }

    private function getProviderStatsAction(int $providerId): JsonResponse
    {
        $this->requireAuthOr401AsString();
        $stats = $this->service->getProviderStats($providerId);
        return $this->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    private function addReviewAction(Request $request): JsonResponse
    {
        $this->requireAuthOr401AsString();
        $data = $this->getJsonData($request);
        if (empty($data)) {
            throw new InvalidArgumentException('Invalid JSON payload');
        }
        $this->validateReviewInput($data);
        try {
            $review = $this->service->addReview($data);
        } catch (\Exception $e) {
            throw new InvalidArgumentException($e->getMessage(), 0, $e);
        }
        return $this->json([
            'success' => true,
            'review' => [
                'id' => $review->getId(),
                'rating' => $review->getRating(),
                'comment' => $review->getComment(),
            ]
        ]);
    }

    // === Serialization Helpers (unchanged) ===
    private function serializeProvider($p): array
    {
        return [
            'id' => $p->getId(),
            'businessName' => $p->getBusinessName(),
            'displayName' => $p->getDisplayName(),
            'description' => $p->getDescription(),
            'category' => $p->getCategory(),
            'subcategory' => $p->getSubcategory(),
            'address' => $p->getAddress(),
            'latitude' => $p->getLatitude(),
            'longitude' => $p->getLongitude(),
            'phone' => $p->getPhone(),
            'rating' => $p->getRating(),
            'reviewCount' => $p->getReviewCount(),
            'startingPrice' => $p->getStartingPrice(),
            'isVerified' => $p->isVerified(),
            'completedJobs' => $p->getCompletedJobs(),
            'status' => $p->getStatus(),
        ];
    }

    private function serializeBooking($b): array
    {
        return [
            'id' => $b->getId(),
            'status' => $b->getStatus(),
            'address' => $b->getAddress(),
            'latitude' => $b->getLatitude(),
            'longitude' => $b->getLongitude(),
            'scheduledAt' => $b->getScheduledAt()->format('c'),
            'duration' => $b->getDuration(),
            'price' => $b->getPrice(),
            'platformFee' => $b->getPlatformFee(),
            'providerEarning' => $b->getProviderEarning(),
            'paymentMethod' => $b->getPaymentMethod(),
            'paymentStatus' => $b->getPaymentStatus(),
            'createdAt' => $b->getCreatedAt()->format('c'),
            'provider' => $b->getProvider() ? $this->serializeProvider($b->getProvider()) : null,
        ];
    }
}