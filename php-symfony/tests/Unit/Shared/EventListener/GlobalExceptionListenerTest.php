<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\EventListener;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\EventListener\GlobalExceptionListener;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]

class GlobalExceptionListenerTest extends TestCase
{
    private LoggerInterface&MockObject $loggerMock;
    private GlobalExceptionListener $listener;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->listener = new GlobalExceptionListener($this->loggerMock);
    }

    private function createRequest(array $server = [], string $accept = 'application/json', string $contentType = 'application/json'): Request
    {
        $request = new Request($server, [], [], [], [], [
            'HTTP_ACCEPT' => $accept,
            'HTTP_CONTENT_TYPE' => $contentType,
        ]);
        return $request;
    }

    private function createExceptionEvent(\Throwable $exception, Request $request): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    public function testOnKernelExceptionIgnoresNonApiRequests(): void
    {
        $request = $this->createRequest([], 'text/html', 'text/html');
        $request->server->set('REQUEST_URI', '/web/page');
        $exception = new \RuntimeException('Test error');
        $event = $this->createExceptionEvent($exception, $request);

        $this->loggerMock->expects($this->never())->method('info');
        $this->loggerMock->expects($this->never())->method('error');

        $this->listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelExceptionHandlesApiRequestByPath(): void
    {
        $request = $this->createRequest([], 'text/html', 'text/html');
        $request->server->set('REQUEST_URI', '/api/users');
        $exception = new \RuntimeException('Test error');
        $event = $this->createExceptionEvent($exception, $request);

        $this->loggerMock->expects($this->once())->method('error');

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testOnKernelExceptionLogsHttpExceptionAtInfoLevel(): void
    {
        $request = $this->createRequest();
        $exception = new NotFoundHttpException('Not found', null, 404);
        $event = $this->createExceptionEvent($exception, $request);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('HTTP Exception', $this->callback(function(array $context) {
                return isset($context['url'], $context['method'], $context['status_code'], $context['message'])
                    && $context['status_code'] === 404;
            }));

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Resource not found', $data['error']);
    }

    public function testOnKernelExceptionHandlesValidationException(): void
    {
        $request = $this->createRequest();
        $errors = ['email' => 'Invalid'];
        $exception = new ValidationException($errors, 'Validation failed');
        $event = $this->createExceptionEvent($exception, $request);

        $this->loggerMock->expects($this->once())->method('error');

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Validation failed', $data['error']);
        $this->assertEquals($errors, $data['details']);
    }

    public function testOnKernelExceptionHandlesRateLimitWithRetryAfter(): void
    {
        $request = $this->createRequest();
        $exception = new HttpException(429, 'Too many requests');
        $event = $this->createExceptionEvent($exception, $request);

        $this->loggerMock->expects($this->once())->method('info');

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(429, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Rate limit exceeded', $data['error']);
        $this->assertArrayHasKey('retry_after', $data);
        $this->assertEquals(60, $data['retry_after']);
    }

    public function testOnKernelExceptionHandlesUnexpectedException(): void
    {
        $request = $this->createRequest();
        $exception = new \Exception('Unexpected error');
        $event = $this->createExceptionEvent($exception, $request);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Unhandled Exception', $this->callback(function(array $context) {
                return isset($context['exception_class']) && $context['exception_class'] === \Exception::class;
            }));

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('An unexpected error occurred', $data['error']);
    }

    public function testOnKernelExceptionSpecialMessagesForSqlErrors(): void
    {
        $request = $this->createRequest();
        $exception = new \RuntimeException('SQLSTATE[HY000] Connection error');
        $event = $this->createExceptionEvent($exception, $request);

        $this->loggerMock->expects($this->once())->method('error');

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Database error', $data['error']);
    }

    public function testOnKernelExceptionMapsBadRequest(): void
    {
        $request = $this->createRequest();
        $exception = new BadRequestHttpException('Invalid request');
        $event = $this->createExceptionEvent($exception, $request);

        $this->loggerMock->expects($this->once())->method('info');
        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Bad Request', $data['error']);
    }

    public function testOnKernelExceptionMapsNotFound(): void
    {
        $request = $this->createRequest();
        $exception = new NotFoundHttpException('Not found');
        $event = $this->createExceptionEvent($exception, $request);

        $this->loggerMock->expects($this->once())->method('info');
        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Resource not found', $data['error']);
    }

    public function testOnKernelExceptionMapsTooManyRequests(): void
    {
        $request = $this->createRequest();
        $exception = new HttpException(429, 'Too many requests');
        $event = $this->createExceptionEvent($exception, $request);

        $this->loggerMock->expects($this->once())->method('info');
        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(429, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Rate limit exceeded', $data['error']);
        $this->assertArrayHasKey('retry_after', $data);
        $this->assertEquals(60, $data['retry_after']);
    }

    public function testOnKernelExceptionMapsInternalServerError(): void
    {
        $request = $this->createRequest();
        $exception = new \RuntimeException('Server error');
        $event = $this->createExceptionEvent($exception, $request);

        $this->loggerMock->expects($this->once())->method('error');
        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('An unexpected error occurred', $data['error']);
    }

    public function testLogExceptionIncludesRequestContext(): void
    {
        $request = $this->createRequest([], 'application/json');
        $request->server->set('REQUEST_URI', '/api/test');
        $request->server->set('REQUEST_METHOD', 'POST');
        $request->headers->set('X-Request-ID', 'req123');
        $exception = new \RuntimeException('Test error');
        $event = $this->createExceptionEvent($exception, $request);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Unhandled Exception', $this->callback(function(array $context) {
                return str_contains($context['url'], '/api/test')
                    && $context['method'] === 'POST'
                    && $context['request_id'] === 'req123'
                    && array_key_exists('ip', $context)
                    && array_key_exists('user_agent', $context);
            }));

        $this->listener->onKernelException($event);
    }

    public function testIsApiRequestDetectsJsonAccept(): void
{
        $request = $this->createRequest([], 'application/json', 'text/plain');
        $reflection = new \ReflectionMethod($this->listener, 'isApiRequest');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->listener, $request);
        $this->assertTrue($result);
    }

    public function testIsApiRequestDetectsJsonContentType(): void
    {
        $request = $this->createRequest([], 'text/html', 'application/json');
        $reflection = new \ReflectionMethod($this->listener, 'isApiRequest');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->listener, $request);
        $this->assertTrue($result);
    }

    public function testIsApiRequestDetectsApiPath(): void
    {
        $request = $this->createRequest([], 'text/html', 'text/html');
        $request->server->set('REQUEST_URI', '/api/something');
        $reflection = new \ReflectionMethod($this->listener, 'isApiRequest');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->listener, $request);
        $this->assertTrue($result);
    }

    public function testIsApiRequestReturnsFalseForNonApi(): void
    {
        $request = $this->createRequest([], 'text/html', 'text/html');
        $request->server->set('REQUEST_URI', '/web/page');
        $reflection = new \ReflectionMethod($this->listener, 'isApiRequest');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->listener, $request);
        $this->assertFalse($result);
    }
}
