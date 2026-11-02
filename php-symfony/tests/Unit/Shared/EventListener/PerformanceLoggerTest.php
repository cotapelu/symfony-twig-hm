<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\EventListener;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\EventListener\PerformanceLogger;
use App\Shared\Service\PerformanceMonitorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[AllowMockObjectsWithoutExpectations]

class PerformanceLoggerTest extends TestCase
{
    private PerformanceMonitorService&MockObject $monitorMock;
    private LoggerInterface&MockObject $loggerMock;
    private PerformanceLogger $loggerListener;

    protected function setUp(): void
    {
        $this->monitorMock = $this->createMock(PerformanceMonitorService::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->loggerListener = new PerformanceLogger($this->monitorMock, $this->loggerMock);
    }

    private function createRequestEvent(Request $request, bool $isMainRequest = true): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $requestType = $isMainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST;
        return new RequestEvent($kernel, $request, $requestType);
    }

    private function createTerminateEvent(Request $request, Response $response): TerminateEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new TerminateEvent($kernel, $request, $response);
    }

    public function testOnKernelRequestStartsTimerAndLogsMemoryForMainRequest(): void
    {
        $request = new Request();
        $event = $this->createRequestEvent($request);

        $this->monitorMock->expects($this->once())->method('startTimer')->with('request');
        $this->monitorMock->expects($this->once())->method('logMemorySnapshot')->with('request_start');

        $this->loggerListener->onKernelRequest($event);
    }

    public function testOnKernelRequestSkipsSubRequest(): void
    {
        $request = new Request();
        $event = $this->createRequestEvent($request, false); // sub-request

        $this->monitorMock->expects($this->never())->method('startTimer');
        $this->monitorMock->expects($this->never())->method('logMemorySnapshot');

        $this->loggerListener->onKernelRequest($event);
    }

    public function testOnKernelTerminateLogsApiCallAndChecksNPlusOne(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');
        $request->server->set('REQUEST_METHOD', 'POST');
        $response = new Response('OK', 200);
        $event = $this->createTerminateEvent($request, $response);

        $timerData = ['duration_ms' => 150.5];
        $this->monitorMock->expects($this->once())->method('endTimer')->with('request')->willReturn($timerData);
        $this->monitorMock->expects($this->once())->method('logApiCall')->with('/api/test', 'POST', 200, 0.1505);
        $this->monitorMock->expects($this->once())->method('checkNPlusOneQueries')->with([]);
        $this->monitorMock->expects($this->once())->method('logMemorySnapshot')->with('request_end');

        $this->loggerListener->onKernelTerminate($event);

        // Queries should be reset
        $this->assertSame([], $this->getPrivateProperty($this->loggerListener, 'queries'));
    }

    public function testOnKernelTerminateLogsWarningForSlowRequest(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/slow');
        $request->server->set('REQUEST_METHOD', 'GET');
        $response = new Response('Slow', 200);
        $event = $this->createTerminateEvent($request, $response);

        $timerData = ['duration_ms' => 2500]; // 2.5 seconds
        $this->monitorMock->expects($this->once())->method('endTimer')->with('request')->willReturn($timerData);
        $this->monitorMock->expects($this->once())->method('logApiCall')->with('/api/slow', 'GET', 200, 2.5);
        $this->monitorMock->expects($this->once())->method('checkNPlusOneQueries')->with([]);
        $this->monitorMock->expects($this->once())->method('logMemorySnapshot')->with('request_end');

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Slow request detected', $this->callback(function(array $context) {
                return $context['endpoint'] === '/api/slow'
                    && $context['method'] === 'GET'
                    && $context['duration_ms'] === 2500;
            }));

        $this->loggerListener->onKernelTerminate($event);
    }

    public function testLogQueryRecordsQueryAndWarnsOnSlowQuery(): void
    {
        $this->monitorMock->expects($this->once())
            ->method('logQuery')
            ->with('SELECT * FROM users', 0.15, 0);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Slow query detected', $this->callback(function(array $context) {
                return $context['sql'] === 'SELECT * FROM users' && $context['duration'] === 0.15;
            }));

        // Simulate calling MetricsMiddleware::recordSlowQuery via closure
        $this->loggerListener->logQuery('SELECT * FROM users', null, 0.15);
    }

    public function testLogQueryDoesNotWarnForFastQuery(): void
    {
        $this->monitorMock->expects($this->once())
            ->method('logQuery')
            ->with('SELECT * FROM products', 0.01, 0);

        $this->loggerMock->expects($this->never())->method('warning');

        $this->loggerListener->logQuery('SELECT * FROM products', null, 0.01);
    }

    public function testQueriesAccumulateAcrossRequests(): void
    {
        // Simulate first request
        $this->loggerListener->logQuery('Query 1', null, 0.01);
        $this->loggerListener->logQuery('Query 2', null, 0.02);

        $this->assertCount(2, $this->getPrivateProperty($this->loggerListener, 'queries'));

        // Simulate terminate clearing
        $this->setPrivateProperty($this->loggerListener, 'queries', []);
        $this->loggerListener->logQuery('Query 3', null, 0.01);
        $this->assertCount(1, $this->getPrivateProperty($this->loggerListener, 'queries'));
    }

    private function getPrivateProperty(object $obj, string $property): mixed
    {
        $reflection = new \ReflectionProperty($obj, $property);
        $reflection->setAccessible(true);
        return $reflection->getValue($obj);
    }

    private function setPrivateProperty(object $obj, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($obj, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($obj, $value);
    }
}
