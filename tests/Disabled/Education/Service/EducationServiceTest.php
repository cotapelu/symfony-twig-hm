<?php declare(strict_types=1);

namespace App\Tests\Unit\Education\Service;

use App\Education\Entity\Course;
use App\Education\Service\EducationService;
use PHPUnit\Framework\TestCase;

class EducationServiceTest extends TestCase
{
    private EducationService $service;

    protected function setUp(): void
    {
        $this->service = new EducationService();
    }

    public function testCreateCourse(): void
    {
        $course = $this->service->createCourse(1, 'PHP Basics', 'programming');
        
        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals(1, $course->getInstructorId());
        $this->assertEquals('PHP Basics', $course->getTitle());
        $this->assertEquals('programming', $course->getCategory());
    }

    public function testGetCourse(): void
    {
        $created = $this->service->createCourse(1, 'PHP Basics', 'programming');
        $created->setId(1);
        
        $result = $this->service->getCourse(1);
        
        $this->assertSame($created, $result);
    }

    public function testGetCourseNotFound(): void
    {
        $result = $this->service->getCourse(999);
        
        $this->assertNull($result);
    }

    public function testGetInstructorCourses(): void
    {
        $course1 = $this->service->createCourse(1, 'PHP Basics', 'programming');
        $course1->setId(1);
        $course2 = $this->service->createCourse(1, 'Java Basics', 'programming');
        $course2->setId(2);
        $course3 = $this->service->createCourse(2, 'Python Basics', 'data');
        $course3->setId(3);
        
        $results = $this->service->getInstructorCourses(1);
        
        $this->assertCount(2, $results);
    }

    public function testGetCoursesByCategory(): void
    {
        $course1 = $this->service->createCourse(1, 'PHP Basics', 'programming');
        $course1->setId(1);
        $course2 = $this->service->createCourse(2, 'Java Basics', 'programming');
        $course2->setId(2);
        $course3 = $this->service->createCourse(3, 'Python Basics', 'data');
        $course3->setId(3);
        
        $results = $this->service->getCoursesByCategory('programming');
        
        $this->assertCount(2, $results);
    }

    public function testSearchCourses(): void
    {
        $course1 = $this->service->createCourse(1, 'PHP Basics', 'programming');
        $course1->setId(1);
        $course1->setDescription('Learn PHP from scratch');
        $course2 = $this->service->createCourse(2, 'Java Basics', 'programming');
        $course2->setId(2);
        
        $results = $this->service->searchCourses('PHP');
        
        $this->assertCount(1, $results);
    }

    public function testSearchCoursesByDescription(): void
    {
        $course1 = $this->service->createCourse(1, 'PHP Basics', 'programming');
        $course1->setId(1);
        $course1->setDescription('Learn PHP from scratch');
        
        $results = $this->service->searchCourses('scratch');
        
        $this->assertCount(1, $results);
    }

    public function testUpdateCourse(): void
    {
        $course = $this->service->createCourse(1, 'PHP Basics', 'programming');
        $course->setId(1);
        
        $result = $this->service->updateCourse(1, [
            'description' => 'Updated description',
            'level' => 'beginner'
        ]);
        
        $this->assertNotNull($result);
        $this->assertEquals('Updated description', $result->getDescription());
        $this->assertEquals('beginner', $result->getLevel());
    }

    public function testUpdateCourseNotFound(): void
    {
        $result = $this->service->updateCourse(999, ['description' => 'Test']);
        
        $this->assertNull($result);
    }

    public function testPublishCourse(): void
    {
        $course = $this->service->createCourse(1, 'PHP Basics', 'programming');
        $course->setId(1);
        
        $result = $this->service->publishCourse(1);
        
        $this->assertNotNull($result);
        $this->assertTrue($result->isPublished());
    }

    public function testArchiveCourse(): void
    {
        $course = $this->service->createCourse(1, 'PHP Basics', 'programming');
        $course->setId(1);
        $course->publish();
        
        $result = $this->service->archiveCourse(1);
        
        $this->assertNotNull($result);
        $this->assertFalse($result->isPublished());
    }

    public function testDeleteCourse(): void
    {
        $course = $this->service->createCourse(1, 'PHP Basics', 'programming');
        $course->setId(1);
        
        $result = $this->service->deleteCourse(1);
        
        $this->assertTrue($result);
        $this->assertNull($this->service->getCourse(1));
    }

    public function testDeleteCourseNotFound(): void
    {
        $result = $this->service->deleteCourse(999);
        
        $this->assertFalse($result);
    }

    public function testGetCoursesByLevel(): void
    {
        $course1 = $this->service->createCourse(1, 'PHP Basics', 'programming');
        $course1->setId(1);
        $course1->setLevel('beginner');
        $course2 = $this->service->createCourse(2, 'Java Advanced', 'programming');
        $course2->setId(2);
        $course2->setLevel('advanced');
        
        $results = $this->service->getCoursesByLevel('beginner');
        
        $this->assertCount(1, $results);
    }

    public function testGetFeaturedCourses(): void
    {
        $course1 = $this->service->createCourse(1, 'PHP Basics', 'programming');
        $course1->setId(1);
        $course1->setPrice(29.99);
        $course1->publish();
        
        $course2 = $this->service->createCourse(2, 'Java Basics', 'programming');
        $course2->setId(2);
        $course2->setPrice(49.99);
        $course2->publish();
        
        $results = $this->service->getFeaturedCourses(1);
        
        $this->assertCount(1, $results);
    }
}
