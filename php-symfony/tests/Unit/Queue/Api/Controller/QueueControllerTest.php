<?php

declare(strict_types=1);

namespace Tests\Unit\Queue\Api\Controller;

use App\Queue\Api\Controller\QueueController;
use App\Queue\Application\Service\QueueService;
use App\Queue\Domain\Entity\Queue;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class QueueControllerTest extends TestCase
{
    private QueueService $queueServiceMock;
    private QueueController $controller;

    protected function setUp(): void
    {
        $this->queueServiceMock = $this->createMock(QueueService::class);
        $this->controller = new QueueController($this->queueServiceMock);
    }

    private function invokePrivate(object $obj, string $method, array $args = [])
    {
        $ref = new ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function setAuthenticatedUser(QueueController $controller, string $userId = 'user123'): void
    {
        $user = new class($userId) implements UserInterface {
            private string $id;
            public function __construct(string $id) { $this->id = $id; }
            public function getId(): string { return $this->id; }
            public function getUserIdentifier(): string { return $this->id; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function getPassword(): ?string { return null; }
            public function getSalt(): ?string { return null; }
            public function eraseCredentials(): void {}
        };
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);
        $refTok = new ReflectionProperty($controller, 'tokenStorage');
        $refTok->setAccessible(true);
        $refTok->setValue($controller, $tokenStorage);
        $controller->setContainer(new ContainerBuilder());
    }

    // ==================== Helpers ====================

    public function testSanitizeString_NullEmpty(): void
    {
        $this->assertSame('', $this->invokePrivate($this->controller, 'sanitizeString', [null]));
    }

    public function testSanitizeString_TrimsAndEscapes(): void
    {
        $input = '  <script>alert("x")</script>  ';
        $result = $this->invokePrivate($this->controller, 'sanitizeString', [$input]);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $result);
    }

    // validateServiceTypes
    public function testValidateServiceTypes_Valid(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateServiceTypes', [['Cấp CCCD', 'Hộ khẩu']]);
        $this->assertEmpty($errors);
    }

    public function testValidateServiceTypes_Invalid(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateServiceTypes', [['Invalid Type']]);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid service types', $errors[0]);
    }

    // validateCreateQueueInput
    public function testValidateCreateQueueInput_MissingName(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateCreateQueueInput', [['wardCode' => 'WC01', 'serviceTypes' => ['Cấp CCCD']]]);
        $this->assertContains('Queue name is required', $errors);
    }

    public function testValidateCreateQueueInput_MissingWardCode(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateCreateQueueInput', [['name' => 'Queue 1', 'serviceTypes' => ['Cấp CCCD']]]);
        $this->assertContains('Ward code is required', $errors);
    }

    public function testValidateCreateQueueInput_MissingServiceTypes(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateCreateQueueInput', [['name' => 'Q1', 'wardCode' => 'WC01']]);
        $this->assertContains('At least one service type is required', $errors);
    }

    public function testValidateCreateQueueInput_InvalidServiceType(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateCreateQueueInput', [
            ['name' => 'Q1', 'wardCode' => 'WC01', 'serviceTypes' => ['Invalid']]
        ]);
        $this->assertStringContainsString('Invalid service types: Invalid', $errors[0]);
    }

    public function testValidateCreateQueueInput_DescriptionTooLong(): void
    {
        $longDesc = str_repeat('a', 1001);
        $errors = $this->invokePrivate($this->controller, 'validateCreateQueueInput', [
            ['name' => 'Q1', 'wardCode' => 'WC01', 'serviceTypes' => ['Cấp CCCD'], 'description' => $longDesc]
        ]);
        $this->assertContains('Description too long', $errors);
    }

    public function testValidateCreateQueueInput_InvalidCounterCount(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateCreateQueueInput', [
            ['name' => 'Q1', 'wardCode' => 'WC01', 'serviceTypes' => ['Cấp CCCD'], 'counterCount' => 0]
        ]);
        $this->assertStringContainsString('Invalid counter count', $errors[0]);
    }

    public function testValidateCreateQueueInput_Valid(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateCreateQueueInput', [
            ['name' => 'Q1', 'wardCode' => 'WC01', 'serviceTypes' => ['Cấp CCCD', 'Hộ khẩu'], 'description' => 'A valid desc', 'counterCount' => 3]
        ]);
        $this->assertEmpty($errors);
    }

    // validateJoinQueueInput
    public function testValidateJoinQueueInput_MissingServiceType(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateJoinQueueInput', [['citizenName' => 'John']]);
        $this->assertContains('Service type is required', $errors);
    }

    public function testValidateJoinQueueInput_MissingCitizenName(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateJoinQueueInput', [['serviceType' => 'Cấp CCCD']]);
        $this->assertContains('Citizen name is required', $errors);
    }

    public function testValidateJoinQueueInput_InvalidPriority(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateJoinQueueInput', [
            ['serviceType' => 'Cấp CCCD', 'citizenName' => 'John', 'priority' => 15]
        ]);
        $this->assertContains('Priority must be between 0 and 10', $errors);
    }

    public function testValidateJoinQueueInput_InvalidPhone(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateJoinQueueInput', [
            ['serviceType' => 'Cấp CCCD', 'citizenName' => 'John', 'citizenPhone' => 'invalid']
        ]);
        $this->assertContains('Invalid phone number format', $errors);
    }

    public function testValidateJoinQueueInput_Valid(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateJoinQueueInput', [
            ['serviceType' => 'Cấp CCCD', 'citizenName' => 'John', 'priority' => 5, 'citizenPhone' => '+84912345678']
        ]);
        $this->assertEmpty($errors);
    }

    // ==================== createQueue endpoint ====================

    public function testCreateQueue_Unauthenticated_Returns401(): void
    {
        // Need container for error json in handle()
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/api/v1/queues', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Q1', 'wardCode' => 'WC01', 'serviceTypes' => ['Cấp CCCD']]));
        $response = $this->controller->createQueue($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        // Unauthenticated triggers InvalidArgumentException caught by handle, returns 400
        $this->assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertSame('Authentication required', $content['error']);
    }

    public function testCreateQueue_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $data = ['name' => '', 'wardCode' => 'WC01', 'serviceTypes' => ['Cấp CCCD']]; // missing name
        $request = Request::create('/api/v1/queues', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->createQueue($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testCreateQueue_Success_Returns201(): void
    {
        $queueMock = $this->createMock(Queue::class);
        $queueMock->method('getId')->willReturn('1');
        $queueMock->method('toArray')->willReturn(['id'=>1, 'name'=>'Q1']);
        $this->queueServiceMock->expects($this->once())
            ->method('createQueue')
            ->with('Q1', 'WC01', ['Cấp CCCD'], null, null, 1, null)
            ->willReturn($queueMock);
        $this->setAuthenticatedUser($this->controller);
        $data = ['name' => 'Q1', 'wardCode' => 'WC01', 'serviceTypes' => ['Cấp CCCD']];
        $request = Request::create('/api/v1/queues', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->createQueue($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('queue', $content);
    }

    // ==================== Additional Queue Endpoints ====================

    public function testListQueues_Unauthenticated_Returns400(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/api/v1/queues', 'GET');
        $response = $this->controller->listQueues($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testListQueues_Success_Returns200(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $queue = $this->createMock(Queue::class);
        $queue->method('toArray')->willReturn(['id'=>'q1']);
        $result = ['data'=>[$queue], 'total'=>1];
        $this->queueServiceMock->expects($this->once())
            ->method('listQueues')
            ->with(1, 20, null, null, null)
            ->willReturn($result);
        $request = Request::create('/api/v1/queues', 'GET');
        $response = $this->controller->listQueues($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $content);
        $this->assertArrayHasKey('queues', $content);
        $this->assertCount(1, $content['queues']);
    }

    public function testListQueues_WithFilters_PassesParameters(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $queue = $this->createMock(Queue::class);
        $queue->method('toArray')->willReturn(['id'=>'q1']);
        $this->queueServiceMock->expects($this->once())
            ->method('listQueues')
            ->with(2, 50, 'WC01', 'Cấp CCCD', 'active')
            ->willReturn(['data'=>[$queue], 'total'=>1]);
        $request = Request::create('/api/v1/queues', 'GET', ['page' => '2', 'limit' => '50', 'wardCode' => 'WC01', 'serviceType' => 'Cấp CCCD', 'status' => 'active']);
        $response = $this->controller->listQueues($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testListQueues_EmptyResult_Returns200(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $this->queueServiceMock->expects($this->once())
            ->method('listQueues')
            ->with(1, 20, null, null, null)
            ->willReturn(['data'=>[], 'total'=>0]);
        $request = Request::create('/api/v1/queues', 'GET');
        $response = $this->controller->listQueues($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEmpty($content['queues']);
        $this->assertSame(0, $content['pagination']['total']);
    }

    // ==================== getQueueStatus ====================

    public function testGetQueueStatus_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = Request::create('/api/v1/queues/0/status', 'GET');
        $response = $this->controller->getQueueStatus(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetQueueStatus_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $this->queueServiceMock->expects($this->once())
            ->method('getQueueStatus')
            ->with(1)
            ->willReturn(null);
        $request = Request::create('/api/v1/queues/1/status', 'GET');
        $response = $this->controller->getQueueStatus(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetQueueStatus_Success_Returns200(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $status = ['queueId'=>'q1','queueName'=>'Queue 1','isOpen'=>true,'waitingCount'=>5];
        $this->queueServiceMock->expects($this->once())
            ->method('getQueueStatus')
            ->with(1)
            ->willReturn($status);
        $request = Request::create('/api/v1/queues/1/status', 'GET');
        $response = $this->controller->getQueueStatus(1, $request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $content);
        $this->assertArrayHasKey('status', $content);
        $this->assertSame($status, $content['status']);
    }

    // ==================== getCitizenQueueStatus ====================

    public function testGetCitizenQueueStatus_Unauthenticated_Returns400(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/api/v1/queues/citizen/status', 'GET');
        $response = $this->controller->getCitizenQueueStatus($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetCitizenQueueStatus_NoActiveTicket_Returns200(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $this->queueServiceMock->expects($this->once())
            ->method('getCitizenQueueStatus')
            ->with('user123')
            ->willReturn(['hasActiveTicket'=>false]);
        $request = Request::create('/api/v1/queues/citizen/status', 'GET');
        $response = $this->controller->getCitizenQueueStatus($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $content);
        $this->assertFalse($content['status']['hasActiveTicket']);
    }

    public function testGetCitizenQueueStatus_WithActiveTicket_Returns200(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $status = [
            'hasActiveTicket' => true,
            'queueId' => 'q1',
            'queueName' => 'Queue 1',
            'ticketNumber' => 'T100',
            'status' => 'waiting',
        ];
        $this->queueServiceMock->expects($this->once())
            ->method('getCitizenQueueStatus')
            ->with('user123')
            ->willReturn($status);
        $request = Request::create('/api/v1/queues/citizen/status', 'GET');
        $response = $this->controller->getCitizenQueueStatus($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['status']['hasActiveTicket']);
        $this->assertSame('T100', $content['status']['ticketNumber']);
    }
}
