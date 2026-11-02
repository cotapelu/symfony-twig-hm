<?php declare(strict_types=1);

namespace App\Tests\Unit\MiniPrograms\Controller;

use App\MiniPrograms\Entity\MiniProgram;
use App\MiniPrograms\Service\MiniProgramService;
use App\MiniPrograms\Controller\MiniProgramsController;
use PHPUnit\Framework\TestCase;

class MiniProgramsControllerTest extends TestCase
{
    private MiniProgramsController $controller;
    private MiniProgramService $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(MiniProgramService::class);
        $this->controller = new MiniProgramsController($this->service);
    }

    public function testListProgramsReturnsSuccess(): void
    {
        $programs = [new MiniProgram(), new MiniProgram()];
        $this->service->method('getAllMiniPrograms')->willReturn($programs);

        $request = ['query' => ['limit' => 50]];

        $response = $this->controller->listPrograms($request);

        $this->assertIsArray($response);
        $this->assertCount(2, $response);
    }

    public function testListProgramsWithDefaultLimit(): void
    {
        $this->service->method('getAllMiniPrograms')->willReturn([]);

        $request = ['query' => []];

        $response = $this->controller->listPrograms($request);

        $this->assertIsArray($response);
    }

    public function testGetProgramReturnsSuccess(): void
    {
        $program = new MiniProgram();
        $this->service->method('getMiniProgram')->willReturn($program);

        $response = $this->controller->getProgram('app_123');

        $this->assertIsArray($response);
    }

    public function testGetProgramReturnsErrorWhenNotFound(): void
    {
        $this->service->method('getMiniProgram')->willReturn(null);

        $response = $this->controller->getProgram('app_nonexistent');

        $this->assertEquals(404, $response['error']);
    }

    public function testSearchProgramsReturnsResults(): void
    {
        $programs = [new MiniProgram()];
        $this->service->method('searchMiniPrograms')->willReturn($programs);

        $request = ['query' => ['q' => 'food', 'limit' => 20]];

        $response = $this->controller->searchPrograms($request);

        $this->assertIsArray($response);
        $this->assertCount(1, $response);
    }

    public function testSearchProgramsReturnsEmptyForEmptyQuery(): void
    {
        $request = ['query' => ['q' => '', 'limit' => 20]];

        $response = $this->controller->searchPrograms($request);

        $this->assertIsArray($response);
        $this->assertEmpty($response);
    }

    public function testInstallProgramReturnsSuccess(): void
    {
        $this->service->method('installMiniProgram')->willReturn(null);

        $response = $this->controller->installProgram('app_123');

        $this->assertEquals(200, $response['message']);
    }

    public function testInstallProgramReturnsErrorWhenNotFound(): void
    {
        $this->service->method('installMiniProgram')->willThrowException(new \InvalidArgumentException('Mini program not found'));

        $response = $this->controller->installProgram('app_nonexistent');

        $this->assertEquals(404, $response['error']);
    }

    public function testMiniProgramEntityProperties(): void
    {
        $program = new MiniProgram();
        $program->setAppId('app_001');
        $program->setName('Food Delivery');
        $program->setDescription('Order food');
        $program->setDeveloperId('dev_123');
        $program->setCategory('food');
        $program->setIconUrl('https://example.com/icon.png');
        $program->setCoverUrl('https://example.com/cover.png');
        $program->setLaunchUrl('https://miniapp.example.com');
        $program->setActive(true);

        $this->assertEquals('app_001', $program->getAppId());
        $this->assertEquals('Food Delivery', $program->getName());
        $this->assertEquals('Order food', $program->getDescription());
        $this->assertEquals('dev_123', $program->getDeveloperId());
        $this->assertEquals('food', $program->getCategory());
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
}