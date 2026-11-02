<?php

declare(strict_types=1);

namespace App\Tests\Controller;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class EducationControllerTest extends DatabaseIntegrationTestCase
{
    public function testListCourses(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/education/courses');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testGetCourse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/education/courses/1');

        $this->assertResponseIsSuccessful();
    }

    public function testEnrollCourse(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/education/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => 'user123',
            'course_id' => 'course456',
            'enrollment_type' => 'self_paced'
        ]));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('enrolled', $responseData);
    }
}
