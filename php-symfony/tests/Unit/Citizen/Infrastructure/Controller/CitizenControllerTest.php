<?php

declare(strict_types=1);

namespace Tests\Unit\Citizen\Infrastructure\Controller;

use App\Citizen\Infrastructure\Controller\CitizenController;
use App\Citizen\Application\Service\CitizenService;
use App\Citizen\Application\DTO\CitizenResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\User\UserInterface;
use PHPUnit\Framework\TestCase;

class CitizenControllerTest extends TestCase
{
    private const VALID_UUID = '550e8400-e29b-41d4-a716-446655440000';
    private CitizenService $citizenServiceMock;
    private ValidatorInterface $validatorMock;
    private CitizenController $controller;

    protected function setUp(): void
    {
        $this->citizenServiceMock = $this->createMock(CitizenService::class);
        $this->validatorMock = $this->createMock(ValidatorInterface::class);
        $this->validatorMock->method('validate')->willReturn(new ConstraintViolationList([]));
        $this->controller = new CitizenController($this->citizenServiceMock, $this->validatorMock);
    }

    private function setAuthenticatedUser(string $userId = 'user-123', array $roles = ['ROLE_CITIZEN']): void
    {
        $user = new class($userId, $roles) implements UserInterface {
            private string $id;
            private array $roles;
            public function __construct(string $id, array $roles) { $this->id = $id; $this->roles = $roles; }
            public function getUserIdentifier(): string { return $this->id; }
            public function getRoles(): array { return $this->roles; }
            public function getPassword(): ?string { return null; }
            public function getSalt(): ?string { return null; }
            public function eraseCredentials(): void {}
        };
        $this->controller->setTestUser($user);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    // ==================== Authentication & Authorization ====================

    public function testUnauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->index($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }

    // ==================== Validation helpers ====================

    public function testValidateCitizenData_MissingFullName_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Full name is required');
        $this->invokePrivate($this->controller, 'validateCitizenData', [[]]);
    }

    public function testValidateUuid_Invalid_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid field ID format');
        $this->invokePrivate($this->controller, 'validateUuid', ['not-a-uuid', 'field ID']);
    }

    public function testValidateUuid_Valid_ReturnsUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $result = $this->invokePrivate($this->controller, 'validateUuid', [$uuid, 'field ID']);
        $this->assertSame($uuid, $result);
    }

    public function testValidatePositiveInt_NullThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('field must be at least 1');
        $this->invokePrivate($this->controller, 'validatePositiveInt', [null, 'field']);
    }

    public function testValidatePositiveInt_BelowMin_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('field must be at least 1');
        $this->invokePrivate($this->controller, 'validatePositiveInt', [0, 'field', 1, null]);
    }

    public function testValidatePositiveInt_AboveMax_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('field must not exceed 10');
        $this->invokePrivate($this->controller, 'validatePositiveInt', [11, 'field', 1, 10]);
    }

    public function testValidatePositiveInt_Valid(): void
    {
        $result = $this->invokePrivate($this->controller, 'validatePositiveInt', [5, 'field', 1, 10]);
        $this->assertSame(5, $result);
    }

    public function testValidateCategories_Empty_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one category is required');
        $this->invokePrivate($this->controller, 'validateCategories', [[]]);
    }

    // ==================== Index (GET) ====================

    public function testIndex_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->index($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testIndex_Forbidden_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_CITIZEN']);
        $this->citizenServiceMock->expects($this->never())->method('searchCitizens');
        $request = new Request();
        $response = $this->controller->index($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame('Forbidden', $content['error']);
    }

    public function testIndex_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $citizens = [$this->createMock(CitizenResponse::class)];
        $this->citizenServiceMock->expects($this->once())
            ->method('searchCitizens')
            ->with('', [], 50, 0)
            ->willReturn($citizens);
        $request = new Request();
        $response = $this->controller->index($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
    }

    // ==================== Create (POST) ====================

    public function testCreate_Unauthenticated_Returns401(): void
    {
        $payload = json_encode([
            'identityNumber' => '123456789012',
            'fullName' => 'John Doe',
        ]);
        $request = Request::create('', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->create($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testCreate_Forbidden_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_USER']);
        $payload = json_encode([
            'identityNumber' => '123456789012',
            'full_name' => 'John Doe',
        ]);
        $request = Request::create('', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreate_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');
        $response = $this->controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreate_MissingFullName_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $payload = json_encode([
            'identityNumber' => '123456789012',
            // missing fullName (validateCitizenData checks)
        ]);
        $request = Request::create('', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Full name is required', $content['error']);
    }



    public function testCreate_Success_Returns201(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $citizenResponse = $this->createMock(CitizenResponse::class);
        $citizenResponse->method('toArray')->willReturn(['id' => 'c1', 'fullName' => 'John Doe']);
        $this->citizenServiceMock->expects($this->once())
            ->method('createCitizen')
            ->with($this->callback(function ($dto) {
                return $dto instanceof \App\Citizen\Application\DTO\CreateCitizenRequest
                    && $dto->identityNumber === '123456789012'
                    && $dto->fullName === 'John Doe'
                    && $dto->userId === 'user-1';
            }))
            ->willReturn($citizenResponse);
        $payload = json_encode([
            'identityNumber' => '123456789012',
            'full_name' => 'John Doe',
        ]);
        $request = Request::create('', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertSame('Citizen created successfully', $content['message']);
    }

    // ==================== Show (GET /{id}) ====================

    public function testShow_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->show('any-id', $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testShow_InvalidUuid_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $request = new Request();
        $response = $this->controller->show('not-a-uuid', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testShow_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $this->citizenServiceMock->expects($this->once())
            ->method('getCitizen')
            ->with($this->anything(), $this->anything())
            ->willReturn(null);
        $request = new Request();
        $response = $this->controller->show(self::VALID_UUID, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testShow_Forbidden_CitizenAccessOther_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_CITIZEN']);
        $citizen = $this->createMock(CitizenResponse::class);
        // Set readonly userId via reflection
        $ref = new \ReflectionProperty($citizen, 'userId');
        $ref->setAccessible(true);
        $ref->setValue($citizen, 'user-2');
        $this->citizenServiceMock->method('getCitizen')->willReturn($citizen);
        $request = new Request();
        $response = $this->controller->show(self::VALID_UUID, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testShow_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $citizen = $this->createMock(CitizenResponse::class);
        $citizen->method('toArray')->willReturn(['id' => self::VALID_UUID]);
        $this->citizenServiceMock->expects($this->once())
            ->method('getCitizen')
            ->with($this->anything(), $this->anything())
            ->willReturn($citizen);
        $request = new Request();
        $response = $this->controller->show(self::VALID_UUID, $request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
    }

    // ==================== Update (PUT /{id}) ====================

    public function testUpdate_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->update('c1', $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUpdate_InvalidUuid_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $request = new Request();
        $response = $this->controller->update('invalid', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdate_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $this->citizenServiceMock->expects($this->once())
            ->method('getCitizen')
            ->with($this->anything(), $this->anything())
            ->willReturn(null);
        $request = new Request();
        $response = $this->controller->update(self::VALID_UUID, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdate_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $existing = $this->createMock(CitizenResponse::class);
        $updated = $this->createMock(CitizenResponse::class);
        $updated->method('toArray')->willReturn(['id' => self::VALID_UUID, 'fullName' => 'Updated']);
        $this->citizenServiceMock->method('getCitizen')->willReturn($existing);
        $this->citizenServiceMock->expects($this->once())
            ->method('updateCitizen')
            ->with(self::VALID_UUID, $this->anything(), $this->anything())
            ->willReturn($updated);
        $request = Request::create('', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['fullName' => 'Updated']));
        $response = $this->controller->update(self::VALID_UUID, $request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
    }

    // ==================== Delete (DELETE /{id}) ====================

    public function testDelete_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->delete('c1', $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testDelete_InvalidUuid_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $request = new Request();
        $response = $this->controller->delete('invalid', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testDelete_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $this->citizenServiceMock->expects($this->once())
            ->method('getCitizen')
            ->with($this->anything(), $this->anything())
            ->willReturn(null);
        $request = new Request();
        $response = $this->controller->delete(self::VALID_UUID, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDelete_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $citizen = $this->createMock(CitizenResponse::class);
        $this->citizenServiceMock->method('getCitizen')->willReturn($citizen);
        $this->citizenServiceMock->expects($this->once())
            ->method('deleteCitizen')
            ->with(self::VALID_UUID, $this->anything())
            ->willReturn(true);
        $request = new Request();
        $response = $this->controller->delete(self::VALID_UUID, $request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $content);
    }

    // ==================== Statistics (GET /statistics) ====================

    public function testStatistics_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->statistics($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testStatistics_Forbidden_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_CITIZEN']);
        $request = new Request();
        $response = $this->controller->statistics($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testStatistics_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $this->citizenServiceMock->expects($this->once())->method('getCitizenStatistics')->willReturn(['total' => 100]);
        $request = new Request();
        $response = $this->controller->statistics($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertSame(['total' => 100], $content['data']);
    }

    // ==================== GetByHousehold (GET /household/{householdId}) ====================

    public function testGetByHousehold_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->getByHousehold('hh1', $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetByHousehold_Forbidden_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_CITIZEN']);
        $request = new Request();
        $response = $this->controller->getByHousehold('hh1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetByHousehold_InvalidLimit_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $request = Request::create('', 'GET', ['limit' => '-1']);
        $response = $this->controller->getByHousehold('hh1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetByHousehold_InvalidOffset_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $request = Request::create('', 'GET', ['offset' => '-1']);
        $response = $this->controller->getByHousehold('hh1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetByHousehold_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $citizen = $this->createMock(CitizenResponse::class);
        $citizen->method('toArray')->willReturn(['id' => 'c1']);
        $this->citizenServiceMock->expects($this->once())
            ->method('getCitizensByHousehold')
            ->with('hh1', 50, 0)
            ->willReturn([$citizen]);
        $request = new Request();
        $response = $this->controller->getByHousehold('hh1', $request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('meta', $content);
        $this->assertSame('hh1', $content['meta']['householdId']);
    }

    // ==================== GetElderly (GET /elderly) ====================

    public function testGetElderly_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->getElderly($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetElderly_Forbidden_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_CITIZEN']);
        $request = new Request();
        $response = $this->controller->getElderly($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetElderly_InvalidMinAge_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $request = Request::create('', 'GET', ['minAge' => '-5']);
        $response = $this->controller->getElderly($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetElderly_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $citizen = $this->createMock(CitizenResponse::class);
        $citizen->method('toArray')->willReturn(['id' => 'c1']);
        $this->citizenServiceMock->expects($this->once())
            ->method('getElderlyCitizens')
            ->with(60, 50, 0)
            ->willReturn([$citizen]);
        $request = new Request();
        $response = $this->controller->getElderly($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('meta', $content);
        $this->assertSame(60, $content['meta']['minAge']);
    }

    // ==================== GetChildren (GET /children) ====================

    public function testGetChildren_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->getChildren($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetChildren_Forbidden_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_CITIZEN']);
        $request = new Request();
        $response = $this->controller->getChildren($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetChildren_InvalidMaxAge_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $request = Request::create('', 'GET', ['maxAge' => '-1']);
        $response = $this->controller->getChildren($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetChildren_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $citizen = $this->createMock(CitizenResponse::class);
        $citizen->method('toArray')->willReturn(['id' => 'c1']);
        $this->citizenServiceMock->expects($this->once())
            ->method('getChildCitizens')
            ->with(16, 50, 0)
            ->willReturn([$citizen]);
        $request = new Request();
        $response = $this->controller->getChildren($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('meta', $content);
        $this->assertSame(16, $content['meta']['maxAge']);
    }

    // ==================== GetBySpecialCategories (GET /categories) ====================

    public function testGetBySpecialCategories_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->getBySpecialCategories($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetBySpecialCategories_Forbidden_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_CITIZEN']);
        $request = new Request();
        $response = $this->controller->getBySpecialCategories($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetBySpecialCategories_MissingCategories_Returns400(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $request = new Request(); // no categories query
        $response = $this->controller->getBySpecialCategories($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetBySpecialCategories_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user-1', ['ROLE_OFFICER']);
        $citizen = $this->createMock(CitizenResponse::class);
        $citizen->method('toArray')->willReturn(['id' => 'c1']);
        $this->citizenServiceMock->expects($this->once())
            ->method('getCitizensBySpecialCategories')
            ->with(['disabled'])
            ->willReturn([$citizen]);
        $request = Request::create('', 'GET', ['categories' => ['disabled']]);
        $response = $this->controller->getBySpecialCategories($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('meta', $content);
    }
}
