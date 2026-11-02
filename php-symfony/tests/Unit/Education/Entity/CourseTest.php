<?php declare(strict_types=1);

namespace App\Tests\Unit\Education\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Education\Entity\Course;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CourseTest extends TestCase
{
    private function createBasicCourse(array $overrides = []): Course
    {
        $course = new Course(
            instructorId: 100,
            title: 'Intro to PHP',
            category: 'Programming',
            duration: 120,
            price: 49.99
        );
        foreach ($overrides as $prop => $value) {
            $setter = 'set' . ucfirst($prop);
            if (method_exists($course, $setter)) {
                $course->$setter($value);
            }
        }
        return $course;
    }

    public function testConstructorInitializesProperties(): void
    {
        $course = $this->createBasicCourse([]);
        $this->assertNull($course->getId());
        $this->assertSame(100, $course->getInstructorId());
        $this->assertSame('Intro to PHP', $course->getTitle());
        $this->assertNull($course->getDescription());
        $this->assertSame('Programming', $course->getCategory());
        $this->assertSame('beginner', $course->getLevel());
        $this->assertSame(120, $course->getDuration());
        $this->assertSame(49.99, $course->getPrice());
        $this->assertFalse($course->isPublished());
        $this->assertNull($course->getThumbnailUrl());
        $this->assertInstanceOf(\DateTime::class, $course->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $course->getUpdatedAt());
        $this->assertSame('draft', $course->getStatus());
    }

    public function testGettersAndSetters(): void
    {
        $course = $this->createBasicCourse([]);

        $course->setDescription('Learn PHP basics');
        $this->assertSame('Learn PHP basics', $course->getDescription());

        $course->setLevel('advanced');
        $this->assertSame('advanced', $course->getLevel());

        $course->setPublished(true);
        $this->assertTrue($course->isPublished());
        $this->assertSame('published', $course->getStatus());

        $course->setDuration(200);
        $this->assertSame(200, $course->getDuration());

        $course->setPrice(99.99);
        $this->assertSame(99.99, $course->getPrice());

        $course->setThumbnailUrl('thumb.jpg');
        $this->assertSame('thumb.jpg', $course->getThumbnailUrl());

        $course->publish();
        $this->assertTrue($course->isPublished());

        $course->archive();
        $this->assertFalse($course->isPublished());

        $course->setLessons(['Intro', 'Variables', 'Functions']);
        // No getter for lessons, but method should exist and return $this
        $this->assertSame($course, $course->setLessons([]));
    }
}
