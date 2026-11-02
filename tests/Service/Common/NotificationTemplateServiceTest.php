<?php declare(strict_types=1);

namespace App\Tests\Service\Common;

use App\Entity\Common\NotificationTemplate;
use App\Service\Common\NotificationTemplateService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NotificationTemplateServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private NotificationTemplateService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new NotificationTemplateService($this->em, $this->logger);
    }

    public function testCreateSuccess(): void
    {
        $name = 'Welcome';
        $type = 'email';
        $subject = 'Welcome!';
        $body = 'Hello';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(NotificationTemplate::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist');
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification template created', $this->anything());

        $result = $this->service->create($name, $type, $subject, $body);
        $this->assertInstanceOf(NotificationTemplate::class, $result);
    }

    public function testCreateFailsValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create('', 'type', 'subject', 'body');
    }

    public function testGetSuccess(): void
    {
        $id = '123e4567-e89b-12d3-a456-426614174000';
        $template = new NotificationTemplate($id, 'Name', 'type', 'Subject', 'Body');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($template);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(NotificationTemplate::class)
            ->willReturn($repo);

        $result = $this->service->get($id);
        $this->assertSame($template, $result);
    }

    public function testGetByNameSuccess(): void
    {
        $name = 'Welcome';
        $template = new NotificationTemplate('id', $name, 'type', 'Subject', 'Body');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $name])
            ->willReturn($template);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getByName($name);
        $this->assertSame($template, $result);
    }

    public function testUpdateSuccess(): void
    {
        $id = 'id123';
        $template = new NotificationTemplate($id, 'Old', 'type', 'OldSubj', 'OldBody');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($template);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $newSubject = 'New Subject';
        $result = $this->service->update($id, ['subject' => $newSubject]);
        $this->assertSame($template, $result);
        $this->assertSame($newSubject, $template->getSubject());
    }

    public function testChangeStatusSuccess(): void
    {
        $id = 'id123';
        $template = new NotificationTemplate($id, 'Name', 'type', 'Subject', 'Body');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($template);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->changeStatus($id, 'INACTIVE');
        $this->assertSame($template, $result);
        $this->assertSame('INACTIVE', $template->getStatus());
    }

    public function testChangeStatusInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->changeStatus('id', 'INVALID');
    }

    public function testDeleteSuccess(): void
    {
        $id = 'id123';
        $template = new NotificationTemplate($id, 'Name', 'type', 'Subject', 'Body');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($template);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($template);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->delete($id);
        $this->assertTrue($result);
    }

    public function testDeleteReturnsFalseWhenNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->delete('nonexistent');
        $this->assertFalse($result);
    }
}
