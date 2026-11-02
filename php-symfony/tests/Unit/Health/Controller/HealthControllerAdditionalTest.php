<?php

declare(strict_types=1);

namespace App\Tests\Unit\Health\Controller;

use PHPUnit\Framework\TestCase;
use App\Health\Controller\HealthController;
use App\Health\Service\HealthService;
use App\Health\Entity\HealthProfile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HealthControllerAdditionalTest extends TestCase
{
    private HealthService $healthService;
    private HealthController $controller;

    protected function setUp(): void
    {
        $this->healthService = $this->createMock(HealthService::class);
        $this->controller = new HealthController($this->healthService);
        $this->setupContainer();
    }

    private function setupContainer(): void
    {
        $container = new ContainerBuilder();
        $this->controller->setContainer($container);
    }

    private function createUserMock(int $id): \Symfony\Component\Security\Core\User\UserInterface
    {
        return new class($id) implements \Symfony\Component\Security\Core\User\UserInterface {
            private int $id;
            public function __construct(int $id) { $this->id = $id; }
            public function getUserIdentifier(): string { return (string)$this->id; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function eraseCredentials() {}
        };
    }

    private function setAuthenticatedUser(int $userId): void
    {
        $user = $this->createUserMock($userId);
        $this->controller->setTestUser($user);
    }

    private function jsonRequest(array $data = []): Request
    {
        return Request::create('/api/health/test', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    // ==================== Additional Validation Tests ====================

    public function testUpdateProfile_ValidationError_WeightNegative(): void
    {
        $this->setAuthenticatedUser(1);
        $profile = $this->createMock(HealthProfile::class);
        $profile->method('getUserId')->willReturn(1);
        $this->healthService->method('getProfile')->with(42)->willReturn($profile);
        $request = $this->jsonRequest(['weight' => -5]);
        $response = $this->controller->updateProfile(42, $request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertContains('Weight must be positive', $data['errors']);
    }

    public function testUpdateProfile_ValidationError_AgeInvalid(): void
    {
        $this->setAuthenticatedUser(1);
        $profile = $this->createMock(HealthProfile::class);
        $profile->method('getUserId')->willReturn(1);
        $this->healthService->method('getProfile')->with(42)->willReturn($profile);
        $request = $this->jsonRequest(['age' => 0]);
        $response = $this->controller->updateProfile(42, $request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertContains('Age must be positive', $data['errors']);
    }

    public function testUpdateProfile_ValidationError_InvalidBloodType(): void
    {
        $this->setAuthenticatedUser(1);
        $profile = $this->createMock(HealthProfile::class);
        $profile->method('getUserId')->willReturn(1);
        $this->healthService->method('getProfile')->with(42)->willReturn($profile);
        $request = $this->jsonRequest(['blood_type' => 'XYZ']);
        $response = $this->controller->updateProfile(42, $request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertContains('Invalid blood type', $data['errors']);
    }

    public function testUpdateProfile_ValidationError_InvalidGender(): void
    {
        $this->setAuthenticatedUser(1);
        $profile = $this->createMock(HealthProfile::class);
        $profile->method('getUserId')->willReturn(1);
        $this->healthService->method('getProfile')->with(42)->willReturn($profile);
        $request = $this->jsonRequest(['gender' => 'unknown']);
        $response = $this->controller->updateProfile(42, $request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertContains('Invalid gender', $data['errors']);
    }
}
