<?php declare(strict_types=1);

namespace Tests\Unit\SecurityEmergency\Controller;

use App\SecurityEmergency\Controller\SecurityController;
use App\SecurityEmergency\Service\SecurityEmergencyService;
use App\SecurityEmergency\Entity\SecurityIncident;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class SecurityControllerTest extends TestCase
{
    private SecurityEmergencyService $serviceMock;
    private SecurityController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(SecurityEmergencyService::class);
        $this->controller = new SecurityController($this->serviceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function setAuthenticatedUser(string $userId = 'user1'): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn($userId);
        $user->method('getId')->willReturn($userId);
        $this->controller->setTestUser($user);
    }

    private function createIncidentMock(array $data): SecurityIncident
    {
        $incident = $this->createMock(SecurityIncident::class);
        $incident->method('getId')->willReturn($data['id'] ?? '123');
        $incident->method('getType')->willReturn($data['type']);
        $incident->method('getStatus')->willReturn($data['status']);
        $incident->method('getLocation')->willReturn($data['location'] ?? null);
        $incident->method('getDescription')->willReturn($data['description'] ?? null);
        $incident->method('getReporterId')->willReturn($data['reporterId']);
        $incident->method('toArray')->willReturn($data);
        return $incident;
    }

    // ==================== listIncidents ====================

    public function testListIncidents_Success(): void
    {
        $incident1 = $this->createIncidentMock(['id' => '1', 'type' => 'Trộm cắp', 'status' => 'Đang điều tra', 'reporterId' => 'user1']);
        $incident2 = $this->createIncidentMock(['id' => '2', 'type' => 'Tai nạn giao thông', 'status' => 'Đã xử lý', 'reporterId' => 'user2']);
        $this->serviceMock->expects($this->once())
            ->method('getAllIncidents')
            ->with(50, 0)
            ->willReturn([$incident1, $incident2]);

        $request = Request::create('/api/security/incidents', 'GET');
        $response = $this->controller->listIncidents($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data['items']);
        $this->assertSame(2, $data['total']);
    }

    public function testListIncidents_InvalidStatus_Returns400(): void
    {
        $request = Request::create('/api/security/incidents', 'GET', ['status' => 'invalid']);
        $response = $this->controller->listIncidents($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid status filter', $response->getContent());
    }

    // ==================== getIncident ====================

    public function testGetIncident_NotFound_Returns404(): void
    {
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('123')
            ->willReturn(null);

        $request = Request::create('/api/security/incidents/123', 'GET');
        $response = $this->controller->getIncident('123');
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Incident not found'], json_decode($response->getContent(), true));
    }

    public function testGetIncident_Success(): void
    {
        $incident = $this->createIncidentMock(['id' => '123', 'type' => 'Trộm cắp', 'status' => 'Đang điều tra', 'reporterId' => 'user1']);
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('123')
            ->willReturn($incident);

        $request = Request::create('/api/security/incidents/123', 'GET');
        $response = $this->controller->getIncident('123');
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('123', $data['id']);
    }

    // ==================== createIncident ====================

    public function testCreateIncident_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/security/incidents', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'type' => 'Trộm cắp',
            'location' => 'Hanoi',
        ]));
        $response = $this->controller->createIncident($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testCreateIncident_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $request = Request::create('/api/security/incidents', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');
        $response = $this->controller->createIncident($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateIncident_ValidationErrors_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $request = Request::create('/api/security/incidents', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'type' => 'InvalidType',
            'status' => 'InvalidStatus',
        ]));
        $response = $this->controller->createIncident($request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testCreateIncident_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $incident = $this->createIncidentMock(['id' => '123', 'type' => 'Trộm cắp', 'status' => 'Đang điều tra', 'reporterId' => 'user1']);
        $this->serviceMock->expects($this->once())
            ->method('createIncident')
            ->with('Trộm cắp', 'user1', 'Hanoi', 'Desc', 'Đang điều tra')
            ->willReturn($incident);

        $request = Request::create('/api/security/incidents', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'type' => 'Trộm cắp',
            'location' => 'Hanoi',
            'description' => 'Desc',
            'status' => 'Đang điều tra',
        ]));
        $response = $this->controller->createIncident($request);
        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('123', $data['id']);
    }

    // ==================== updateIncidentStatus ====================

    public function testUpdateIncidentStatus_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('123')
            ->willReturn(null);

        $request = Request::create('/api/security/incidents/123/status', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['status' => 'Đã xử lý']));
        $response = $this->controller->updateIncidentStatus('123', $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateIncidentStatus_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $incident = $this->createIncidentMock(['id' => '123', 'type' => 'Trộm cắp', 'status' => 'Đã xử lý', 'reporterId' => 'user1']);
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('123')
            ->willReturn($incident);
        $this->serviceMock->expects($this->once())
            ->method('updateIncidentStatus')
            ->with('123', 'Đã xử lý')
            ->willReturn($incident);

        $request = Request::create('/api/security/incidents/123/status', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['status' => 'Đã xử lý']));
        $response = $this->controller->updateIncidentStatus('123', $request);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Đã xử lý', $data['status']);
    }

    // ==================== deleteIncident ====================

    public function testDeleteIncident_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $incident = $this->createIncidentMock(['id' => '123', 'type' => 'Trộm cắp', 'status' => 'Đang điều tra', 'reporterId' => 'user1']);
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('123')
            ->willReturn($incident);
        $this->serviceMock->expects($this->once())
            ->method('deleteIncident')
            ->with('123')
            ->willReturn(true);

        $request = Request::create('/api/security/incidents/123', 'DELETE');
        $response = $this->controller->deleteIncident('123', $request);
        $this->assertSame(204, $response->getStatusCode());
    }

    // ==================== incidentStatistics ====================

    public function testIncidentStatistics_Returns200(): void
    {
        $incident1 = $this->createIncidentMock(['id' => '1', 'type' => 'Trộm cắp', 'status' => 'Đang điều tra', 'reporterId' => 'user1']);
        $incident2 = $this->createIncidentMock(['id' => '2', 'type' => 'Tai nạn giao thông', 'status' => 'Đã xử lý', 'reporterId' => 'user2']);
        $this->serviceMock->expects($this->once())
            ->method('getAllIncidents')
            ->with(1000)
            ->willReturn([$incident1, $incident2]);

        $response = $this->controller->incidentStatistics();
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(2, $data['total']);
        $this->assertSame(1, $data['resolved']);
        $this->assertSame(1, $data['pending']);
        $this->assertSame(2, $data['patrolTeams']);
    }

    // ==================== listPatrols ====================

    public function testListPatrols_Returns200(): void
    {
        $response = $this->controller->listPatrols();
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(['items' => [], 'total' => 0], $data);
    }

    // ==================== emergencyContacts ====================

    public function testEmergencyContacts_Returns200(): void
    {
        $response = $this->controller->emergencyContacts();
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(4, $data);
        $this->assertSame('113', $data[0]['phone']);
    }
}
