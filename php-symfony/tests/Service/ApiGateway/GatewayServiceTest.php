<?php

declare(strict_types=1);

namespace App\Tests\Service\ApiGateway;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use Platform\ApiGateway\Service\GatewayService;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Test suite for GatewayService
 * 
 * Quality First:
 * - Functions <= 20 lines, single responsibility
 * - Cyclomatic complexity <= 10
 * - 100% error handling coverage
 * - Input validation on all public APIs
 * - No hardcoded secrets/configs
 * - Pure functions where possible
 * - Dependency injection for external services
 */
#[AllowMockObjectsWithoutExpectations]
class GatewayServiceTest extends TestCase
{
    private GatewayService $gatewayService;
    private HttpClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $routes = [
            '/api/auth' => 'http://localhost:8081',
            '/api/users' => 'http://localhost:8082',
        ];
        $this->gatewayService = new GatewayService($routes, 30, $this->httpClient);
    }

    public function testGetRoutesReturnsArrayOfStrings(): void
    {
        $routes = $this->gatewayService->getRoutes();
        
        $this->assertIsArray($routes);
        $this->assertNotEmpty($routes);
        
        foreach ($routes as $route) {
            $this->assertIsString($route);
            $this->assertStringStartsWith('/api/', $route);
        }
    }

    public function testGetRouteConfigReturnsArrayWithStringKeysAndValues(): void
    {
        $config = $this->gatewayService->getRouteConfig();
        
        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
        
        foreach ($config as $key => $value) {
            $this->assertIsString($key);
            $this->assertIsString($value);
            $this->assertStringStartsWith('/api/', $key);
            $this->assertStringContainsString('http://', $value);
        }
    }

    public function testFindRouteReturnsTargetWhenPathMatchesPrefix(): void
    {
        $path = '/api/auth/login';
        $expected = 'http://localhost:8081';
        
        $result = $this->gatewayService->findRoute($path);
        
        $this->assertSame($expected, $result);
    }

    public function testFindRouteReturnsNullWhenPathDoesNotMatchAnyPrefix(): void
    {
        $path = '/unknown/path';
        
        $result = $this->gatewayService->findRoute($path);
        
        $this->assertNull($result);
    }

    public function testFindRouteReturnsNullWhenPathIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Path must be a non-empty string');

        $this->gatewayService->findRoute('');
    }

    public function testProxyReturnsSuccessResponseWhenRequestSucceeds(): void
    {
        $method = 'GET';
        $path = '/api/auth/user';
        $expectedStatus = 200;
        $expectedBody = '{"id":1,"name":"John"}';
        $expectedHeaders = ['Content-Type' => ['application/json']];
        
        $httpResponse = $this->createMock(ResponseInterface::class);
        $httpResponse->method('getStatusCode')->willReturn($expectedStatus);
        $httpResponse->method('getContent')->willReturn($expectedBody);
        $httpResponse->method('getHeaders')->willReturn($expectedHeaders);
        
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with($method, 'http://localhost:8081/api/auth/user', [
                'headers' => [],
                'body' => null,
            ])
            ->willReturn($httpResponse);
        
        $result = $this->gatewayService->proxy($method, $path);
        
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('body', $result);
        $this->assertArrayHasKey('headers', $result);
        
        $this->assertSame($expectedStatus, $result['status']);
        $this->assertSame($expectedBody, $result['body']);
        $this->assertSame($expectedHeaders, $result['headers']);
    }

    public function testProxyReturnsErrorResponseWhenRouteNotFound(): void
    {
        $method = 'GET';
        $path = '/unknown/endpoint';
        
        $result = $this->gatewayService->proxy($method, $path);
        
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('body', $result);
        
        $this->assertSame(404, $result['status']);
        $this->assertIsArray($result['body']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertStringContainsString('No route found', $result['body']['error']);
    }

    public function testProxyReturnsBadGatewayWhenHttpClientThrowsException(): void
    {
        $method = 'POST';
        $path = '/api/auth/login';
        
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with($method, 'http://localhost:8081/api/auth/login', [
                'headers' => [],
                'body' => null,
            ])
            ->willThrowException(new \Exception('Connection failed'));
        
        $result = $this->gatewayService->proxy($method, $path);
        
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('body', $result);
        
        $this->assertSame(502, $result['status']);
        $this->assertIsArray($result['body']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertStringContainsString('Bad gateway: Connection failed', $result['body']['error']);
    }

    public function testFilterHeadersRemovesHopByHopHeaders(): void
    {
        $inputHeaders = [
            'Connection' => 'keep-alive',
            'Keep-Alive' => 'timeout=5',
            'Proxy-Authenticate' => 'Basic realm="test"',
            'Proxy-Authorization' => 'Basic abc123',
            'Te' => 'trailers',
            'Trailers' => 'max-age=10',
            'Transfer-Encoding' => 'chunked',
            'Upgrade' => 'h2c',
            'Authorization' => 'Bearer token123',
            'Content-Type' => 'application/json',
            'X-Custom-Header' => 'custom-value',
        ];
        
        $expectedHeaders = [
            'Authorization' => 'Bearer token123',
            'Content-Type' => 'application/json',
            'X-Custom-Header' => 'custom-value',
        ];
        
        $reflection = new \ReflectionClass($this->gatewayService);
        $method = $reflection->getMethod('filterHeaders');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->gatewayService, $inputHeaders);
        
        $this->assertIsArray($result);
        $this->assertSame($expectedHeaders, $result);
    }

    public function testFilterHeadersPreservesCaseSensitivity(): void
    {
        $inputHeaders = [
            'content-type' => 'application/json',
            'X-API-KEY' => 'secret123',
        ];
        
        $reflection = new \ReflectionClass($this->gatewayService);
        $method = $reflection->getMethod('filterHeaders');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->gatewayService, $inputHeaders);
        
        $this->assertArrayHasKey('content-type', $result);
        $this->assertArrayHasKey('X-API-KEY', $result);
        $this->assertSame('application/json', $result['content-type']);
        $this->assertSame('secret123', $result['X-API-KEY']);
    }
}