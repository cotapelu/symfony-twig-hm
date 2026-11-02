<?php declare(strict_types=1);

namespace App\Tests\Unit\Ecommerce\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Ecommerce\Entity\InventoryReservation;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class InventoryReservationTest extends TestCase
{
    private function createReservation(
        ?string $productId = null,
        ?string $sessionId = null,
        ?int $quantity = null,
        ?int $minutes = 15
    ): InventoryReservation {
        return new InventoryReservation(
            productId: $productId ?? 'prod-123',
            sessionId: $sessionId ?? 'sess-456',
            quantity: $quantity ?? 2,
            reservationMinutes: $minutes
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $reservation = $this->createReservation();
        $this->assertNull($reservation->getId());
        $this->assertSame('prod-123', $reservation->getProductId());
        $this->assertSame('sess-456', $reservation->getSessionId());
        $this->assertSame(2, $reservation->getQuantity());
        $this->assertInstanceOf(\DateTime::class, $reservation->getReservedAt());
        $this->assertInstanceOf(\DateTime::class, $reservation->getExpiresAt());
        $this->assertSame('pending', $reservation->getStatus());
    }

    public function testIsExpired(): void
    {
        $reservation = $this->createReservation(minutes: 0); // expires now
        // isExpired might be false depending on microsecond timing; we'll manipulate via reflection.
        $refExpires = new \ReflectionProperty($reservation, 'expiresAt');
        $refExpires->setAccessible(true);
        $refExpires->setValue($reservation, (new \DateTime())->modify('-1 minute'));
        $this->assertTrue($reservation->isExpired());

        $refExpires->setValue($reservation, (new \DateTime())->modify('+1 minute'));
        $this->assertFalse($reservation->isExpired());
    }

    public function testStatusTransitions(): void
    {
        $reservation = $this->createReservation();
        $this->assertSame('pending', $reservation->getStatus());

        $reservation->confirm();
        $this->assertSame('confirmed', $reservation->getStatus());

        $reservation->cancel();
        $this->assertSame('cancelled', $reservation->getStatus());

        // expired transition
        $reservation->expire();
        $this->assertSame('expired', $reservation->getStatus());
    }

    public function testToArray(): void
    {
        $reservation = $this->createReservation(productId: 'p1', sessionId: 's1', quantity: 5);
        // Simulate ID
        $refId = new \ReflectionProperty($reservation, 'id');
        $refId->setAccessible(true);
        $refId->setValue($reservation, 42);

        $arr = $reservation->toArray();
        $this->assertSame(42, $arr['id']);
        $this->assertSame('p1', $arr['product_id']);
        $this->assertSame('s1', $arr['session_id']);
        $this->assertSame(5, $arr['quantity']);
        $this->assertSame('pending', $arr['status']);
        $this->assertFalse($arr['is_expired']);
        // Check datetime format is Y-m-d H:i:s
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['reserved_at']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['expires_at']);
    }
}
