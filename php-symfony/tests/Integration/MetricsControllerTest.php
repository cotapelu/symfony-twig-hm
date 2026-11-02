<?php

declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MetricsControllerTest extends IntegrationTestCase
{
    public function testMetricsEndpointReturnsPrometheusFormat(): void
    {
        $client = static::$client;
        $client->request('GET', '/metrics');

        $response = $client->getResponse();
        $this->assertStringStartsWith('text/plain; version=0.0.4', $response->headers->get('Content-Type'));
        $this->assertResponseStatusCodeSame(200);

        $content = $response->getContent();
        $this->assertIsString($content);

        // Check for expected HELP and TYPE lines
        $this->assertStringContainsString('# HELP http_requests_total', $content);
        $this->assertStringContainsString('# TYPE http_requests_total counter', $content);
        // The metric value should be at least 1 (this request)
        $this->assertMatchesRegularExpression('/^http_requests_total \d+$/m', $content);

        $this->assertStringContainsString('# HELP database_queries_total', $content);
        $this->assertStringContainsString('database_queries_total ', $content);
    }
}
