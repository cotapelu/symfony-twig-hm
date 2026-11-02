<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\MediaFileRepository;
use App\Entity\Common\MediaFile;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MediaFileRepositoryTest extends TestCase
{
    private MediaFileRepository&MockObject $repo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(MediaFileRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
    }

    public function testDeleteByUserIdBuildsCorrectQueryAndExecutes(): void
    {
        $userId = 'user-123';
        $affected = 3;

        $this->qb->expects($this->once())
            ->method('delete')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('f.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn($affected);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($this->qb);

        $result = $this->repo->deleteByUserId($userId);
        $this->assertSame($affected, $result);
    }

    public function testDeleteByFolderIdBuildsCorrectQueryAndExecutes(): void
    {
        $folderId = 'folder-456';
        $affected = 7;

        $this->qb->expects($this->once())
            ->method('delete')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('f.folderId = :folderId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with('folderId', $folderId)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn($affected);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($this->qb);

        $result = $this->repo->deleteByFolderId($folderId);
        $this->assertSame($affected, $result);
    }

    public function testGetTotalSizeByUserIdReturnsInt(): void
    {
        $userId = 'user-789';
        $totalSize = '123456'; // string from DB

        $this->qb->expects($this->once())
            ->method('select')
            ->with('SUM(f.size)')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('f.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn($totalSize);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($this->qb);

        $result = $this->repo->getTotalSizeByUserId($userId);
        $this->assertSame(123456, $result);
        $this->assertIsInt($result);
    }
}
