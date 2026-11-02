<?php

declare(strict_types=1);

namespace Tests\Unit\MiniPrograms\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\MiniPrograms\Service\MiniProgramService;
use App\MiniPrograms\Entity\MiniProgram;
use App\MiniPrograms\Repository\MiniProgramRepository;
use App\Shared\EventBus\EventBus;
use App\MiniPrograms\Event\ProgramInstalledEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MiniProgramServiceTest extends TestCase
{
    private MiniProgramRepository&MockObject $programRepository;
    private EventBus&MockObject $eventBus;
    private MiniProgramService $service;

    protected function setUp(): void
    {
        $this->programRepository = $this->createMock(MiniProgramRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);
        $this->service = new MiniProgramService($this->programRepository, $this->eventBus);
    }

    public function testCreateMiniProgramSuccess(): void
    {
        $this->programRepository->expects($this->once())
            ->method('findByAppId')
            ->with('app123')
            ->willReturn(null);

        $this->programRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(MiniProgram::class));

        $data = [
            'developer_id' => 'dev1',
            'name' => 'MyApp',
            'app_id' => 'app123',
            'category' => 'productivity',
            'version' => '1.0.0',
        ];

        $result = $this->service->createMiniProgram($data);

        $this->assertInstanceOf(MiniProgram::class, $result);
        $this->assertSame('dev1', $result->getDeveloperId());
        $this->assertSame('MyApp', $result->getName());
        $this->assertSame('app123', $result->getAppId());
        $this->assertSame('productivity', $result->getCategory());
        $this->assertSame('1.0.0', $result->getVersion());
    }

    public function testCreateMiniProgramFailsWhenMissingDeveloperId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'developer_id' is required");
        $this->service->createMiniProgram(['name' => 'n', 'app_id' => 'a']);
    }

    public function testCreateMiniProgramFailsWhenMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'name' is required");
        $this->service->createMiniProgram(['developer_id' => 'd', 'app_id' => 'a']);
    }

    public function testCreateMiniProgramFailsWhenMissingAppId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'app_id' is required");
        $this->service->createMiniProgram(['developer_id' => 'd', 'name' => 'n']);
    }

    public function testCreateMiniProgramFailsWhenDuplicateAppId(): void
    {
        $existing = $this->createMock(MiniProgram::class);
        $this->programRepository->expects($this->once())
            ->method('findByAppId')
            ->with('app123')
            ->willReturn($existing);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('App ID already exists');

        $this->service->createMiniProgram([
            'developer_id' => 'dev1',
            'name' => 'MyApp',
            'app_id' => 'app123',
        ]);
    }

    public function testCreateMiniProgramFailsWhenInvalidCategory(): void
    {
        $this->programRepository->expects($this->once())
            ->method('findByAppId')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid category');

        $this->service->createMiniProgram([
            'developer_id' => 'dev1',
            'name' => 'MyApp',
            'app_id' => 'app123',
            'category' => 'invalidcat',
        ]);
    }

    public function testCreateMiniProgramFailsWhenConfigNotArray(): void
    {
        $this->programRepository->expects($this->once())
            ->method('findByAppId')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Config must be an array');

        $this->service->createMiniProgram([
            'developer_id' => 'dev1',
            'name' => 'MyApp',
            'app_id' => 'app123',
            'config' => 'not an array',
        ]);
    }

    public function testInstallMiniProgramSuccess(): void
    {
        $program = $this->createMock(MiniProgram::class);
        $program->method('isActive')->willReturn(true);
        $program->method('getDeveloperId')->willReturn('dev1');

        $this->programRepository->expects($this->once())
            ->method('findByAppId')
            ->with('app123')
            ->willReturn($program);

        $this->programRepository->expects($this->once())
            ->method('incrementInstallCount')
            ->with('app123')
            ->willReturn(1);

        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ProgramInstalledEvent::class));

        $this->service->installMiniProgram('app123', 'user456');
    }

    public function testInstallMiniProgramFailsWhenNotFound(): void
    {
        $this->programRepository->expects($this->once())
            ->method('findByAppId')
            ->with('app123')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mini program not found');

        $this->service->installMiniProgram('app123', 'user456');
    }

    public function testInstallMiniProgramFailsWhenInactive(): void
    {
        $program = $this->createMock(MiniProgram::class);
        $program->method('isActive')->willReturn(false);

        $this->programRepository->expects($this->once())
            ->method('findByAppId')
            ->with('app123')
            ->willReturn($program);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mini program is not active');

        $this->service->installMiniProgram('app123', 'user456');
    }

    public function testGetMiniProgram(): void
    {
        $program = $this->createMock(MiniProgram::class);
        $this->programRepository->expects($this->once())
            ->method('findByAppId')
            ->with('app123')
            ->willReturn($program);

        $result = $this->service->getMiniProgram('app123');
        $this->assertSame($program, $result);
    }
}
