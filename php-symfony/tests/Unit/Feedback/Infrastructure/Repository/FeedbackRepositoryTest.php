<?php declare(strict_types=1);

namespace Tests\Unit\Feedback\Infrastructure\Repository;

use App\Feedback\Domain\Entity\Feedback;
use App\Feedback\Infrastructure\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeedbackRepositoryTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ClassMetadata $classMetadata;
    private FeedbackRepository $repository;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->classMetadata->name = Feedback::class;
        $this->repository = new FeedbackRepository($this->em, $this->classMetadata);
    }

    private function createFeedbackMock(): MockObject
    {
        return $this->createMock(Feedback::class);
    }

    private function createQueryBuilderMock(): MockObject
    {
        return $this->createMock(QueryBuilder::class);
    }

    private function createQueryMock(): MockObject
    {
        return $this->createMock(Query::class);
    }

    // ==================== findById ====================

    public function testFindById_ReturnsNull_WhenNotFound(): void
    {
        $this->em->expects($this->once())
            ->method('find')
            ->with(Feedback::class, 'nonexistent')
            ->willReturn(null);

        $result = $this->repository->findById('nonexistent');
        $this->assertNull($result);
    }

    public function testFindById_ReturnsFeedback_WhenFound(): void
    {
        $feedback = $this->createFeedbackMock();
        $this->em->expects($this->once())
            ->method('find')
            ->with(Feedback::class, 'feedback-123')
            ->willReturn($feedback);

        $result = $this->repository->findById('feedback-123');
        $this->assertSame($feedback, $result);
    }

    // ==================== findByNumber ====================

    public function testFindByNumber_ReturnsNull_WhenNotFound(): void
    {
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();

        $this->em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('f')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Feedback::class, 'f')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('f.feedbackNumber = :number')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('number', 'NUM123')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getOneOrNullResult')->willReturn(null);

        $result = $this->repository->findByNumber('NUM123');
        $this->assertNull($result);
    }

    public function testFindByNumber_ReturnsFeedback_WhenFound(): void
    {
        $feedback = $this->createFeedbackMock();
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();

        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('f')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Feedback::class, 'f')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('f.feedbackNumber = :number')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('number', 'NUM456')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getOneOrNullResult')->willReturn($feedback);

        $result = $this->repository->findByNumber('NUM456');
        $this->assertSame($feedback, $result);
    }

    // ==================== findByCitizen ====================

    public function testFindByCitizen_ReturnsEmptyArray_WhenNoResults(): void
    {
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();

        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('f')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Feedback::class, 'f')->willReturnSelf();
        $qb->expects($this->once())->method('innerJoin')->with('f.citizen', 'c')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('c.id = :citizenId')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('citizenId', 'cit-123')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('f.createdAt', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn([]);

        $result = $this->repository->findByCitizen('cit-123');
        $this->assertEmpty($result);
    }

    public function testFindByCitizen_ReturnsFeedbacks_WhenFound(): void
    {
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();
        $feedbacks = [$this->createFeedbackMock(), $this->createFeedbackMock()];

        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('f')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Feedback::class, 'f')->willReturnSelf();
        $qb->expects($this->once())->method('innerJoin')->with('f.citizen', 'c')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('c.id = :citizenId')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('citizenId', 'cit-456')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('f.createdAt', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn($feedbacks);

        $result = $this->repository->findByCitizen('cit-456');
        $this->assertCount(2, $result);
    }

    // ==================== findByPhone ====================

    public function testFindByPhone_ReturnsEmptyArray_WhenNoResults(): void
    {
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();

        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('f')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Feedback::class, 'f')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('f.citizenPhone = :phone')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('phone', '0123456789')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('f.createdAt', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn([]);

        $result = $this->repository->findByPhone('0123456789');
        $this->assertEmpty($result);
    }

    public function testFindByPhone_ReturnsFeedbacks_WhenFound(): void
    {
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();
        $feedbacks = [$this->createFeedbackMock()];

        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('f')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Feedback::class, 'f')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('f.citizenPhone = :phone')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('phone', '0987654321')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('f.createdAt', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn($feedbacks);

        $result = $this->repository->findByPhone('0987654321');
        $this->assertCount(1, $result);
    }

    // ==================== findPendingFeedback ====================

    public function testFindPendingFeedback_ReturnsEmptyArray_WhenNone(): void
    {
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();

        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('f')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Feedback::class, 'f')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('f.status IN (:statuses)')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('statuses', [
            Feedback::STATUS_SUBMITTED,
            Feedback::STATUS_UNDER_REVIEW,
            Feedback::STATUS_IN_PROGRESS
        ])->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('f.priority', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('addOrderBy')->with('f.createdAt', 'ASC')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn([]);

        $result = $this->repository->findPendingFeedback();
        $this->assertEmpty($result);
    }

    public function testFindPendingFeedback_ReturnsFeedbacks_WhenExists(): void
    {
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();
        $feedbacks = [$this->createFeedbackMock()];

        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('f')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Feedback::class, 'f')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('f.status IN (:statuses)')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('statuses', [
            Feedback::STATUS_SUBMITTED,
            Feedback::STATUS_UNDER_REVIEW,
            Feedback::STATUS_IN_PROGRESS
        ])->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('f.priority', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('addOrderBy')->with('f.createdAt', 'ASC')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn($feedbacks);

        $result = $this->repository->findPendingFeedback();
        $this->assertCount(1, $result);
    }

    // ==================== findByWard without status ====================

    public function testFindByWard_WithoutStatus(): void
    {
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();
        $feedbacks = [$this->createFeedbackMock()];

        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('f')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Feedback::class, 'f')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('f.wardCode = :wardCode')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('wardCode', 'WARD01')->willReturnSelf();
        $qb->expects($this->never())->method('andWhere');
        $qb->expects($this->once())->method('orderBy')->with('f.createdAt', 'DESC')->willReturnSelf();
        $qb->expects($this->never())->method('setMaxResults');
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn($feedbacks);

        $result = $this->repository->findByWard('WARD01');
        $this->assertCount(1, $result);
    }

    // ==================== save ====================

    public function testSave_PersistsNewEntity(): void
    {
        $feedback = $this->createFeedbackMock();

        $this->em->expects($this->once())
            ->method('contains')
            ->with($feedback)
            ->willReturn(false);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($feedback)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->repository->save($feedback);
        $this->addToAssertionCount(1);
    }

    public function testSave_DoesNotPersistIfAlreadyManaged(): void
    {
        $feedback = $this->createFeedbackMock();

        $this->em->expects($this->once())
            ->method('contains')
            ->with($feedback)
            ->willReturn(true);
        $this->em->expects($this->never())
            ->method('persist');
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->repository->save($feedback);
        $this->addToAssertionCount(1);
    }

    // ==================== remove ====================

    public function testRemove(): void
    {
        $feedback = $this->createFeedbackMock();

        $this->em->expects($this->once())
            ->method('remove')
            ->with($feedback)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->repository->remove($feedback);
        $this->addToAssertionCount(1);
    }
}
