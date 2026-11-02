<?php

declare(strict_types=1);

namespace App\Controller\Gov;

use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use InvalidArgumentException;

#[Route('/api/gov', name: 'api_gov_')]
#[IsGranted('ROLE_USER')]
final class GovOfficerController extends BaseController
{
    private function assertAdminAccess(): void
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        $roles = $user->getRoles();
        foreach (['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'] as $adminRole) {
            if (in_array($adminRole, $roles, true)) {
                return;
            }
        }

        throw new AccessDeniedHttpException('Access denied: Admin role required');
    }

    private function getJsonData(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON payload');
        }
        return $data;
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            return $this->handleException('gov', $e);
        }
    }

    private function handleException(string $context, \Throwable $e): JsonResponse
    {
        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createAction($request));
    }

    private function createAction(Request $request): JsonResponse
    {
        $this->assertAdminAccess();
        $data = $this->getJsonData($request);

        if (empty($data['officerId']) || empty($data['fullName'])) {
            throw new InvalidArgumentException('officerId and fullName are required');
        }

        return $this->json([
            'id' => uniqid(),
            'officerId' => $data['officerId'],
            'fullName' => $data['fullName'],
            'department' => $data['department'] ?? '',
            'status' => 'active'
        ], Response::HTTP_CREATED);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->searchAction($request));
    }

    private function searchAction(Request $request): JsonResponse
    {
        $this->assertAdminAccess();
        $query = $request->query->get('q', '');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        return $this->json([
            'data' => [],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_items' => 0,
                'total_pages' => 0,
            ]
        ]);
    }
}