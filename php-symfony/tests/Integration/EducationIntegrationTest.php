<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Education\Entity\Course;
use App\Education\Service\EducationService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EducationIntegrationTest extends TestCase
{
    private EducationService $service;

    protected function setUp(): void
    {
        $this->service = new EducationService();
    }

    public function testFullCourseLifecycle(): void
    {
        $course = $this->service->createCourse(1, 'PHP Basics', 'programming', 120, 29.99);
        $this->assertNotNull($course->getId());
        
        $this->service->updateCourse($course->getId(), [
            'description' => 'Learn PHP from scratch',
            'level' => 'beginner'
        ]);
        
        $retrieved = $this->service->getCourse($course->getId());
        $this->assertNotNull($retrieved);
        $this->assertEquals('Learn PHP from scratch', $retrieved->getDescription());
        $this->assertEquals('beginner', $retrieved->getLevel());
    }

    public function testCourseSearchAndFilter(): void
    {
        $this->service->createCourse(1, 'PHP Basics', 'programming', 120, 29.99);
        $this->service->createCourse(2, 'Java Basics', 'programming', 180, 39.99);
        $this->service->createCourse(3, 'Python Data Science', 'data', 240, 49.99);
        
        $programming = $this->service->getCoursesByCategory('programming');
        $this->assertCount(2, $programming);
        
        $search = $this->service->searchCourses('PHP');
        $this->assertCount(1, $search);
    }

    public function testCoursePublishing(): void
    {
        $course = $this->service->createCourse(1, 'PHP Advanced', 'programming', 180, 59.99);
        $this->assertFalse($course->isPublished());
        
        $this->service->publishCourse($course->getId());
        $published = $this->service->getCourse($course->getId());
        
        $this->assertTrue($published->isPublished());
    }

    public function testCourseDeletion(): void
    {
        $course = $this->service->createCourse(1, 'To Delete', 'test', 60, 9.99);
        $id = $course->getId();
        
        $this->service->deleteCourse($id);
        $result = $this->service->getCourse($id);
        
        $this->assertNull($result);
    }

    public function testFeaturedCourses(): void
    {
        $course1 = $this->service->createCourse(1, 'Basic PHP', 'programming', 60, 9.99);
        $course1->publish();
        
        $course2 = $this->service->createCourse(2, 'Advanced PHP', 'programming', 180, 99.99);
        $course2->publish();
        
        $featured = $this->service->getFeaturedCourses(1);
        
        $this->assertCount(1, $featured);
    }

    public function testInstructorCourses(): void
    {
        $this->service->createCourse(1, 'Course 1', 'programming', 60, 9.99);
        $this->service->createCourse(1, 'Course 2', 'programming', 60, 9.99);
        $this->service->createCourse(2, 'Course 3', 'programming', 60, 9.99);
        
        $instructor1 = $this->service->getInstructorCourses(1);
        $instructor2 = $this->service->getInstructorCourses(2);
        
        $this->assertCount(2, $instructor1);
        $this->assertCount(1, $instructor2);
    }
}
