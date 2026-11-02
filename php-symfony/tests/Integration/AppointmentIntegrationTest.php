<?php

declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Appointment API.
 *
 * Skipped because controller is not yet implemented.
 */
#[AllowMockObjectsWithoutExpectations]
class AppointmentIntegrationTest extends IntegrationTestCase
{
    private string $jwtToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Appointment API integration tests skipped pending controller implementation.');
    }

    public function testCreateAppointmentRequiresAuthentication(): void
    {
        $client = static::$client;

        $payload = [
            'wardCode' => 'W001',
            'citizenName' => 'John Doe',
            'citizenPhone' => '0123456789',
            'appointmentDate' => '2025-01-15',
            'appointmentTime' => '09:00',
            'purpose' => 'consultation'
        ];

        $client->request('POST', '/api/appointments', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($payload));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateAppointmentSuccess(): void
    {
        if (empty($this->jwtToken)) {
            $this->markTestSkipped('JWT token not set; set TEST_JWT_TOKEN env var');
        }

        $client = static::$client;

        $payload = [
            'wardCode' => 'W001',
            'citizenName' => 'John Doe',
            'citizenPhone' => '0123456789',
            'appointmentDate' => '2025-01-15',
            'appointmentTime' => '09:00',
            'purpose' => 'consultation'
        ];

        $client->request('POST', '/api/appointments', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken
        ], json_encode($payload));

        $this->assertResponseStatusCodeSame(201);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('appointment', $response);
        $appointment = $response['appointment'];
        $this->assertArrayHasKey('id', $appointment);
        $this->assertArrayHasKey('appointment_number', $appointment);
        $this->assertSame('pending', $appointment['status']);
    }

    public function testListAppointmentsWithPagination(): void
    {
        if (empty($this->jwtToken)) {
            $this->markTestSkipped('JWT token not set');
        }

        $client = static::$client;

        // Create a few appointments first (could be done via fixture)
        // Then test listing with limit/offset
        $client->request('GET', '/api/appointments?limit=5&offset=0', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('limit', $data['meta']);
        $this->assertArrayHasKey('offset', $data['meta']);
        $this->assertLessThanOrEqual(5, count($data['data']));
    }
}
