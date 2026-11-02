<?php declare(strict_types=1);

namespace App\Tests\Unit\Insurance\Controller;

use App\Insurance\Controller\InsuranceController;
use App\Insurance\Entity\InsurancePolicy;
use App\Insurance\Service\InsuranceService;
use PHPUnit\Framework\TestCase;

class InsuranceControllerTest extends TestCase
{
    private InsuranceController $controller;
    private InsuranceService $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(InsuranceService::class);
        $reflection = new \ReflectionClass(InsuranceController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $reflectionProperty = $reflection->getProperty('service');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($controller, $this->service);
        $this->controller = $controller;
    }

    private function createPolicy(): InsurancePolicy
    {
        $policy = new InsurancePolicy(1, 'health', 'Prudential', 'POL-001');
        $policy->setId(1);
        return $policy;
    }

    public function testCreatePolicySuccess(): void
    {
        $policy = $this->createPolicy();
        
        $this->service->expects($this->once())
            ->method('createPolicy')
            ->with(1, 'health', 'Prudential', 'POL-001')
            ->willReturn($policy);

        $request = [
            'user_id' => 1,
            'body' => [
                'type' => 'health',
                'provider' => 'Prudential',
                'policy_number' => 'POL-001'
            ]
        ];

        $result = $this->controller->createPolicy($request);

        $this->assertEquals(201, $result['status']);
    }

    public function testGetPolicyFound(): void
    {
        $policy = $this->createPolicy();
        
        $this->service->expects($this->once())
            ->method('getPolicy')
            ->with(1)
            ->willReturn($policy);

        $request = ['params' => ['id' => 1]];

        $result = $this->controller->getPolicy($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetPolicyNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('getPolicy')
            ->with(999)
            ->willReturn(null);

        $request = ['params' => ['id' => 999]];

        $result = $this->controller->getPolicy($request);

        $this->assertEquals(404, $result['status']);
    }

    public function testGetUserPolicies(): void
    {
        $policy = $this->createPolicy();
        
        $this->service->expects($this->once())
            ->method('getUserPolicies')
            ->with(1)
            ->willReturn([$policy]);

        $request = ['params' => ['user_id' => 1]];

        $result = $this->controller->getUserPolicies($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetPoliciesByType(): void
    {
        $policy = $this->createPolicy();
        
        $this->service->expects($this->once())
            ->method('getPoliciesByType')
            ->with('health')
            ->willReturn([$policy]);

        $request = ['params' => ['type' => 'health']];

        $result = $this->controller->getPoliciesByType($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testUpdatePolicySuccess(): void
    {
        $policy = $this->createPolicy();
        
        $this->service->expects($this->once())
            ->method('updatePolicy')
            ->with(1, ['coverage_amount' => 1000000])
            ->willReturn($policy);

        $request = [
            'params' => ['id' => 1],
            'body' => ['coverage_amount' => 1000000]
        ];

        $result = $this->controller->updatePolicy($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testUpdatePolicyNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('updatePolicy')
            ->with(999, [])
            ->willReturn(null);

        $request = [
            'params' => ['id' => 999],
            'body' => []
        ];

        $result = $this->controller->updatePolicy($request);

        $this->assertEquals(404, $result['status']);
    }

    public function testActivatePolicySuccess(): void
    {
        $policy = $this->createPolicy();
        
        $this->service->expects($this->once())
            ->method('activatePolicy')
            ->with(1)
            ->willReturn($policy);

        $request = ['params' => ['id' => 1]];

        $result = $this->controller->activatePolicy($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testCancelPolicySuccess(): void
    {
        $policy = $this->createPolicy();
        
        $this->service->expects($this->once())
            ->method('cancelPolicy')
            ->with(1)
            ->willReturn($policy);

        $request = ['params' => ['id' => 1]];

        $result = $this->controller->cancelPolicy($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testRenewPolicySuccess(): void
    {
        $policy = $this->createPolicy();
        
        $this->service->expects($this->once())
            ->method('renewPolicy')
            ->with(1, 12)
            ->willReturn($policy);

        $request = ['params' => ['id' => 1], 'body' => ['months' => 12]];

        $result = $this->controller->renewPolicy($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testSearchPolicies(): void
    {
        $policy = $this->createPolicy();
        
        $this->service->expects($this->once())
            ->method('searchPolicies')
            ->with(['type' => 'health'])
            ->willReturn([$policy]);

        $request = ['query' => ['type' => 'health']];

        $result = $this->controller->searchPolicies($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetExpiringPolicies(): void
    {
        $policy = $this->createPolicy();
        
        $this->service->expects($this->once())
            ->method('getExpiringPolicies')
            ->with(30)
            ->willReturn([$policy]);

        $request = ['query' => ['days' => 30]];

        $result = $this->controller->getExpiringPolicies($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testDeletePolicySuccess(): void
    {
        $this->service->expects($this->once())
            ->method('deletePolicy')
            ->with(1)
            ->willReturn(true);

        $request = ['params' => ['id' => 1]];

        $result = $this->controller->deletePolicy($request);

        $this->assertEquals(204, $result['status']);
    }

    public function testDeletePolicyNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('deletePolicy')
            ->with(999)
            ->willReturn(false);

        $request = ['params' => ['id' => 999]];

        $result = $this->controller->deletePolicy($request);

        $this->assertEquals(404, $result['status']);
    }
}
