<?php

declare(strict_types=1);

namespace App\MiniPrograms\Controller;

use App\MiniPrograms\Service\MiniProgramService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use DateTimeImmutable;

#[Route('/api/mini-programs')]
class MiniProgramsController extends BaseController
{
    public function __construct(
        private MiniProgramService $miniProgramService,
    ) {
    }

    private function logAction(string $action, array $context = []): void
    {
        $userId = $this->getCurrentUser()?->getId() ?? 'anonymous';
        $timestamp = new DateTimeImmutable();
        error_log(json_encode([
            'timestamp' => $timestamp->format(DateTimeImmutable::ATOM),
            'action' => $action,
            'userId' => $userId,
            'module' => 'MiniPrograms',
            'context' => $context
        ]));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode([
            'metric' => $metric,
            'tags' => $tags,
            'timestamp' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM)
        ]));
    }

    #[Route('/', methods: ['GET'])]
    public function listPrograms(Request $request): JsonResponse
    {
        try {
            $limit = $request->query->getInt('limit', 50);
            $programs = $this->miniProgramService->getAllMiniPrograms($limit);

            $this->logAction('mini_programs_listed', ['count' => count($programs)]);
            $this->incrementMetric('mini_programs_listed_total');

            return $this->json($programs);
        } catch (\Throwable $e) {
            $this->logAction('list_programs_failed', ['error' => $e->getMessage()]);
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }

    #[Route('/my', methods: ['GET'])]
    public function getMyPrograms(Request $request): JsonResponse
    {
        try {
            $user = $this->getCurrentUser();
            $programs = $this->miniProgramService->getDeveloperPrograms($user->getId(), 50);

            $this->logAction('my_programs_retrieved', ['developer_id' => $user->getId()]);
            $this->incrementMetric('my_programs_retrieved_total');

            return $this->json($programs);
        } catch (\Throwable $e) {
            $this->logAction('get_my_programs_failed', ['error' => $e->getMessage()]);
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }

    #[Route('/', methods: ['POST'])]
    public function createProgram(Request $request): JsonResponse
    {
        try {
            $user = $this->getCurrentUser();
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;

            if (!$data) {
                return $this->json(['error' => 'Invalid JSON'], 400);
            }

            if (empty($data['name']) || empty($data['app_id'])) {
                return $this->json(['error' => 'Name and app_id are required'], 400);
            }

            $program = $this->miniProgramService->createMiniProgram([
                'developer_id' => $user->getId(),
                ...$data,
            ]);

            $this->logAction('mini_program_created', ['program_id' => $program->getId(), 'developer_id' => $user->getId()]);
            $this->incrementMetric('mini_programs_created_total');

            return $this->json($program->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->logAction('create_program_failed', ['error' => $e->getMessage()]);
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getProgram(string $id): JsonResponse
    {
        try {
            $program = $this->miniProgramService->getMiniProgram($id);

            if (!$program) {
                return $this->json(['error' => 'Mini program not found'], 404);
            }

            $this->logAction('mini_program_viewed', ['program_id' => $id]);
            $this->incrementMetric('mini_program_viewed_total');

            return $this->json($program->toArray());
        } catch (\Throwable $e) {
            $this->logAction('get_program_failed', ['error' => $e->getMessage()]);
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }

    #[Route('/{id}/install', methods: ['POST'])]
    public function installProgram(string $id): JsonResponse
    {
        try {
            $user = $this->getCurrentUser();
            $this->miniProgramService->installMiniProgram($id, $user->getId());

            $this->logAction('mini_program_installed', ['program_id' => $id, 'user_id' => $user->getId()]);
            $this->incrementMetric('mini_program_installed_total');

            return $this->json(['message' => 'Mini program installed successfully']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            $this->logAction('install_program_failed', ['error' => $e->getMessage()]);
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }

    #[Route('/search', methods: ['GET'])]
    public function searchPrograms(Request $request): JsonResponse
    {
        try {
            $query = $request->query->get('q', '');
            $limit = $request->query->getInt('limit', 20);

            if (empty(trim($query))) {
                throw new \InvalidArgumentException('Search query is required');
            }

            $programs = $this->miniProgramService->searchMiniPrograms($query, $limit);

            $this->logAction('mini_programs_searched', ['query' => $query, 'count' => count($programs)]);
            $this->incrementMetric('mini_programs_searched_total');

            return $this->json($programs);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->logAction('search_programs_failed', ['error' => $e->getMessage()]);
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }
}
