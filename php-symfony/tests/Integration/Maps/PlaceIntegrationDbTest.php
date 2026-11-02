<?php

declare(strict_types=1);

namespace App\Tests\Integration\Maps;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Maps\Entity\Place;

#[AllowMockObjectsWithoutExpectations]
class PlaceIntegrationDbTest extends DatabaseIntegrationTestCase
{
    public function testCreateAndRetrievePlace(): void
    {
        $place = new Place();
        $place->setName('Eiffel Tower');
        $place->setAddress('Champ de Mars, Paris');
        $place->setLatitude(48.8584);
        $place->setLongitude(2.2945);
        $place->setCategory('Landmark');
        $place->setPlaceType('tourist_attraction');
        $place->setPhone('+33 892 70 12 39');
        $place->setRating(4.7);
        $place->setReviewCount(150000);
        $place->setDescription('Famous iron lattice tower');
        $place->setMetadata(['visitors_per_year' => 7_000_000]);

        $em = self::getEntityManager();
        $em->persist($place);
        $em->flush();

        $this->assertNotNull($place->getId());

        $found = $em->getRepository(Place::class)->find($place->getId());

        $this->assertNotNull($found);
        $this->assertSame('Eiffel Tower', $found->getName());
        $this->assertSame('Champ de Mars, Paris', $found->getAddress());
        $this->assertSame(48.8584, $found->getLatitude());
        $this->assertSame(2.2945, $found->getLongitude());
        $this->assertSame('Landmark', $found->getCategory());
        $this->assertSame('tourist_attraction', $found->getPlaceType());
        $this->assertSame('+33 892 70 12 39', $found->getPhone());
        $this->assertSame(4.7, $found->getRating());
        $this->assertSame(150000, $found->getReviewCount());
        $this->assertSame('Famous iron lattice tower', $found->getDescription());
        $this->assertSame(['visitors_per_year' => 7_000_000], $found->getMetadata());
    }

    public function testUpdatePlace(): void
    {
        $place = new Place();
        $place->setName('Old Name');
        $place->setLatitude(0);
        $place->setLongitude(0);
        $place->setCategory('Misc');

        $em = self::getEntityManager();
        $em->persist($place);
        $em->flush();

        $place->setName('New Name');
        $place->setRating(5.0);
        $em->flush();

        $found = $em->getRepository(Place::class)->find($place->getId());
        $this->assertSame('New Name', $found->getName());
        $this->assertSame(5.0, $found->getRating());
    }

    public function testDeletePlace(): void
    {
        $place = new Place();
        $place->setName('Temp Place');
        $place->setLatitude(10);
        $place->setLongitude(20);
        $place->setCategory('Test');

        $em = self::getEntityManager();
        $em->persist($place);
        $em->flush();

        $id = $place->getId();
        $em->remove($place);
        $em->flush();

        $found = $em->getRepository(Place::class)->find($id);
        $this->assertNull($found);
    }

    public function testPlaceIdIsUuid(): void
    {
        $place = new Place();
        $place->setName('UUID Test');
        $place->setLatitude(0);
        $place->setLongitude(0);
        $place->setCategory('Test');

        $em = self::getEntityManager();
        $em->persist($place);
        $em->flush();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $place->getId());
    }
}
