<?php declare(strict_types=1);

namespace App\Tests\Unit\Education\Entity;

use App\Education\Entity\Course;
use PHPUnit\Framework\TestCase;

class CourseTest extends TestCase
{
    public function testConstructorSetsRequiredProperties(): void
    {
        $course = new Course(1, 'PHP Basics', 'programming', 120, 29.99);
        
        $this->assertNull($course->getId());
        $this->assertEquals(1, $course->getInstructorId());
        $this->assertEquals('PHP Basics', $course->getTitle());
        $this->assertEquals('programming', $course->getCategory());
        $this->assertEquals(120, $course->getDuration());
        $this->assertEquals(29.99, $course->getPrice());
        $this->assertFalse($course->isPublished());
    }

    public function testSetDescription(): void
    {
        $course = new Course(1, 'PHP Basics', 'programming', 120, 29.99);
        $course->setDescription('Learn PHP from scratch');
        
        $this->assertEquals('Learn PHP from scratch', $course->getDescription());
    }

    public function testSetLevel(): void
    {
        $course = new Course(1, 'PHP Basics', 'programming', 120, 29.99);
        $course->setLevel('beginner');
        
        $this->assertEquals('beginner', $course->getLevel());
    }

    public function testPublish(): void
    {
        $course = new Course(1, 'PHP Basics', 'programming', 120, 29.99);
        $this->assertFalse($course->isPublished());
        
        $course->publish();
        
        $this->assertTrue($course->isPublished());
        $this->assertEquals('published', $course->getStatus());
    }

    public function testArchive(): void
    {
        $course = new Course(1, 'PHP Basics', 'programming', 120, 29.99);
        $course->publish();
        $this->assertTrue($course->isPublished());
        
        $course->archive();
        
        $this->assertFalse($course->isPublished());
        $this->assertEquals('draft', $course->getStatus());
    }

    public function testSetId(): void
    {
        $course = new Course(1, 'PHP Basics', 'programming', 120, 29.99);
        $course->setId(42);
        
        $this->assertEquals(42, $course->getId());
    }

    public function testSetDuration(): void
    {
        $course = new Course(1, 'PHP Basics', 'programming', 120, 29.99);
        $course->setDuration(180);
        
        $this->assertEquals(180, $course->getDuration());
    }

    public function testSetPrice(): void
    {
        $course = new Course(1, 'PHP Basics', 'programming', 120, 29.99);
        $course->setPrice(19.99);
        
        $this->assertEquals(19.99, $course->getPrice());
    }

    public function testSetLessons(): void
    {
        $course = new Course(1, 'PHP Basics', 'programming', 120, 29.99);
        $result = $course->setLessons(['lesson1', 'lesson2']);
        
        $this->assertInstanceOf(Course::class, $result);
    }

    public function testGetCreatedAt(): void
    {
        $course = new Course(1, 'PHP Basics', 'programming', 120, 29.99);
        
        $this->assertInstanceOf(\DateTime::class, $course->getCreatedAt());
    }

    public function testGetUpdatedAt(): void
    {
        $course = new Course(1, 'PHP Basics', 'programming', 120, 29.99);
        
        $this->assertInstanceOf(\DateTime::class, $course->getUpdatedAt());
    }
}
