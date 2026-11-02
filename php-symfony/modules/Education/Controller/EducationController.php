<?php

declare(strict_types=1);

namespace App\Education\Controller;

use App\Education\Service\EducationService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/education')]
class EducationController extends BaseController
{
    private const VALID_LEVELS = ['beginner', 'intermediate', 'advanced'];
    private const VALID_CATEGORIES = ['programming', 'design', 'business', 'marketing', 'language', 'science', 'mathematics', 'other'];
    private const MIN_DURATION = 15;
    private const MAX_DURATION = 1440;
    private const MIN_PRICE = 0;
    private const MAX_PRICE = 99999.99;

    public function __construct(
        private readonly EducationService $service
    ) {}

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Authentication required'], 401);
        }
        return $user->getUserIdentifier();
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('controller', $e);
        }
    }

    // ==================== Pagination & Validation Helpers ====================

    private function extractPagination(Request $request): array
    {
        $page = max(1, (int)($request->query->get('page', 1)));
        $limit = max(1, min(100, (int)($request->query->get('limit', 20))));
        $offset = ($page - 1) * $limit;
        return compact('page', 'limit', 'offset');
    }

    // New validation helpers for creation (required fields)
    private function requireTitle(array $data): void
    {
        if (empty($data['title'])) {
            throw new InvalidArgumentException('Title is required');
        }
    }

    private function requireCategory(array $data): void
    {
        if (empty($data['category']) || !in_array($data['category'], self::VALID_CATEGORIES)) {
            throw new InvalidArgumentException('Invalid or missing category');
        }
    }

    // Helpers for optional fields (used in both create & update)
    private function validateLevelIfSet(array $data): void
    {
        if (isset($data['level']) && !in_array($data['level'], self::VALID_LEVELS)) {
            throw new InvalidArgumentException('Invalid level');
        }
    }

    private function validateDurationIfSet(array $data): void
    {
        if (isset($data['duration'])) {
            $duration = (int)$data['duration'];
            if ($duration < self::MIN_DURATION || $duration > self::MAX_DURATION) {
                throw new InvalidArgumentException('Duration must be between '.self::MIN_DURATION.' and '.self::MAX_DURATION.' minutes');
            }
        }
    }

    private function validatePriceIfSet(array $data): void
    {
        if (isset($data['price'])) {
            $price = (float)$data['price'];
            if ($price < self::MIN_PRICE || $price > self::MAX_PRICE) {
                throw new InvalidArgumentException('Price must be between '.self::MIN_PRICE.' and '.self::MAX_PRICE);
            }
        }
    }

    // Helpers for update (if set)
    private function validateTitleIfSet(array $data): void
    {
        if (isset($data['title']) && empty($data['title'])) {
            throw new InvalidArgumentException('Title cannot be empty');
        }
    }

    private function validateCategoryIfSet(array $data): void
    {
        if (isset($data['category']) && !in_array($data['category'], self::VALID_CATEGORIES)) {
            throw new InvalidArgumentException('Invalid category');
        }
    }

    // ==================== Public Endpoints ====================

    #[Route('/courses', name: 'education_create_course', methods: ['POST'])]
    public function createCourse(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $instructorId = (int) $userIdOr401;

            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;

            $this->validateCourseCreationData($data);
            $course = $this->service->createCourse($instructorId, $data['title'], $data['category']);
            $this->applyCourseFields($course, $data);
            $this->logAction('course_created', ['course_id'=>$course->getId(),'instructor_id'=>$instructorId,'category'=>$data['category']]); $this->incrementMetric('courses_created');

            return $this->json(['success'=>true, 'data'=>$course], Response::HTTP_CREATED);
        });
    }

    #[Route('/courses', name: 'education_get_courses', methods: ['GET'])]
    public function getCourses(Request $request): JsonResponse
    {
        try {
            $pagination = $this->extractPagination($request);
            $courses = $this->service->getPublishedCourses();
            $paginatedCourses = $this->paginateCourses($courses, $pagination);

            return $this->buildCourseListResponse($courses, $pagination, $paginatedCourses);
        } catch (\Exception $e) {
            return $this->handleException('get_courses', $e);
        }
    }

    #[Route('/courses/{id}', name: 'education_get_course', methods: ['GET'])]
    public function getCourse(int $id): JsonResponse
    {
        try {
            if ($id <= 0) {
                throw new InvalidArgumentException('Course ID must be a positive integer');
            }

            $course = $this->service->getCourse($id);

            if (!$course) {
                return $this->json(['success' => false, 'error' => 'Course not found'], Response::HTTP_NOT_FOUND);
            }

            return $this->json(['success' => true, 'data' => $course]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('get_course', $e);
        }
    }

    #[Route('/courses/{id}', name: 'education_update_course', methods: ['PUT'])]
    public function updateCourse(int $id, Request $request): JsonResponse
    {
        return $this->handle(function() use ($id, $request) {
            if ($id <= 0) {
                throw new InvalidArgumentException('Course ID must be a positive integer');
            }

            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $instructorId = (int) $userIdOr401;

            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;

            $course = $this->service->getCourse($id);
            if (!$course) {
                return $this->json(['success' => false, 'error' => 'Course not found'], Response::HTTP_NOT_FOUND);
            }

            if ($course->getInstructorId() !== $instructorId) {
                return $this->json(['success' => false, 'error' => 'Access denied: You can only update your own courses'], Response::HTTP_FORBIDDEN);
            }

            $this->validateCourseUpdateData($data);
            $this->applyCourseFields($course, $data);
            $updated = $this->service->updateCourse($id, $data);
            $this->logAction('course_updated', ['course_id'=>$id,'instructor_id'=>$instructorId]); $this->incrementMetric('courses_updated');

            return $this->json(['success' => true, 'data' => $updated]);
        });
    }

    #[Route('/courses/{id}', name: 'education_delete_course', methods: ['DELETE'])]
    public function deleteCourse(int $id, Request $request): JsonResponse
    {
        return $this->handle(function() use ($id, $request) {
            if ($id <= 0) {
                throw new InvalidArgumentException('Course ID must be a positive integer');
            }

            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $instructorId = (int) $userIdOr401;

            $course = $this->service->getCourse($id);
            if (!$course) {
                return $this->json(['success' => false, 'error' => 'Course not found'], Response::HTTP_NOT_FOUND);
            }

            if ($course->getInstructorId() !== $instructorId) {
                return $this->json(['success' => false, 'error' => 'Access denied: You can only delete your own courses'], Response::HTTP_FORBIDDEN);
            }

            $this->service->deleteCourse($id);
            $this->logAction('course_deleted', ['course_id'=>$id,'instructor_id'=>$instructorId]); $this->incrementMetric('courses_deleted');

            return $this->json(['success' => true]);
        });
    }

    // ==================== Private Helper Methods ====================

    private function applyCourseFields($course, array $data): void
    {
        if (isset($data['description'])) {
            $course->setDescription($data['description']);
        }
        if (isset($data['level'])) {
            $course->setLevel($data['level']);
        }
        if (isset($data['duration'])) {
            $course->setDuration((int) $data['duration']);
        }
        if (isset($data['price'])) {
            $course->setPrice((float) $data['price']);
        }
        if (isset($data['thumbnail_url'])) {
            $course->setThumbnailUrl($data['thumbnail_url']);
        }
    }

    private function paginateCourses(array $courses, array $pagination): array
    {
        $offset = $pagination['offset'];
        $limit = $pagination['limit'];
        return array_slice($courses, $offset, $limit);
    }

    private function buildCourseListResponse(array $courses, array $pagination, array $paginatedCourses): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => array_values($paginatedCourses),
            'meta' => [
                'total' => count($courses),
                'limit' => $pagination['limit'],
                'offset' => $pagination['offset']
            ]
        ]);
    }

    // Validation orchestrators (now thin wrappers)
    private function validateCourseCreationData(array $data): void
    {
        $this->requireTitle($data);
        $this->requireCategory($data);
        $this->validateLevelIfSet($data);
        $this->validateDurationIfSet($data);
        $this->validatePriceIfSet($data);
    }

    private function validateCourseUpdateData(array $data): void
    {
        $this->validateTitleIfSet($data);
        $this->validateCategoryIfSet($data);
        $this->validateLevelIfSet($data);
        $this->validateDurationIfSet($data);
        $this->validatePriceIfSet($data);
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new \DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'context'=>$context], JSON_UNESCAPED_UNICODE));
    }

    private function incrementMetric(string $metricName): void
    {
        error_log(json_encode(['timestamp'=>(new \DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metricName, 'value'=>1]));
    }
}
