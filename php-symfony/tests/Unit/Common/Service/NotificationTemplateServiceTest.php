<?php declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use App\Common\Service\NotificationTemplateService;
use App\Entity\Common\NotificationTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationTemplateServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private NotificationTemplateService $service;
    private EntityRepository&MockObject $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->method('getRepository')
            ->with(NotificationTemplate::class)
            ->willReturn($this->repo);

        $this->service = new NotificationTemplateService($this->em, $this->logger);
    }

    private function createTemplate(string $id, string $name, string $type, string $subject, string $body, ?string $category = null, ?array $variables = null, string $status = 'ACTIVE'): NotificationTemplate
    {
        $template = new NotificationTemplate($id, $name, $type, $subject, $body);
        $template->setCategory($category);
        $template->setVariables($variables);
        $template->setStatus($status);
        return $template;
    }

    // ========== create() tests ==========
    public function testCreateSuccess(): void
    {
        $name = 'Test Template';
        $type = 'EMAIL';
        $subject = 'Hello';
        $body = 'Body text';
        $category = 'General';
        $variables = ['var' => 'value'];
        $status = 'ACTIVE';

        $this->em->expects($this->once())->method('persist')->with($this->callback(function (NotificationTemplate $t) use ($name, $type, $subject, $body, $category, $variables, $status) {
            return $t->getName() === $name
                && $t->getType() === $type
                && $t->getSubject() === $subject
                && $t->getBody() === $body
                && $t->getCategory() === $category
                && $t->getVariables() === $variables
                && $t->getStatus() === $status;
        }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())->method('info')
            ->with('Notification template created', $this->arrayHasKey('id'));

        $result = $this->service->create($name, $type, $subject, $body, $category, $variables, $status);

        $this->assertInstanceOf(NotificationTemplate::class, $result);
        $this->assertSame($name, $result->getName());
        $this->assertSame($type, $result->getType());
        $this->assertSame($subject, $result->getSubject());
        $this->assertSame($body, $result->getBody());
        $this->assertSame($category, $result->getCategory());
        $this->assertSame($variables, $result->getVariables());
        $this->assertSame($status, $result->getStatus());
    }

    public function testCreateThrowsWhenNameEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template name is required');
        $this->em->expects($this->never())->method('persist');
        $this->service->create('', 'type', 'subject', 'body');
    }

    public function testCreateThrowsWhenTypeEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template type is required');
        $this->em->expects($this->never())->method('persist');
        $this->service->create('name', '', 'subject', 'body');
    }

    public function testCreateThrowsWhenSubjectEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template subject is required');
        $this->em->expects($this->never())->method('persist');
        $this->service->create('name', 'type', '', 'body');
    }

    public function testCreateThrowsWhenBodyEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template body is required');
        $this->em->expects($this->never())->method('persist');
        $this->service->create('name', 'type', 'subject', '');
    }

    public function testCreateHandlesORMException(): void
    {
        $name = 'Test';
        $type = 'EMAIL';
        $subject = 'Sub';
        $body = 'Body';

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(NotificationTemplate::class));
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())->method('error')
            ->with('Failed to create notification template', $this->arrayHasKey('error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to create notification template');

        $this->service->create($name, $type, $subject, $body);
    }

    // ========== get() tests ==========
    public function testGetSuccess(): void
    {
        $id = 'template-id-123';
        $template = $this->createTemplate($id, 'Name', 'EMAIL', 'Sub', 'Body');

        $this->repo->expects($this->once())->method('find')->with($id)->willReturn($template);

        $result = $this->service->get($id);
        $this->assertSame($template, $result);
    }

    public function testGetNotFound(): void
    {
        $id = 'nonexistent';
        $this->repo->expects($this->once())->method('find')->with($id)->willReturn(null);
        $result = $this->service->get($id);
        $this->assertNull($result);
    }

    // ========== getByName() tests ==========
    public function testGetByNameSuccess(): void
    {
        $name = 'UniqueName';
        $template = $this->createTemplate('id1', $name, 'EMAIL', 'Sub', 'Body');

        $this->repo->expects($this->once())->method('findOneBy')->with(['name' => $name])->willReturn($template);

        $result = $this->service->getByName($name);
        $this->assertSame($template, $result);
    }

    public function testGetByNameNotFound(): void
    {
        $name = 'NonExistent';
        $this->repo->expects($this->once())->method('findOneBy')->with(['name' => $name])->willReturn(null);
        $result = $this->service->getByName($name);
        $this->assertNull($result);
    }

    // ========== getByType() tests ==========
    public function testGetByTypeSuccess(): void
    {
        $type = 'EMAIL';
        $templates = [
            $this->createTemplate('id1', 'N1', $type, 'S1', 'B1'),
            $this->createTemplate('id2', 'N2', $type, 'S2', 'B2'),
        ];

        $this->repo->expects($this->once())->method('findBy')->with(['type' => $type])->willReturn($templates);

        $result = $this->service->getByType($type);
        $this->assertSame($templates, $result);
    }

    // ========== update() tests ==========
    public function testUpdateSuccess(): void
    {
        $id = 'template-id';
        $template = $this->createTemplate($id, 'Old Name', 'OLDTYPE', 'Old Subject', 'Old Body', 'General', ['old' => 'var'], 'ACTIVE');

        $this->repo->expects($this->once())->method('find')->with($id)->willReturn($template);

        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())->method('info')
            ->with('Notification template updated', ['id' => $id]);

        $data = [
            'name' => 'New Name',
            'type' => 'NEWTYPE',
            'subject' => 'New Subject',
            'body' => 'New Body',
            'category' => 'New Category',
            'variables' => ['new' => 'var'],
            'status' => 'INACTIVE',
        ];

        $result = $this->service->update($id, $data);

        $this->assertSame($template, $result);
        $this->assertSame($data['name'], $template->getName());
        $this->assertSame($data['type'], $template->getType());
        $this->assertSame($data['subject'], $template->getSubject());
        $this->assertSame($data['body'], $template->getBody());
        $this->assertSame($data['category'], $template->getCategory());
        $this->assertSame($data['variables'], $template->getVariables());
        $this->assertSame($data['status'], $template->getStatus());
    }

    public function testUpdateTemplateNotFound(): void
    {
        $id = 'missing';
        $this->repo->expects($this->once())->method('find')->with($id)->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Notification template not found: $id");

        $this->service->update($id, ['name' => 'New']);
    }

    public function testUpdateThrowsWhenNameEmpty(): void
    {
        $id = 'template-id';
        $template = $this->createTemplate($id, 'Old', 'TYPE', 'Sub', 'Body');
        $this->repo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $this->em->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template name cannot be empty');

        $this->service->update($id, ['name' => '   ']);
    }

    public function testUpdateThrowsWhenTypeEmpty(): void
    {
        $id = 'template-id';
        $template = $this->createTemplate($id, 'Old', 'TYPE', 'Sub', 'Body');
        $this->repo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $this->em->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template type cannot be empty');

        $this->service->update($id, ['type' => '  ']);
    }

    public function testUpdateThrowsWhenSubjectEmpty(): void
    {
        $id = 'template-id';
        $template = $this->createTemplate($id, 'Old', 'TYPE', 'Sub', 'Body');
        $this->repo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $this->em->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template subject cannot be empty');

        $this->service->update($id, ['subject' => "\t"]);
    }

    public function testUpdateThrowsWhenBodyEmpty(): void
    {
        $id = 'template-id';
        $template = $this->createTemplate($id, 'Old', 'TYPE', 'Sub', 'Body');
        $this->repo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $this->em->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template body cannot be empty');

        $this->service->update($id, ['body' => '   ']);
    }

    public function testUpdateHandlesORMException(): void
    {
        $id = 'template-id';
        $template = $this->createTemplate($id, 'Old', 'TYPE', 'Sub', 'Body');
        $this->repo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())->method('error')
            ->with('Failed to update notification template', $this->arrayHasKey('error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to update notification template');

        $this->service->update($id, ['name' => 'New Name']);
    }

    // ========== changeStatus() tests ==========
    public function testChangeStatusSuccess(): void
    {
        $id = 'template-id';
        $template = $this->createTemplate($id, 'Name', 'TYPE', 'Sub', 'Body', status: 'ACTIVE');
        $this->repo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())->method('info')
            ->with('Template status changed', ['id' => $id, 'status' => 'INACTIVE']);

        $result = $this->service->changeStatus($id, 'INACTIVE');

        $this->assertSame($template, $result);
        $this->assertSame('INACTIVE', $template->getStatus());
    }

    public function testChangeStatusThrowsForInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status. Allowed: ACTIVE, INACTIVE, DRAFT');
        $this->repo->expects($this->never())->method('find');
        $this->service->changeStatus('id', 'INVALID');
    }

    public function testChangeStatusTemplateNotFound(): void
    {
        $id = 'missing';
        $this->repo->expects($this->once())->method('find')->with($id)->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Notification template not found: $id");

        $this->service->changeStatus($id, 'DRAFT');
    }

    public function testChangeStatusHandlesORMException(): void
    {
        $id = 'template-id';
        $template = $this->createTemplate($id, 'Name', 'TYPE', 'Sub', 'Body', status: 'ACTIVE');
        $this->repo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())->method('error')
            ->with('Failed to change template status', $this->arrayHasKey('error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to change template status');

        $this->service->changeStatus($id, 'DRAFT');
    }

    // ========== delete() tests ==========
    public function testDeleteSuccess(): void
    {
        $id = 'template-id';
        $template = $this->createTemplate($id, 'Name', 'TYPE', 'Sub', 'Body');

        $this->repo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $this->em->expects($this->once())->method('remove')->with($template);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())->method('info')
            ->with('Notification template deleted', ['id' => $id]);

        $result = $this->service->delete($id);
        $this->assertTrue($result);
    }

    public function testDeleteNotFound(): void
    {
        $id = 'missing';
        $this->repo->expects($this->once())->method('find')->with($id)->willReturn(null);
        $this->em->expects($this->never())->method('remove');
        $this->em->expects($this->never())->method('flush');

        $result = $this->service->delete($id);
        $this->assertFalse($result);
    }

    public function testDeleteHandlesORMException(): void
    {
        $id = 'template-id';
        $template = $this->createTemplate($id, 'Name', 'TYPE', 'Sub', 'Body');
        $this->repo->expects($this->once())->method('find')->with($id)->willReturn($template);
        $this->em->expects($this->once())->method('remove')->with($template);
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())->method('error')
            ->with('Failed to delete notification template', $this->arrayHasKey('error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to delete notification template');

        $this->service->delete($id);
    }
}
