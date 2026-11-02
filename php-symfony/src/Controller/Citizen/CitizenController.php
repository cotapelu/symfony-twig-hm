<?php

declare(strict_types=1);

namespace App\Controller\Citizen;

use App\Service\Security\OwnershipChecker;
use App\Service\Php\CitizenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use InvalidArgumentException;

#[Route('/api/citizen', name: 'api_citizen_')]
#[IsGranted('ROLE_USER')]
final class CitizenController extends AbstractController
{
    private const ALLOWED_ROLES = ['ROLE_OFFICER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];

    private function getJsonData(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON');
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
        } catch (\Exception $e) {
            return $this->handleException('citizen', $e);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createAction($request));
    }

    private function createAction(Request $request): JsonResponse
    {
        $this->assertCitizenAccess();
        $data = $this->getJsonData($request);
        $errors = $this->validateCitizenCreationData($data);
        if (!empty($errors)) {
            return $this->json(['error' => 'Validation failed', 'fields' => $errors], Response::HTTP_BAD_REQUEST);
        }
        return $this->json([
            'id' => uniqid(),
            'citizenId' => $data['citizenId'],
            'fullName' => $data['fullName'],
            'status' => 'active'
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        return $this->handle(fn() => $this->getAction($id));
    }

    private function getAction(string $id): JsonResponse
    {
        $this->assertCitizenAccess();
        return $this->json(['id' => $id, 'fullName' => 'Test Citizen']);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->searchAction($request));
    }

    private function searchAction(Request $request): JsonResponse
    {
        $this->assertCitizenAccess();
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

    // ========== PRIVATE HELPERS ==========

    private function assertCitizenAccess(): void
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Authentication required');
        }
        $roles = $user->getRoles();
        foreach (self::ALLOWED_ROLES as $requiredRole) {
            if (in_array($requiredRole, $roles, true)) {
                return;
            }
        }
        throw new AccessDeniedHttpException('Access denied: Officer or admin role required');
    }

    private function validateCitizenCreationData(array $data): array
    {
        return array_merge(
            $this->validateCitizenId($data),
            $this->validateFullName($data),
            $this->validateEmail($data),
            $this->validatePhone($data),
            $this->validateDateOfBirth($data)
        );
    }

    private function validateCitizenId(array $data): array
    {
        if (empty($data['citizenId'])) {
            return ['citizenId' => 'Citizen ID is required'];
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{5,50}$/', $data['citizenId'])) {
            return ['citizenId' => 'Citizen ID must be 5-50 alphanumeric characters'];
        }
        return [];
    }

    private function validateFullName(array $data): array
    {
        if (empty($data['fullName'])) {
            return ['fullName' => 'Full name is required'];
        }
        $trimmed = trim($data['fullName']);
        if (strlen($trimmed) < 2) {
            return ['fullName' => 'Full name must be at least 2 characters'];
        }
        if (strlen($trimmed) > 100) {
            return ['fullName' => 'Full name cannot exceed 100 characters'];
        }
        if (!preg_match('/^[a-zA-Z\s\-\']+$/', $trimmed)) {
            return ['fullName' => 'Full name contains invalid characters'];
        }
        return [];
    }

    private function validateEmail(array $data): array
    {
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['email' => 'Invalid email format'];
        }
        return [];
    }

    private function validatePhone(array $data): array
    {
        if (!empty($data['phone']) && !preg_match('/^[0-9+\-()\s]{8,20}$/', $data['phone'])) {
            return ['phone' => 'Invalid phone number format'];
        }
        return [];
    }

    private function validateDateOfBirth(array $data): array
    {
        if (!empty($data['dateOfBirth'])) {
            $dob = \DateTime::createFromFormat('Y-m-d', $data['dateOfBirth']);
            if (!$dob || $dob > new \DateTime()) {
                return ['dateOfBirth' => 'Invalid date of birth'];
            }
        }
        return [];
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("CitizenController::{$context} error: " . $e->getMessage());
        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}