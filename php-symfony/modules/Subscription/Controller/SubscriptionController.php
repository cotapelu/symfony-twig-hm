<?php

declare(strict_types=1);

namespace App\Subscription\Controller;

use App\Subscription\Service\SubscriptionService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use InvalidArgumentException;

#[Route('/api/subscriptions')]
class SubscriptionController extends BaseController
{
    private const MAX_NAME_LENGTH = 100;
    private const MAX_DESCRIPTION_LENGTH = 500;
    private const MAX_PLAN_ID_LENGTH = 50;
    private const MAX_PRICE = 1000000000;
    private const MAX_DURATION_DAYS = 3650;
    private const MAX_FEATURES = 50;

    public function __construct(
        private SubscriptionService $subscriptionService,
        private ?TokenStorageInterface $tokenStorage = null,
        ?AuthorizationCheckerInterface $authChecker = null
    ) {
        parent::__construct();
        $this->authChecker = $authChecker;
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['action'=>$action,'context'=>$context], JSON_UNESCAPED_UNICODE));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode(['metric'=>$metric,'tags'=>$tags]));
    }

    private function sanitizeString(?string $value): string
    {
        return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function isAuthenticated(): bool
    {
        if ($this->tokenStorage === null || $this->authChecker === null) return false;
        return $this->tokenStorage->getToken() !== null && $this->authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }

    private function getCurrentUserId(): ?string
    {
        if (!$this->isAuthenticated()) return null;
        $token = $this->tokenStorage?->getToken();
        if (!$token) return null;
        $user = $token->getUser();
        if (!$user || !method_exists($user, 'getUserIdentifier')) return null;
        return $user->getUserIdentifier();
    }

    private function requireAuth(): JsonResponse|true
    {
        if (!$this->isAuthenticated()) return $this->json(['error' => 'Authentication required'], 401);
        return true;
    }

    private function isAdmin(): bool
    {
        if (!$this->isAuthenticated()) return false;
        if ($this->authChecker === null) return false;
        return $this->authChecker->isGranted('ROLE_ADMIN');
    }

    private function requireAdmin(): JsonResponse|true
    {
        if (!$this->isAdmin()) return $this->json(['error' => 'Admin access required'], 403);
        return true;
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['status' => 400, 'error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            error_log('SubscriptionController error: ' . $e->getMessage());
            return $this->json(['status' => 500, 'error' => 'Internal server error'], 500);
        }
    }

    // ==================== Wrappers ====================
    #[Route('/plans', methods: ['GET'])]
    public function getPlans(): JsonResponse
    {
        return $this->handle(fn() => $this->getPlansAction());
    }

    #[Route('/plans', methods: ['POST'])]
    public function createPlan(Request $request): JsonResponse
    {
        $authCheck = $this->requireAdmin();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        return $this->handle(fn() => $this->createPlanAction($request));
    }

    #[Route('/plans/{id}', methods: ['PUT'])]
    public function updatePlan(Request $request, string $id): JsonResponse
    {
        $authCheck = $this->requireAdmin();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        return $this->handle(fn() => $this->updatePlanAction($request, $id));
    }

    #[Route('/plans/{id}', methods: ['DELETE'])]
    public function deletePlan(string $id): JsonResponse
    {
        $authCheck = $this->requireAdmin();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        return $this->handle(fn() => $this->deletePlanAction($id));
    }

    #[Route('/', methods: ['POST'])]
    public function createSubscription(Request $request): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        return $this->handle(fn() => $this->createSubscriptionAction($request));
    }

    #[Route('/my', methods: ['GET'])]
    public function getMySubscription(): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        return $this->handle(fn() => $this->getMySubscriptionAction());
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getSubscription(string $id): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        return $this->handle(fn() => $this->getSubscriptionAction($id));
    }

    #[Route('/{id}/cancel', methods: ['POST'])]
    public function cancelSubscription(string $id): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        return $this->handle(fn() => $this->cancelSubscriptionAction($id));
    }

    // ==================== Actions ====================
    private function getPlansAction(): JsonResponse
    {
        $plans = $this->subscriptionService->getAllPlans();
        return $this->json($plans);
    }

    private function createPlanAction(Request $request): JsonResponse
    {
        $data = $this->decodeJsonOr400($request);
        if ($data instanceof JsonResponse) return $data;
        if (($err = $this->runValidators($data, $this->getPlanValidators())) !== null) return $err;
        $plan = $this->subscriptionService->createPlan($data);
        return $this->json($plan->toArray(), 201);
    }

    private function updatePlanAction(Request $request, string $id): JsonResponse
    {
        $data = $this->decodeJsonOr400($request);
        if ($data instanceof JsonResponse) return $data;
        if (($err = $this->validateUpdatePlanData($id, $data)) !== null) return $err;
        $plan = $this->subscriptionService->updatePlan($id, $data);
        return $this->json($plan->toArray());
    }

    private function deletePlanAction(string $id): JsonResponse
    {
        if (strlen($id) > self::MAX_PLAN_ID_LENGTH) {
            return $this->json(['error' => 'Invalid plan ID'], 400);
        }
        $this->subscriptionService->deletePlan($id);
        return $this->json(['message' => 'Plan deleted successfully']);
    }

    private function createSubscriptionAction(Request $request): JsonResponse
    {
        $userId = $this->getCurrentUserId();
        if ($userId === null) return $this->json(['error' => 'User not found'], 401);
        $data = $this->decodeJsonOr400($request);
        if ($data instanceof JsonResponse) return $data;
        if (empty($data['plan_id'])) return $this->json(['error' => 'Plan ID is required'], 400);
        if (isset($data['auto_renew']) && !is_bool($data['auto_renew'])) {
            return $this->json(['error' => 'auto_renew must be boolean'], 400);
        }
        $subscription = $this->subscriptionService->createSubscription(
            userId: $userId,
            planId: $data['plan_id'],
            options: $data
        );
        return $this->json($subscription->toArray(), 201);
    }

    private function getMySubscriptionAction(): JsonResponse
    {
        $userId = $this->getCurrentUserId();
        if ($userId === null) return $this->json(['error' => 'User not found'], 401);
        $subscription = $this->subscriptionService->getUserSubscription($userId);
        if (!$subscription) return $this->json(['error' => 'No active subscription found'], 404);
        return $this->json($subscription->toArray());
    }

    private function getSubscriptionAction(string $id): JsonResponse
    {
        $subscription = $this->subscriptionService->getSubscription($id);
        if (!$subscription) return $this->json(['error' => 'Subscription not found'], 404);
        return $this->json($subscription->toArray());
    }

    private function cancelSubscriptionAction(string $id): JsonResponse
    {
        $userId = $this->getCurrentUserId();
        if ($userId === null) return $this->json(['error' => 'User not found'], 401);
        $subscription = $this->subscriptionService->getSubscription($id);
        if (!$subscription) return $this->json(['error' => 'Subscription not found'], 404);
        if ($subscription->getUser()->getId() !== $userId) {
            return $this->json(['error' => 'You can only cancel your own subscription'], 403);
        }
        $this->subscriptionService->cancelSubscription($id);
        return $this->json(['message' => 'Subscription cancelled successfully']);
    }

    // ==================== Validation ====================
    private function getPlanValidators(): array
    {
        return [
            [$this, 'validateRequiredFields'],
            [$this, 'validatePlanId'],
            [$this, 'validateName'],
            [$this, 'validatePrice'],
            [$this, 'validateDuration'],
            [$this, 'validateDescription'],
            [$this, 'validateFeatures'],
        ];
    }

    private function runValidators(array $data, array $validators): ?JsonResponse
    {
        foreach ($validators as $validator) {
            $result = call_user_func($validator, $data);
            if ($result instanceof JsonResponse) return $result;
        }
        return null;
    }

    private function validateUpdatePlanData(string $id, array $data): ?JsonResponse
    {
        $data['id'] = $id;
        $validators = [
            [$this, 'validatePlanId'],
            [$this, 'validateName'],
            [$this, 'validatePrice'],
            [$this, 'validateDuration'],
            [$this, 'validateDescription'],
        ];
        return $this->runValidators($data, $validators);
    }

    private function validateRequiredFields(array $data): ?JsonResponse
    {
        if (empty($data['id']) || empty($data['name']) || !isset($data['price'])) {
            return $this->json(['error' => 'ID, name, and price are required'], 400);
        }
        return null;
    }

    private function validatePlanId(array $data): ?JsonResponse
    {
        if (strlen($data['id']) > self::MAX_PLAN_ID_LENGTH) {
            return $this->json(['error' => 'Plan ID too long'], 400);
        }
        return null;
    }

    private function validateName(array $data): ?JsonResponse
    {
        if (isset($data['name']) && strlen($data['name']) > self::MAX_NAME_LENGTH) {
            return $this->json(['error' => 'Plan name too long'], 400);
        }
        return null;
    }

    private function validatePrice(array $data): ?JsonResponse
    {
        if (isset($data['price']) && ($data['price'] < 0 || $data['price'] > self::MAX_PRICE)) {
            return $this->json(['error' => 'Invalid price'], 400);
        }
        return null;
    }

    private function validateDuration(array $data): ?JsonResponse
    {
        if (isset($data['duration_days']) && ($data['duration_days'] < 1 || $data['duration_days'] > self::MAX_DURATION_DAYS)) {
            return $this->json(['error' => 'Invalid duration'], 400);
        }
        return null;
    }

    private function validateDescription(array $data): ?JsonResponse
    {
        if (!empty($data['description']) && strlen($data['description']) > self::MAX_DESCRIPTION_LENGTH) {
            return $this->json(['error' => 'Description too long'], 400);
        }
        return null;
    }

    private function validateFeatures(array $data): ?JsonResponse
    {
        if (isset($data['features']) && is_array($data['features']) && count($data['features']) > self::MAX_FEATURES) {
            return $this->json(['error' => 'Too many features'], 400);
        }
        return null;
    }
}