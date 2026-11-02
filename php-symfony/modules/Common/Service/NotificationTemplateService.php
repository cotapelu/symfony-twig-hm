<?php declare(strict_types=1);

namespace App\Common\Service;

use App\Entity\Common\NotificationTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;

/**
 * Service for managing notification templates
 */
class NotificationTemplateService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    private function generateId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    private function validateTemplate(string $name, string $type, string $subject, string $body)
    {
        if (empty(trim($name))) throw new \InvalidArgumentException('Template name is required');
        if (empty(trim($type))) throw new \InvalidArgumentException('Template type is required');
        if (empty(trim($subject))) throw new \InvalidArgumentException('Template subject is required');
        if (empty(trim($body))) throw new \InvalidArgumentException('Template body is required');
    }

    public function create(
        string $name,
        string $type,
        string $subject,
        string $body,
        ?string $category = null,
        ?array $variables = null,
        string $status = 'ACTIVE'
    ) {
        $this->validateTemplate($name, $type, $subject, $body);
        $template = $this->buildTemplateEntity($name, $type, $subject, $body, $category, $variables, $status);
        $this->persistAndLog($template, ['name' => $name, 'type' => $type]);
        return $template;
    }

    private function persistAndLog(NotificationTemplate $template, array $context): void
    {
        try {
            $this->em->persist($template);
            $this->em->flush();
            $this->logger->info('Notification template created', array_merge($context, ['id' => $template->getId()]));
        } catch (ORMException $e) {
            $this->logger->error('Failed to create notification template', array_merge($context, ['error' => $e->getMessage()]));
            throw new \RuntimeException('Unable to create notification template', 0, $e);
        }
    }

    public function get(string $id)
    {
        return $this->em->getRepository(NotificationTemplate::class)->find($id);
    }

    public function getByName(string $name)
    {
        return $this->em->getRepository(NotificationTemplate::class)->findOneBy(['name' => $name]);
    }

    public function getByType(string $type)
    {
        return $this->em->getRepository(NotificationTemplate::class)->findBy(['type' => $type]);
    }

    public function update(string $id, array $data)
    {
        $template = $this->get($id);
        if (!$template) throw new \RuntimeException("Notification template not found: $id");
        $this->applyTemplateFields($template, $data);
        try {
            $this->em->flush();
        } catch (ORMException $e) {
            $this->logger->error('Failed to update notification template', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Unable to update notification template', 0, $e);
        }
        $this->logger->info('Notification template updated', ['id' => $id]);
        return $template;
    }

    public function changeStatus(string $id, string $status)
    {
        $allowed = ['ACTIVE', 'INACTIVE', 'DRAFT'];
        if (!in_array($status, $allowed, true)) throw new \InvalidArgumentException("Invalid status. Allowed: " . implode(', ', $allowed));
        $template = $this->get($id);
        if (!$template) throw new \RuntimeException("Notification template not found: $id");
        $template->setStatus($status);
        try {
            $this->em->flush();
        } catch (ORMException $e) {
            $this->logger->error('Failed to change template status', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Unable to change template status', 0, $e);
        }
        $this->logger->info('Template status changed', ['id' => $id, 'status' => $status]);
        return $template;
    }

    public function delete(string $id)
    {
        $template = $this->get($id);
        if (!$template) return false;
        try {
            $this->em->remove($template);
            $this->em->flush();
        } catch (ORMException $e) {
            $this->logger->error('Failed to delete notification template', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Unable to delete notification template', 0, $e);
        }
        $this->logger->info('Notification template deleted', ['id' => $id]);
        return true;
    }

    private function applyTemplateFields(NotificationTemplate $template, array $data): void
    {
        $this->applyTemplateName($template, $data);
        $this->applyTemplateType($template, $data);
        $this->applyTemplateSubject($template, $data);
        $this->applyTemplateBody($template, $data);
        $this->applyTemplateCategory($template, $data);
        $this->applyTemplateVariables($template, $data);
        $this->applyTemplateStatus($template, $data);
    }

    private function applyTemplateName(NotificationTemplate $template, array $data): void
    {
        if (!array_key_exists('name', $data)) return;
        $name = trim($data['name']);
        if ($name === '') throw new \InvalidArgumentException('Template name cannot be empty');
        $template->setName($name);
    }

    private function applyTemplateType(NotificationTemplate $template, array $data): void
    {
        if (!array_key_exists('type', $data)) return;
        $type = trim($data['type']);
        if ($type === '') throw new \InvalidArgumentException('Template type cannot be empty');
        $template->setType($type);
    }

    private function applyTemplateSubject(NotificationTemplate $template, array $data): void
    {
        if (!array_key_exists('subject', $data)) return;
        $subject = trim($data['subject']);
        if ($subject === '') throw new \InvalidArgumentException('Template subject cannot be empty');
        $template->setSubject($subject);
    }

    private function applyTemplateBody(NotificationTemplate $template, array $data): void
    {
        if (!array_key_exists('body', $data)) return;
        $body = trim($data['body']);
        if ($body === '') throw new \InvalidArgumentException('Template body cannot be empty');
        $template->setBody($body);
    }

    private function applyTemplateCategory(NotificationTemplate $template, array $data): void
    {
        if (array_key_exists('category', $data)) $template->setCategory($data['category']);
    }

    private function applyTemplateVariables(NotificationTemplate $template, array $data): void
    {
        if (array_key_exists('variables', $data)) $template->setVariables($data['variables']);
    }

    private function applyTemplateStatus(NotificationTemplate $template, array $data): void
    {
        if (array_key_exists('status', $data)) $template->setStatus($data['status']);
    }

    private function buildTemplateEntity(
        string $name,
        string $type,
        string $subject,
        string $body,
        ?string $category,
        ?array $variables,
        string $status
    ): NotificationTemplate {
        $template = new NotificationTemplate($this->generateId(), $name, $type, $subject, $body);
        $template->setCategory($category);
        $template->setVariables($variables);
        $template->setStatus($status);
        return $template;
    }
}
