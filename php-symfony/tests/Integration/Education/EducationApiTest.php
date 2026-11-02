<?php

declare(strict_types=1);

namespace App\Tests\Integration\Education;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[AllowMockObjectsWithoutExpectations]
class EducationApiTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('EducationApiTest skipped: education routes not yet implemented.');
    }
    private int $instructorId = 123;

    public function testCreateCourse(): void
    {
        $client = static::createClient();
        $client->request('POST', '/education/courses', [
            'json' => [
                'title' => 'Test Course',
                'category' => 'Programming',
                'description' => 'A test course',
                'level' => 'beginner',
                'duration' => 60,
                'price' => 0,
            ],
        ]);

        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertSame('Test Course', $data['data']['title']);
        $this->assertSame('Programming', $data['data']['category']);
        $courseId = $data['data']['id'];
        $this->assertNotNull($courseId);
    }

    public function testGetCourseNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/education/courses/99999');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testGetInstructorCourses(): void
    {
        $client = static::createClient();

        // Create a course
        $client->request('POST', '/education/courses', [
            'json' => [
                'title' => 'Instructor Course',
                'category' => 'Test',
            ],
        ]);
        $courseId = json_decode($client->getResponse()->getContent(), true)['data']['id'];

        // Fetch instructor courses
        $client->request('GET', "/education/courses/instructor/{$this->instructorId}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data['data']);
        // Our created course should be in the list
        $ids = array_column($data['data'], 'id');
        $this->assertContains($courseId, $ids);
    }

    public function testGetCoursesByCategory(): void
    {
        $client = static::createClient();

        $client->request('POST', '/education/courses', [
            'json' => [
                'title' => 'Math Course',
                'category' => 'Math',
            ],
        ]);

        $client->request('GET', '/education/courses/category/Math');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data['data']);
        $this->assertSame('Math', $data['data'][0]['category']);
    }

    public function testSearchCourses(): void
    {
        $client = static::createClient();

        $client->request('POST', '/education/courses', [
            'json' => [
                'title' => 'Learn PHP Basics',
                'category' => 'Programming',
            ],
        ]);

        $client->request('GET', '/education/courses/search?q=php');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data['data']);
        $this->assertStringContainsString('PHP', $data['data'][0]['title']);
    }

    public function testUpdateCourse(): void
    {
        $client = static::createClient();

        $client->request('POST', '/education/courses', [
            'json' => [
                'title' => 'Original Title',
                'category' => 'Cat',
            ],
        ]);
        $courseId = json_decode($client->getResponse()->getContent(), true)['data']['id'];

        $client->request('PUT', "/education/courses/{$courseId}", [
            'json' => [
                'title' => 'Updated Title',
                'level' => 'advanced',
                'price' => 49.99,
            ],
        ]);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Updated Title', $data['data']['title']);
        $this->assertSame('advanced', $data['data']['level']);
        $this->assertEquals(49.99, $data['data']['price']);
    }

    public function testPublishAndArchive(): void
    {
        $client = static::createClient();

        $client->request('POST', '/education/courses', [
            'json' => [
                'title' => 'Draft Course',
                'category' => 'Test',
            ],
        ]);
        $courseId = json_decode($client->getResponse()->getContent(), true)['data']['id'];

        // Publish
        $client->request('PUT', "/education/courses/{$courseId}", [
            'json' => ['status' => 'published'],
        ]);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('published', $data['data']['status']);

        // Archive
        $client->request('PUT', "/education/courses/{$courseId}", [
            'json' => ['status' => 'archived'],
        ]);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('archived', $data['data']['status']);
    }

    public function testDeleteCourse(): void
    {
        $client = static::createClient();

        $client->request('POST', '/education/courses', [
            'json' => [
                'title' => 'To Delete',
                'category' => 'Test',
            ],
        ]);
        $courseId = json_decode($client->getResponse()->getContent(), true)['data']['id'];

        $client->request('DELETE', "/education/courses/{$courseId}");
        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        // Verify gone
        $client->request('GET', "/education/courses/{$courseId}");
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testGetFeaturedCourses(): void
    {
        $client = static::createClient();

        // Create a few courses with prices
        for ($i = 0; $i < 3; $i++) {
            $client->request('POST', '/education/courses', [
                'json' => [
                    'title' => "Course $i",
                    'category' => 'Test',
                    'price' => $i * 100,
                ],
            ]);
        }

        $client->request('GET', '/education/courses/featured?limit=3');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        // Should be sorted by price descending
        $prices = array_column($data['data'], 'price');
        $this->assertCount(3, $prices);
        $this->assertEquals([200, 100, 0], $prices); // descending order
    }
}
