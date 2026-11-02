<?php

declare(strict_types=1);

namespace App\Tests\Unit\Health\Controller;

use PHPUnit\Framework\TestCase;
use App\Health\Controller\HealthController;
use App\Health\Service\HealthService;
use App\Health\Entity\HealthProfile;
use App\Health\Entity\HealthRecord;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class HealthControllerTest extends TestCase
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
        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        // No serializer -> json() returns raw JsonResponse (no serialization)
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

    // ==================== Health Profile ====================

    public function testCreateProfile_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $profile = $this->createMock(HealthProfile::class);
        $profile->method('getId')->willReturn(42);
        $this->healthService->method('createProfile')->with(1)->willReturn($profile);

        $response = $this->controller->createProfile(new Request());
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateProfile_Unauthenticated(): void
    {
        $response = $this->controller->createProfile(new Request());
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Authentication required', $data['error']);
    }

    public function testGetProfile_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $profile = $this->createMock(HealthProfile::class);
        $profile->method('getUserId')->willReturn(1);
        $this->healthService->method('getProfile')->with(42)->willReturn($profile);

        $response = $this->controller->getProfile(42);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetProfile_InvalidId(): void
    {
        $response = $this->controller->getProfile(0);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetProfile_NotFound(): void
    {
        $this->setAuthenticatedUser(1);
        $this->healthService->method('getProfile')->with(99)->willReturn(null);
        $response = $this->controller->getProfile(99);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetProfile_AccessDenied(): void
    {
        $this->setAuthenticatedUser(1);
        $profile = $this->createMock(HealthProfile::class);
        $profile->method('getUserId')->willReturn(2);
        $this->healthService->method('getProfile')->with(42)->willReturn($profile);
        $response = $this->controller->getProfile(42);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetUserProfile_Success(): void
    {
        $this->setAuthenticatedUser(5);
        $profile = $this->createMock(HealthProfile::class);
        $this->healthService->method('getUserProfile')->with('5')->willReturn($profile);
        $response = $this->controller->getUserProfile(5);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetUserProfile_AccessDenied(): void
    {
        $this->setAuthenticatedUser(5);
        $this->healthService->method('getUserProfile')->with('10')->willReturn($this->createMock(HealthProfile::class));
        $response = $this->controller->getUserProfile(10);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testUpdateProfile_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $profile = $this->createMock(HealthProfile::class);
        $profile->method('getUserId')->willReturn(1);
        $this->healthService->method('getProfile')->with(42)->willReturn($profile);
        $this->healthService->method('updateProfile')->with(42, ['height' => 170])->willReturn($profile);
        $request = $this->jsonRequest(['height' => 170]);
        $response = $this->controller->updateProfile(42, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUpdateProfile_InvalidJson(): void
    {
        $this->setAuthenticatedUser(1);
        $profile = $this->createMock(HealthProfile::class);
        $profile->method('getUserId')->willReturn(1);
        $this->healthService->method('getProfile')->with(1)->willReturn($profile);
        $request = Request::create('/api/health/profiles/1', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->updateProfile(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateProfile_ValidationError(): void
    {
        $this->setAuthenticatedUser(1);
        $profile = $this->createMock(HealthProfile::class);
        $profile->method('getUserId')->willReturn(1);
        $this->healthService->method('getProfile')->with(42)->willReturn($profile);
        $request = $this->jsonRequest(['height' => -10]);
        $response = $this->controller->updateProfile(42, $request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testDeleteProfile_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $profile = $this->createMock(HealthProfile::class);
        $profile->method('getUserId')->willReturn(1);
        $this->healthService->method('getProfile')->with(42)->willReturn($profile);
        $this->healthService->method('deleteProfile')->with(42)->willReturn(true);
        $response = $this->controller->deleteProfile(42, new Request());
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testCheckProfileExists_Exists(): void
    {
        $this->setAuthenticatedUser(1);
        $this->healthService->method('profileExists')->with(1)->willReturn(true);
        $response = $this->controller->checkProfileExists(new Request());
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['exists']);
    }

    // ==================== Health Records ====================

    public function testCreateHealthRecord_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $record = $this->createMock(HealthRecord::class);
        $record->method('getId')->willReturn(100);
        $this->healthService->method('createHealthRecord')->with('1', 'blood_pressure', ['systolic' => 120, 'diastolic' => 80], null)->willReturn($record);
        $request = $this->jsonRequest(['type' => 'blood_pressure', 'value' => ['systolic' => 120, 'diastolic' => 80]]);
        $response = $this->controller->createHealthRecord($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateHealthRecord_MissingType(): void
    {
        $this->setAuthenticatedUser(1);
        $request = $this->jsonRequest(['value' => 'test']);
        $response = $this->controller->createHealthRecord($request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('required', $data['error']);
    }

    public function testUploadRecordFile_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $response = $this->controller->uploadRecordFile(5, new Request());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUploadRecordFile_InvalidId(): void
    {
        $response = $this->controller->uploadRecordFile(0, new Request());
        $this->assertSame(400, $response->getStatusCode());
    }
}
