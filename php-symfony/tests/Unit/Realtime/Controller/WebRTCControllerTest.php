<?php

namespace App\Tests\Unit\Realtime\Controller;

use App\Realtime\Controller\WebRTCController;
use App\Realtime\Service\WebRTCSignalingService;
use App\Shared\Security\AuthenticationService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\TestCase;

class WebRTCControllerTest extends TestCase
{
    private WebRTCSignalingService $signalingService;
    private AuthenticationService $authenticationService;
    private WebRTCController $controller;

    protected function setUp(): void
    {
        $this->signalingService = $this->createMock(WebRTCSignalingService::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->controller = new WebRTCController($this->signalingService, $this->authenticationService);

        // Set up container with serializer
        $container = new ContainerBuilder();
        $container->set('serializer', new Serializer([], [new JsonEncoder()]));
        $this->controller->setContainer($container);
    }

    private function createRequestWithJson(array $data = []): Request
    {
        $content = json_encode($data);
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
        return Request::create('/api/webrtc', 'POST', [], [], [], $server, $content);
    }

    public function testJoinRoomRequiresAuthentication(): void
    {
        $this->authenticationService->method('requireAuthentication')
            ->will($this->throwException(new \Exception('Unauthenticated')));

        $request = $this->createRequestWithJson();
        $response = $this->controller->joinRoom($request, 'anyroom');

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Authentication required', $response->getContent());
    }

    public function testJoinRoomInvalidRoomId(): void
    {
        $this->authenticationService->method('requireAuthentication')
            ->willReturn(['id' => 'user-123']);

        $request = $this->createRequestWithJson();
        $response = $this->controller->joinRoom($request, 'invalid room!'); // space invalid

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid room ID format', $response->getContent());
    }

    public function testJoinRoomNotFound(): void
    {
        $this->authenticationService->method('requireAuthentication')
            ->willReturn(['id' => 'user-123']);

        $this->signalingService->expects($this->once())
            ->method('joinRoom')
            ->with('room1', 'user-123')
            ->willReturn(null);

        $request = $this->createRequestWithJson();
        $response = $this->controller->joinRoom($request, 'room1');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Room not found', $response->getContent());
    }

    public function testJoinRoomAccessDenied(): void
    {
        $this->authenticationService->method('requireAuthentication')
            ->willReturn(['id' => 'user-123']);

        $this->signalingService->expects($this->once())
            ->method('joinRoom')
            ->with('room1', 'user-123')
            ->willReturn([
                'roomId' => 'room1',
                'participants' => ['otheruser'], // user-123 not in participants
            ]);

        $request = $this->createRequestWithJson();
        $response = $this->controller->joinRoom($request, 'room1');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Access denied', $response->getContent());
    }

    public function testJoinRoomSuccess(): void
    {
        $this->authenticationService->method('requireAuthentication')
            ->willReturn(['id' => 'user-123']);

        $this->signalingService->expects($this->once())
            ->method('joinRoom')
            ->with('room1', 'user-123')
            ->willReturn([
                'roomId' => 'room1',
                'participants' => ['user-123'],
                'offers' => [],
            ]);

        $request = $this->createRequestWithJson();
        $response = $this->controller->joinRoom($request, 'room1');

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame([
            'roomId' => 'room1',
            'participants' => ['user-123'],
            'offers' => [],
        ], $data);
    }
}
