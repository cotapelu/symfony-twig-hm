<?php declare(strict_types=1);

namespace Tests\Unit\Subscription\Controller;

use App\Subscription\Controller\SubscriptionController;
use App\Subscription\Service\SubscriptionService;
use App\Subscription\Entity\SubscriptionPlan as Plan;
use App\Subscription\Entity\Subscription;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SubscriptionControllerTest extends TestCase
{
    private SubscriptionService $serviceMock;
    private TokenStorageInterface&MockObject $tokenStorageMock;
    private AuthorizationCheckerInterface&MockObject $authCheckerMock;
    private SubscriptionController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(SubscriptionService::class);
        $this->tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $this->authCheckerMock = $this->createMock(AuthorizationCheckerInterface::class);
        $this->controller = new SubscriptionController(
            $this->serviceMock,
            $this->tokenStorageMock,
            $this->authCheckerMock
        );
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function setAuthenticatedUser(int $userId, bool $isAdmin = false): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('getUserIdentifier')->willReturn("user$userId");

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->tokenStorageMock->method('getToken')->willReturn($token);
        $this->authCheckerMock->method('isGranted')
            ->willReturnCallback(function (string $attribute) use ($isAdmin) {
                if ($attribute === 'IS_AUTHENTICATED_REMEMBERED') return true;
                if ($attribute === 'ROLE_ADMIN') return $isAdmin;
                return false;
            });
    }

    private function jsonRequest(string $method, string $uri, array $body = []): Request
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode($body);
        return Request::create($uri, $method, [], [], [], $server, $content);
    }

    private function createPlanMock(array $data): Plan
    {
        $plan = $this->createMock(Plan::class);
        $plan->method('toArray')->willReturn($data);
        $plan->method('getId')->willReturn($data['id'] ?? 'plan-1');
        $plan->method('getName')->willReturn($data['name'] ?? '');
        $plan->method('getPrice')->willReturn($data['price'] ?? 0);
        return $plan;
    }

    private function createSubscriptionMock(array $data): Subscription
    {
        $sub = $this->createMock(Subscription::class);
        $sub->method('toArray')->willReturn($data);
        $sub->method('getId')->willReturn($data['id'] ?? 'sub-1');
        return $sub;
    }

    // ==================== GET /api/subscriptions/plans ====================
    public function testGetPlans_ReturnsAllPlans(): void
    {
        $plans = array($this->createPlanMock(array('id' => 'p1', 'name' => 'Basic', 'price' => 100)));
        $this->serviceMock->expects($this->once())
            ->method('getAllPlans')
            ->willReturn($plans);

        $response = $this->controller->getPlans();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $decoded = json_decode($response->getContent(), true);
        $this->assertCount(1, $decoded);
    }

    // ==================== POST /api/subscriptions/plans (admin) ====================
    public function testCreatePlan_RequiresAuth(): void
    {
        $request = $this->jsonRequest('POST', '/api/subscriptions/plans', ['id' => 'p1', 'name' => 'Basic', 'price' => 100]);
        $response = $this->controller->createPlan($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testCreatePlan_RequiresAdmin(): void
    {
        $this->setAuthenticatedUser(1, false);
        $request = $this->jsonRequest('POST', '/api/subscriptions/plans', ['id' => 'p1', 'name' => 'Basic', 'price' => 100]);
        $response = $this->controller->createPlan($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testCreatePlan_InvalidJson(): void
    {
        $this->setAuthenticatedUser(1, true);
        $request = Request::create('/api/subscriptions/plans', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');
        $response = $this->controller->createPlan($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreatePlan_MissingRequiredFields(): void
    {
        $this->setAuthenticatedUser(1, true);
        $request = $this->jsonRequest('POST', '/api/subscriptions/plans', ['name' => 'Basic']);
        $response = $this->controller->createPlan($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('ID, name, and price are required', $response->getContent());
    }

    public function testCreatePlan_InvalidPlanIdLength(): void
    {
        $this->setAuthenticatedUser(1, true);
        $longId = str_repeat('a', 51);
        $request = $this->jsonRequest('POST', '/api/subscriptions/plans', [
            'id' => $longId,
            'name' => 'Basic',
            'price' => 100
        ]);
        $response = $this->controller->createPlan($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Plan ID too long', $response->getContent());
    }

    public function testCreatePlan_InvalidNameLength(): void
    {
        $this->setAuthenticatedUser(1, true);
        $longName = str_repeat('a', 101);
        $request = $this->jsonRequest('POST', '/api/subscriptions/plans', [
            'id' => 'p1',
            'name' => $longName,
            'price' => 100
        ]);
        $response = $this->controller->createPlan($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Plan name too long', $response->getContent());
    }

    public function testCreatePlan_InvalidPrice(): void
    {
        $this->setAuthenticatedUser(1, true);
        $request = $this->jsonRequest('POST', '/api/subscriptions/plans', [
            'id' => 'p1',
            'name' => 'Basic',
            'price' => -10
        ]);
        $response = $this->controller->createPlan($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid price', $response->getContent());
    }

    public function testCreatePlan_InvalidDuration(): void
    {
        $this->setAuthenticatedUser(1, true);
        $request = $this->jsonRequest('POST', '/api/subscriptions/plans', [
            'id' => 'p1',
            'name' => 'Basic',
            'price' => 100,
            'duration_days' => 4000
        ]);
        $response = $this->controller->createPlan($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid duration', $response->getContent());
    }

    public function testCreatePlan_InvalidDescription(): void
    {
        $this->setAuthenticatedUser(1, true);
        $longDesc = str_repeat('a', 501);
        $request = $this->jsonRequest('POST', '/api/subscriptions/plans', [
            'id' => 'p1',
            'name' => 'Basic',
            'price' => 100,
            'description' => $longDesc
        ]);
        $response = $this->controller->createPlan($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Description too long', $response->getContent());
    }

    public function testCreatePlan_TooManyFeatures(): void
    {
        $this->setAuthenticatedUser(1, true);
        $features = array_fill(0, 51, 'feature');
        $request = $this->jsonRequest('POST', '/api/subscriptions/plans', [
            'id' => 'p1',
            'name' => 'Basic',
            'price' => 100,
            'features' => $features
        ]);
        $response = $this->controller->createPlan($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Too many features', $response->getContent());
    }

    public function testCreatePlan_Success(): void
    {
        $this->setAuthenticatedUser(1, true);
        $plan = $this->createPlanMock(['id' => 'p1', 'name' => 'Basic', 'price' => 100]);
        $this->serviceMock->expects($this->once())
            ->method('createPlan')
            ->with($this->callback(function($data) {
                return $data['id'] === 'p1' && $data['name'] === 'Basic' && $data['price'] === 100;
            }))
            ->willReturn($plan);
        $request = $this->jsonRequest('POST', '/api/subscriptions/plans', [
            'id' => 'p1',
            'name' => 'Basic',
            'price' => 100
        ]);
        $response = $this->controller->createPlan($request);
        $this->assertSame(201, $response->getStatusCode());
        $decoded = json_decode($response->getContent(), true);
        $this->assertSame('p1', $decoded['id']);
    }

    // ==================== PUT /api/subscriptions/plans/{id} (admin) ====================
    public function testUpdatePlan_RequiresAuth(): void
    {
        $request = $this->jsonRequest('PUT', '/api/subscriptions/plans/p1', ['name' => 'Updated']);
        $response = $this->controller->updatePlan($request, 'p1');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testUpdatePlan_RequiresAdmin(): void
    {
        $this->setAuthenticatedUser(1, false);
        $request = $this->jsonRequest('PUT', '/api/subscriptions/plans/p1', ['name' => 'Updated']);
        $response = $this->controller->updatePlan($request, 'p1');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testUpdatePlan_InvalidPlanIdLength(): void
    {
        $this->setAuthenticatedUser(1, true);
        $longId = str_repeat('a', 51);
        $request = $this->jsonRequest('PUT', "/api/subscriptions/plans/$longId", ['name' => 'Updated']);
        $response = $this->controller->updatePlan($request, $longId);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdatePlan_Success(): void
    {
        $this->setAuthenticatedUser(1, true);
        $plan = $this->createPlanMock(['id' => 'p1', 'name' => 'Updated', 'price' => 200]);
        $this->serviceMock->expects($this->once())
            ->method('updatePlan')
            ->with('p1', $this->callback(fn($data) => $data['name'] === 'Updated' && $data['price'] === 200))
            ->willReturn($plan);
        $request = $this->jsonRequest('PUT', '/api/subscriptions/plans/p1', [
            'name' => 'Updated',
            'price' => 200
        ]);
        $response = $this->controller->updatePlan($request, 'p1');
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== DELETE /api/subscriptions/plans/{id} (admin) ====================
    public function testDeletePlan_RequiresAuth(): void
    {
        $response = $this->controller->deletePlan('p1');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testDeletePlan_RequiresAdmin(): void
    {
        $this->setAuthenticatedUser(1, false);
        $response = $this->controller->deletePlan('p1');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testDeletePlan_InvalidIdLength(): void
    {
        $this->setAuthenticatedUser(1, true);
        $longId = str_repeat('a', 51);
        $response = $this->controller->deletePlan($longId);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testDeletePlan_Success(): void
    {
        $this->setAuthenticatedUser(1, true);
        $this->serviceMock->expects($this->once())
            ->method('deletePlan')
            ->with('p1');
        $response = $this->controller->deletePlan('p1');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Plan deleted successfully', json_decode($response->getContent(), true)['message']);
    }

    // ==================== POST /api/subscriptions (auth) ====================
    public function testCreateSubscription_RequiresAuth(): void
    {
        $request = $this->jsonRequest('POST', '/api/subscriptions', ['plan_id' => 'p1']);
        $response = $this->controller->createSubscription($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testCreateSubscription_MissingPlanId(): void
    {
        $this->setAuthenticatedUser(1);
        $request = $this->jsonRequest('POST', '/api/subscriptions', []);
        $response = $this->controller->createSubscription($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Plan ID is required', $response->getContent());
    }

    public function testCreateSubscription_InvalidAutoRenew(): void
    {
        $this->setAuthenticatedUser(1);
        $request = $this->jsonRequest('POST', '/api/subscriptions', [
            'plan_id' => 'p1',
            'auto_renew' => 'yes'
        ]);
        $response = $this->controller->createSubscription($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('auto_renew must be boolean', $response->getContent());
    }

    public function testCreateSubscription_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $sub = $this->createSubscriptionMock(['id' => 's1', 'plan_id' => 'p1']);
        $this->serviceMock->expects($this->once())
            ->method('createSubscription')
            ->with('user1', 'p1', $this->callback(fn($opt) => $opt['auto_renew'] === null))
            ->willReturn($sub);
        $request = $this->jsonRequest('POST', '/api/subscriptions', ['plan_id' => 'p1']);
        $response = $this->controller->createSubscription($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateSubscription_WithOptions(): void
    {
        $this->setAuthenticatedUser(2);
        $sub = $this->createSubscriptionMock(['id' => 's2', 'plan_id' => 'p2']);
        $this->serviceMock->expects($this->once())
            ->method('createSubscription')
            ->with('user2', 'p2', $this->callback(fn($opt) => $opt['auto_renew'] === true && $opt['start_date'] === '2025-01-01'))
            ->willReturn($sub);
        $request = $this->jsonRequest('POST', '/api/subscriptions', [
            'plan_id' => 'p2',
            'auto_renew' => true,
            'start_date' => '2025-01-01'
        ]);
        $response = $this->controller->createSubscription($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    // ==================== GET /api/subscriptions/my (auth) ====================
    public function testGetMySubscription_RequiresAuth(): void
    {
        $request = $this->jsonRequest('GET', '/api/subscriptions/my');
        $response = $this->controller->getMySubscription($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetMySubscription_NotFound(): void
    {
        $this->setAuthenticatedUser(1);
        $this->serviceMock->expects($this->once())
            ->method('getUserSubscription')
            ->with('user1')
            ->willReturn(null);
        $request = $this->jsonRequest('GET', '/api/subscriptions/my');
        $response = $this->controller->getMySubscription($request);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('No active subscription found', $response->getContent());
    }

    public function testGetMySubscription_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $sub = $this->createSubscriptionMock(['id' => 's1', 'plan_id' => 'p1']);
        $this->serviceMock->expects($this->once())
            ->method('getUserSubscription')
            ->with('user1')
            ->willReturn($sub);
        $request = $this->jsonRequest('GET', '/api/subscriptions/my');
        $response = $this->controller->getMySubscription($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== GET /api/subscriptions/{id} (auth) ====================
    public function testGetSubscription_RequiresAuth(): void
    {
        $request = $this->jsonRequest('GET', '/api/subscriptions/s1');
        $response = $this->controller->getSubscription('s1', $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetSubscription_NotFound(): void
    {
        $this->setAuthenticatedUser(1);
        $this->serviceMock->expects($this->once())
            ->method('getSubscription')
            ->with('s1')
            ->willReturn(null);
        $request = $this->jsonRequest('GET', '/api/subscriptions/s1');
        $response = $this->controller->getSubscription('s1', $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetSubscription_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $sub = $this->createSubscriptionMock(['id' => 's1']);
        $this->serviceMock->expects($this->once())
            ->method('getSubscription')
            ->with('s1')
            ->willReturn($sub);
        $request = $this->jsonRequest('GET', '/api/subscriptions/s1');
        $response = $this->controller->getSubscription('s1', $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== POST /api/subscriptions/{id}/cancel (auth) ====================
    public function testCancelSubscription_RequiresAuth(): void
    {
        $request = $this->jsonRequest('POST', '/api/subscriptions/s1/cancel');
        $response = $this->controller->cancelSubscription('s1', $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testCancelSubscription_NotFound(): void
    {
        $this->setAuthenticatedUser(1);
        $this->serviceMock->expects($this->once())
            ->method('getSubscription')
            ->with('s1')
            ->willReturn(null);
        $request = $this->jsonRequest('POST', '/api/subscriptions/s1/cancel');
        $response = $this->controller->cancelSubscription('s1', $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testCancelSubscription_NotOwner(): void
    {
        $this->setAuthenticatedUser(1);
        $ownerUser = $this->createMock(User::class);
        $ownerUser->method('getId')->willReturn('user2');
        $sub = $this->createSubscriptionMock(['id' => 's1']);
        $sub->method('getUser')->willReturn($ownerUser);
        $this->serviceMock->expects($this->once())
            ->method('getSubscription')
            ->with('s1')
            ->willReturn($sub);
        $request = $this->jsonRequest('POST', '/api/subscriptions/s1/cancel');
        $response = $this->controller->cancelSubscription('s1', $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testCancelSubscription_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $ownerUser = $this->createMock(User::class);
        $ownerUser->method('getId')->willReturn('user1');
        $sub = $this->createSubscriptionMock(['id' => 's1']);
        $sub->method('getUser')->willReturn($ownerUser);
        $this->serviceMock->expects($this->once())
            ->method('getSubscription')
            ->with('s1')
            ->willReturn($sub);
        $this->serviceMock->expects($this->once())
            ->method('cancelSubscription')
            ->with('s1');
        $request = $this->jsonRequest('POST', '/api/subscriptions/s1/cancel');
        $response = $this->controller->cancelSubscription('s1', $request);
        $this->assertSame(200, $response->getStatusCode());
    }
}
