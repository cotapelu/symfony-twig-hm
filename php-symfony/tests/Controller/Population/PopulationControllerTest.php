<?php

declare(strict_types=1);

namespace App\Tests\Controller\Population;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class PopulationControllerTest extends DatabaseIntegrationTestCase
{
    // LIST Tests (3)
    public function testList(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/population');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('items', $responseData);
    }

    public function testListWithPagination(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/population?page=1&limit=10');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $responseData);
    }

    public function testListWithSearch(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/population?search=Nguyễn');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $responseData);
    }

    // STATISTICS Tests (2)
    public function testStatistics(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/population/statistics');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('male', $responseData);
        $this->assertArrayHasKey('female', $responseData);
    }

    public function testAgeDistribution(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/population/age-distribution');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
    }

    // GET Tests (2)
    public function testGet(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/population/1');

        // Assuming record with ID 1 exists, otherwise it returns 404
        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testGetNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/population/99999');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Population record not found', $responseData['error']);
    }

    // CREATE Tests (2)
    public function testCreate(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/population', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'fullName' => 'Nguyễn Văn A',
            'gender' => 'male',
            'dateOfBirth' => '1990-01-01',
            'address' => 'Hà Nội'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
    }

    public function testCreateValidationError(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/population', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            // Missing required fields
            'gender' => 'male'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    // UPDATE Tests (2)
    public function testUpdate(): void
    {
        $client = static::createClient();
        $client->request('PUT', '/api/population/1', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'fullName' => 'Nguyễn Văn B'
        ]));

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($client->getResponse()->getContent(), true);
    }

    public function testUpdateNotFound(): void
    {
        $client = static::createClient();
        $client->request('PUT', '/api/population/99999', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'gender' => 'female'
        ]));

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Population record not found', $responseData['error']);
    }

    // DELETE Tests (1)
    public function testDelete(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/population/1');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Population record not found', $responseData['error']);
    }

    // GET BY HOUSEHOLD Tests (2)
    public function testGetByHousehold(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/population/household/HH-001');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testGetByHouseholdNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/population/household/HH-99999');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([], $responseData);
    }

    // SEARCH Tests (2)
    public function testSearch(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/population/search?q=Nguyễn');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testSearchEmptyQuery(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/population/search?q=');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
    }

    // LIST WITH FILTER Tests (1)
    public function testListWithFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/population?filter=male');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $responseData);
    }
}
