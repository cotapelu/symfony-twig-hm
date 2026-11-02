<?php declare(strict_types=1);

namespace App\Tests\Unit\MiniPrograms\Entity;

use App\MiniPrograms\Entity\MiniProgram;
use PHPUnit\Framework\TestCase;

class MiniProgramEntityTest extends TestCase
{
    public function testMiniProgramCreation(): void
    {
        $program = new MiniProgram();
        $program->setAppId('app_001');
        $program->setName('Food Delivery');
        $program->setDescription('Order food from local restaurants');
        $program->setDeveloperId('dev_123');
        $program->setCategory('food');
        $program->setLaunchUrl('https://miniapp.fooddelivery.com');
        $program->setActive(true);

        $this->assertEquals('app_001', $program->getAppId());
        $this->assertEquals('Food Delivery', $program->getName());
        $this->assertEquals('Order food from local restaurants', $program->getDescription());
        $this->assertEquals('dev_123', $program->getDeveloperId());
        $this->assertEquals('food', $program->getCategory());
    }

    public function testMiniProgramIdGenerated(): void
    {
        $program = new MiniProgram();
        
        $this->assertNotEmpty($program->getId());
    }

    public function testMiniProgramDefaultActive(): void
    {
        $program = new MiniProgram();
        $this->assertTrue($program->isActive());
    }

    public function testMiniProgramDeactivation(): void
    {
        $program = new MiniProgram();
        
        $program->setActive(false);
        
        $this->assertFalse($program->isActive());
    }

    public function testMiniProgramImages(): void
    {
        $program = new MiniProgram();
        $program->setIconUrl('https://example.com/icon.png');
        $program->setCoverUrl('https://example.com/cover.png');

        $this->assertEquals('https://example.com/icon.png', $program->getIconUrl());
        $this->assertEquals('https://example.com/cover.png', $program->getCoverUrl());
    }

    public function testMiniProgramCategory(): void
    {
        $program = new MiniProgram();
        
        $program->setCategory('shopping');
        $this->assertEquals('shopping', $program->getCategory());
        
        $program->setCategory('food');
        $this->assertEquals('food', $program->getCategory());
        
        $program->setCategory('travel');
        $this->assertEquals('travel', $program->getCategory());
        
        $program->setCategory('finance');
        $this->assertEquals('finance', $program->getCategory());
    }

    public function testMiniProgramLaunchUrl(): void
    {
        $program = new MiniProgram();
        $program->setLaunchUrl('https://miniapp.example.com/start');

        $this->assertEquals('https://miniapp.example.com/start', $program->getLaunchUrl());
    }

    public function testMiniProgramCreatedAt(): void
    {
        $program = new MiniProgram();
        
        $this->assertInstanceOf(\DateTime::class, $program->getCreatedAt());
    }

    public function testMiniProgramOptionalFields(): void
    {
        $program = new MiniProgram();
        $program->setAppId('app_minimal');
        $program->setName('Minimal App');
        $program->setDeveloperId('dev_001');
        
        // Optional fields should be null
        $this->assertNull($program->getDescription());
        $this->assertNull($program->getIconUrl());
        $this->assertNull($program->getCoverUrl());
        $this->assertNull($program->getCategory());
        $this->assertNull($program->getLaunchUrl());
    }
}
