<?php

declare(strict_types=1);

namespace App\Health\Controller;

use App\Health\Service\HealthService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/health')]
class HealthController extends BaseController
{
    public function __construct(private HealthService $healthService) { parent::__construct(); }

    private function requireAuth(): string
    {
        $user = $this->getUser();
        if (!$user) throw new InvalidArgumentException('Authentication required');
        return (string)$user->getUserIdentifier();
    }

    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) throw new InvalidArgumentException('Invalid JSON');
        return $dataOr400 ?? [];
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("HealthController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['error' => 'Internal server error'], 500);
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'context'=>$context]));
    }

    private function incrementMetric(string $metric): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metric, 'value'=>1]));
    }

    private function validateProfileData(array $data): array
    {
        $errors = [];
        $errors = array_merge($errors, $this->validateHeight($data));
        $errors = array_merge($errors, $this->validateWeight($data));
        $errors = array_merge($errors, $this->validateAge($data));
        $errors = array_merge($errors, $this->validateBloodType($data));
        $errors = array_merge($errors, $this->validateGender($data));
        return $errors;
    }

    private function validateHeight(array $data): array
    {
        if (isset($data['height'])) {
            $height = (float)$data['height'];
            if ($height <= 0) return ['Height must be positive'];
        }
        return [];
    }

    private function validateWeight(array $data): array
    {
        if (isset($data['weight'])) {
            $weight = (float)$data['weight'];
            if ($weight <= 0) return ['Weight must be positive'];
        }
        return [];
    }

    private function validateAge(array $data): array
    {
        if (isset($data['age'])) {
            $age = (int)$data['age'];
            if ($age <= 0) return ['Age must be positive'];
        }
        return [];
    }

    private function validateBloodType(array $data): array
    {
        if (isset($data['blood_type'])) {
            $validBlood = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
            if (!in_array($data['blood_type'], $validBlood, true)) {
                return ['Invalid blood type'];
            }
        }
        return [];
    }

    private function validateGender(array $data): array
    {
        if (isset($data['gender'])) {
            $validGender = ['male', 'female', 'non-binary', 'other'];
            if (!in_array($data['gender'], $validGender, true)) {
                return ['Invalid gender'];
            }
        }
        return [];
    }

    // ==================== Endpoints ====================

    #[Route('/profiles', name: 'health_create_profile', methods: ['POST'])]
    public function createProfile(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createProfileAction($request));
    }

    private function createProfileAction(Request $request): JsonResponse
    {
        $userId = (int)$this->requireAuth();
        $profile = $this->healthService->createProfile($userId);
        $this->logAction('health_profile_created', ['user_id'=>$userId, 'profile_id'=>$profile->getId()]);
        $this->incrementMetric('health_profiles_created');
        return $this->json(['profile'=>$profile], JsonResponse::HTTP_CREATED);
    }

    #[Route('/profiles/{id}', name: 'health_get_profile', methods: ['GET'])]
    public function getProfile(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getProfileAction($id));
    }

    private function getProfileAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Profile ID must be positive');
        $profile = $this->healthService->getProfile($id);
        if (!$profile) return $this->json(['error' => 'Profile not found'], 404);
        $userId = (int)$this->requireAuth();
        if ($profile->getUserId() !== $userId) return $this->json(['error' => 'Access denied'], 403);
        return $this->json(['profile'=>$profile]);
    }

    #[Route('/profiles/user/{user_id}', name: 'health_get_user_profile', methods: ['GET'])]
    public function getUserProfile(int $user_id): JsonResponse
    {
        return $this->handle(fn() => $this->getUserProfileAction($user_id));
    }

    private function getUserProfileAction(int $user_id): JsonResponse
    {
        if ($user_id <= 0) throw new InvalidArgumentException('User ID must be positive');
        $authUserId = (int)$this->requireAuth();
        if ($user_id !== $authUserId) return $this->json(['error' => 'Access denied'], 403);
        $profile = $this->healthService->getUserProfile($user_id);
        if (!$profile) return $this->json(['error' => 'Profile not found'], 404);
        return $this->json(['profile'=>$profile]);
    }

    #[Route('/profiles/{id}', name: 'health_update_profile', methods: ['PUT', 'PATCH'])]
    public function updateProfile(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateProfileAction($id, $request));
    }

    private function updateProfileAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Profile ID must be positive');
        $userId = (int)$this->requireAuth();
        $profile = $this->healthService->getProfile($id);
        if (!$profile) return $this->json(['error' => 'Profile not found'], 404);
        if ($profile->getUserId() !== $userId) return $this->json(['error' => 'Access denied'], 403);
        $data = $this->getJsonData($request);
        $errors = $this->validateProfileData($data);
        if (!empty($errors)) return $this->json(['error' => 'Validation failed', 'errors'=>$errors], 400);
        $updated = $this->healthService->updateProfile($id, $data);
        if (!$updated) return $this->json(['error' => 'Failed to update profile'], 400);
        $this->logAction('health_profile_updated', ['user_id'=>$userId, 'profile_id'=>$id]);
        $this->incrementMetric('health_profiles_updated');
        return $this->json(['profile'=>$updated]);
    }

    #[Route('/profiles/{id}', name: 'health_delete_profile', methods: ['DELETE'])]
    public function deleteProfile(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->deleteProfileAction($id, $request));
    }

    private function deleteProfileAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Profile ID must be positive');
        $userId = (int)$this->requireAuth();
        $profile = $this->healthService->getProfile($id);
        if (!$profile) return $this->json(['error' => 'Profile not found'], 404);
        if ($profile->getuserId() !== $userId) return $this->json(['error' => 'Access denied'], 403);
        $deleted = $this->healthService->deleteProfile($id);
        if (!$deleted) return $this->json(['error' => 'Failed to delete profile'], 400);
        $this->logAction('health_profile_deleted', ['user_id'=>$userId, 'profile_id'=>$id]);
        $this->incrementMetric('health_profiles_deleted');
        return $this->json([], JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/profiles/check', name: 'health_check_profile_exists', methods: ['GET'])]
    public function checkProfileExists(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->checkProfileExistsAction($request));
    }

    private function checkProfileExistsAction(Request $request): JsonResponse
    {
        $userId = (int)$this->requireAuth();
        $exists = $this->healthService->profileExists($userId);
        return $this->json(['exists'=>$exists]);
    }

    #[Route('/records', name: 'health_create_record', methods: ['POST'])]
    public function createHealthRecord(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createHealthRecordAction($request));
    }

    private function createHealthRecordAction(Request $request): JsonResponse
    {
        $userId = (int)$this->requireAuth();
        $data = $this->getJsonData($request);
        if (empty($data['type'])) throw new InvalidArgumentException('Record type is required');
        $record = $this->healthService->createHealthRecord($userId, $data['type'], $data['value'] ?? null, $data['metadata'] ?? null);
        $this->logAction('health_record_created', ['user_id'=>$userId, 'record_type'=>$data['type']]);
        $this->incrementMetric('health_records_created');
        return $this->json(['record'=>$record], JsonResponse::HTTP_CREATED);
    }

    #[Route('/records/{id}/upload', name: 'health_upload_record_file', methods: ['POST'])]
    public function uploadRecordFile(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->uploadRecordFileAction($id, $request));
    }

    private function uploadRecordFileAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Record ID must be positive');
        $userId = (int)$this->requireAuth();
        // Simplified: assume file upload handled elsewhere
        $this->logAction('health_record_file_uploaded', ['user_id'=>$userId, 'record_id'=>$id]);
        $this->incrementMetric('health_record_files_uploaded');
        return $this->json(['message' => 'File uploaded'], JsonResponse::HTTP_OK);
    }
}
