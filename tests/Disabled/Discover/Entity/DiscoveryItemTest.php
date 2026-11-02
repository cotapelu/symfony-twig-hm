<?php declare(strict_types=1);

namespace App\Tests\Unit\Discover\Entity;

use App\Discover\Entity\DiscoveryItem;
use PHPUnit\Framework\TestCase;

class DiscoveryItemTest extends TestCase
{
    public function testConstructorSetsRequiredProperties(): void
    {
        $item = new DiscoveryItem(1, 'place', 'Coffee Shop');
        
        $this->assertNull($item->getId());
        $this->assertEquals(1, $item->getUserId());
        $this->assertEquals('place', $item->getType());
        $this->assertEquals('Coffee Shop', $item->getTitle());
        $this->assertEquals(0, $item->getScore());
    }

    public function testSetDescription(): void
    {
        $item = new DiscoveryItem(1, 'place', 'Coffee Shop');
        $item->setDescription('Best coffee in town');
        
        $this->assertEquals('Best coffee in town', $item->getDescription());
    }

    public function testSetImageUrl(): void
    {
        $item = new DiscoveryItem(1, 'place', 'Coffee Shop');
        $item->setImageUrl('https://example.com/image.jpg');
        
        $this->assertEquals('https://example.com/image.jpg', $item->getImageUrl());
    }

    public function testSetLocation(): void
    {
        $item = new DiscoveryItem(1, 'place', 'Coffee Shop');
        $item->setLocation(10.7769, 106.7009);
        
        $this->assertEquals(10.7769, $item->getLatitude());
        $this->assertEquals(106.7009, $item->getLongitude());
    }

    public function testSetAddress(): void
    {
        $item = new DiscoveryItem(1, 'place', 'Coffee Shop');
        $item->setAddress('123 Main Street');
        
        $this->assertEquals('123 Main Street', $item->getAddress());
    }

    public function testSetTags(): void
    {
        $item = new DiscoveryItem(1, 'place', 'Coffee Shop');
        $item->setTags(['coffee', 'wifi', 'quiet']);
        
        $this->assertCount(3, $item->getTags());
    }

    public function testIncrementScore(): void
    {
        $item = new DiscoveryItem(1, 'place', 'Coffee Shop');
        $this->assertEquals(0, $item->getScore());
        
        $item->incrementScore();
        
        $this->assertEquals(1, $item->getScore());
    }

    public function testMultipleIncrementScore(): void
    {
        $item = new DiscoveryItem(1, 'place', 'Coffee Shop');
        
        $item->incrementScore();
        $item->incrementScore();
        $item->incrementScore();
        
        $this->assertEquals(3, $item->getScore());
    }

    public function testSetId(): void
    {
        $item = new DiscoveryItem(1, 'place', 'Coffee Shop');
        $item->setId(42);
        
        $this->assertEquals(42, $item->getId());
    }
}
