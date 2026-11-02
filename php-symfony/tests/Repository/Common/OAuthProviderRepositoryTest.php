<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\OAuthProviderRepository;
use App\Entity\Common\OAuthProvider;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OAuthProviderRepositoryTest extends TestCase
{
    private OAuthProviderRepository&MockObject $repo;
    private EntityManagerInterface&MockObject $em;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(OAuthProviderRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findBy', 'findOneBy', 'count', 'getEntityManager'])
            ->getMock();

        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $provider = $this->createMock(OAuthProvider::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($provider);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->save($provider);
    }

    public function testRemoveRemovesAndFlushes(): void
    {
        $provider = $this->createMock(OAuthProvider::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($provider);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->remove($provider);
    }

    public function testFindById(): void
    {
        $id = 'prov-123';
        $expected = $this->createMock(OAuthProvider::class);

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expected);

        $result = $this->repo->findById($id);
        $this->assertSame($expected, $result);
    }

    public function testFindByUserId(): void
    {
        $userId = 'user-123';
        $expected = [$this->createMock(OAuthProvider::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($expected);

        $result = $this->repo->findByUserId($userId);
        $this->assertSame($expected, $result);
    }

    public function testFindByProvider(): void
    {
        $userId = 'user-123';
        $provider = 'google';
        $expected = $this->createMock(OAuthProvider::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'provider' => $provider])
            ->willReturn($expected);

        $result = $this->repo->findByProvider($userId, $provider);
        $this->assertSame($expected, $result);
    }

    public function testFindByProviderUserId(): void
    {
        $provider = 'facebook';
        $providerUserId = 'fb-12345';
        $expected = $this->createMock(OAuthProvider::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['provider' => $provider, 'providerUserId' => $providerUserId])
            ->willReturn($expected);

        $result = $this->repo->findByProviderUserId($provider, $providerUserId);
        $this->assertSame($expected, $result);
    }

    public function testCountByProvider(): void
    {
        $provider = 'github';
        $count = 5;

        $this->repo->expects($this->once())
            ->method('count')
            ->with(['provider' => $provider])
            ->willReturn($count);

        $result = $this->repo->countByProvider($provider);
        $this->assertSame($count, $result);
    }
}
