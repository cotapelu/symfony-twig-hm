<?php declare(strict_types=1);

namespace Tests\Unit\ServicesMarketplace\Controller;

use App\ServicesMarketplace\Controller\ServicesMarketplaceController;
use App\ServicesMarketplace\Service\ServicesMarketplaceService;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

// DTO interfaces for providers, bookings, etc.
interface ProviderDto
{
    public function getId(): int;
    public function getBusinessName(): string;
    public function getDisplayName(): string;
    public function getDescription(): string;
    public function getCategory(): string;
    public function getSubcategory(): ?string;
    public function getAddress(): string;
    public function getLatitude(): float;
    public function getLongitude(): float;
    public function getPhone(): string;
    public function getRating(): float;
    public function getReviewCount(): int;
    public function getStartingPrice(): float;
    public function isVerified(): bool;
    public function getCompletedJobs(): int;
    public function getStatus(): string;
}

interface BookingDto
{
    public function getId(): int;
    public function getStatus(): string;
    public function getAddress(): string;
    public function getLatitude(): float;
    public function getLongitude(): float;
    public function getScheduledAt(): \DateTimeInterface;
    public function getDuration(): int;
    public function getPrice(): float;
    public function getPlatformFee(): float;
    public function getProviderEarning(): float;
    public function getPaymentMethod(): string;
    public function getPaymentStatus(): string;
    public function getCreatedAt(): \DateTimeInterface;
    public function getProvider(): ProviderDto;
    public function getUserId(): string;
}

interface CategoryDto
{
    public function getId(): int;
    public function getName(): string;
    public function getSlug(): string;
    public function getDescription(): string;
    public function getIconUrl(): ?string;
}

interface ReviewDto
{
    public function getId(): int;
    public function getRating(): float;
    public function getComment(): ?string;
}

class ServicesMarketplaceControllerTest extends TestCase
{
    private ServicesMarketplaceService $serviceMock;
    private ServicesMarketplaceController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(ServicesMarketplaceService::class);
        $this->controller = new ServicesMarketplaceController($this->serviceMock);
    }

    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function setAuthenticatedUser(string $userId = '123'): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn($userId);
        $user->method('getId')->willReturn($userId);
        $this->controller->setTestUser($user);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function createProviderMock(int $id, array $config = []): ProviderDto
    {
        $mock = $this->createMock(ProviderDto::class);
        $mock->method('getId')->willReturn($id);
        $mock->method('getBusinessName')->willReturn($config['businessName'] ?? 'Biz');
        $mock->method('getDisplayName')->willReturn($config['displayName'] ?? 'Display');
        $mock->method('getDescription')->willReturn($config['description'] ?? 'Desc');
        $mock->method('getCategory')->willReturn($config['category'] ?? 'cat');
        $mock->method('getSubcategory')->willReturn($config['subcategory'] ?? null);
        $mock->method('getAddress')->willReturn($config['address'] ?? 'Addr');
        $mock->method('getLatitude')->willReturn($config['latitude'] ?? 21.0);
        $mock->method('getLongitude')->willReturn($config['longitude'] ?? 105.0);
        $mock->method('getPhone')->willReturn($config['phone'] ?? '1234567890');
        $mock->method('getRating')->willReturn($config['rating'] ?? 5.0);
        $mock->method('getReviewCount')->willReturn($config['reviewCount'] ?? 0);
        $mock->method('getStartingPrice')->willReturn($config['startingPrice'] ?? 1000.0);
        $mock->method('isVerified')->willReturn($config['isVerified'] ?? false);
        $mock->method('getCompletedJobs')->willReturn($config['completedJobs'] ?? 0);
        $mock->method('getStatus')->willReturn($config['status'] ?? 'active');
        return $mock;
    }

    private function createBookingMock(int $id, array $config = []): BookingDto
    {
        $mock = $this->createMock(BookingDto::class);
        $mock->method('getId')->willReturn($id);
        $mock->method('getStatus')->willReturn($config['status'] ?? 'PENDING');
        $mock->method('getAddress')->willReturn($config['address'] ?? 'Addr');
        $mock->method('getLatitude')->willReturn($config['latitude'] ?? 21.0);
        $mock->method('getLongitude')->willReturn($config['longitude'] ?? 105.0);
        $scheduledAt = $config['scheduledAt'] ?? new \DateTime('+1 day');
        $mock->method('getScheduledAt')->willReturn($scheduledAt);
        $mock->method('getDuration')->willReturn($config['duration'] ?? 60);
        $mock->method('getPrice')->willReturn($config['price'] ?? 5000.0);
        $mock->method('getPlatformFee')->willReturn($config['platformFee'] ?? 500.0);
        $mock->method('getProviderEarning')->willReturn($config['providerEarning'] ?? 4500.0);
        $mock->method('getPaymentMethod')->willReturn($config['paymentMethod'] ?? 'cash');
        $mock->method('getPaymentStatus')->willReturn($config['paymentStatus'] ?? 'PENDING');
        $mock->method('getCreatedAt')->willReturn($config['createdAt'] ?? new \DateTime());
        $provider = $this->createProviderMock(1);
        $mock->method('getProvider')->willReturn($provider);
        $mock->method('getUserId')->willReturn($config['userId'] ?? 'user1');
        return $mock;
    }

    // ==================== Validation helpers ====================

    public function testValidateCoordinate_Valid(): void
    {
        $this->assertNull($this->invokePrivate($this->controller, 'validateCoordinate', [21.0, 105.0]));
    }

    public function testValidateCoordinate_InvalidLat(): void
    {
        $this->assertSame('Invalid latitude', $this->invokePrivate($this->controller, 'validateCoordinate', [91.0, 105.0]));
    }

    public function testValidateCoordinate_InvalidLng(): void
    {
        $this->assertSame('Invalid longitude', $this->invokePrivate($this->controller, 'validateCoordinate', [21.0, 181.0]));
    }

    public function testValidateRating_Valid(): void
    {
        $this->assertNull($this->invokePrivate($this->controller, 'validateRating', [3.5]));
    }

    public function testValidateRating_TooLow(): void
    {
        $this->assertSame('Rating must be between 1 and 5', $this->invokePrivate($this->controller, 'validateRating', [0.5]));
    }

    public function testValidateRating_TooHigh(): void
    {
        $this->assertSame('Rating must be between 1 and 5', $this->invokePrivate($this->controller, 'validateRating', [5.5]));
    }

    public function testValidateRequiredFields(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateRequiredFields', [['name' => 'Test', 'email' => ''], ['name', 'email']]);
        $this->assertIsArray($errors);
        $this->assertContains('Missing required field: email', $errors);
    }

    public function testValidateBusinessNameLength(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateBusinessNameLength', [['businessName' => str_repeat('a', 256)]]);
        $this->assertIsArray($errors);
        $this->assertStringContainsString('Business name too long', $errors[0]);
    }

    public function testValidatePhoneLength(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validatePhoneLength', [['phone' => str_repeat('1', 21)]]);
        $this->assertIsArray($errors);
        $this->assertStringContainsString('Phone number too long', $errors[0]);
    }

    public function testValidateStartingPrice_Negative(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateStartingPrice', [['startingPrice' => -1]]);
        $this->assertIsArray($errors);
        $this->assertStringContainsString('Invalid starting price', $errors[0]);
    }

    public function testValidateStartingPrice_TooHigh(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateStartingPrice', [['startingPrice' => 200000000]]);
        $this->assertIsArray($errors);
        $this->assertStringContainsString('Invalid starting price', $errors[0]);
    }

    public function testValidatePaymentMethod_Invalid(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validatePaymentMethod', [['paymentMethod' => 'bitcoin']]);
        $this->assertIsArray($errors);
        $this->assertStringContainsString('Invalid payment method', $errors[0]);
    }

    public function testValidateReviewInput_MissingFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing field: bookingId');
        $this->invokePrivate($this->controller, 'validateReviewInput', [['rating' => 5]]);
    }

    public function testValidateReviewInput_InvalidRating(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rating must be between 1 and 5');
        $this->invokePrivate($this->controller, 'validateReviewInput', [['bookingId' => 1, 'providerId' => 1, 'rating' => 6]]);
    }

    public function testValidateReviewInput_CommentTooLong(): void
    {
        $longComment = str_repeat('a', 1001);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Comment too long');
        $this->invokePrivate($this->controller, 'validateReviewInput', [['bookingId' => 1, 'providerId' => 1, 'rating' => 4, 'comment' => $longComment]]);
    }

    // ==================== getCategories (public) ====================

    public function testGetCategories_Success(): void
    {
        // No auth required
        $cat1 = $this->createMock(CategoryDto::class);
        $cat1->method('getId')->willReturn(1);
        $cat1->method('getName')->willReturn('Cleaning');
        $cat1->method('getSlug')->willReturn('cleaning');
        $cat1->method('getDescription')->willReturn('Cleaning services');
        $cat1->method('getIconUrl')->willReturn(null);
        $cat2 = $this->createMock(CategoryDto::class);
        $cat2->method('getId')->willReturn(2);
        $cat2->method('getName')->willReturn('Plumbing');
        $cat2->method('getSlug')->willReturn('plumbing');
        $cat2->method('getDescription')->willReturn('Plumbing services');
        $cat2->method('getIconUrl')->willReturn(null);
        $this->serviceMock->expects($this->once())->method('getCategories')->willReturn([$cat1, $cat2]);
        $response = $this->controller->getCategories();
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertCount(2, $content['categories']);
    }

    // ==================== getNearbyProviders (public) ====================

    public function testGetNearbyProviders_MissingCoords_Returns400(): void
    {
        $request = Request::create('/api/services-marketplace/providers/nearby', 'GET', ['radius' => 10]);
        $this->assertSame(400, $this->controller->getNearbyProviders($request)->getStatusCode());
    }

    public function testGetNearbyProviders_InvalidLat_Returns400(): void
    {
        $request = Request::create('/api/services-marketplace/providers/nearby', 'GET', ['latitude' => 100.0, 'longitude' => 105.0]);
        $this->assertSame(400, $this->controller->getNearbyProviders($request)->getStatusCode());
    }

    public function testGetNearbyProviders_RadiusOutOfRange_Defaults(): void
    {
        $provider = $this->createProviderMock(1);
        $this->serviceMock->expects($this->once())->method('getNearbyProviders')->with(21.0, 105.0, 10.0)->willReturn([$provider]);
        $request = Request::create('/api/services-marketplace/providers/nearby', 'GET', ['latitude' => 21.0, 'longitude' => 105.0, 'radius' => 100]);
        $response = $this->controller->getNearbyProviders($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetNearbyProviders_Success(): void
    {
        $provider = $this->createProviderMock(1);
        $this->serviceMock->expects($this->once())->method('getNearbyProviders')->with(21.0, 105.0, 5.0)->willReturn([$provider]);
        $request = Request::create('/api/services-marketplace/providers/nearby', 'GET', ['latitude' => 21.0, 'longitude' => 105.0, 'radius' => 5]);
        $this->assertSame(200, $this->controller->getNearbyProviders($request)->getStatusCode());
    }

    // ==================== getProvidersByCategory (public) ====================

    public function testGetProvidersByCategory_Success(): void
    {
        $provider = $this->createProviderMock(1, ['category' => 'cleaning']);
        $this->serviceMock->expects($this->once())->method('getProvidersByCategory')->with('cleaning')->willReturn([$provider]);
        $response = $this->controller->getProvidersByCategory('cleaning');
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== searchProviders (public) ====================

    public function testSearchProviders_TermTooShort_Returns400(): void
    {
        $request = Request::create('/api/services-marketplace/providers/search', 'GET', ['q' => 'a']);
        $this->assertSame(400, $this->controller->searchProviders($request)->getStatusCode());
    }

    public function testSearchProviders_TermTooLong_Returns400(): void
    {
        $long = str_repeat('a', 256);
        $request = Request::create('/api/services-marketplace/providers/search', 'GET', ['q' => $long]);
        $this->assertSame(400, $this->controller->searchProviders($request)->getStatusCode());
    }

    public function testSearchProviders_Success(): void
    {
        $provider = $this->createProviderMock(1);
        $this->serviceMock->expects($this->once())->method('searchProviders')->with('clean')->willReturn([$provider]);
        $request = Request::create('/api/services-marketplace/providers/search', 'GET', ['q' => 'clean']);
        $this->assertSame(200, $this->controller->searchProviders($request)->getStatusCode());
    }

    // ==================== getProvider (public) ====================

    public function testGetProvider_NotFound_Returns404(): void
    {
        $this->serviceMock->method('getProvider')->with(1)->willReturn(null);
        $this->assertSame(404, $this->controller->getProvider(1)->getStatusCode());
    }

    public function testGetProvider_Success(): void
    {
        $provider = $this->createProviderMock(1);
        $this->serviceMock->expects($this->once())->method('getProvider')->with(1)->willReturn($provider);
        $response = $this->controller->getProvider(1);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== registerProvider ====================

    public function testRegisterProvider_Unauthenticated_Returns403(): void
    {
        $request = Request::create('/api/services-marketplace/providers/register', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'businessName' => 'Test', 'displayName' => 'Test', 'category' => 'gen', 'address' => 'Addr', 'phone' => '123', 'startingPrice' => 1000
        ]));
        $this->assertSame(403, $this->controller->registerProvider($request)->getStatusCode());
    }

    public function testRegisterProvider_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $this->assertSame(400, $this->controller->registerProvider($request)->getStatusCode());
    }

    public function testRegisterProvider_ValidationErrors(): void
    {
        $this->setAuthenticatedUser();
        $request = Request::create('/api/services-marketplace/providers/register', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'businessName' => str_repeat('a', 300),
            'displayName' => 'OK',
            'category' => 'gen',
            'address' => 'OK',
            'phone' => str_repeat('1', 21),
            'startingPrice' => -1
        ]));
        $response = $this->controller->registerProvider($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Validation failed', $response->getContent());
    }

    public function testRegisterProvider_Success(): void
    {
        $this->setAuthenticatedUser();
        $provider = $this->createProviderMock(1);
        $this->serviceMock->expects($this->once())->method('registerProvider')->with($this->callback(fn($data) =>
            $data['businessName'] === 'Test Business' && $data['category'] === 'general' && $data['startingPrice'] == 1000
        ))->willReturn($provider);
        $request = Request::create('/api/services-marketplace/providers/register', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'businessName' => 'Test Business', 'displayName' => 'Test Display', 'category' => 'general',
            'address' => 'Hanoi', 'phone' => '1234567890', 'startingPrice' => 1000
        ]));
        $this->assertSame(200, $this->controller->registerProvider($request)->getStatusCode());
    }

    // ==================== createBooking ====================

    public function testCreateBooking_Unauthenticated_Returns403(): void
    {
        $request = Request::create('/api/services-marketplace/bookings', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'providerId' => 1, 'address' => 'Addr', 'scheduledAt' => '2025-01-01T10:00:00', 'price' => 5000
        ]));
        $this->assertSame(403, $this->controller->createBooking($request)->getStatusCode());
    }

    public function testCreateBooking_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $this->assertSame(400, $this->controller->createBooking($request)->getStatusCode());
    }

    public function testCreateBooking_ValidationErrors(): void
    {
        $this->setAuthenticatedUser();
        $request = Request::create('/api/services-marketplace/bookings', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'providerId' => 1,
            'address' => str_repeat('a', 501),
            'scheduledAt' => '2020-01-01',
            'price' => -100,
            'paymentMethod' => 'crypto'
        ]));
        $response = $this->controller->createBooking($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateBooking_Success(): void
    {
        $this->setAuthenticatedUser();
        $booking = $this->createBookingMock(1);
        $this->serviceMock->expects($this->once())->method('createBooking')->with($this->callback(fn($data) =>
            $data['providerId'] == 1 && $data['userId'] === '123' && $data['price'] == 5000
        ))->willReturn($booking);
        $request = Request::create('/api/services-marketplace/bookings', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'providerId' => 1, 'address' => 'Hanoi', 'scheduledAt' => '2030-01-01T10:00:00', 'price' => 5000, 'paymentMethod' => 'cash'
        ]));
        $this->assertSame(200, $this->controller->createBooking($request)->getStatusCode());
    }

    // ==================== getBooking (authenticated) ====================

    public function testGetBooking_Unauthenticated_Returns403(): void
    {
        $this->assertSame(403, $this->controller->getBooking(1)->getStatusCode());
    }

    public function testGetBooking_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->expects($this->once())->method('getBooking')->with(1)->willReturn(null);
        $this->assertSame(404, $this->controller->getBooking(1)->getStatusCode());
    }

    public function testGetBooking_Success(): void
    {
        $this->setAuthenticatedUser();
        $booking = $this->createBookingMock(1);
        $this->serviceMock->expects($this->once())->method('getBooking')->with(1)->willReturn($booking);
        $response = $this->controller->getBooking(1);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== booking actions ====================

    private function bookingActionSuccessTest(string $method, string $serviceMethod): void
    {
        $this->setAuthenticatedUser();
        $booking = $this->createBookingMock(1);
        $this->serviceMock->expects($this->once())->method($serviceMethod)->with(1)->willReturn($booking);
        $response = $this->controller->$method(1);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testConfirmBooking_Unauthenticated_Returns403(): void
    {
        $this->assertSame(403, $this->controller->confirmBooking(1)->getStatusCode());
    }

    public function testStartBooking_Unauthenticated_Returns403(): void
    {
        $this->assertSame(403, $this->controller->startBooking(1)->getStatusCode());
    }

    public function testCompleteBooking_Unauthenticated_Returns403(): void
    {
        $this->assertSame(403, $this->controller->completeBooking(1)->getStatusCode());
    }

    public function testCancelBooking_Unauthenticated_Returns403(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['reason' => 'No show']));
        $this->assertSame(403, $this->controller->cancelBooking($request, 1)->getStatusCode());
    }

    public function testCancelBooking_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $this->assertSame(400, $this->controller->cancelBooking($request, 1)->getStatusCode());
    }

    public function testCancelBooking_Success(): void
    {
        $this->setAuthenticatedUser();
        $booking = $this->createBookingMock(1, ['status' => 'CANCELLED']);
        $this->serviceMock->expects($this->once())->method('cancelBooking')->with(1, 'No show')->willReturn($booking);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['reason' => 'No show']));
        $this->assertSame(200, $this->controller->cancelBooking($request, 1)->getStatusCode());
    }

    public function testConfirmBooking_Success(): void { $this->bookingActionSuccessTest('confirmBooking', 'confirmBooking'); }
    public function testStartBooking_Success(): void { $this->bookingActionSuccessTest('startBooking', 'startBooking'); }
    public function testCompleteBooking_Success(): void { $this->bookingActionSuccessTest('completeBooking', 'completeBooking'); }

    // ==================== getCustomerBookings ====================

    public function testGetCustomerBookings_Unauthenticated_Returns403(): void
    {
        $this->assertSame(403, $this->controller->getCustomerBookings('user1')->getStatusCode());
    }

    public function testGetCustomerBookings_Forbidden_Returns403(): void
    {
        $this->setAuthenticatedUser('other_user');
        $this->assertSame(403, $this->controller->getCustomerBookings('user1')->getStatusCode());
    }

    public function testGetCustomerBookings_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $booking = $this->createBookingMock(1);
        $this->serviceMock->expects($this->once())->method('getUserBookings')->with('user1')->willReturn([$booking]);
        $response = $this->controller->getCustomerBookings('user1');
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== getProviderBookings ====================

    public function testGetProviderBookings_Unauthenticated_Returns403(): void
    {
        $this->assertSame(403, $this->controller->getProviderBookings(1)->getStatusCode());
    }

    public function testGetProviderBookings_Success(): void
    {
        $this->setAuthenticatedUser();
        $booking = $this->createBookingMock(1);
        $this->serviceMock->expects($this->once())->method('getProviderBookings')->with(1)->willReturn([$booking]);
        $response = $this->controller->getProviderBookings(1);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== getProviderStats ====================

    public function testGetProviderStats_Unauthenticated_Returns403(): void
    {
        $this->assertSame(403, $this->controller->getProviderStats(1)->getStatusCode());
    }

    public function testGetProviderStats_Success(): void
    {
        $this->setAuthenticatedUser();
        $stats = ['total_bookings' => 10, 'completed_bookings' => 8, 'total_earnings' => 4000, 'pending_bookings' => 2];
        $this->serviceMock->expects($this->once())->method('getProviderStats')->with(1)->willReturn($stats);
        $response = $this->controller->getProviderStats(1);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals($stats, $content['stats']);
    }

    // ==================== addReview ====================

    public function testAddReview_Unauthenticated_Returns403(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'bookingId' => 1, 'providerId' => 1, 'rating' => 5
        ]));
        $this->assertSame(403, $this->controller->addReview($request)->getStatusCode());
    }

    public function testAddReview_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $this->assertSame(400, $this->controller->addReview($request)->getStatusCode());
    }

    public function testAddReview_MissingFields_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['bookingId' => 1, 'providerId' => 1]));
        $this->assertSame(400, $this->controller->addReview($request)->getStatusCode());
    }

    public function testAddReview_CommentTooLong_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $long = str_repeat('a', 1001);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'bookingId' => 1, 'providerId' => 1, 'rating' => 4, 'comment' => $long
        ]));
        $this->assertSame(400, $this->controller->addReview($request)->getStatusCode());
    }

    public function testAddReview_Success(): void
    {
        $this->setAuthenticatedUser();
        $review = $this->createMock(ReviewDto::class);
        $review->method('getId')->willReturn(1);
        $review->method('getRating')->willReturn(5.0);
        $review->method('getComment')->willReturn('Great');
        $this->serviceMock->expects($this->once())->method('addReview')->with($this->callback(fn($data) =>
            $data['bookingId'] == 1 && $data['providerId'] == 1 && $data['rating'] == 5
        ))->willReturn($review);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'bookingId' => 1, 'providerId' => 1, 'rating' => 5, 'comment' => 'Great'
        ]));
        $this->assertSame(200, $this->controller->addReview($request)->getStatusCode());
    }

    // ==================== Additional validation coverage ====================

    public function testValidateCoordinates_Various(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateCoordinates', [['latitude' => 21, 'longitude' => 105]]);
        $this->assertEmpty($errors);
        $errors = $this->invokePrivate($this->controller, 'validateCoordinates', [['latitude' => 100]]);
        $this->assertIsArray($errors);
        $this->assertContains('Invalid latitude', $errors);
    }

    public function testValidateAddressLength(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateAddressLength', [['address' => str_repeat('a', 501)]]);
        $this->assertIsArray($errors);
        $this->assertStringContainsString('Address too long', $errors[0]);
    }

    public function testValidatePrice_Negative(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validatePrice', [['price' => -10]]);
        $this->assertIsArray($errors);
        $this->assertStringContainsString('Invalid price', $errors[0]);
    }

    public function testValidatePrice_TooHigh(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validatePrice', [['price' => 200000000]]);
        $this->assertIsArray($errors);
        $this->assertStringContainsString('Invalid price', $errors[0]);
    }

    public function testValidateScheduledAt_InvalidFormat(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateScheduledAt', [['scheduledAt' => 'invalid-date']]);
        $this->assertIsArray($errors);
        $this->assertContains('Invalid scheduledAt format', $errors);
    }

    public function testValidateScheduledAt_PastDate(): void
    {
        $past = (new \DateTime('-1 day'))->format('Y-m-d H:i:s');
        $errors = $this->invokePrivate($this->controller, 'validateScheduledAt', [['scheduledAt' => $past]]);
        $this->assertIsArray($errors);
        $this->assertContains('Scheduled time must be in the future', $errors);
    }

    public function testGetJsonData_InvalidJson_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');
        $this->invokePrivate($this->controller, 'getJsonData', [$request]);
    }
}