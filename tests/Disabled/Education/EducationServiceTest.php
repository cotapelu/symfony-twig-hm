<?php

declare(strict_types=1);

namespace App\Tests\Unit\Education;

use App\Education\Service\EducationService;
use App\Education\Entity\Course;
use PHPUnit\Framework\TestCase;

class EducationServiceTest extends TestCase
{
    private EducationService $service;

    protected function setUp(): void
    {
        $this->service = new EducationService();
    }

    public function testCreateCourseReturnsCourse(): void
    {
        $course = $this->service->createCourse(1, 'PHP Basics', 'Programming');
        $this->assertInstanceOf(Course::class, $course);
        $this->assertSame('PHP Basics', $course->getTitle());
        $this->assertSame('Programming', $course->getCategory());
        $this->assertSame(1, $course->getInstructorId());
    }

    public function testGetCourseReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->service->getCourse(999));
    }

    public function testGetCourseReturnsCourseAfterCreate(): void
    {
        $course = $this->service->createCourse(1, 'Test Course', 'Test');
        $found = $this->service->getCourse($course->getId());
        $this->assertSame($course, $found);
    }

    public function testGetInstructorCoursesFiltersByInstructor(): void
    {
        $this->service->createCourse(1, 'A', 'Cat1');
        $this->service->createCourse(2, 'B', 'Cat2');
        $this->service->createCourse(1, 'C', 'Cat3');

        $instructor1 = $this->service->getInstructorCourses(1);
        $this->assertCount(2, $instructor1);
        foreach ($instructor1 as $c) {
            $this->assertSame(1, $c->getInstructorId());
        }
    }

    public function testGetCoursesByCategoryFiltersCorrectly(): void
    {
        $this->service->createCourse(1, 'A', 'Math');
        $this->service->createCourse(1, 'B', 'Science');
        $this->service->createCourse(2, 'C', 'Math');

        $math = $this->service->getCoursesByCategory('Math');
        $this->assertCount(2, $math);
        foreach ($math as $c) {
            $this->assertSame('Math', $c->getCategory());
        }
    }

    public function testSearchCoursesMatchesTitleAndDescription(): void
    {
        $this->service->createCourse(1, 'Learn PHP', 'Programming');
        $this->service->createCourse(1, 'Learn Java', 'Programming');
        $this->service->createCourse(1, 'Python Basics', 'Programming');

        $result = $this->service->searchCourses('php');
        $this->assertCount(1, $result);
        $course = array_values($result)[0];
        $this->assertSame('Learn PHP', $course->getTitle());

        $result = $this->service->searchCourses('basics');
        $this->assertCount(1, $result);
        $course = array_values($result)[0];
        $this->assertSame('Python Basics', $course->getTitle());
    }

    public function testUpdateCourseModifiesFields(): void
    {
        $course = $this->service->createCourse(1, 'Original', 'Cat');
        $id = $course->getId();

        $updated = $this->service->updateCourse($id, [
            'description' => 'New desc',
            'level' => 'advanced',
            'duration' => 120,
            'price' => 49.99,
            'lessons' => ['lesson1', 'lesson2'],
        ]);

        $this->assertNotNull($updated);
        $this->assertSame('New desc', $updated->getDescription());
        $this->assertSame('advanced', $updated->getLevel());
        $this->assertSame(120, $updated->getDuration());
        $this->assertEquals(49.99, $updated->getPrice());
        $this->assertSame(['lesson1', 'lesson2'], $updated->getLessons());
    }

    public function testUpdateCourseReturnsNullIfNotFound(): void
    {
        $result = $this->service->updateCourse(999, ['title' => 'X']);
        $this->assertNull($result);
    }

    public function testPublishAndArchiveChangeStatus(): void
    {
        $course = $this->service->createCourse(1, 'Draft Course', 'Cat');
        $id = $course->getId();
        $this->assertSame('draft', $course->getStatus());

        $published = $this->service->publishCourse($id);
        $this->assertSame('published', $published->getStatus());

        $archived = $this->service->archiveCourse($id);
        $this->assertSame('archived', $archived->getStatus());
    }

    public function testGetPublishedCoursesFiltersByStatus(): void
    {
        $c1 = $this->service->createCourse(1, 'A', 'Cat');
        $c2 = $this->service->createCourse(1, 'B', 'Cat');
        $c3 = $this->service->createCourse(1, 'ArArchived', 'Cat');
        $this->service->publishCourse($c1->getId());
        $this->service->publishCourse($c2->getId());
        $this->service->archiveCourse($c3->getId());

        $published = $this->service->getPublishedCourses();
        $this->assertCount(2, $published);
        foreach ($published as $c) {
            $this->assertSame('published', $c->getStatus());
        }
    }

    public function testGetCoursesByLevelFiltersCorrectly(): void
    {
        $this->service->createCourse(1, 'A', 'Cat')->setLevel('beginner');
        $this->service->createCourse(1, 'B', 'Cat')->setLevel('intermediate');
        $this->service->createCourse(1, 'C', 'Cat')->setLevel('advanced');

        $intermediate = $this->service->getCoursesByLevel('intermediate');
        $this->assertCount(1, $intermediate);
        $course = array_values($intermediate)[0];
        $this->assertSame('intermediate', $course->getLevel());
    }

    public function testDeleteCourseRemovesIt(): void
    {
        $course = $this->service->createCourse(1, 'ToDelete', 'Cat');
        $id = $course->getId();
        $this->assertTrue($this->service->deleteCourse($id));
        $this->assertNull($this->service->getCourse($id));
    }

    public function testDeleteCourseReturnsFalseWhenNotFound(): void
    {
        $this->assertFalse($this->service->deleteCourse(999));
    }

    public function testGetFeaturedCoursesSortedByPriceDesc(): void
    {
        $this->service->createCourse(1, 'Cheap', 'Cat')->setPrice(10)->publish();
        $this->service->createCourse(1, 'Expensive', 'Cat')->setPrice(100)->publish();
        $this->service->createCourse(1, 'Mid', 'Cat')->setPrice(50)->publish();

        $featured = $this->service->getFeaturedCourses(3);
        $this->assertCount(3, $featured);
        $this->assertSame('Expensive', $featured[0]->getTitle());
        $this->assertSame('Mid', $featured[1]->getTitle());
        $this->assertSame('Cheap', $featured[2]->getTitle());
    }

    public function testGetFeaturedCoursesLimitsResults(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $c = $this->service->createCourse(1, "Course $i", 'Cat')->setPrice($i * 10)->publish();
        }
        $featured = $this->service->getFeaturedCourses(2);
        $this->assertCount(2, $featured);
    }
}
