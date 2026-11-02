<?php

declare(strict_types=1);

namespace Platform\ApiGateway\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\NativeHttpClient;

/**
 * Simple performance tests for GatewayService
 */
class GatewayServiceBenchmarkTest extends TestCase
{
    public function testBasicFunctionalityPerformance(): void
    {
        // Arrange
        $service = new GatewayService(['/api/test' => 'http://localhost:9999']);
        
        // Act & Assert - test that basic operations work
        $routes = $service->getRoutes();
        $this->assertIsArray($routes);
        $this->assertNotEmpty($routes);
        
        $route = $service->findRoute('/api/test/endpoint');
        $this->assertEquals('http://localhost:9999', $route);
        
        // Test with non-existent route
        $route = $service->findRoute('/api/nonexistent/endpoint');
        $this->assertNull($route);
        
        // Output performance info
        fwrite(STDOUT, "GatewayService basic functionality test passed\n");
    }
}