<?php declare(strict_types=1);

namespace App\Tests\Unit\Business\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Business\Entity\ServiceBooking;
use App\Business\Entity\ServiceReview;
use App\Core\Entity\User;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ServiceReviewTest extends TestCase
{
    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setPasswordHash('hashed');
        return $user;
    }

    private function createBooking(?User $provider = null, ?User $customer = null): ServiceBooking
    {
        return new ServiceBooking(
            provider: $provider ?? $this->createUser(),
            customer: $customer ?? $this->createUser()
        );
    }

    private function createReview(ServiceBooking $booking, User $user, int $rating = 5): ServiceReview
    {
        return new ServiceReview($booking, $user, $rating);
    }

    public function testConstructorInitializesProperties(): void
    {
        $provider = $this->createUser();
        $customer = $this->createUser();
        $booking = $this->createBooking($provider, $customer);
        $review = $this->createReview($booking, $customer, 4);

        $this->assertIsString($review->getId());
        $this->assertNotEmpty($review->getId());
        $this->assertSame($booking, $review->getBooking());
        $this->assertSame($customer, $review->getUser());
        $this->assertSame(4, $review->getRating());
        $this->assertNull($review->getComment());
        $this->assertNull($review->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $review->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $booking = $this->createBooking();
        $user = $this->createUser();
        $review = $this->createReview($booking, $user, 3);

        $review->setRating(5);
        $this->assertSame(5, $review->getRating());

        $review->setComment('Great service!');
        $this->assertSame('Great service!', $review->getComment());

        $review->setComment(null);
        $this->assertNull($review->getComment());

        $review->setMetadata(['source' => 'mobile']);
        $this->assertSame(['source' => 'mobile'], $review->getMetadata());

        // Test setBooking and setUser
        $newBooking = $this->createBooking();
        $review->setBooking($newBooking);
        $this->assertSame($newBooking, $review->getBooking());

        $newUser = $this->createUser();
        $review->setUser($newUser);
        $this->assertSame($newUser, $review->getUser());
    }

    public function testToArray(): void
    {
        $provider = $this->createUser();
        $customer = $this->createUser();
        $booking = $this->createBooking($provider, $customer);
        $review = $this->createReview($booking, $customer, 5);
        $review->setComment('Excellent');
        $review->setMetadata(['category' => 'service']);

        $arr = $review->toArray();
        $this->assertArrayHasKey('id', $arr);
        $this->assertSame($booking->getId(), $arr['bookingId']);
        $this->assertSame($customer->getId(), $arr['userId']);
        $this->assertSame(5, $arr['rating']);
        $this->assertSame('Excellent', $arr['comment']);
        $this->assertSame(['category' => 'service'], $arr['metadata']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['createdAt']);
    }
}
