<?php

declare(strict_types=1);

namespace App\Tests\Controller;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class HealthControllerTest extends DatabaseIntegrationTestCase
{
    public function testHealthEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertInArray($responseData['status'], ['healthy', 'unhealthy', 'degraded']);
    }

    public function testReadyEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health/ready');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $responseData);
    }

    public function testLiveEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health/live');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('alive', $responseData['status']);
    }
}
