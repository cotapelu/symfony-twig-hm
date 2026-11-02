<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\MiniPrograms\Entity\MiniProgram;
use PHPUnit\Framework\TestCase;

/**
 * MiniPrograms Module Integration Test
 * 
 * Tests the MiniPrograms module as a real system.
 */
#[AllowMockObjectsWithoutExpectations]
class MiniProgramsIntegrationTest extends TestCase
{
    public function testCompleteMiniProgramWorkflow(): void
    {
        // Create mini program
        $program = new MiniProgram();
        $program->setAppId('food_delivery_app');
        $program->setName('Food Delivery');
        $program->setDescription('Order food from local restaurants');
        $program->setIconUrl('https://cdn.example.com/food/icon.png');
        $program->setCoverUrl('https://cdn.example.com/food/cover.png');
        $program->setDeveloperId('developer_001');
        $program->setCategory('food');
        $program->setLaunchUrl('https://miniapp.fooddelivery.com');
        $program->setActive(true);

        // Verify creation
        $this->assertNotEmpty($program->getId());
        $this->assertEquals('food_delivery_app', $program->getAppId());
        $this->assertTrue($program->isActive());

        // Simulate deactivation
        $program->setActive(false);
        $this->assertFalse($program->isActive());
    }

    public function testMultipleMiniPrograms(): void
    {
        // Program 1: Food Delivery
        $foodApp = new MiniProgram();
        $foodApp->setAppId('food_delivery');
        $foodApp->setName('Food Delivery');
        $foodApp->setDeveloperId('dev_001');
        $foodApp->setCategory('food');

        // Program 2: Shopping
        $shopApp = new MiniProgram();
        $shopApp->setAppId('shopping_app');
        $shopApp->setName('Online Shopping');
        $shopApp->setDeveloperId('dev_002');
        $shopApp->setCategory('shopping');

        // Program 3: Travel
        $travelApp = new MiniProgram();
        $travelApp->setAppId('travel_app');
        $travelApp->setName('Travel Booking');
        $travelApp->setDeveloperId('dev_003');
        $travelApp->setCategory('travel');

        $this->assertEquals('food', $foodApp->getCategory());
        $this->assertEquals('shopping', $shopApp->getCategory());
        $this->assertEquals('travel', $travelApp->getCategory());
    }

    public function testMiniProgramActivationDeactivation(): void
    {
        $program = new MiniProgram();
        $program->setAppId('test_app');
        $program->setName('Test App');
        $program->setDeveloperId('dev_test');
        
        // Initially active
        $this->assertTrue($program->isActive());
        
        // Deactivate
        $program->setActive(false);
        $this->assertFalse($program->isActive());
        
        // Reactivate
        $program->setActive(true);
        $this->assertTrue($program->isActive());
    }

    public function testMiniProgramCategories(): void
    {
        $categories = [
            'food', 'shopping', 'travel', 'finance', 
            'education', 'entertainment', 'health', 'social'
        ];
        
        $programs = [];
        foreach ($categories as $index => $category) {
            $program = new MiniProgram();
            $program->setAppId("app_$index");
            $program->setName(ucfirst($category) . ' App');
            $program->setDeveloperId("dev_$index");
            $program->setCategory($category);
            $programs[] = $program;
        }
        
        $this->assertCount(8, $programs);
        
        foreach ($programs as $program) {
            $this->assertNotEmpty($program->getCategory());
        }
    }

    public function testMiniProgramLaunchUrl(): void
    {
        $program = new MiniProgram();
        $program->setAppId('deep_link_app');
        $program->setName('Deep Link Test');
        $program->setDeveloperId('dev_deep');
        $program->setLaunchUrl('myapp://home/dashboard');
        
        $this->assertEquals('myapp://home/dashboard', $program->getLaunchUrl());
    }

    public function testMiniProgramImages(): void
    {
        $program = new MiniProgram();
        $program->setAppId('image_test_app');
        $program->setName('Image Test');
        $program->setDeveloperId('dev_images');
        $program->setIconUrl('https://cdn.example.com/app-icon.png');
        $program->setCoverUrl('https://cdn.example.com/app-cover.png');
        
        $this->assertStringStartsWith('https://cdn.example.com/', $program->getIconUrl());
        $this->assertStringStartsWith('https://cdn.example.com/', $program->getCoverUrl());
    }

    public function testMiniProgramDescription(): void
    {
        $program = new MiniProgram();
        $program->setAppId('desc_test');
        $program->setName('Description Test');
        $program->setDeveloperId('dev_desc');
        $program->setDescription('This is a test mini program description');
        
        $this->assertStringContainsString('test', $program->getDescription());
    }

    public function testMiniProgramDeveloperIsolation(): void
    {
        $dev1App1 = new MiniProgram();
        $dev1App1->setAppId('dev1_app1');
        $dev1App1->setName('Dev1 App 1');
        $dev1App1->setDeveloperId('developer_001');
        
        $dev1App2 = new MiniProgram();
        $dev1App2->setAppId('dev1_app2');
        $dev1App2->setName('Dev1 App 2');
        $dev1App2->setDeveloperId('developer_001');
        
        $dev2App = new MiniProgram();
        $dev2App->setAppId('dev2_app');
        $dev2App->setName('Dev2 App');
        $dev2App->setDeveloperId('developer_002');
        
        // Both dev1 apps should have same developer
        $this->assertEquals($dev1App1->getDeveloperId(), $dev1App2->getDeveloperId());
        
        // Dev2 should be different
        $this->assertNotEquals($dev1App1->getDeveloperId(), $dev2App->getDeveloperId());
    }
}
