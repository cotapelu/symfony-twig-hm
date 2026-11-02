<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\ServiceReview;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ServiceReviewTest extends TestCase
{
    private function createReview(
        string $id = 'review-id',
        string $businessId = 'business-1',
        string $customerId = 'customer-1',
        ?string $bookingId = null,
        int $rating = 5
    ): ServiceReview {
        return new ServiceReview($id, $businessId, $customerId, $rating);
    }

    public function testConstructor(): void
    {
        $r = $this->createReview();
        $this->assertSame('review-id', $r->getId());
        $this->assertSame('business-1', $r->getBusinessId());
        $this->assertSame('customer-1', $r->getCustomerId());
        $this->assertNull($r->getBookingId());
        $this->assertSame(5, $r->getRating());
        $this->assertNull($r->getComment());
        $this->assertSame('PUBLISHED', $r->getStatus());
        $this->assertNull($r->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $r->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $r->getUpdatedAt());
        $this->assertTrue($r->isPublished());
        $this->assertFalse($r->isHidden());
        $this->assertFalse($r->isDeleted());
        $this->assertTrue($r->isPositive());
        $this->assertFalse($r->isNeutral());
        $this->assertFalse($r->isNegative());
        $this->assertFalse($r->hasBooking());
        $this->assertFalse($r->hasComment());
    }

    public function testSetBusinessId(): void
    {
        $r = $this->createReview();
        $original = $r->getUpdatedAt();
        usleep(1000);
        $r->setBusinessId('business-2');
        $this->assertSame('business-2', $r->getBusinessId());
        $this->assertNotEquals($original, $r->getUpdatedAt());
    }

    public function testSetCustomerId(): void
    {
        $r = $this->createReview();
        $original = $r->getUpdatedAt();
        usleep(1000);
        $r->setCustomerId('customer-2');
        $this->assertSame('customer-2', $r->getCustomerId());
        $this->assertNotEquals($original, $r->getUpdatedAt());
    }

    public function testSetBookingId(): void
    {
        $r = $this->createReview();
        $original = $r->getUpdatedAt();
        usleep(1000);
        $r->setBookingId('booking-1');
        $this->assertSame('booking-1', $r->getBookingId());
        $this->assertTrue($r->hasBooking());
        $this->assertNotEquals($original, $r->getUpdatedAt());
        $r->setBookingId(null);
        $this->assertNull($r->getBookingId());
        $this->assertFalse($r->hasBooking());
    }

    public function testSetRating(): void
    {
        $r = $this->createReview();
        $original = $r->getUpdatedAt();
        usleep(1000);
        $r->setRating(2);
        $this->assertSame(2, $r->getRating());
        $this->assertTrue($r->isNegative());
        $this->assertFalse($r->isNeutral());
        $this->assertFalse($r->isPositive());
        $this->assertNotEquals($original, $r->getUpdatedAt());

        $r->setRating(3);
        $this->assertTrue($r->isNeutral());
        $r->setRating(4);
        $this->assertTrue($r->isPositive());
    }

    public function testSetComment(): void
    {
        $r = $this->createReview();
        $original = $r->getUpdatedAt();
        usleep(1000);
        $r->setComment('Great service!');
        $this->assertSame('Great service!', $r->getComment());
        $this->assertTrue($r->hasComment());
        $this->assertNotEquals($original, $r->getUpdatedAt());
        $r->setComment(null);
        $this->assertNull($r->getComment());
        $this->assertFalse($r->hasComment());

        $r->setComment('   '); // whitespace only
        $this->assertFalse($r->hasComment());
    }

    public function testSetStatus(): void
    {
        $r = $this->createReview();
        $original = $r->getUpdatedAt();
        usleep(1000);
        $r->setStatus('HIDDEN');
        $this->assertSame('HIDDEN', $r->getStatus());
        $this->assertTrue($r->isHidden());
        $this->assertFalse($r->isPublished());
        $this->assertNotEquals($original, $r->getUpdatedAt());

        $r->setStatus('DELETED');
        $this->assertTrue($r->isDeleted());
    }

    public function testSetMetadata(): void
    {
        $r = $this->createReview();
        $original = $r->getUpdatedAt();
        usleep(1000);
        $meta = ['source' => 'mobile'];
        $r->setMetadata($meta);
        $this->assertSame($meta, $r->getMetadata());
        $this->assertNotEquals($original, $r->getUpdatedAt());
        $r->setMetadata(null);
        $this->assertNull($r->getMetadata());
    }

    public function testIsPositiveNeutralNegativeEdges(): void
    {
        $r = $this->createReview();
        $r->setRating(1);
        $this->assertTrue($r->isNegative());
        $this->assertFalse($r->isNeutral());
        $this->assertFalse($r->isPositive());

        $r->setRating(2);
        $this->assertTrue($r->isNegative());
        $r->setRating(3);
        $this->assertTrue($r->isNeutral());
        $r->setRating(4);
        $this->assertTrue($r->isPositive());
        $r->setRating(5);
        $this->assertTrue($r->isPositive());

        $r->setRating(0);
        $this->assertTrue($r->isNegative());
        $r->setRating(6);
        $this->assertTrue($r->isPositive());
    }
}
