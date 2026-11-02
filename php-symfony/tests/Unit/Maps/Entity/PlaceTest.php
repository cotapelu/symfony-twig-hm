<?php declare(strict_types=1);

namespace App\Tests\Unit\Maps\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Maps\Entity\Place;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PlaceTest extends TestCase
{
    private function createPlace(array $overrides = []): Place
    {
        $place = new Place();
        // Set required non-nullable fields
        $place->setName('Central Park');
        $place->setLatitude(40.785091);
        $place->setLongitude(-73.968285);
        $place->setCategory('Park');
        foreach ($overrides as $prop => $value) {
            $setter = 'set' . ucfirst($prop);
            if (method_exists($place, $setter)) {
                $place->$setter($value);
            }
        }
        return $place;
    }

    public function testConstructorInitializesIdAndCreatedAt(): void
    {
        $place = $this->createPlace([]);
        $this->assertIsString($place->getId());
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F-]{36}$/', $place->getId());
        $this->assertInstanceOf(\DateTime::class, $place->getCreatedAt());
    }

    // Note: other properties are uninitialized until set; avoid accessing them pre-initialization.
    // We'll test getters/setters explicitly.

    public function testGettersAndSetters(): void
    {
        $place = $this->createPlace([]);

        $place->setName('Museum of Art');
        $this->assertSame('Museum of Art', $place->getName());

        $place->setAddress('123 Main St');
        $this->assertSame('123 Main St', $place->getAddress());

        $place->setLatitude(34.0522);
        $this->assertSame(34.0522, $place->getLatitude());

        $place->setLongitude(-118.2437);
        $this->assertSame(-118.2437, $place->getLongitude());

        $place->setCategory('Landmark');
        $this->assertSame('Landmark', $place->getCategory());

        $place->setPlaceType('tourist_attraction');
        $this->assertSame('tourist_attraction', $place->getPlaceType());

        $place->setPhone('+1 123 456 789');
        $this->assertSame('+1 123 456 789', $place->getPhone());

        $place->setRating(4.5);
        $this->assertSame(4.5, $place->getRating());

        $place->setReviewCount(150);
        $this->assertSame(150, $place->getReviewCount());

        $place->setDescription('A beautiful place');
        $this->assertSame('A beautiful place', $place->getDescription());

        $place->setMetadata(['website' => 'example.com']);
        $this->assertSame(['website' => 'example.com'], $place->getMetadata());
    }
}
