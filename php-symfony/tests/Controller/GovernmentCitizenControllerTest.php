<?php

declare(strict_types=1);

namespace App\Tests\Controller;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class GovernmentCitizenControllerTest extends DatabaseIntegrationTestCase
{
    public function testCreateCitizen(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/government/citizens', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'fullName' => 'Nguyễn Văn A',
            'identityNumber' => '123456789',
            'province' => 'Hà Nội',
            'district' => 'Ba Đình',
            'ward' => 'Phường X',
            'street' => 'Đường ABC',
            'dateOfBirth' => '1990-01-15',
            'gender' => 'male',
            'nationality' => 'Vietnam'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('fullName', $responseData);
        $this->assertEquals('Nguyễn Văn A', $responseData['fullName']);
    }

    public function testGetCitizen(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/government/citizens/123456789');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
    }

    public function testListCitizens(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/government/citizens?province=Hà+Nội&limit=10');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testUpdateCitizen(): void
    {
        $client = static::createClient();
        $client->request('PUT', '/api/government/citizens/123456789', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'occupation' => 'Kỹ sư',
            'educationLevel' => 'Đại học'
        ]));

        $this->assertResponseIsSuccessful();
    }
}
