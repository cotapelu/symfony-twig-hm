<?php

declare(strict_types=1);

namespace App\MiniPrograms\Service;

use App\MiniPrograms\Entity\MiniProgram;
use App\MiniPrograms\Repository\MiniProgramRepository;
use App\Shared\EventBus\EventBus;
use App\MiniPrograms\Event\ProgramInstalledEvent;
use InvalidArgumentException;

class MiniProgramService
{
    private const ALLOWED_CATEGORIES = ['productivity', 'education', 'entertainment', 'utilities', 'social', 'games', 'finance', 'health', 'other'];

    public function __construct(
        private MiniProgramRepository $programRepository,
        private EventBus $eventBus,
    ) {}

    public function createMiniProgram(array $data)
    {
        $this->validateRequiredFields($data);
        $this->ensureNoDuplicate($data['app_id']);

        $program = new MiniProgram();
        $program->setDeveloperId($data['developer_id']);
        $program->setName($data['name']);
        $program->setAppId($data['app_id']);

        $this->applyMiniProgramFields($program, $data);

        $this->programRepository->save($program);
        return $program;
    }

    private function validateRequiredFields(array $data): void
    {
        foreach (['developer_id', 'name', 'app_id'] as $field) {
            if (empty($data[$field] ?? null)) {
                throw new InvalidArgumentException("Field '{$field}' is required");
            }
        }
    }

    private function ensureNoDuplicate(string $appId): void
    {
        $existing = $this->programRepository->findByAppId($appId);
        if ($existing) {
            throw new InvalidArgumentException('App ID already exists');
        }
    }

    private function applyMiniProgramFields(MiniProgram $program, array $data): void
    {
        $simple = [
            'description' => fn($v) => $program->setDescription($v),
            'icon_url' => fn($v) => $program->setIconUrl($v),
            'version' => fn($v) => $program->setVersion($v),
            'launch_url' => fn($v) => $program->setLaunchUrl($v),
        ];
        foreach ($simple as $field => $setter) {
            if (isset($data[$field])) {
                $setter($data[$field]);
            }
        }

        if (isset($data['config'])) {
            if (!is_array($data['config'])) {
                throw new InvalidArgumentException('Config must be an array');
            }
            $program->setConfig($data['config']);
        }

        if (isset($data['category'])) {
            $category = strtolower(trim($data['category']));
            if (!in_array($category, self::ALLOWED_CATEGORIES, true)) {
                throw new InvalidArgumentException('Invalid category');
            }
            $program->setCategory($category);
        }
    }

    public function installMiniProgram(string $appId, string $userId)
    {
        $program = $this->programRepository->findByAppId($appId);
        if (!$program) {
            throw new InvalidArgumentException('Mini program not found');
        }

        if (!$program->isActive()) {
            throw new InvalidArgumentException('Mini program is not active');
        }

        // Atomic increment
        $affected = $this->programRepository->incrementInstallCount($appId);
        if ($affected === 0) {
            // Could be not found or inactive, but we already checked, so maybe race condition
            // Still throw?
            throw new \RuntimeException('Failed to increment install count');
        }

        $event = new ProgramInstalledEvent(
            appId: $appId,
            userId: $userId,
            developerId: $program->getDeveloperId()
        );

        $this->eventBus->dispatch($event);
    }

    public function getMiniProgram(string $appId)
    {
        return $this->programRepository->findByAppId($appId);
    }

    public function getAllMiniPrograms(int $limit = 50)
    {
        return $this->programRepository->findAllActive($limit);
    }

    public function searchMiniPrograms(string $query, int $limit = 20)
    {
        if (empty(trim($query))) {
            throw new InvalidArgumentException('Search query cannot be empty');
        }
        return $this->programRepository->searchPrograms($query, $limit);
    }

    public function getDeveloperPrograms(string $developerId, int $limit = 50)
    {
        return $this->programRepository->findByDeveloper($developerId, $limit);
    }
}
