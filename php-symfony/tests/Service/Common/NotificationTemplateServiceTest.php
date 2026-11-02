<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\NotificationTemplate;
use App\Repository\Common\NotificationTemplateRepository;
use App\Service\Common\NotificationTemplateService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class NotificationTemplateServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private NotificationTemplateRepository $templateRepo;
    private LoggerInterface $logger;
    private NotificationTemplateService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->templateRepo = $this->createMock(NotificationTemplateRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->method('getRepository')
            ->willReturnMap([
                [NotificationTemplate::class, $this->templateRepo],
            ]);
    }

    public function testCreateTemplate(): void
    {
        $name = 'Welcome Email';
        $type = 'email';
        $subject = 'Welcome';
        $body = 'Hello {{user}}';
        $category = 'transactional';
        $variables = ['user' => 'string'];
        $status = 'ACTIVE';

        $captured = null;
        $this->templateRepo->expects($this->never())->method('find');
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (NotificationTemplate $t) use (&$captured, $name, $type, $subject, $body, $category, $variables, $status) {
                $captured = $t;
                return $captured !== null
                    && $captured->getName() === $name
                    && $captured->getType() === $type
                    && $captured->getSubject() === $subject
                    && $captured->getBody() === $body
                    && $captured->getCategory() === $category
                    && $captured->getVariables() === $variables
                    && $captured->getStatus() === $status;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification template created', $this->arrayHasKey('id'));

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $result = $this->service->create($name, $type, $subject, $body, $category, $variables, $status);

        $this->assertInstanceOf(NotificationTemplate::class, $result);
        $this->assertSame($name, $result->getName());
        $this->assertNotNull($result->getId());
    }

    public function testCreateTemplateMinimal(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())->method('info');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $result = $this->service->create('Minimal', 'sms', 'Subj', 'Body');
        $this->assertInstanceOf(NotificationTemplate::class, $result);
    }

    public function testCreateTemplateInvalidName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template name is required');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->service->create('', 'type', 'subject', 'body');
    }

    public function testCreateTemplateInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template type is required');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->service->create('Name', '', 'subject', 'body');
    }

    public function testCreateTemplateInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template subject is required');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->service->create('Name', 'type', '', 'body');
    }

    public function testCreateTemplateInvalidBody(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template body is required');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->service->create('Name', 'type', 'subject', '');
    }

    public function testCreateTemplatePersistenceException(): void
    {
        $this->templateRepo->expects($this->never())->method('find');
        $this->em->expects($this->once())->method('persist');
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to create notification template', $this->arrayHasKey('error'));

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to create notification template');
        $this->service->create('Name', 'type', 'subject', 'body');
    }

    public function testGetTemplate(): void
    {
        $id = 'template-1';
        $template = $this->createMock(NotificationTemplate::class);
        $this->templateRepo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($template);

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $result = $this->service->get($id);
        $this->assertSame($template, $result);
    }

    public function testGetByName(): void
    {
        $name = 'Welcome';
        $template = $this->createMock(NotificationTemplate::class);
        $this->templateRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $name])
            ->willReturn($template);

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $result = $this->service->getByName($name);
        $this->assertSame($template, $result);
    }

    public function testGetByType(): void
    {
        $type = 'email';
        $templates = [$this->createMock(NotificationTemplate::class)];
        $this->templateRepo->expects($this->once())
            ->method('findBy')
            ->with(['type' => $type])
            ->willReturn($templates);

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $result = $this->service->getByType($type);
        $this->assertSame($templates, $result);
    }

    public function testUpdate(): void
    {
        $id = 'tpl-1';
        $template = $this->createMock(NotificationTemplate::class);
        $template->expects($this->once())->method('setName')->with('New Name');
        $template->expects($this->once())->method('setType')->with('push');
        $template->expects($this->once())->method('setSubject')->with('New Subject');
        $template->expects($this->once())->method('setBody')->with('New Body');
        $template->expects($this->never())->method('setCategory'); // not provided
        $template->expects($this->never())->method('setVariables');

        $this->templateRepo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification template updated', ['id' => $id]);

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $result = $this->service->update($id, [
            'name' => 'New Name',
            'type' => 'push',
            'subject' => 'New Subject',
            'body' => 'New Body',
        ]);
        $this->assertSame($template, $result);
    }

    public function testUpdateWithOptionalFields(): void
    {
        $id = 'tpl-2';
        $template = $this->createMock(NotificationTemplate::class);
        $template->expects($this->once())->method('setCategory')->with('marketing');
        $template->expects($this->once())->method('setVariables')->with(['key' => 'value']);
        $template->expects($this->once())->method('setStatus')->with('INACTIVE');

        $this->templateRepo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $this->em->expects($this->once())->method('flush');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $result = $this->service->update($id, [
            'category' => 'marketing',
            'variables' => ['key' => 'value'],
            'status' => 'INACTIVE',
        ]);
        $this->assertSame($template, $result);
    }

    public function testUpdateNotFound(): void
    {
        $this->templateRepo->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Notification template not found: missing');
        $this->service->update('missing', ['name' => 'New']);
    }

    public function testUpdateEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template name cannot be empty');

        $template = $this->createMock(NotificationTemplate::class);
        $this->templateRepo->expects($this->once())->method('find')->with('id')->willReturn($template);
        $this->em->expects($this->never())->method('flush');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->service->update('id', ['name' => '   ']);
    }

    public function testUpdateEmptyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template type cannot be empty');

        $template = $this->createMock(NotificationTemplate::class);
        $this->templateRepo->expects($this->once())->method('find')->with('id')->willReturn($template);
        $this->em->expects($this->never())->method('flush');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->service->update('id', ['type' => '']);
    }

    public function testUpdateEmptySubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template subject cannot be empty');

        $template = $this->createMock(NotificationTemplate::class);
        $this->templateRepo->expects($this->once())->method('find')->with('id')->willReturn($template);
        $this->em->expects($this->never())->method('flush');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->service->update('id', ['subject' => '']);
    }

    public function testUpdateEmptyBody(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template body cannot be empty');

        $template = $this->createMock(NotificationTemplate::class);
        $this->templateRepo->expects($this->once())->method('find')->with('id')->willReturn($template);
        $this->em->expects($this->never())->method('flush');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->service->update('id', ['body' => '']);
    }

    public function testUpdatePersistenceException(): void
    {
        $id = 'tpl-3';
        $template = $this->createMock(NotificationTemplate::class);
        $template->expects($this->once())->method('setName')->with('New');
        $this->templateRepo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to update notification template', $this->arrayHasKey('error'));

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to update notification template');
        $this->service->update($id, ['name' => 'New']);
    }

    public function testChangeStatus(): void
    {
        $id = 'tpl-4';
        $status = 'INACTIVE';
        $template = $this->createMock(NotificationTemplate::class);
        $template->expects($this->once())->method('setStatus')->with($status);

        $this->templateRepo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Template status changed', ['id' => $id, 'status' => $status]);

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $result = $this->service->changeStatus($id, $status);
        $this->assertSame($template, $result);
    }

    public function testChangeStatusInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status. Allowed: ACTIVE, INACTIVE, DRAFT');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->service->changeStatus('id', 'INVALID');
    }

    public function testChangeStatusNotFound(): void
    {
        $this->templateRepo->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Notification template not found: missing');
        $this->service->changeStatus('missing', 'ACTIVE');
    }

    public function testChangeStatusPersistenceException(): void
    {
        $id = 'tpl-5';
        $template = $this->createMock(NotificationTemplate::class);
        $template->expects($this->once())->method('setStatus')->with('DRAFT');
        $this->templateRepo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to change template status', $this->arrayHasKey('error'));

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to change template status');
        $this->service->changeStatus($id, 'DRAFT');
    }

    public function testDeleteTemplate(): void
    {
        $id = 'tpl-6';
        $template = $this->createMock(NotificationTemplate::class);

        $this->templateRepo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $this->em->expects($this->once())->method('remove')->with($template);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification template deleted', ['id' => $id]);

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $result = $this->service->delete($id);
        $this->assertTrue($result);
    }

    public function testDeleteTemplateNotFound(): void
    {
        $this->templateRepo->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->em->expects($this->never())->method('remove');

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $result = $this->service->delete('missing');
        $this->assertFalse($result);
    }

    public function testDeleteTemplatePersistenceException(): void
    {
        $id = 'tpl-7';
        $template = $this->createMock(NotificationTemplate::class);
        $this->templateRepo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $this->em->expects($this->once())->method('remove')->with($template);
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to delete notification template', $this->arrayHasKey('error'));

        $this->service = new NotificationTemplateService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to delete notification template');
        $this->service->delete($id);
    }
}
