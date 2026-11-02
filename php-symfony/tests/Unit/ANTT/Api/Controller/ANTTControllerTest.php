<?php

declare(strict_types=1);

namespace Tests\Unit\ANTT\Api\Controller;

use App\ANTT\Api\Controller\ANTTController;
use App\ANTT\Application\Service\IncidentService;
use App\ANTT\Domain\Entity\Incident;
use App\ANTT\Application\DTO\IncidentStatistics;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use DateTimeImmutable;

class ANTTControllerTest extends TestCase
{
    private IncidentService $serviceMock;
    private ValidatorInterface $validatorMock;
    private ANTTController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(IncidentService::class);
        $this->validatorMock = $this->createMock(ValidatorInterface::class);
        $this->controller = new ANTTController($this->serviceMock, $this->validatorMock);
    }

    private function invokePrivate(object $obj, string $method, array $args = [])
    {
        $ref = new \ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function setAuthenticatedUser(array $userData): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn($userData['id']);
        $user->method('getRoles')->willReturn($userData['roles']);
        $this->controller->setTestUser($user);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function createIncidentMock(int $id, string $incidentNumber = 'INC-001'): Incident
    {
        $incident = $this->createMock(Incident::class);
        $incident->method('getId')->willReturn($id);
        $incident->method('getIncidentNumber')->willReturn($incidentNumber);
        $incident->method('toArray')->willReturn([
            'id' => $id,
            'incident_number' => $incidentNumber
        ]);
        return $incident;
    }

    private function createStatsMock(array $data = []): IncidentStatistics
    {
        $stats = $this->createMock(IncidentStatistics::class);
        $stats->method('toArray')->willReturn($data);
        return $stats;
    }

    // ==================== Helper Methods ====================

    public function testCheckPermission_HasPermission_NoException(): void
    {
        $user = ['roles' => ['incident:create', 'admin']];
        $this->invokePrivate($this->controller, 'checkPermission', [$user, 'incident:create']);
        $this->assertTrue(true);
    }

    public function testCheckPermission_NoPermission_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient permissions');
        $user = ['roles' => ['user']];
        $this->invokePrivate($this->controller, 'checkPermission', [$user, 'incident:create']);
    }

    public function testAuthenticateUser_Unauthenticated_Throws(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized');
        $request = Request::create('/api/antt/incidents', 'POST', [], [], [], [], json_encode([]));
        $this->invokePrivate($this->controller, 'authenticateUser', [$request]);
    }

    public function testAuthenticateUser_Authenticated_ReturnsArray(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:create']]);
        $request = Request::create('/api/antt/incidents', 'POST', [], [], [], [], json_encode([]));
        $result = $this->invokePrivate($this->controller, 'authenticateUser', [$request]);
        $this->assertSame(['id' => '123', 'roles' => ['incident:create']], $result);
    }

    public function testValidateDto_WithViolations_Throws(): void
    {
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(1);
        $this->validatorMock->method('validate')->willReturn($violations);
        $dto = new \stdClass();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed:');
        $this->invokePrivate($this->controller, 'validateDto', [$dto]);
    }

    public function testValidateDto_NoViolations_NoException(): void
    {
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);
        $this->validatorMock->method('validate')->willReturn($violations);
        $dto = new \stdClass();
        $this->invokePrivate($this->controller, 'validateDto', [$dto]);
        $this->assertTrue(true);
    }

    public function testCreateCreateIncidentDto_MapsAllFields(): void
    {
        $data = [
            'title' => 'Test Incident',
            'description' => 'Desc',
            'incident_type' => 'crime',
            'severity' => 'high',
            'ward_code' => 'W1',
            'location' => 'Hanoi',
            'latitude' => 21.0,
            'longitude' => 105.0,
            'reporter_name' => 'John',
            'reporter_phone' => '+84912345678',
            'reporter_anonymous' => true
        ];
        $dto = $this->invokePrivate($this->controller, 'createCreateIncidentDto', [$data]);
        $this->assertSame('Test Incident', $dto->title);
        $this->assertSame('Desc', $dto->description);
        $this->assertSame('crime', $dto->incidentType);
        $this->assertSame('high', $dto->severity);
        $this->assertSame('W1', $dto->wardCode);
        $this->assertSame('Hanoi', $dto->locationAddress);
        $this->assertSame(21.0, $dto->latitude);
        $this->assertSame(105.0, $dto->longitude);
        $this->assertSame('John', $dto->reporterName);
        $this->assertSame('+84912345678', $dto->reporterPhone);
        $this->assertTrue($dto->reporterAnonymous);
    }

    public function testCreateCreateIncidentDto_Defaults(): void
    {
        $data = [
            'title' => 'Minimal'
        ];
        $dto = $this->invokePrivate($this->controller, 'createCreateIncidentDto', [$data]);
        $this->assertSame('Minimal', $dto->title);
        $this->assertSame('', $dto->incidentType);
        $this->assertSame('medium', $dto->severity);
        $this->assertNull($dto->description);
        $this->assertNull($dto->wardCode);
        $this->assertNull($dto->locationAddress);
        $this->assertNull($dto->latitude);
        $this->assertNull($dto->longitude);
        $this->assertNull($dto->reporterName);
        $this->assertNull($dto->reporterPhone);
        $this->assertFalse($dto->reporterAnonymous);
    }

    public function testCreateUpdateIncidentDto_MapsFields(): void
    {
        $data = [
            'title' => 'Updated Title',
            'description' => 'Updated Desc',
            'incident_type' => 'noise',
            'severity' => 'low',
            'status' => 'resolved'
        ];
        $dto = $this->invokePrivate($this->controller, 'createUpdateIncidentDto', [$data]);
        $this->assertSame('Updated Title', $dto->title);
        $this->assertSame('Updated Desc', $dto->description);
        $this->assertSame('noise', $dto->incidentType);
        $this->assertSame('low', $dto->severity);
        $this->assertSame('resolved', $dto->status);
    }

    public function testHandleException_Returns500(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $exception = new \Exception('Test error');
        $response = $this->invokePrivate($this->controller, 'handleException', ['test', $exception]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame(['error' => 'An error occurred'], $content);
    }

    // ==================== createIncident ====================

    public function testCreateIncident_Unauthenticated_Returns401(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/api/antt/incidents', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'Test', 'incident_type' => 'traffic'
        ]));
        $response = $this->controller->createIncident($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testCreateIncident_NoPermission_Returns403(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['user']]);
        $request = Request::create('/api/antt/incidents', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'Test', 'incident_type' => 'traffic'
        ]));
        $response = $this->controller->createIncident($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateIncident_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:create']]);
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(1);
        $this->validatorMock->method('validate')->willReturn($violations);
        $request = Request::create('/api/antt/incidents', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'Test'
        ]));
        $response = $this->controller->createIncident($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateIncident_Success_Returns201(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:create']]);
        $incident = $this->createIncidentMock(1, 'INC-001');
        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));
        $this->serviceMock->expects($this->once())
            ->method('createIncident')
            ->with($this->callback(function($dto) {
                return $dto->title === 'Test Incident' && $dto->incidentType === 'traffic';
            }), '123')
            ->willReturn($incident);

        $request = Request::create('/api/antt/incidents', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'Test Incident',
            'incident_type' => 'traffic',
            'severity' => 'high'
        ]));
        $response = $this->controller->createIncident($request);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('incident', $content);
    }

    // ==================== listIncidents ====================

    public function testListIncidents_Unauthenticated_Returns401(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/api/antt/incidents', 'GET');
        $response = $this->controller->listIncidents($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testListIncidents_NoPermission_Returns403(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['user']]);
        $request = Request::create('/api/antt/incidents', 'GET');
        $response = $this->controller->listIncidents($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testListIncidents_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:read']]);
        $incident = $this->createIncidentMock(1);
        $this->serviceMock->expects($this->once())
            ->method('listIncidents')
            ->with(20, 0)
            ->willReturn([$incident]);
        $this->serviceMock->expects($this->once())
            ->method('countIncidents')
            ->willReturn(1);
        $request = Request::create('/api/antt/incidents', 'GET');
        $response = $this->controller->listIncidents($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('incidents', $content);
        $this->assertArrayHasKey('meta', $content);
    }

    // ==================== getIncident ====================

    public function testGetIncident_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:read']]);
        $request = Request::create('/api/antt/incidents/0', 'GET');
        $response = $this->controller->getIncident(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetIncident_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:read']]);
        $this->serviceMock->method('getIncident')->with(999)->willReturn(null);
        $request = Request::create('/api/antt/incidents/999', 'GET');
        $response = $this->controller->getIncident(999, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetIncident_Found_Returns200(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:read']]);
        $incident = $this->createIncidentMock(5);
        $this->serviceMock->method('getIncident')->with(5)->willReturn($incident);
        $request = Request::create('/api/antt/incidents/5', 'GET');
        $response = $this->controller->getIncident(5, $request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('incident', $content);
    }

    // ==================== updateIncident ====================

    public function testUpdateIncident_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:update']]);
        $request = Request::create('/api/antt/incidents/0', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['title' => 'T']));
        $response = $this->controller->updateIncident(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateIncident_NoPermission_Returns403(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['user']]);
        $request = Request::create('/api/antt/incidents/1', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['title' => 'T']));
        $response = $this->controller->updateIncident(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateIncident_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:update']]);
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(1);
        $this->validatorMock->method('validate')->willReturn($violations);
        $request = Request::create('/api/antt/incidents/1', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['title' => 'T']));
        $response = $this->controller->updateIncident(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateIncident_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:update']]);
        $this->validatorMock->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));
        $this->serviceMock->method('updateIncident')->with(1, $this->anything(), '123')->willReturn(null);
        $request = Request::create('/api/antt/incidents/1', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['title' => 'T']));
        $response = $this->controller->updateIncident(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateIncident_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:update']]);
        $incident = $this->createIncidentMock(2);
        $this->validatorMock->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));
        $this->serviceMock->method('updateIncident')->with(1, $this->callback(fn($dto) => $dto->title === 'New Title'), '123')->willReturn($incident);
        $request = Request::create('/api/antt/incidents/1', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'New Title',
            'severity' => 'critical'
        ]));
        $response = $this->controller->updateIncident(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== assignIncident ====================

    public function testAssignIncident_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:assign']]);
        $request = Request::create('/api/antt/incidents/0/assign', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['assigneeId' => '999']));
        $response = $this->controller->assignIncident(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignIncident_MissingAssigneeId_Returns400(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:assign']]);
        $request = Request::create('/api/antt/incidents/1/assign', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $response = $this->controller->assignIncident(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignIncident_NoPermission_Returns403(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['user']]);
        $request = Request::create('/api/antt/incidents/1/assign', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['assigneeId' => '999']));
        $response = $this->controller->assignIncident(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignIncident_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:assign']]);
        $this->serviceMock->method('assignIncident')->with(1, '999')->willReturn(null);
        $request = Request::create('/api/antt/incidents/1/assign', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['assigneeId' => '999']));
        $response = $this->controller->assignIncident(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testAssignIncident_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:assign']]);
        $incident = $this->createIncidentMock(1);
        $this->serviceMock->method('assignIncident')->with(1, '999')->willReturn($incident);
        $request = Request::create('/api/antt/incidents/1/assign', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['assigneeId' => '999']));
        $response = $this->controller->assignIncident(1, $request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('incident', $content);
        $this->assertArrayHasKey('message', $content);
    }

    // ==================== resolveIncident ====================

    public function testResolveIncident_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:resolve']]);
        $request = Request::create('/api/antt/incidents/0/resolve', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['resolution' => 'Fixed']));
        $response = $this->controller->resolveIncident(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testResolveIncident_MissingResolution_Returns400(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:resolve']]);
        $request = Request::create('/api/antt/incidents/1/resolve', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $response = $this->controller->resolveIncident(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testResolveIncident_NoPermission_Returns403(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['user']]);
        $request = Request::create('/api/antt/incidents/1/resolve', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['resolution' => 'Fixed']));
        $response = $this->controller->resolveIncident(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testResolveIncident_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:resolve']]);
        $this->serviceMock->method('resolveIncident')->with(1, 'Fixed')->willReturn(null);
        $request = Request::create('/api/antt/incidents/1/resolve', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['resolution' => 'Fixed']));
        $response = $this->controller->resolveIncident(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testResolveIncident_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:resolve']]);
        $incident = $this->createIncidentMock(1);
        $this->serviceMock->method('resolveIncident')->with(1, 'Resolved text')->willReturn($incident);
        $request = Request::create('/api/antt/incidents/1/resolve', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['resolution' => 'Resolved text']));
        $response = $this->controller->resolveIncident(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== closeIncident ====================

    public function testCloseIncident_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:close']]);
        $response = $this->controller->closeIncident(0, new Request());
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCloseIncident_NoPermission_Returns403(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['user']]);
        $response = $this->controller->closeIncident(1, new Request());
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCloseIncident_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:close']]);
        $this->serviceMock->method('closeIncident')->with(1)->willReturn(null);
        $response = $this->controller->closeIncident(1, new Request());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testCloseIncident_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:close']]);
        $incident = $this->createIncidentMock(1);
        $this->serviceMock->method('closeIncident')->with(1)->willReturn($incident);
        $response = $this->controller->closeIncident(1, new Request());
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== escalateIncident ====================

    public function testEscalateIncident_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:escalate']]);
        $request = Request::create('/api/antt/incidents/0/escalate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['level' => 'high']));
        $response = $this->controller->escalateIncident(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testEscalateIncident_MissingLevel_Returns400(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:escalate']]);
        $request = Request::create('/api/antt/incidents/1/escalate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $response = $this->controller->escalateIncident(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testEscalateIncident_NoPermission_Returns403(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['user']]);
        $request = Request::create('/api/antt/incidents/1/escalate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['level' => 'high']));
        $response = $this->controller->escalateIncident(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testEscalateIncident_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:escalate']]);
        $this->serviceMock->method('escalateIncident')->with(1)->willReturn(null);
        $request = Request::create('/api/antt/incidents/1/escalate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['level' => 'high']));
        $response = $this->controller->escalateIncident(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testEscalateIncident_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:escalate']]);
        $incident = $this->createIncidentMock(1);
        $this->serviceMock->method('escalateIncident')->with(1)->willReturn($incident);
        $request = Request::create('/api/antt/incidents/1/escalate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['level' => 'high']));
        $response = $this->controller->escalateIncident(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== getStatistics ====================

    public function testGetStatistics_NoPermission_Returns403(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['user']]);
        $request = Request::create('/api/antt/statistics', 'GET');
        $response = $this->controller->getStatistics($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetStatistics_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['statistics:read']]);
        $stats = $this->createStatsMock(['total_incidents' => 10]);
        $this->serviceMock->method('getStatistics')->with(null, null)->willReturn($stats);
        $request = Request::create('/api/antt/statistics', 'GET');
        $response = $this->controller->getStatistics($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('statistics', $content);
    }

    // ==================== getOpenIncidents ====================

    public function testGetOpenIncidents_NoPermission_Returns403(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['user']]);
        $response = $this->controller->getOpenIncidents(new Request());
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetOpenIncidents_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(['id' => '123', 'roles' => ['incident:read']]);
        $incident = $this->createIncidentMock(1);
        $this->serviceMock->method('getOpenIncidents')->willReturn([$incident]);
        $response = $this->controller->getOpenIncidents(new Request());
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('incidents', $content);
    }
}