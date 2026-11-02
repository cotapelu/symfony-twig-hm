<?php declare(strict_types=1);

namespace App\Tests\Unit\Education\Controller;

use App\Education\Controller\EducationController;
use App\Education\Entity\Course;
use App\Education\Service\EducationService;
use PHPUnit\Framework\TestCase;

class EducationControllerTest extends TestCase
{
    private EducationController $controller;
    private EducationService $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(EducationService::class);
        $reflection = new \ReflectionClass(EducationController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $reflectionProperty = $reflection->getProperty('service');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($controller, $this->service);
        $this->controller = $controller;
    }

    private function createCourse(int $id = 1): Course
    {
        $course = new Course(1, 'PHP Basics', 'programming', 120, 29.99);
        $course->setId($id);
        return $course;
    }

    public function testCreateCourseSuccess(): void
    {
        $course = $this->createCourse();
        
        $this->service->expects($this->once())
            ->method('createCourse')
            ->with(1, 'PHP Basics', 'programming')
            ->willReturn($course);

        $request = [
            'user_id' => 1,
            'body' => [
                'title' => 'PHP Basics',
                'category' => 'programming'
            ]
        ];

        $result = $this->controller->createCourse($request);

        $this->assertEquals(201, $result['status']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testCreateCourseMissingTitle(): void
    {
        $request = [
            'user_id' => 1,
            'body' => [
                'category' => 'programming'
            ]
        ];

        $result = $this->controller->createCourse($request);

        $this->assertEquals(201, $result['status']);
    }

    public function testGetCourses(): void
    {
        $course = $this->createCourse();
        $course->publish();
        
        $this->service->expects($this->once())
            ->method('getPublishedCourses')
            ->willReturn([$course]);

        $request = [];

        $result = $this->controller->getCourses($request);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
    }

    public function testGetCourseFound(): void
    {
        $course = $this->createCourse();
        
        $this->service->expects($this->once())
            ->method('getCourse')
            ->with(1)
            ->willReturn($course);

        $request = ['params' => ['id' => 1]];

        $result = $this->controller->getCourse($request);

        $this->assertEquals(200, $result['status']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testGetCourseNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('getCourse')
            ->with(999)
            ->willReturn(null);

        $request = ['params' => ['id' => 999]];

        $result = $this->controller->getCourse($request);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Course not found', $result['error']);
    }

    public function testUpdateCourseSuccess(): void
    {
        $course = $this->createCourse();
        
        $this->service->expects($this->once())
            ->method('updateCourse')
            ->with(1, ['description' => 'Updated'])
            ->willReturn($course);

        $request = [
            'params' => ['id' => 1],
            'body' => ['description' => 'Updated']
        ];

        $result = $this->controller->updateCourse($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testUpdateCourseNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('updateCourse')
            ->with(999, [])
            ->willReturn(null);

        $request = [
            'params' => ['id' => 999],
            'body' => []
        ];

        $result = $this->controller->updateCourse($request);

        $this->assertEquals(404, $result['status']);
    }

    public function testDeleteCourseSuccess(): void
    {
        $this->service->expects($this->once())
            ->method('deleteCourse')
            ->with(1)
            ->willReturn(true);

        $request = ['params' => ['id' => 1]];

        $result = $this->controller->deleteCourse($request);

        $this->assertEquals(204, $result['status']);
    }

    public function testDeleteCourseNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('deleteCourse')
            ->with(999)
            ->willReturn(false);

        $request = ['params' => ['id' => 999]];

        $result = $this->controller->deleteCourse($request);

        $this->assertEquals(404, $result['status']);
    }

    public function testGetInstructorCourses(): void
    {
        $course = $this->createCourse();
        
        $this->service->expects($this->once())
            ->method('getInstructorCourses')
            ->with(1)
            ->willReturn([$course]);

        $request = ['params' => ['instructor_id' => 1]];

        $result = $this->controller->getInstructorCourses($request);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
    }

    public function testGetCoursesByCategory(): void
    {
        $course = $this->createCourse();
        
        $this->service->expects($this->once())
            ->method('getCoursesByCategory')
            ->with('programming')
            ->willReturn([$course]);

        $request = ['params' => ['category' => 'programming']];

        $result = $this->controller->getCoursesByCategory($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testSearchCourses(): void
    {
        $course = $this->createCourse();
        
        $this->service->expects($this->once())
            ->method('searchCourses')
            ->with('PHP')
            ->willReturn([$course]);

        $request = ['query' => ['q' => 'PHP']];

        $result = $this->controller->searchCourses($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetFeaturedCourses(): void
    {
        $course = $this->createCourse();
        
        $this->service->expects($this->once())
            ->method('getFeaturedCourses')
            ->with(10)
            ->willReturn([$course]);

        $request = ['query' => ['limit' => 10]];

        $result = $this->controller->getFeaturedCourses($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetCoursesByLevel(): void
    {
        $course = $this->createCourse();
        
        $this->service->expects($this->once())
            ->method('getCoursesByLevel')
            ->with('beginner')
            ->willReturn([$course]);

        $request = ['params' => ['level' => 'beginner']];

        $result = $this->controller->getCoursesByLevel($request);

        $this->assertEquals(200, $result['status']);
    }
}
