<?php

declare(strict_types=1);

namespace App\Tests\Controller\Security;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class SecurityControllerTest extends DatabaseIntegrationTestCase
{
    // INCIDENT LIST Tests (3)
    public function testListIncidents(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/security/incidents');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertIsArray($responseData['items']);
        $this->assertGreaterThan(0, $responseData['total']);
    }

    public function testListIncidentsWithStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/security/incidents?status=Đang điều tra');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total', $responseData);
    }

    public function testListIncidentsWithResolvedStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/security/incidents?status=Đã xử lý');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total', $responseData);
    }

    // INCIDENT STATISTICS Tests (1)
    public function testIncidentStatistics(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/security/incidents/statistics');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('resolved', $responseData);
        $this->assertArrayHasKey('pending', $responseData);
        $this->assertArrayHasKey('patrolTeams', $responseData);
        $this->assertGreaterThan(0, $responseData['total']);
    }

    // GET INCIDENT Tests (2)
    public function testGetIncident(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/security/incidents/1');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('1', $responseData['id']);
        $this->assertArrayHasKey('type', $responseData);
        $this->assertArrayHasKey('location', $responseData);
    }

    public function testGetIncidentNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/security/incidents/99999');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Incident not found', $responseData['error']);
    }

    // CREATE INCIDENT Tests (2)
    public function testCreateIncident(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/security/incidents', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'type' => 'Trộm cắp',
            'location' => 'Khu đê mới',
            'status' => 'Đang điều tra',
            'description' => 'Mất tài khoản ngân hàng'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals('Trộm cắp', $responseData['type']);
        $this->assertEquals('Khu đê mới', $responseData['location']);
    }

    public function testCreateIncidentWithMinimalData(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/security/incidents', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'type' => 'Tai nạn giao thông'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals('Tai nạn giao thông', $responseData['type']);
        $this->assertEquals('Đang điều tra', $responseData['status']);
    }

    // PATROL LIST Tests (1)
    public function testListPatrols(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/security/patrols');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertIsArray($responseData['items']);
        $this->assertGreaterThan(0, $responseData['total']);
    }

    // GET PATROL Tests (2)
    public function testGetPatrol(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/security/patrols/1');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('1', $responseData['id']);
        $this->assertArrayHasKey('team', $responseData);
        $this->assertArrayHasKey('area', $responseData);
        $this->assertEquals('Đội 1', $responseData['team']);
    }

    public function testGetPatrolNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/security/patrols/99999');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Patrol not found', $responseData['error']);
    }

    // CREATE PATROL Tests (2)
    public function testCreatePatrol(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/security/patrols', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'team' => 'Đội 4',
            'area' => 'Khu D',
            'time' => '08:00 - 16:00',
            'status' => 'Đang tuần tra'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals('Đội 4', $responseData['team']);
        $this->assertEquals('Khu D', $responseData['area']);
    }

    public function testCreatePatrolWithMinimalData(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/security/patrols', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'team' => 'Đội 5'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals('Đội 5', $responseData['team']);
        $this->assertEquals('Chuẩn bị', $responseData['status']);
    }

    // EMERGENCY CONTACTS Tests (1)
    public function testEmergencyContacts(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/security/emergency/contacts');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertGreaterThan(0, count($responseData));
        $this->assertArrayHasKey('service', $responseData[0]);
        $this->assertArrayHasKey('phone', $responseData[0]);
    }

    // EMERGENCY CALL Tests (2)
    public function testMakeEmergencyCall(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/security/emergency/call', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'service' => 'Công an'
        ]));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Emergency call initiated', $responseData['message']);
        $this->assertEquals('Công an', $responseData['service']);
        $this->assertArrayHasKey('timestamp', $responseData);
    }

    public function testMakeEmergencyCallWithoutService(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/security/emergency/call', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Unknown', $responseData['service']);
    }

    // STATISTICS EDGE CASE Tests (1)
    public function testStatisticsAllResolved(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/security/incidents/statistics?status=Đã xử lý');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('resolved', $responseData);
        $this->assertArrayHasKey('pending', $responseData);
    }
}
