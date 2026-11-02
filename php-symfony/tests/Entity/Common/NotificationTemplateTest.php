<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\NotificationTemplate;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationTemplateTest extends TestCase
{
    private function createTemplate(
        string $id = 'tpl-id',
        string $name = 'Template',
        string $type = 'email',
        string $subject = 'Subject',
        string $body = 'Body'
    ): NotificationTemplate {
        return new NotificationTemplate($id, $name, $type, $subject, $body);
    }

    public function testConstructor(): void
    {
        $tpl = $this->createTemplate();
        $this->assertSame('tpl-id', $tpl->getId());
        $this->assertSame('Template', $tpl->getName());
        $this->assertSame('email', $tpl->getType());
        $this->assertSame('Subject', $tpl->getSubject());
        $this->assertSame('Body', $tpl->getBody());
        $this->assertNull($tpl->getCategory());
        $this->assertNull($tpl->getVariables());
        $this->assertSame('ACTIVE', $tpl->getStatus());
        $this->assertInstanceOf(\DateTime::class, $tpl->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $tpl->getUpdatedAt());
    }

    public function testSetName(): void
    {
        $tpl = $this->createTemplate();
        $originalUpdatedAt = $tpl->getUpdatedAt();
        usleep(1000);
        $tpl->setName('New Name');
        $this->assertSame('New Name', $tpl->getName());
        $this->assertNotEquals($originalUpdatedAt, $tpl->getUpdatedAt());
    }

    public function testSetType(): void
    {
        $tpl = $this->createTemplate();
        $originalUpdatedAt = $tpl->getUpdatedAt();
        usleep(1000);
        $tpl->setType('push');
        $this->assertSame('push', $tpl->getType());
        $this->assertNotEquals($originalUpdatedAt, $tpl->getUpdatedAt());
    }

    public function testSetSubject(): void
    {
        $tpl = $this->createTemplate();
        $originalUpdatedAt = $tpl->getUpdatedAt();
        usleep(1000);
        $tpl->setSubject('New Subject');
        $this->assertSame('New Subject', $tpl->getSubject());
        $this->assertNotEquals($originalUpdatedAt, $tpl->getUpdatedAt());
    }

    public function testSetBody(): void
    {
        $tpl = $this->createTemplate();
        $originalUpdatedAt = $tpl->getUpdatedAt();
        usleep(1000);
        $tpl->setBody('New Body');
        $this->assertSame('New Body', $tpl->getBody());
        $this->assertNotEquals($originalUpdatedAt, $tpl->getUpdatedAt());
    }

    public function testSetCategory(): void
    {
        $tpl = $this->createTemplate();
        $originalUpdatedAt = $tpl->getUpdatedAt();
        usleep(1000);
        $tpl->setCategory('marketing');
        $this->assertSame('marketing', $tpl->getCategory());
        $this->assertNotEquals($originalUpdatedAt, $tpl->getUpdatedAt());
        $tpl->setCategory(null);
        $this->assertNull($tpl->getCategory());
    }

    public function testSetVariables(): void
    {
        $tpl = $this->createTemplate();
        $originalUpdatedAt = $tpl->getUpdatedAt();
        usleep(1000);
        $vars = ['key' => 'value'];
        $tpl->setVariables($vars);
        $this->assertSame($vars, $tpl->getVariables());
        $this->assertNotEquals($originalUpdatedAt, $tpl->getUpdatedAt());
        $tpl->setVariables(null);
        $this->assertNull($tpl->getVariables());
    }

    public function testSetStatus(): void
    {
        $tpl = $this->createTemplate();
        $originalUpdatedAt = $tpl->getUpdatedAt();
        usleep(1000);
        $tpl->setStatus('INACTIVE');
        $this->assertSame('INACTIVE', $tpl->getStatus());
        $this->assertNotEquals($originalUpdatedAt, $tpl->getUpdatedAt());
    }
}
