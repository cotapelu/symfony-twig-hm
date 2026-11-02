<?php declare(strict_types=1);

namespace App\Tests\Unit\MiniPrograms\Service;

use App\MiniPrograms\Entity\MiniProgram;
use App\MiniPrograms\Entity\MiniProgramEntity;
use App\MiniPrograms\Event\ProgramInstalledEvent;
use App\MiniPrograms\Repository\MiniProgramRepository;
use App\Shared\EventBus\EventBus;
use App\MiniPrograms\Service\MiniProgramService;
use PHPUnit\Framework\TestCase;

class MiniProgramServiceTest extends TestCase
{
    private MiniProgramService $service;
    private MiniProgramRepository $programRepository;
    private EventBus $eventBus;

    protected function setUp(): void
    {
        $this->programRepository = $this->createMock(MiniProgramRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);

        $this->service = new MiniProgramService(
            $this->programRepository,
            $this->eventBus
        );
    }

    public function testCreateMiniProgram(): void
    {
        $data = [
            'developer_id' => 'dev-123',
            'name' => 'Food Delivery',
            'description' => 'Order food from local restaurants',
            'icon_url' => 'https://example.com/icon.png',
            'app_id' => 'app_food_delivery',
        ];

        $program = $this->service->createMiniProgram($data);

        $this->assertInstanceOf(MiniProgram::class, $program);
        $this->assertEquals('dev-123', $program->getDeveloperId());
        $this->assertEquals('Food Delivery', $program->getName());
        $this->assertEquals('Order food from local restaurants', $program->getDescription());
        $this->assertEquals('https://example.com/icon.png', $program->getIconUrl());
        $this->assertEquals('app_food_delivery', $program->getAppId());
        $this->assertTrue($program->isActive());
    }

    public function testCreateMiniProgramWithMinimalData(): void
    {
        $data = [
            'developer_id' => 'dev-456',
            'name' => 'Simple App',
            'app_id' => 'app_simple',
        ];

        $program = $this->service->createMiniProgram($data);

        $this->assertInstanceOf(MiniProgram::class, $program);
        $this->assertEquals('dev-456', $program->getDeveloperId());
        $this->assertEquals('Simple App', $program->getName());
        $this->assertNull($program->getDescription());
    }

    public function testGetMiniProgram(): void
    {
        $program = new MiniProgram();
        $this->programRepository->method('findByAppId')->willReturn($program);

        $result = $this->service->getMiniProgram('app_123');

        $this->assertNotNull($result);
        $this->assertInstanceOf(MiniProgram::class, $result);
    }

    public function testGetMiniProgramReturnsNullWhenNotFound(): void
    {
        $this->programRepository->method('findByAppId')->willReturn(null);

        $result = $this->service->getMiniProgram('app_nonexistent');

        $this->assertNull($result);
    }

    public function testGetAllMiniPrograms(): void
    {
        $programs = [new MiniProgram(), new MiniProgram()];
        $this->programRepository->method('findAllActive')->willReturn($programs);

        $result = $this->service->getAllMiniPrograms(50);

        $this->assertCount(2, $result);
    }

    public function testGetAllMiniProgramsWithCustomLimit(): void
    {
        $this->programRepository->method('findAllActive')->willReturn([]);

        $result = $this->service->getAllMiniPrograms(20);

        $this->assertCount(0, $result);
    }

    public function testSearchMiniPrograms(): void
    {
        $programs = [new MiniProgram()];
        $this->programRepository->method('searchPrograms')->willReturn($programs);

        $result = $this->service->searchMiniPrograms('food', 20);

        $this->assertCount(1, $result);
    }

    public function testSearchMiniProgramsWithCustomLimit(): void
    {
        $this->programRepository->method('searchPrograms')->willReturn([]);

        $result = $this->service->searchMiniPrograms('delivery', 10);

        $this->assertCount(0, $result);
    }

    public function testGetDeveloperPrograms(): void
    {
        $programs = [new MiniProgram(), new MiniProgram()];
        $this->programRepository->method('findByDeveloper')->willReturn($programs);

        $result = $this->service->getDeveloperPrograms('dev-123', 50);

        $this->assertCount(2, $result);
    }

    public function testGetDeveloperProgramsWithCustomLimit(): void
    {
        $this->programRepository->method('findByDeveloper')->willReturn([]);

        $result = $this->service->getDeveloperPrograms('dev-456', 10);

        $this->assertCount(0, $result);
    }

    public function testMiniProgramProperties(): void
    {
        $program = new MiniProgram();
        $program->setAppId('app_001');
        $program->setName('Test App');
        $program->setDescription('A test application');
        $program->setDeveloperId('dev_123');
        $program->setCategory('utilities');
        $program->setIconUrl('https://example.com/icon.png');
        $program->setCoverUrl('https://example.com/cover.png');
        $program->setLaunchUrl('https://miniapp.example.com');
        $program->setActive(true);

        $this->assertEquals('app_001', $program->getAppId());
        $this->assertEquals('Test App', $program->getName());
        $this->assertEquals('A test application', $program->getDescription());
        $this->assertEquals('dev_123', $program->getDeveloperId());
        $this->assertEquals('utilities', $program->getCategory());
        $this->assertEquals('https://example.com/icon.png', $program->getIconUrl());
        $this->assertEquals('https://example.com/cover.png', $program->getCoverUrl());
        $this->assertEquals('https://miniapp.example.com', $program->getLaunchUrl());
        $this->assertTrue($program->isActive());
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

    public function testMiniProgramCreatedAt(): void
    {
        $program = new MiniProgram();
        $this->assertInstanceOf(\DateTime::class, $program->getCreatedAt());
    }

    public function testMiniProgramIdGenerated(): void
    {
        $program = new MiniProgram();
        $this->assertNotEmpty($program->getId());
    }

    public function testMiniProgramOptionalFields(): void
    {
        $program = new MiniProgram();
        $program->setAppId('app_minimal');
        $program->setName('Minimal App');
        $program->setDeveloperId('dev_001');

        $this->assertNull($program->getDescription());
        $this->assertNull($program->getIconUrl());
        $this->assertNull($program->getCoverUrl());
        $this->assertNull($program->getCategory());
        $this->assertNull($program->getLaunchUrl());
    }
}