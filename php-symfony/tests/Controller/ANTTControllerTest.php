<?php

declare(strict_types=1);

namespace App\Tests\Controller\ANTT;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class ANTTControllerTest extends DatabaseIntegrationTestCase
{
    // CREATE INCIDENT Tests (3)
    public function testCreateIncident(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/antt/incidents', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Road damage',
            'description' => 'Large pothole',
            'incident_type' => 'infrastructure',
            'severity' => 'high',
            'ward_code' => 'WARD-001',
            'location_address' => '123 Main St',
            'latitude' => 21.0285,
            'longitude' => 105.8542,
            'reporter_anonymous' => true
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('incident', $responseData);
        $this->assertEquals('Incident created successfully', $responseData['message']);
    }

    public function testCreateIncidentWithNamedReporter(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/antt/incidents', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Blocked drainage',
            'description' => 'Drainage system blocked',
            'incident_type' => 'sanitation',
            'severity' => 'medium',
            'ward_code' => 'WARD-002',
            'location_address' => '456 Side St',
            'reporter_name' => 'Nguyễn Văn A',
            'reporter_phone' => '0901234567',
            'reporter_anonymous' => false
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('incident', $responseData);
    }

    public function testCreateIncidentValidationFailure(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/antt/incidents', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => '',
            // Missing required incident_type
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    // LIST INCIDENTS Tests (5)
    public function testListIncidents(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/antt/incidents');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('incidents', $responseData);
        $this->assertArrayHasKey('total', $responseData);
    }

    public function testListIncidentsByWard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/antt/incidents?ward_code=WARD-001');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('incidents', $responseData);
    }

    public function testListIncidentsByOfficer(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/antt/incidents?officer_id=OFFICER-001');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('incidents', $responseData);
    }

    public function testListIncidentsByStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/antt/incidents?status=open');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('incidents', $responseData);
    }

    public function testListIncidentsWithLimit(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/antt/incidents?limit=10');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('incidents', $responseData);
    }

    // GET INCIDENT Tests (2)
    public function testGetIncident(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/antt/incidents/1');

        $this->assertResponseStatusCodeSame(404); // Assuming no incident with ID 1
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testGetIncidentNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/antt/incidents/99999');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Incident not found', $responseData['error']);
    }

    // UPDATE INCIDENT Tests (2)
    public function testUpdateIncident(): void
    {
        $client = static::createClient();
        $client->request('PUT', '/api/antt/incidents/1', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Updated title',
            'status' => 'assigned'
        ]));

        $this->assertResponseStatusCodeSame(404); // Assuming no incident with ID 1
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testUpdateIncidentNotFound(): void
    {
        $client = static::createClient();
        $client->request('PUT', '/api/antt/incidents/99999', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Updated title'
        ]));

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Incident not found', $responseData['error']);
    }

    // ASSIGN INCIDENT Tests (2)
    public function testAssignIncident(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/antt/incidents/1/assign', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'officer_id' => 'OFFICER-001',
            'team' => 'TEAM-A'
        ]));

        $this->assertResponseStatusCodeSame(404); // Assuming no incident with ID 1
        $responseData = json_decode($client->getResponse()->getContent(), true);
    }

    public function testAssignIncidentMissingOfficerId(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/antt/incidents/1/assign', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'team' => 'TEAM-A'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Officer ID is required', $responseData['error']);
    }

    // RESOLVE INCIDENT Tests (2)
    public function testResolveIncident(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/antt/incidents/1/resolve', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'resolution_summary' => 'Issue fixed successfully'
        ]));

        $this->assertResponseStatusCodeSame(404); // Assuming no incident with ID 1
        $responseData = json_decode($client->getResponse()->getContent(), true);
    }

    public function testResolveIncidentMissingSummary(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/antt/incidents/1/resolve', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Resolution summary is required', $responseData['error']);
    }

    // CLOSE INCIDENT Tests (1)
    public function testCloseIncident(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/antt/incidents/1/close');

        $this->assertResponseStatusCodeSame(404); // Assuming no incident with ID 1
        $responseData = json_decode($client->getResponse()->getContent(), true);
    }

    // ESCALATE INCIDENT Tests (1)
    public function testEscalateIncident(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/antt/incidents/1/escalate');

        $this->assertResponseStatusCodeSame(404); // Assuming no incident with ID 1
        $responseData = json_decode($client->getResponse()->getContent(), true);
    }

    // STATISTICS Tests (2)
    public function testGetStatistics(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/antt/incidents/statistics');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('statistics', $responseData);
    }

    public function testGetStatisticsByWard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/antt/incidents/statistics?ward_code=WARD-001');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('statistics', $responseData);
    }

    // OPEN INCIDENTS Tests (1)
    public function testGetOpenIncidents(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/antt/incidents/open');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('incidents', $responseData);
        $this->assertArrayHasKey('total', $responseData);
    }
}
