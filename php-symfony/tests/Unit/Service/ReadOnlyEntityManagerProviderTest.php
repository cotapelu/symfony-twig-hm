<?php declare(strict_types=1);

namespace App\Tests\Unit\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Service\ReadOnlyEntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ReadOnlyEntityManagerProviderTest extends TestCase
{
    private ManagerRegistry $registryMock;
    private ReadOnlyEntityManagerProvider $provider;

    protected function setUp(): void
    {
        $this->registryMock = $this->createMock(ManagerRegistry::class);
        $this->provider = new ReadOnlyEntityManagerProvider($this->registryMock);
    }

    public function testGetReturnsReadOnlyEntityManager(): void
    {
        $readOnlyEm = $this->createMock(EntityManagerInterface::class);
        $this->registryMock->expects($this->once())
            ->method('getManager')
            ->with('read_only')
            ->willReturn($readOnlyEm);

        $result = $this->provider->get();
        $this->assertSame($readOnlyEm, $result);
    }
}
