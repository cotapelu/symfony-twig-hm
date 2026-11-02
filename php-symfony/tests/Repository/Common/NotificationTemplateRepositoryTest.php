<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\NotificationTemplateRepository;
use App\Entity\Common\NotificationTemplate;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationTemplateRepositoryTest extends TestCase
{
    private NotificationTemplateRepository&MockObject $repo;
    private EntityManagerInterface&MockObject $em;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(NotificationTemplateRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findBy', 'findOneBy', 'count', 'getEntityManager'])
            ->getMock();

        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $template = $this->createMock(NotificationTemplate::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($template);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->save($template);
    }

    public function testRemoveRemovesAndFlushes(): void
    {
        $template = $this->createMock(NotificationTemplate::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($template);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->remove($template);
    }

    public function testFindById(): void
    {
        $id = 'tpl-123';
        $expected = $this->createMock(NotificationTemplate::class);

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expected);

        $result = $this->repo->findById($id);
        $this->assertSame($expected, $result);
    }

    public function testFindByType(): void
    {
        $type = 'EMAIL';
        $expected = [$this->createMock(NotificationTemplate::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['type' => $type])
            ->willReturn($expected);

        $result = $this->repo->findByType($type);
        $this->assertSame($expected, $result);
    }

    public function testFindByChannel(): void
    {
        $channel = 'SMS';
        $expected = [$this->createMock(NotificationTemplate::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['channel' => $channel])
            ->willReturn($expected);

        $result = $this->repo->findByChannel($channel);
        $this->assertSame($expected, $result);
    }

    public function testFindByTypeAndChannel(): void
    {
        $type = 'PUSH';
        $channel = 'FCM';
        $expected = $this->createMock(NotificationTemplate::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['type' => $type, 'channel' => $channel])
            ->willReturn($expected);

        $result = $this->repo->findByTypeAndChannel($type, $channel);
        $this->assertSame($expected, $result);
    }

    public function testFindActiveTemplates(): void
    {
        $expected = [$this->createMock(NotificationTemplate::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findActiveTemplates();
        $this->assertSame($expected, $result);
    }

    public function testFindInactiveTemplates(): void
    {
        $expected = [$this->createMock(NotificationTemplate::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'INACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findInactiveTemplates();
        $this->assertSame($expected, $result);
    }
}
