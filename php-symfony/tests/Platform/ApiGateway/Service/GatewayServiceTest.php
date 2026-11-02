<?php

declare(strict_types=1);

namespace Platform\ApiGateway\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\NativeHttpClient;
use InvalidArgumentException;

#[AllowMockObjectsWithoutExpectations]
class GatewayServiceTest extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $service = new GatewayService();
        
        // When no routes are provided, getRoutes() should return an empty array
        $this->assertIsArray($service->getRoutes());
        $this->assertEmpty($service->getRoutes());
        
        // Proxy should return a 404 error when no routes match
        $result = $service->proxy('GET', '/test');
        $this->assertEquals(404, $result['status']);
        $this->assertStringContainsString('No route found', $result['body']['error']);
    }

    public function testConstructorWithCustomRoutes(): void
    {
        $routes = ['/api/test' => 'http://localhost:9999'];
        $service = new GatewayService($routes);
        
        $this->assertEquals(['/api/test'], $service->getRoutes());
        $this->assertEquals(['/api/test' => 'http://localhost:9999'], $service->getRouteConfig());
    }

    public function testConstructorWithInvalidRoutesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route prefix must be a non-empty string');
        
        new GatewayService(['' => 'http://localhost:8080']);
    }

    public function testConstructorWithInvalidTargetThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route target must be a non-empty string');
        
        new GatewayService(['/api/test' => '']);
    }

    public function testConstructorWithInvalidUrlThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route target must be a valid URL starting with http:// or https://');
        
        new GatewayService(['/api/test' => 'localhost:8080']);
    }

    public function testFindRouteReturnsTargetWhenMatchFound(): void
    {
        $routes = ['/api/auth' => 'http://localhost:8081'];
        $service = new GatewayService($routes);
        
        $result = $service->findRoute('/api/auth/login');
        
        $this->assertEquals('http://localhost:8081', $result);
    }

    public function testFindRouteReturnsNullWhenNoMatchFound(): void
    {
        $routes = ['/api/auth' => 'http://localhost:8081'];
        $service = new GatewayService($routes);
        
        $result = $service->findRoute('/api/users/profile');
        
        $this->assertNull($result);
    }

    public function testFindRouteWithInvalidPathThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path must be a non-empty string');
        
        $service = new GatewayService(['/api/auth' => 'http://localhost:8081']);
        $service->findRoute('');
    }

    public function testFindRouteWithPathNotStartingWithSlashThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path must start with a forward slash');
        
        $service = new GatewayService(['/api/auth' => 'http://localhost:8081']);
        $service->findRoute('api/auth/login');
    }

    public function testProxyReturnsErrorWhenNoRouteFound(): void
    {
        $routes = ['/api/auth' => 'http://localhost:8081'];
        $service = new GatewayService($routes);
        
        $result = $service->proxy('GET', '/api/users/profile');
        
        $this->assertEquals(404, $result['status']);
        $this->assertEquals(['error' => 'No route found for path: /api/users/profile'], $result['body']);
    }

    public function testConstructorWithInvalidTimeoutThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeout must be a positive integer');
        
        new GatewayService([], 0);
    }

    public function testConstructorWithNegativeTimeoutThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeout must be a positive integer');
        
        new GatewayService([], -5);
    }
}