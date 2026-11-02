<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\MediaFolderRepository;
use App\Entity\Common\MediaFolder;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MediaFolderRepositoryTest extends TestCase
{
    private MediaFolderRepository&MockObject $repo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(MediaFolderRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
    }

    public function testFindRootFoldersWithoutUserBuildsCorrectQuery(): void
    {
        // No userId: only where parentId IS NULL
        $this->qb->expects($this->once())
            ->method('where')
            ->with('f.parentId IS NULL')
            ->willReturnSelf();
        $this->qb->expects($this->never())
            ->method('andWhere');
        $this->qb->expects($this->never())
            ->method('setParameter');
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($this->qb);

        $result = $this->repo->findRootFolders();
        $this->assertIsArray($result);
    }

    public function testFindRootFoldersWithUserBuildsCorrectQuery(): void
    {
        $userId = 'user-123';
        $this->qb->expects($this->once())
            ->method('where')
            ->with('f.parentId IS NULL')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('f.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use ($userId) {
                $this->assertSame('userId', $name);
                $this->assertSame($userId, $value);
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($this->qb);

        $result = $this->repo->findRootFolders($userId);
        $this->assertIsArray($result);
    }
}
