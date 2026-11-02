<?php

declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class HealthControllerTest extends IntegrationTestCase
{
    public function testIamHealthEndpoint(): void
    {
        $client = static::$client;
        $client->request('GET', '/platform/health/iam');

        $response = $client->getResponse();
        $content = $response->getContent();

        // If response is not JSON, output for debugging
        if (strpos($response->headers->get('Content-Type'), 'application/json') === false) {
            echo "Response content: $content\n";
        }

        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('status', $data);
        $this->assertTrue(in_array($data['status'], ['healthy', 'unhealthy']));
        $this->assertArrayHasKey('service', $data);
        $this->assertSame('IAM', $data['service']);
    }
}
