<?php declare(strict_types=1);

namespace App\Tests\Unit\FeatureFlags\Repository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\FeatureFlags\Repository\FeatureFlagRepository;
use App\FeatureFlags\Entity\FeatureFlag;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FeatureFlagRepositoryTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private FeatureFlagRepository $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = new FeatureFlagRepository($this->em);
    }

    public function testFindByNameReturnsFeatureFlag(): void
    {
        $name = 'my_feature';
        $expected = $this->createMock(FeatureFlag::class);

        $entityRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $name])
            ->willReturn($expected);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(FeatureFlag::class)
            ->willReturn($entityRepo);

        $result = $this->repo->findByName($name);
        $this->assertSame($expected, $result);
    }

    public function testFindByNameReturnsNullWhenNotFound(): void
    {
        $name = 'missing';

        $entityRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $name])
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(FeatureFlag::class)
            ->willReturn($entityRepo);

        $result = $this->repo->findByName($name);
        $this->assertNull($result);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $flag = $this->createMock(FeatureFlag::class);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($flag)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $result = $this->repo->save($flag);
        $this->assertSame($flag, $result);
    }

    public function testFindAllReturnsAllFeatureFlags(): void
    {
        $flags = [$this->createMock(FeatureFlag::class), $this->createMock(FeatureFlag::class)];

        $entityRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findAll')
            ->willReturn($flags);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(FeatureFlag::class)
            ->willReturn($entityRepo);

        $result = $this->repo->findAll();
        $this->assertSame($flags, $result);
    }
}
