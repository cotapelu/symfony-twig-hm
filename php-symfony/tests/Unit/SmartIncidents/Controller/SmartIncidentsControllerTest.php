<?php

declare(strict_types=1);

namespace Tests\Unit\SmartIncidents\Controller;

use App\SmartIncidents\Controller\SmartIncidentsController;
use App\SmartIncidents\Domain\Service\SmartIncidentsService;
use App\SmartIncidents\Domain\Entity\SmartIncident;
use App\SmartIncidents\Domain\Entity\SmartIncidentMessage;
use App\SmartIncidents\Domain\Entity\SmartIncidentAttachment;
use App\SmartIncidents\Domain\Entity\SmartIncidentActivity;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SmartIncidentsControllerTest extends TestCase
{
    private SmartIncidentsService $serviceMock;
    private SmartIncidentsController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(SmartIncidentsService::class);
        $this->controller = new SmartIncidentsController($this->serviceMock, null, null);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $ref = new ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function setAuthenticatedUser(bool $isAdmin = false, bool $isOfficer = false): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('user123');
        $roles = [];
        if ($isAdmin) $roles[] = 'ROLE_ADMIN';
        if ($isOfficer) $roles[] = 'ROLE_OFFICER';
        if (!$isAdmin && !$isOfficer) $roles[] = 'ROLE_USER';
        $user->method('getRoles')->willReturn($roles);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturnCallback(function ($role) use ($isAdmin, $isOfficer) {
            if ($role === 'IS_AUTHENTICATED_REMEMBERED') return true;
            if ($role === 'ROLE_ADMIN') return $isAdmin;
            if ($role === 'ROLE_OFFICER') return $isOfficer || $isAdmin;
            return false;
        });

        $ref = new \ReflectionProperty($this->controller, 'tokenStorage');
        $ref->setAccessible(true);
        $ref->setValue($this->controller, $tokenStorage);
        $refAuth = new \ReflectionProperty($this->controller, 'authChecker');
        $refAuth->setAccessible(true);
        $refAuth->setValue($this->controller, $authChecker);
    }

    private function createIncidentMock(array $data = []): SmartIncident
    {
        $incident = $this->createMock(SmartIncident::class);
        $incident->method('toArray')->willReturn($data + ['incident_number' => $data['incident_number'] ?? 'INC-001']);
        $incident->method('getUpvoteCount')->willReturn($data['upvote_count'] ?? 0);
        return $incident;
    }

    private function createMessageMock(array $data = []): SmartIncidentMessage
    {
        $msg = $this->createMock(SmartIncidentMessage::class);
        $msg->method('toArray')->willReturn($data + ['id' => $data['id'] ?? 1]);
        return $msg;
    }

    private function createAttachmentMock(array $data = []): SmartIncidentAttachment
    {
        $att = $this->createMock(SmartIncidentAttachment::class);
        $att->method('toArray')->willReturn($data + ['id' => $data['id'] ?? 1]);
        return $att;
    }

    private function createActivityMock(array $data = []): SmartIncidentActivity
    {
        $act = $this->createMock(SmartIncidentActivity::class);
        $act->method('toArray')->willReturn($data + ['id' => $data['id'] ?? 1]);
        return $act;
    }

    private function createJsonRequest(array $data, string $method = 'POST'): Request
    {
        $content = json_encode($data);
        $server = ['CONTENT_TYPE' => 'application/json'];
        return Request::create('/api/smart-incidents', $method, [], [], [], $server, $content);
    }

    private function createQueryRequest(array $query = []): Request
    {
        return Request::create('/api/smart-incidents', 'GET', $query);
    }

    // ==================== Validation Tests ====================

    public function testValidateTitle_Missing_ReturnsError(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateTitle', [['other' => 'data']]);
        $this->assertSame('Title is required', $error);
    }

    public function testValidateTitle_TooLong_ReturnsError(): void
    {
        $long = str_repeat('a', 256);
        $error = $this->invokePrivate($this->controller, 'validateTitle', [['title' => $long]]);
        $this->assertStringContainsString('Title too long', $error);
    }

    public function testValidateTitle_Valid_ReturnsNull(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateTitle', [['title' => 'Short']]);
        $this->assertNull($error);
    }

    public function testValidateDescription_TooLong_ReturnsError(): void
    {
        $long = str_repeat('a', 5001);
        $error = $this->invokePrivate($this->controller, 'validateDescription', [['description' => $long]]);
        $this->assertStringContainsString('Description too long', $error);
    }

    public function testValidateDescription_Valid_ReturnsNull(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateDescription', [['description' => 'OK']]);
        $this->assertNull($error);
    }

    public function testValidateIncidentType_Missing_ReturnsError(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateIncidentType', [[]]);
        $this->assertSame('Incident type is required', $error);
    }

    public function testValidateIncidentType_Invalid_ReturnsError(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateIncidentType', [['incident_type' => 'invalid']]);
        $this->assertStringContainsString('Invalid incident type', $error);
    }

    public function testValidateIncidentType_Valid_ReturnsNull(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateIncidentType', [['incident_type' => 'safety']]);
        $this->assertNull($error);
    }

    public function testValidateSeverity_Invalid_ReturnsError(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateSeverity', [['severity' => 'extreme']]);
        $this->assertStringContainsString('Invalid severity', $error);
    }

    public function testValidateSeverity_Valid_ReturnsNull(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateSeverity', [['severity' => 'high']]);
        $this->assertNull($error);
    }

    public function testValidateLocation_LatitudeOutOfRange_ReturnsError(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateLocation', [['latitude' => 100]]);
        $this->assertStringContainsString('Latitude must be between -90 and 90', $error);
    }

    public function testValidateLocation_LongitudeOutOfRange_ReturnsError(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateLocation', [['longitude' => 200]]);
        $this->assertStringContainsString('Longitude must be between -180 and 180', $error);
    }

    public function testValidateLocation_Valid_ReturnsNull(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateLocation', [['latitude' => 10, 'longitude' => 20]]);
        $this->assertNull($error);
    }

    public function testValidateMessageInput_MissingFields_ReturnsError(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateMessageInput', [['sender_type' => 'citizen']]);
        $this->assertStringContainsString('Sender ID is required', $error);
    }

    public function testValidateMessageInput_ContentTooLong_ReturnsError(): void
    {
        $data = [
            'sender_type' => 'citizen',
            'sender_id' => '1',
            'sender_name' => 'Test',
            'content' => str_repeat('a', 2001)
        ];
        $error = $this->invokePrivate($this->controller, 'validateMessageInput', [$data]);
        $this->assertStringContainsString('Message too long', $error);
    }

    public function testValidateMessageInput_Valid_ReturnsNull(): void
    {
        $data = [
            'sender_type' => 'citizen',
            'sender_id' => '1',
            'sender_name' => 'Test',
            'content' => 'Hello'
        ];
        $error = $this->invokePrivate($this->controller, 'validateMessageInput', [$data]);
        $this->assertNull($error);
    }

    public function testValidateAttachmentInput_MissingFileType_ReturnsError(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateAttachmentInput', [['file_name' => 'a.jpg', 'file_url' => 'http://x', 'file_size' => 100]]);
        $this->assertSame('File type is required', $error);
    }

    public function testValidateAttachmentInput_MissingFileName_ReturnsError(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateAttachmentInput', [['file_type' => 'image', 'file_url' => 'http://x', 'file_size' => 100]]);
        $this->assertSame('File name is required', $error);
    }

    public function testValidateAttachmentInput_MissingFileUrl_ReturnsError(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateAttachmentInput', [['file_type' => 'image', 'file_name' => 'a.jpg', 'file_size' => 100]]);
        $this->assertSame('File URL is required', $error);
    }

    public function testValidateAttachmentInput_MissingFileSize_ReturnsError(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateAttachmentInput', [['file_type' => 'image', 'file_name' => 'a.jpg', 'file_url' => 'http://x']]);
        $this->assertSame('File size is required', $error);
    }

    public function testValidateAttachmentInput_FileSizeTooLarge_ReturnsError(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateAttachmentInput', [
            ['file_type' => 'image', 'file_name' => 'a.jpg', 'file_url' => 'http://x', 'file_size' => 15 * 1024 * 1024]
        ]);
        $this->assertStringContainsString('File size exceeds maximum', $error);
    }

    public function testValidateAttachmentInput_Valid_ReturnsNull(): void
    {
        $error = $this->invokePrivate($this->controller, 'validateAttachmentInput', [
            ['file_type' => 'image', 'file_name' => 'a.jpg', 'file_url' => 'http://x', 'file_size' => 1024]
        ]);
        $this->assertNull($error);
    }

    // ==================== getAllIncidents ====================

    public function testGetAllIncidents_Unauthenticated_Returns401(): void
    {
        $request = $this->createQueryRequest([]);
        $response = $this->controller->getAllIncidents($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetAllIncidents_Authenticated_Returns200(): void
    {
        $this->setAuthenticatedUser();
        $incident = $this->createIncidentMock(['id' => 1, 'title' => 'Test']);
        $this->serviceMock->expects($this->once())
            ->method('getAllIncidents')
            ->with(['status' => null, 'severity' => null, 'ward_code' => null, 'incident_type' => null, 'limit' => 20])
            ->willReturn([$incident]);
        $request = $this->createQueryRequest([]);
        $response = $this->controller->getAllIncidents($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('incidents', $content);
        $this->assertCount(1, $content['incidents']);
    }

    public function testGetAllIncidents_WithFilters_PassesFilters(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->expects($this->once())
            ->method('getAllIncidents')
            ->with($this->arrayHasKey('status'))
            ->willReturn([]);
        $request = $this->createQueryRequest(['status' => 'reported', 'severity' => 'high', 'ward_code' => 'WC01']);
        $this->controller->getAllIncidents($request);
    }

    public function testGetAllIncidents_LimitCapped_Returns100(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->expects($this->once())
            ->method('getAllIncidents')
            ->with($this->callback(fn($f) => $f['limit'] === 100))
            ->willReturn([]);
        $request = $this->createQueryRequest(['limit' => '999']);
        $this->controller->getAllIncidents($request);
    }

    // ==================== getIncident ====================

    public function testGetIncident_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getIncident('INC-001');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetIncident_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn(null);
        $response = $this->controller->getIncident('INC-001');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetIncident_Success_Returns200(): void
    {
        $this->setAuthenticatedUser();
        $incident = $this->createIncidentMock(['incident_number' => 'INC-001', 'title' => 'Test']);
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn($incident);
        $this->serviceMock->expects($this->once())
            ->method('incrementViewCount')
            ->with($incident);
        $response = $this->controller->getIncident('INC-001');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('incident_number', $content);
    }

    // ==================== createIncident ====================

    public function testCreateIncident_Unauthenticated_Returns401(): void
    {
        $request = $this->createJsonRequest(['title' => 'Test', 'incident_type' => 'infrastructure']);
        $response = $this->controller->createIncident($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testCreateIncident_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $request = Request::create('/api/smart-incidents', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->createIncident($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateIncident_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $request = $this->createJsonRequest(['title' => '', 'incident_type' => 'invalid']);
        $response = $this->controller->createIncident($request);
        $this->assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testCreateIncident_Success_Returns201(): void
    {
        $this->setAuthenticatedUser();
        $incident = $this->createIncidentMock(['incident_number' => 'INC-001', 'title' => 'New']);
        $this->serviceMock->expects($this->once())
            ->method('createIncident')
            ->with($this->callback(function($data) {
                return $data['title'] === 'New' && $data['incident_type'] === 'infrastructure';
            }))
            ->willReturn($incident);
        $request = $this->createJsonRequest(['title' => 'New', 'incident_type' => 'infrastructure']);
        $response = $this->controller->createIncident($request);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('incident_number', $content);
    }

    // ==================== updateStatus ====================

    public function testUpdateStatus_NotOfficer_Returns403(): void
    {
        $this->setAuthenticatedUser(false, false);
        $request = $this->createJsonRequest(['status' => 'resolved']);
        $response = $this->controller->updateStatus($request, 'INC-001');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testUpdateStatus_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser(false, true);
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn(null);
        $request = $this->createJsonRequest(['status' => 'resolved']);
        $response = $this->controller->updateStatus($request, 'INC-001');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateStatus_MissingStatus_Returns400(): void
    {
        $this->setAuthenticatedUser(false, true);
        $incident = $this->createIncidentMock();
        $this->serviceMock->method('getIncident')->willReturn($incident);
        $request = $this->createJsonRequest([]);
        $response = $this->controller->updateStatus($request, 'INC-001');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateStatus_InvalidStatus_Returns400(): void
    {
        $this->setAuthenticatedUser(false, true);
        $incident = $this->createIncidentMock();
        $this->serviceMock->method('getIncident')->willReturn($incident);
        $request = $this->createJsonRequest(['status' => 'invalid']);
        $response = $this->controller->updateStatus($request, 'INC-001');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateStatus_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(false, true);
        $incident = $this->createIncidentMock(['status' => 'reported']);
        $updated = $this->createIncidentMock(['status' => 'resolved']);
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn($incident);
        $this->serviceMock->expects($this->once())
            ->method('updateIncidentStatus')
            ->with($incident, 'resolved')
            ->willReturn($updated);
        $request = $this->createJsonRequest(['status' => 'resolved']);
        $response = $this->controller->updateStatus($request, 'INC-001');
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== assignIncident ====================

    public function testAssignIncident_NotOfficer_Returns403(): void
    {
        $this->setAuthenticatedUser(false, false);
        $request = $this->createJsonRequest(['officer_id' => 'OFF-001']);
        $response = $this->controller->assignIncident($request, 'INC-001');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAssignIncident_MissingOfficerId_Returns400(): void
    {
        $this->setAuthenticatedUser(false, true);
        $incident = $this->createIncidentMock();
        $this->serviceMock->method('getIncident')->willReturn($incident);
        $request = $this->createJsonRequest([]);
        $response = $this->controller->assignIncident($request, 'INC-001');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignIncident_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(false, true);
        $incident = $this->createIncidentMock();
        $updated = $this->createIncidentMock();
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn($incident);
        $this->serviceMock->expects($this->once())
            ->method('assignIncident')
            ->with($incident, 'OFF-001', null)
            ->willReturn($updated);
        $request = $this->createJsonRequest(['officer_id' => 'OFF-001']);
        $response = $this->controller->assignIncident($request, 'INC-001');
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== resolveIncident ====================

    public function testResolveIncident_NotOfficer_Returns403(): void
    {
        $this->setAuthenticatedUser(false, false);
        $request = $this->createJsonRequest(['summary' => 'Fixed']);
        $response = $this->controller->resolveIncident($request, 'INC-001');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testResolveIncident_MissingSummary_Returns400(): void
    {
        $this->setAuthenticatedUser(false, true);
        $incident = $this->createIncidentMock();
        $this->serviceMock->method('getIncident')->willReturn($incident);
        $request = $this->createJsonRequest([]);
        $response = $this->controller->resolveIncident($request, 'INC-001');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testResolveIncident_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(false, true);
        $incident = $this->createIncidentMock(['status' => 'in_progress']);
        $updated = $this->createIncidentMock(['status' => 'resolved']);
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn($incident);
        $this->serviceMock->expects($this->once())
            ->method('resolveIncident')
            ->with($incident, 'Fixed')
            ->willReturn($updated);
        $request = $this->createJsonRequest(['summary' => 'Fixed']);
        $response = $this->controller->resolveIncident($request, 'INC-001');
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== upvote ====================

    public function testUpvote_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->upvote('INC-001');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUpvote_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn(null);
        $response = $this->controller->upvote('INC-001');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpvote_Success_Returns200(): void
    {
        $this->setAuthenticatedUser();
        $incident = $this->createIncidentMock(['incident_number' => 'INC-001', 'upvote_count' => 5]);
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn($incident);
        $this->serviceMock->expects($this->once())
            ->method('upvoteIncident')
            ->with($incident);
        $response = $this->controller->upvote('INC-001');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $content);
        $this->assertArrayHasKey('upvote_count', $content);
    }

    // ==================== getMessages ====================

    public function testGetMessages_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getMessages('INC-001');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetMessages_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn(null);
        $response = $this->controller->getMessages('INC-001');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetMessages_Success_Returns200(): void
    {
        $this->setAuthenticatedUser();
        $incident = $this->createIncidentMock();
        $messages = [$this->createMessageMock(['id' => 1, 'content' => 'Hello'])];
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn($incident);
        $this->serviceMock->expects($this->once())
            ->method('getMessages')
            ->with($incident)
            ->willReturn($messages);
        $response = $this->controller->getMessages('INC-001');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('messages', $content);
        $this->assertCount(1, $content['messages']);
    }

    // ==================== addMessage ====================

    public function testAddMessage_Unauthenticated_Returns401(): void
    {
        $request = $this->createJsonRequest(['sender_type' => 'citizen', 'sender_id' => '1', 'sender_name' => 'Test', 'content' => 'Hi']);
        $response = $this->controller->addMessage($request, 'INC-001');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testAddMessage_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn(null);
        $request = $this->createJsonRequest(['sender_type' => 'citizen', 'sender_id' => '1', 'sender_name' => 'Test', 'content' => 'Hi']);
        $response = $this->controller->addMessage($request, 'INC-001');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testAddMessage_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $incident = $this->createIncidentMock();
        $this->serviceMock->method('getIncident')->willReturn($incident);
        $request = $this->createJsonRequest(['sender_type' => 'citizen', 'sender_id' => '1']); // missing sender_name and content
        $response = $this->controller->addMessage($request, 'INC-001');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAddMessage_Success_Returns201(): void
    {
        $this->setAuthenticatedUser();
        $incident = $this->createIncidentMock();
        $msg = $this->createMessageMock(['id' => 5, 'content' => 'Hello']);
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn($incident);
        $this->serviceMock->expects($this->once())
            ->method('addMessage')
            ->with($incident, $this->callback(fn($d) => $d['content'] === 'Hello' && $d['sender_type'] === 'citizen'))
            ->willReturn($msg);
        $request = $this->createJsonRequest(['sender_type' => 'citizen', 'sender_id' => '1', 'sender_name' => 'Test', 'content' => 'Hello']);
        $response = $this->controller->addMessage($request, 'INC-001');
        $this->assertSame(201, $response->getStatusCode());
    }

    // ==================== getActivityLog ====================

    public function testGetActivityLog_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getActivityLog('INC-001');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetActivityLog_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn(null);
        $response = $this->controller->getActivityLog('INC-001');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetActivityLog_Success_Returns200(): void
    {
        $this->setAuthenticatedUser();
        $incident = $this->createIncidentMock();
        $activities = [$this->createActivityMock(['id' => 1, 'action' => 'created'])];
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn($incident);
        $this->serviceMock->expects($this->once())
            ->method('getActivityLog')
            ->with($incident)
            ->willReturn($activities);
        $response = $this->controller->getActivityLog('INC-001');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('activities', $content);
        $this->assertCount(1, $content['activities']);
    }

    // ==================== addAttachment ====================

    public function testAddAttachment_Unauthenticated_Returns401(): void
    {
        $request = $this->createJsonRequest(['file_type' => 'image', 'file_name' => 'a.jpg', 'file_url' => 'http://x', 'file_size' => 1024]);
        $response = $this->controller->addAttachment($request, 'INC-001');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testAddAttachment_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn(null);
        $request = $this->createJsonRequest(['file_type' => 'image', 'file_name' => 'a.jpg', 'file_url' => 'http://x', 'file_size' => 1024]);
        $response = $this->controller->addAttachment($request, 'INC-001');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testAddAttachment_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $incident = $this->createIncidentMock();
        $this->serviceMock->method('getIncident')->willReturn($incident);
        $request = $this->createJsonRequest(['file_type' => 'image', 'file_name' => 'a.jpg']); // missing file_url and file_size
        $response = $this->controller->addAttachment($request, 'INC-001');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAddAttachment_Success_Returns201(): void
    {
        $this->setAuthenticatedUser();
        $incident = $this->createIncidentMock();
        $att = $this->createAttachmentMock(['id' => 5]);
        $this->serviceMock->expects($this->once())
            ->method('getIncident')
            ->with('INC-001')
            ->willReturn($incident);
        $this->serviceMock->expects($this->once())
            ->method('addAttachment')
            ->with($incident, $this->callback(fn($d) => $d['file_type'] === 'image' && $d['file_size'] === 1024))
            ->willReturn($att);
        $request = $this->createJsonRequest(['file_type' => 'image', 'file_name' => 'a.jpg', 'file_url' => 'http://x', 'file_size' => 1024]);
        $response = $this->controller->addAttachment($request, 'INC-001');
        $this->assertSame(201, $response->getStatusCode());
    }
}
