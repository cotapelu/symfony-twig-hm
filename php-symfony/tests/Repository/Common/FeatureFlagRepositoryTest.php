<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\FeatureFlagRepository;
use App\Entity\Common\FeatureFlag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FeatureFlagRepositoryTest extends TestCase
{
    private FeatureFlagRepository&MockObject $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(FeatureFlagRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findBy', 'findOneBy', 'count'])
            ->getMock();
    }

    public function testFindOneByKey(): void
    {
        $key = 'new-feature';
        $expected = $this->createMock(FeatureFlag::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key])
            ->willReturn($expected);

        $result = $this->repo->findOneByKey($key);
        $this->assertSame($expected, $result);
    }

    public function testFindByKeyAndEnvironment(): void
    {
        $key = 'feature-x';
        $environment = 'production';
        $expected = $this->createMock(FeatureFlag::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with([
                'key' => $key,
                'environment' => $environment,
                'enabled' => true,
            ])
            ->willReturn($expected);

        $result = $this->repo->findByKeyAndEnvironment($key, $environment);
        $this->assertSame($expected, $result);
    }

    public function testFindEnabledFlagsWithEnvironment(): void
    {
        $environment = 'staging';
        $expected = [$this->createMock(FeatureFlag::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['enabled' => true, 'environment' => $environment])
            ->willReturn($expected);

        $result = $this->repo->findEnabledFlags($environment);
        $this->assertSame($expected, $result);
    }

    public function testFindEnabledFlagsWithoutEnvironment(): void
    {
        $expected = [$this->createMock(FeatureFlag::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['enabled' => true])
            ->willReturn($expected);

        $result = $this->repo->findEnabledFlags();
        $this->assertSame($expected, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'DEPRECATED';
        $expected = [$this->createMock(FeatureFlag::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($expected);

        $result = $this->repo->findByStatus($status);
        $this->assertSame($expected, $result);
    }
}
