<?php declare(strict_types=1);
namespace App\ServicesMarketplace\Service;

use App\ServicesMarketplace\Entity\Booking;
use App\ServicesMarketplace\Entity\Category;
use App\ServicesMarketplace\Entity\Service;
use App\ServicesMarketplace\Entity\ServiceProvider;
use App\ServicesMarketplace\Entity\Review;
use App\ServicesMarketplace\Repository\BookingRepository;
use App\ServicesMarketplace\Repository\CategoryRepository;
use App\ServicesMarketplace\Repository\ServiceProviderRepository;
use App\ServicesMarketplace\Repository\ServiceRepository;
use App\ServicesMarketplace\Repository\ReviewRepository;

class ServicesMarketplaceService
{
    private ServiceProviderRepository $providerRepo;
    private ServiceRepository $serviceRepo;
    private BookingRepository $bookingRepo;
    private ReviewRepository $reviewRepo;
    private CategoryRepository $categoryRepo;

    private const PLATFORM_FEE_RATE = 0.20;

    public function __construct(
        ServiceProviderRepository $providerRepo,
        ServiceRepository $serviceRepo,
        BookingRepository $bookingRepo,
        ReviewRepository $reviewRepo,
        CategoryRepository $categoryRepo
    ) {
        $this->providerRepo = $providerRepo;
        $this->serviceRepo = $serviceRepo;
        $this->bookingRepo = $bookingRepo;
        $this->reviewRepo = $reviewRepo;
        $this->categoryRepo = $categoryRepo;
    }

    public function registerProvider(array $data)
    {
        $provider = new ServiceProvider();
        $provider->setUserId($data['userId']);
        $provider->setBusinessName($data['businessName']);
        $provider->setDisplayName($data['displayName'] ?? $data['businessName']);
        $provider->setDescription($data['description'] ?? null);
        $provider->setCategory($data['category']);
        $provider->setSubcategory($data['subcategory'] ?? null);
        $provider->setAddress($data['address']);
        $provider->setLatitude((float) $data['latitude']);
        $provider->setLongitude((float) $data['longitude']);
        $provider->setPhone($data['phone'] ?? null);
        $provider->setImageUrl($data['imageUrl'] ?? null);
        $provider->setStartingPrice((float) ($data['startingPrice'] ?? 0));
        $provider->setStatus('pending');

        $this->providerRepo->save($provider);
        return $provider;
    }

    public function getNearbyProviders(float $lat, float $lng, float $radius = 10, int $limit = 20)
    {
        return $this->providerRepo->findNearby($lat, $lng, $radius, $limit);
    }

    public function getProvidersByCategory(string $category)
    {
        return $this->providerRepo->findByCategory($category);
    }

    public function searchProviders(string $keyword)
    {
        return $this->providerRepo->searchByName($keyword);
    }

    public function getProvider(int $providerId)
    {
        return $this->providerRepo->find($providerId);
    }

    public function getProviderByUserId(string $userId)
    {
        return $this->providerRepo->findByUserId($userId);
    }

    public function approveProvider(int $providerId)
    {
        $provider = $this->providerRepo->find($providerId);
        if (!$provider) return null;

        $provider->setStatus('approved');
        $this->providerRepo->save($provider);
        return $provider;
    }

    public function createBooking(array $data)
    {
        $provider = $this->providerRepo->find($data['providerId']);
        if (!$provider || $provider->getStatus() !== 'approved') {
            throw new \Exception('Provider not available');
        }

        $service = $this->serviceRepo->find($data['serviceId']);
        if (!$service) {
            throw new \Exception('Service not found');
        }

        $price = $service->getPrice();
        $platformFee = $price * self::PLATFORM_FEE_RATE;
        $providerEarning = $price - $platformFee;

        $booking = new Booking();
        $booking->setUserId($data['userId']);
        $booking->setProvider($provider);
        $booking->setService($service);
        $booking->setAddress($data['address']);
        $booking->setLatitude((float) $data['latitude']);
        $booking->setLongitude((float) $data['longitude']);
        $booking->setScheduledAt(new \DateTime($data['scheduledAt']));
        $booking->setDuration($service->getEstimatedDuration() ?? 60);
        $booking->setStatus(Booking::STATUS_PENDING);
        $booking->setPrice($price);
        $booking->setPlatformFee($platformFee);
        $booking->setProviderEarning($providerEarning);
        $booking->setUserNote($data['note'] ?? null);
        $booking->setPaymentMethod($data['paymentMethod'] ?? 'wallet');

        $this->bookingRepo->save($booking);
        $this->serviceRepo->incrementBookedCount($service->getId());

        return $booking;
    }

    public function confirmBooking(int $bookingId)
    {
        $booking = $this->bookingRepo->find($bookingId);
        if (!$booking || $booking->getStatus() !== Booking::STATUS_PENDING) {
            return null;
        }

        $booking->setStatus(Booking::STATUS_CONFIRMED);
        $this->bookingRepo->save($booking);
        return $booking;
    }

    public function startBooking(int $bookingId)
    {
        $booking = $this->bookingRepo->find($bookingId);
        if (!$booking || $booking->getStatus() !== Booking::STATUS_CONFIRMED) {
            return null;
        }

        $booking->setStatus(Booking::STATUS_IN_PROGRESS);
        $this->bookingRepo->save($booking);
        return $booking;
    }

    public function completeBooking(int $bookingId)
    {
        $booking = $this->bookingRepo->find($bookingId);
        if (!$booking || $booking->getStatus() !== Booking::STATUS_IN_PROGRESS) {
            return null;
        }

        $booking->setStatus(Booking::STATUS_COMPLETED);
        $booking->setCompletedAt(new \DateTime());
        $booking->setPaymentStatus('completed');

        $provider = $booking->getProvider();
        $provider->setCompletedJobs($provider->getCompletedJobs() + 1);
        $this->providerRepo->save($provider);

        $this->bookingRepo->save($booking);
        return $booking;
    }

    public function cancelBooking(int $bookingId, string $reason)
    {
        $booking = $this->bookingRepo->find($bookingId);
        if (!$booking || !$this->canCancel($booking)) {
            return null;
        }

        $booking->setStatus(Booking::STATUS_CANCELLED);
        $this->bookingRepo->save($booking);
        return $booking;
    }

    private function canCancel(Booking $booking)
    {
        return in_array($booking->getStatus(), [
            Booking::STATUS_PENDING,
            Booking::STATUS_CONFIRMED
        ]);
    }

    public function getUserBookings(string $userId)
    {
        return $this->bookingRepo->findByUserId($userId);
    }

    public function getProviderBookings(int $providerId)
    {
        return $this->bookingRepo->findByProvider($providerId);
    }

    public function getUpcomingBookings(int $providerId)
    {
        return $this->bookingRepo->findUpcomingBookings($providerId);
    }

    public function addReview(array $data)
    {
        $booking = $this->bookingRepo->find($data['bookingId']);
        if (!$booking) {
            throw new \Exception('Booking not found');
        }

        $review = new Review();
        $review->setBooking($booking);
        $review->setProvider($booking->getProvider());
        $review->setUserId($data['userId']);
        $review->setRating((float) $data['rating']);
        $review->setComment($data['comment'] ?? null);
        $review->setServiceType($data['serviceType'] ?? null);

        $this->reviewRepo->save($review);

        $this->updateProviderRating($booking->getProvider()->getId());

        return $review;
    }

    private function updateProviderRating(int $providerId)
    {
        $reviews = $this->reviewRepo->findByProvider($providerId);
        $count = count($reviews);
        $avgRating = array_sum(array_map(fn($r) => $r->getRating(), $reviews)) / max($count, 1);

        $this->providerRepo->updateRating($providerId, $avgRating, $count);
    }

    public function getCategories()
    {
        return $this->categoryRepo->findActiveCategories();
    }

    public function createCategory(array $data)
    {
        $category = new Category();
        $category->setName($data['name']);
        $category->setSlug($this->slugify($data['name']));
        $category->setDescription($data['description'] ?? null);
        $category->setIconUrl($data['iconUrl'] ?? null);
        $category->setDisplayOrder($data['displayOrder'] ?? 0);

        $this->categoryRepo->save($category);
        return $category;
    }

    private function slugify(string $text)
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
    }

    public function getProviderStats(int $providerId)
    {
        $bookings = $this->bookingRepo->findByProvider($providerId);
        $completed = array_filter($bookings, fn($b) => $b->getStatus() === Booking::STATUS_COMPLETED);
        $totalEarnings = array_sum(array_map(fn($b) => $b->getProviderEarning(), $completed));

        return [
            'total_bookings' => count($bookings),
            'completed_bookings' => count($completed),
            'total_earnings' => $totalEarnings,
            'pending_bookings' => count(array_filter($bookings, fn($b) => in_array($b->getStatus(), [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED]))),
        ];
    }

    public function getBooking(int $bookingId)
    {
        return $this->bookingRepo->find($bookingId);
    }
}
