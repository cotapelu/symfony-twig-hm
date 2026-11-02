<?php declare(strict_types=1);

namespace App\Tests\Unit\MiniPrograms\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\MiniPrograms\Entity\MiniProgram;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MiniProgramTest extends TestCase
{
    private function createMiniProgram(): MiniProgram
    {
        return new MiniProgram();
    }

    public function testConstructorGeneratesIdAndCreatedAt(): void
    {
        $prog = $this->createMiniProgram();
        $this->assertIsString($prog->getId());
        $this->assertNotEmpty($prog->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $prog->getCreatedAt());
    }

    public function testDefaultOptionalProperties(): void
    {
        $prog = $this->createMiniProgram();
        // Optional and defaulted properties
        $this->assertNull($prog->getDescription());
        $this->assertNull($prog->getIconUrl());
        $this->assertNull($prog->getCoverUrl());
        $this->assertNull($prog->getCategory());
        $this->assertNull($prog->getLaunchUrl());
        $this->assertSame(0, $prog->getInstallCount());
        $this->assertSame('1.0.0', $prog->getVersion());
        $this->assertNull($prog->getConfig());
        $this->assertTrue($prog->isActive());
    }

    public function testAppIdSetter(): void
    {
        $prog = $this->createMiniProgram();
        $prog->setAppId('app-123');
        $this->assertSame('app-123', $prog->getAppId());
    }

    public function testAppIdThrowsOnEmpty(): void
    {
        $prog = $this->createMiniProgram();
        $this->expectException(\InvalidArgumentException::class);
        $prog->setAppId('   ');
    }

    public function testNameSetter(): void
    {
        $prog = $this->createMiniProgram();
        $prog->setName('My Mini Program');
        $this->assertSame('My Mini Program', $prog->getName());
    }

    public function testNameThrowsOnEmpty(): void
    {
        $prog = $this->createMiniProgram();
        $this->expectException(\InvalidArgumentException::class);
        $prog->setName('');
    }

    public function testDeveloperIdSetter(): void
    {
        $prog = $this->createMiniProgram();
        $prog->setDeveloperId('dev-456');
        $this->assertSame('dev-456', $prog->getDeveloperId());
    }

    public function testDeveloperIdThrowsOnEmpty(): void
    {
        $prog = $this->createMiniProgram();
        $this->expectException(\InvalidArgumentException::class);
        $prog->setDeveloperId('   ');
    }

    public function testInstallCountSetter(): void
    {
        $prog = $this->createMiniProgram();
        $prog->setInstallCount(100);
        $this->assertSame(100, $prog->getInstallCount());
    }

    public function testInstallCountThrowsOnNegative(): void
    {
        $prog = $this->createMiniProgram();
        $this->expectException(\InvalidArgumentException::class);
        $prog->setInstallCount(-5);
    }

    public function testIncrementInstallCount(): void
    {
        $prog = $this->createMiniProgram();
        $prog->setInstallCount(10);
        $prog->incrementInstallCount();
        $this->assertSame(11, $prog->getInstallCount());
        $prog->incrementInstallCount();
        $this->assertSame(12, $prog->getInstallCount());
    }

    public function testVersionSetter(): void
    {
        $prog = $this->createMiniProgram();
        $prog->setVersion('2.1.0');
        $this->assertSame('2.1.0', $prog->getVersion());
    }

    public function testActiveFlag(): void
    {
        $prog = $this->createMiniProgram();
        $this->assertTrue($prog->isActive());
        $prog->setActive(false);
        $this->assertFalse($prog->isActive());
    }

    public function testConfigSetter(): void
    {
        $prog = $this->createMiniProgram();
        $config = ['key' => 'value', 'num' => 123];
        $prog->setConfig($config);
        $this->assertSame($config, $prog->getConfig());
    }

    public function testConfigThrowsOnTooManyKeys(): void
    {
        $prog = $this->createMiniProgram();
        $big = [];
        for ($i = 0; $i < 101; $i++) {
            $big["k$i"] = $i;
        }
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Config array too large');
        $prog->setConfig($big);
    }

    public function testConfigThrowsWhenTooLarge(): void
    {
        $prog = $this->createMiniProgram();
        $bigValue = str_repeat('a', 70000);
        $config = ['big' => $bigValue];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Config data too large');
        $prog->setConfig($config);
    }

    public function testOtherSetters(): void
    {
        $prog = $this->createMiniProgram();

        $prog->setDescription('A description');
        $this->assertSame('A description', $prog->getDescription());

        $prog->setIconUrl('http://example.com/icon.png');
        $this->assertSame('http://example.com/icon.png', $prog->getIconUrl());

        $prog->setCoverUrl('http://example.com/cover.jpg');
        $this->assertSame('http://example.com/cover.jpg', $prog->getCoverUrl());

        $prog->setCategory('education');
        $this->assertSame('education', $prog->getCategory());

        $prog->setLaunchUrl('http://example.com/launch');
        $this->assertSame('http://example.com/launch', $prog->getLaunchUrl());
    }
}
