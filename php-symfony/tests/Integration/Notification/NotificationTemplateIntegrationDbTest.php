<?php

declare(strict_types=1);

namespace App\Tests\Integration\Notification;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Notification\Entity\NotificationTemplate;

#[AllowMockObjectsWithoutExpectations]
class NotificationTemplateIntegrationDbTest extends DatabaseIntegrationTestCase
{
    public function testCreateAndRetrieveNotificationTemplate(): void
    {
        $template = new NotificationTemplate();
        $template->setTemplateCode('WELCOME_EMAIL')
            ->setName('Welcome Email')
            ->setSubject('Welcome to our platform')
            ->setBody('Hello {{name}}, welcome!')
            ->setVariables(['name' => 'string'])
            ->setStatus(NotificationTemplate::STATUS_ACTIVE);

        $em = self::getEntityManager();
        $em->persist($template);
        $em->flush();

        $this->assertNotNull($template->getId());

        $found = $em->getRepository(NotificationTemplate::class)->find($template->getId());
        $this->assertNotNull($found);
        $this->assertSame('WELCOME_EMAIL', $found->getTemplateCode());
        $this->assertSame('Welcome Email', $found->getName());
        $this->assertSame('Welcome to our platform', $found->getSubject());
        $this->assertSame('Hello {{name}}, welcome!', $found->getBody());
        $this->assertSame(['name' => 'string'], $found->getVariables());
        $this->assertSame(NotificationTemplate::STATUS_ACTIVE, $found->getStatus());
    }

    public function testUpdateNotificationTemplate(): void
    {
        $template = new NotificationTemplate();
        $template->setTemplateCode('PASSWORD_RESET')
            ->setName('Password Reset')
            ->setBody('Reset your password')
            ->setStatus(NotificationTemplate::STATUS_INACTIVE);

        $em = self::getEntityManager();
        $em->persist($template);
        $em->flush();

        $template->setName('Password Reset Email')
            ->setStatus(NotificationTemplate::STATUS_ACTIVE);
        $em->flush();

        $found = $em->getRepository(NotificationTemplate::class)->find($template->getId());
        $this->assertSame('Password Reset Email', $found->getName());
        $this->assertSame(NotificationTemplate::STATUS_ACTIVE, $found->getStatus());
    }

    public function testDeleteNotificationTemplate(): void
    {
        $template = new NotificationTemplate();
        $template->setTemplateCode('TEST_DELETE')
            ->setName('To Delete')
            ->setBody('Body');

        $em = self::getEntityManager();
        $em->persist($template);
        $em->flush();

        $id = $template->getId();
        $em->remove($template);
        $em->flush();

        $found = $em->getRepository(NotificationTemplate::class)->find($id);
        $this->assertNull($found);
    }

    public function testNotificationTemplateIdIsUuid(): void
    {
        $template = new NotificationTemplate();
        $template->setTemplateCode('UUID_TEST')
            ->setName('UUID Test')
            ->setBody('Body');

        $em = self::getEntityManager();
        $em->persist($template);
        $em->flush();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $template->getId());
    }
}
