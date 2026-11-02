<?php declare(strict_types=1);

namespace App\Tests\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Service\ReadOnlyEntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ReadOnlyEntityManagerProviderTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private ReadOnlyEntityManagerProvider $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->provider = new ReadOnlyEntityManagerProvider($this->registry);
    }

    public function testGetReturnsReadOnlyEntityManager(): void
    {
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $this->registry->expects($this->once())
            ->method('getManager')
            ->with('read_only')
            ->willReturn($em);

        $result = $this->provider->get();
        $this->assertSame($em, $result);
    }
}
