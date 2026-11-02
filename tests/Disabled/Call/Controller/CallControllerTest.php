<?php declare(strict_types=1);

namespace App\Tests\Unit\Call\Controller;

use App\Call\Controller\CallController;
use App\Call\Entity\CallRecord;
use App\Call\Service\CallService;
use PHPUnit\Framework\TestCase;

class CallControllerTest extends TestCase
{
    private CallController $controller;
    private CallService $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(CallService::class);
        $reflection = new \ReflectionClass(CallController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $reflectionProperty = $reflection->getProperty('service');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($controller, $this->service);
        $this->controller = $controller;
    }

    public function testInitiateCallSuccess(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->setId(1);
        $this->service->expects($this->once())
            ->method('initiateCall')
            ->with(1, 2, 'audio')
            ->willReturn($call);

        $request = [
            'body' => [
                'caller_id' => 1,
                'receiver_id' => 2,
                'type' => 'audio'
            ]
        ];

        $result = $this->controller->initiateCall($request);

        $this->assertEquals(201, $result['status']);
        $this->assertInstanceOf(CallRecord::class, $result['data']);
    }

    public function testGetCallFound(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->setId(1);
        $this->service->expects($this->once())
            ->method('getCall')
            ->with(1)
            ->willReturn($call);

        $request = ['params' => ['id' => 1]];
        $result = $this->controller->getCall($request);

        $this->assertEquals(200, $result['status']);
        $this->assertSame($call, $result['data']);
    }

    public function testGetCallNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('getCall')
            ->with(999)
            ->willReturn(null);

        $request = ['params' => ['id' => 999]];
        $result = $this->controller->getCall($request);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Call not found', $result['error']);
    }

    public function testAnswerCallSuccess(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->setId(1);
        $call->setAnswered();
        $this->service->expects($this->once())
            ->method('answerCall')
            ->with(1)
            ->willReturn($call);

        $request = ['params' => ['id' => 1]];
        $result = $this->controller->answerCall($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testEndCallSuccess(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->setId(1);
        $call->end();
        $this->service->expects($this->once())
            ->method('endCall')
            ->with(1)
            ->willReturn($call);

        $request = ['params' => ['id' => 1]];
        $result = $this->controller->endCall($request);

        $this->assertEquals(200, $result['status']);
    }
}
