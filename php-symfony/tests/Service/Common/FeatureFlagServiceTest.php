<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\FeatureFlag;
use App\Repository\Common\FeatureFlagRepository;
use App\Service\Common\FeatureFlagService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FeatureFlagServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private FeatureFlagRepository&MockObject $repository;
    private FeatureFlagService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(FeatureFlagRepository::class);

        $this->em->method('getRepository')
            ->with(FeatureFlag::class)
            ->willReturn($this->repository);

        $this->service = new FeatureFlagService($this->em, $this->repository);
    }

    public function testGetFlagWithEnvironment(): void
    {
        $key = 'feature-x';
        $env = 'prod';
        $expected = $this->createMock(FeatureFlag::class);

        $this->repository->expects($this->once())
            ->method('findByKeyAndEnvironment')
            ->with($key, $env)
            ->willReturn($expected);

        $result = $this->service->getFlag($key, $env);
        $this->assertSame($expected, $result);
    }

    public function testGetFlagWithoutEnvironmentWhenNotFoundReturnsNull(): void
    {
        $key = 'feature-y';
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key, 'enabled' => true])
            ->willReturn(null);

        $result = $this->service->getFlag($key);
        $this->assertNull($result);
    }

    public function testGetFlagWithoutEnvironmentReturnsFlag(): void
    {
        $key = 'feature-z';
        $expected = $this->createMock(FeatureFlag::class);
        $expected->method('isEnabled')->willReturn(true);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key, 'enabled' => true])
            ->willReturn($expected);

        $result = $this->service->getFlag($key);
        $this->assertSame($expected, $result);
    }

    public function testIsEnabledReturnsTrueWhenFlagExistsAndEnabled(): void
    {
        $service = $this->getMockBuilder(FeatureFlagService::class)
            ->setConstructorArgs([$this->em, $this->repository])
            ->onlyMethods(['getFlag'])
            ->getMock();

        $flag = $this->createMock(FeatureFlag::class);
        $flag->method('isEnabled')->willReturn(true);
        $service->method('getFlag')->with('key1')->willReturn($flag);

        $this->assertTrue($service->isEnabled('key1'));
    }

    public function testIsEnabledReturnsFalseWhenFlagNotFound(): void
    {
        $service = $this->getMockBuilder(FeatureFlagService::class)
            ->setConstructorArgs([$this->em, $this->repository])
            ->onlyMethods(['getFlag'])
            ->getMock();

        $service->method('getFlag')->with('key2')->willReturn(null);

        $this->assertFalse($service->isEnabled('key2'));
    }

    public function testCreateFlagGeneratesIdAndPersists(): void
    {
        $key = 'new-feature';
        $env = 'default';
        $rules = ['percentage' => 10];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (FeatureFlag $flag) use (&$captured, $key, $env, $rules) {
                $captured = $flag;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createFlag($key, $env, $rules);

        $this->assertInstanceOf(FeatureFlag::class, $result);
        $this->assertSame($key, $result->getKey());
        $this->assertSame($env, $result->getEnvironment());
        $this->assertSame($rules, $result->getRules());
        $this->assertTrue($result->isEnabled());
        $this->assertSame('ACTIVE', $result->getStatus());
        $this->assertNotNull($result->getId());
        $this->assertSame($captured, $result);
    }

    public function testUpdateFlagWhenFoundUpdatesRulesAndFlushes(): void
    {
        $key = 'update-key';
        $rules = ['new' => 'rules'];

        $existingFlag = $this->createMock(FeatureFlag::class);
        $existingFlag->method('getId')->willReturn('flag-id');
        $existingFlag->expects($this->once())
            ->method('setRules')
            ->with($rules);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key])
            ->willReturn($existingFlag);

        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateFlag($key, $rules);
        $this->assertSame($existingFlag, $result);
    }

    public function testUpdateFlagWhenNotFoundReturnsNull(): void
    {
        $key = 'missing-key';
        $rules = [];

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key])
            ->willReturn(null);

        $result = $this->service->updateFlag($key, $rules);
        $this->assertNull($result);
    }

    public function testToggleFlagWhenFoundTogglesEnabledAndFlushes(): void
    {
        $key = 'toggle-key';
        $enabled = false;

        $flag = $this->createMock(FeatureFlag::class);
        $flag->method('getId')->willReturn('flag-id');
        $flag->expects($this->once())
            ->method('setEnabled')
            ->with($enabled);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key])
            ->willReturn($flag);

        $this->em->expects($this->once())->method('flush');

        $result = $this->service->toggleFlag($key, $enabled);
        $this->assertSame($flag, $result);
    }

    public function testToggleFlagWhenNotFoundReturnsNull(): void
    {
        $key = 'missing-toggle';
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key])
            ->willReturn(null);

        $result = $this->service->toggleFlag($key, true);
        $this->assertNull($result);
    }

    public function testDeleteFlagWhenFoundRemovesAndFlushesReturnsTrue(): void
    {
        $key = 'delete-key';
        $flag = $this->createMock(FeatureFlag::class);
        $flag->method('getId')->willReturn('flag-id');

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key])
            ->willReturn($flag);

        $this->em->expects($this->once())->method('remove')->with($flag);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->deleteFlag($key);
        $this->assertTrue($result);
    }

    public function testDeleteFlagWhenNotFoundReturnsFalse(): void
    {
        $key = 'missing-delete';
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key])
            ->willReturn(null);

        $result = $this->service->deleteFlag($key);
        $this->assertFalse($result);
    }

    public function testGetAllFlagsCallsFindAll(): void
    {
        $expected = [$this->createMock(FeatureFlag::class)];
        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($expected);

        $result = $this->service->getAllFlags();
        $this->assertSame($expected, $result);
    }

    public function testGetFlagsByEnvironmentCallsFindByEnabled(): void
    {
        $environment = 'staging';
        $expected = [$this->createMock(FeatureFlag::class)];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['enabled' => true])
            ->willReturn($expected);

        $result = $this->service->getFlagsByEnvironment($environment);
        $this->assertSame($expected, $result);
    }
}
