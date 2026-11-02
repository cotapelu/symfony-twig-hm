<?php

declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class SmartCityIntegrationDbTest extends DatabaseIntegrationTestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::getClient();
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'smartcity-test@example.com',
            'name' => 'SmartCity Test',
            'password' => 'SecurePassword123!',
        ]));

        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->token = $data['token'];
    }

    public function testGetMetrics(): void
    {
        $client = static::getClient();
        $client->request('GET', '/api/smartcity/metrics', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertArrayHasKey('airQuality', $data['data']);
        $this->assertArrayHasKey('traffic', $data['data']);
        $this->assertArrayHasKey('energyUsage', $data['data']);
    }

    public function testGetIncidents(): void
    {
        $client = static::getClient();
        $client->request('GET', '/api/smartcity/incidents', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('incidents', $data);
        $this->assertIsArray($data['incidents']);
        $this->assertNotEmpty($data['incidents']);
        $this->assertArrayHasKey('id', $data['incidents'][0]);
        $this->assertArrayHasKey('type', $data['incidents'][0]);
        $this->assertArrayHasKey('description', $data['incidents'][0]);
        $this->assertArrayHasKey('status', $data['incidents'][0]);
    }

    public function testGetSensors(): void
    {
        $client = static::getClient();
        $client->request('GET', '/api/smartcity/sensors', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('sensors', $data);
        $this->assertIsArray($data['sensors']);
        $this->assertNotEmpty($data['sensors']);
        $this->assertArrayHasKey('id', $data['sensors'][0]);
        $this->assertArrayHasKey('type', $data['sensors'][0]);
        $this->assertArrayHasKey('value', $data['sensors'][0]);
        $this->assertArrayHasKey('unit', $data['sensors'][0]);
    }
}
