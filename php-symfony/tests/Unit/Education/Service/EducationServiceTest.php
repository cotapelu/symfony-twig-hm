<?php declare(strict_types=1);

namespace App\Tests\Unit\Education\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Education\Service\EducationService;
use App\Education\Entity\Course;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EducationServiceTest extends TestCase
{
    private EducationService $service;

    protected function setUp(): void
    {
        $this->service = new EducationService();
    }

    public function testCreateCourseReturnsCourseWithIncrementedIdAndProperties(): void
    {
        $course = $this->service->createCourse(10, 'PHP Basics', 'programming', 120, 49.99);
        $this->assertInstanceOf(Course::class, $course);
        $this->assertSame(1, $course->getId());
        $this->assertSame(10, $course->getInstructorId());
        $this->assertSame('PHP Basics', $course->getTitle());
        $this->assertSame('programming', $course->getCategory());
        $this->assertSame(120, $course->getDuration());
        $this->assertSame(49.99, $course->getPrice());
        $this->assertFalse($course->isPublished()); // default draft
        $this->assertSame('beginner', $course->getLevel()); // default
    }

    public function testCreateCourseIncrementsId(): void
    {
        $c1 = $this->service->createCourse(1, 'C1', 'cat', 60, 0.0);
        $c2 = $this->service->createCourse(2, 'C2', 'cat', 60, 0.0);
        $this->assertSame(1, $c1->getId());
        $this->assertSame(2, $c2->getId());
    }

    public function testGetCourseReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->service->getCourse(999));
    }

    public function testGetCourseReturnsExistingCourse(): void
    {
        $course = $this->service->createCourse(5, 'Title', 'cat', 60, 0.0);
        $found = $this->service->getCourse($course->getId());
        $this->assertSame($course, $found);
    }

    public function testGetInstructorCoursesFiltersByInstructor(): void
    {
        $this->service->createCourse(1, 'A', 'cat', 60, 0.0);
        $this->service->createCourse(2, 'B', 'cat', 60, 0.0);
        $this->service->createCourse(1, 'C', 'cat', 60, 0.0);

        $courses = $this->service->getInstructorCourses(1);
        $this->assertCount(2, $courses);
        foreach ($courses as $c) {
            $this->assertSame(1, $c->getInstructorId());
        }
    }

    public function testGetCoursesByCategoryFilters(): void
    {
        $this->service->createCourse(1, 'A', 'math', 60, 0.0);
        $this->service->createCourse(2, 'B', 'science', 60, 0.0);
        $this->service->createCourse(3, 'C', 'math', 60, 0.0);

        $mathCourses = $this->service->getCoursesByCategory('math');
        $this->assertCount(2, $mathCourses);
        foreach ($mathCourses as $c) {
            $this->assertSame('math', $c->getCategory());
        }
    }

    public function testSearchCoursesMatchesTitleAndDescription(): void
    {
        $c1 = $this->service->createCourse(1, 'Learn PHP', 'dev', 60, 0.0);
        $c1->setDescription('PHP is a popular language');
        $c2 = $this->service->createCourse(2, 'JavaScript Guide', 'dev', 60, 0.0);
        $c2->setDescription('Covers JS fundamentals');

        $results = $this->service->searchCourses('php');
        $this->assertCount(1, $results);
        $this->assertContains($c1, $results);

        $results2 = $this->service->searchCourses('js');
        $this->assertCount(1, $results2);
        $this->assertContains($c2, $results2);
    }

    public function testSearchCoursesIsCaseInsensitive(): void
    {
        $c = $this->service->createCourse(1, 'Python Course', 'dev', 60, 0.0);
        $results = $this->service->searchCourses('PYTHON');
        $this->assertCount(1, $results);
        $this->assertContains($c, $results);
    }

    public function testUpdateCourseUpdatesProvidedFields(): void
    {
        $course = $this->service->createCourse(1, 'Title', 'cat', 60, 0.0);
        $updated = $this->service->updateCourse($course->getId(), [
            'description' => 'New desc',
            'level' => 'advanced',
            'duration' => 90,
            'price' => 99.99,
            'lessons' => ['l1', 'l2'],
        ]);

        $this->assertSame($course, $updated);
        $this->assertSame('New desc', $course->getDescription());
        $this->assertSame('advanced', $course->getLevel());
        $this->assertSame(90, $course->getDuration());
        $this->assertSame(99.99, $course->getPrice());
    }

    public function testUpdateCoursePublishesWhenStatusPublished(): void
    {
        $course = $this->service->createCourse(1, 'Title', 'cat', 60, 0.0);
        $this->service->updateCourse($course->getId(), ['status' => 'published']);
        $this->assertTrue($course->isPublished());
        $this->assertSame('published', $course->getStatus());
    }

    public function testUpdateCourseArchivesWhenStatusDraft(): void
    {
        $course = $this->service->createCourse(1, 'Title', 'cat', 60, 0.0);
        $course->publish(); // make published
        $this->service->updateCourse($course->getId(), ['status' => 'draft']);
        $this->assertFalse($course->isPublished());
        $this->assertSame('draft', $course->getStatus());
    }

    public function testUpdateCourseReturnsNullWhenNotFound(): void
    {
        $result = $this->service->updateCourse(999, ['title' => 'x']);
        $this->assertNull($result);
    }

    public function testPublishCoursePublishesAndReturnsCourse(): void
    {
        $course = $this->service->createCourse(1, 'Title', 'cat', 60, 0.0);
        $result = $this->service->publishCourse($course->getId());
        $this->assertSame($course, $result);
        $this->assertTrue($course->isPublished());
    }

    public function testPublishCourseReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->service->publishCourse(999));
    }

    public function testArchiveCourseArchivesAndReturnsCourse(): void
    {
        $course = $this->service->createCourse(1, 'Title', 'cat', 60, 0.0);
        $course->publish();
        $result = $this->service->archiveCourse($course->getId());
        $this->assertSame($course, $result);
        $this->assertFalse($course->isPublished());
    }

    public function testArchiveCourseReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->service->archiveCourse(999));
    }

    public function testGetPublishedCoursesReturnsOnlyPublished(): void
    {
        $c1 = $this->service->createCourse(1, 'A', 'cat', 60, 0.0);
        $c2 = $this->service->createCourse(2, 'B', 'cat', 60, 0.0);
        $c3 = $this->service->createCourse(3, 'C', 'cat', 60, 0.0);
        $c2->publish();
        $c3->publish();

        $published = $this->service->getPublishedCourses();
        $this->assertCount(2, $published);
        $this->assertContains($c2, $published);
        $this->assertContains($c3, $published);
    }

    public function testGetCoursesByLevelFilters(): void
    {
        $this->service->createCourse(1, 'A', 'cat', 60, 0.0)->setLevel('beginner');
        $this->service->createCourse(2, 'B', 'cat', 60, 0.0)->setLevel('intermediate');
        $this->service->createCourse(3, 'C', 'cat', 60, 0.0)->setLevel('advanced');

        $advanced = $this->service->getCoursesByLevel('advanced');
        $this->assertCount(1, $advanced);
        $this->assertSame('advanced', current($advanced)->getLevel());
    }

    public function testDeleteCourseRemovesAndReturnsTrue(): void
    {
        $course = $this->service->createCourse(1, 'Title', 'cat', 60, 0.0);
        $id = $course->getId();
        $result = $this->service->deleteCourse($id);
        $this->assertTrue($result);
        $this->assertNull($this->service->getCourse($id));
    }

    public function testDeleteCourseReturnsFalseWhenNotFound(): void
    {
        $this->assertFalse($this->service->deleteCourse(999));
    }

    public function testGetFeaturedCoursesReturnsSortedByPriceDesc(): void
    {
        $c1 = $this->service->createCourse(1, 'A', 'cat', 60, 10.0);
        $c2 = $this->service->createCourse(2, 'B', 'cat', 60, 100.0);
        $c3 = $this->service->createCourse(3, 'C', 'cat', 60, 50.0);
        $c1->publish();
        $c2->publish();
        $c3->publish();

        $featured = $this->service->getFeaturedCourses(2);
        $this->assertCount(2, $featured);
        // Should be highest price first: c2 (100), c3 (50)
        $this->assertSame($c2, $featured[0]);
        $this->assertSame($c3, $featured[1]);
    }

    public function testGetFeaturedCoursesLimitsCorrectly(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $c = $this->service->createCourse($i, "Course$i", 'cat', 60, $i * 10.0);
            $c->publish();
        }
        $featured = $this->service->getFeaturedCourses(3);
        $this->assertCount(3, $featured);
        // Prices descending
        $prices = array_map(fn($c) => $c->getPrice(), $featured);
        $this->assertSame([40.0, 30.0, 20.0], $prices);
    }
}
