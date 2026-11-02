<?php

namespace App\Education\Service;

use App\Education\Entity\Course;

class EducationService
{
    private array $courses = [];
    private int $nextId = 1;

    public function createCourse(int $instructorId, string $title, string $category, int $duration = 60, float $price = 0.0)
    {
        $course = new Course($instructorId, $title, $category, $duration, $price);
        $course->setId($this->nextId++);
        $this->courses[$course->getId()] = $course;
        return $course;
    }

    public function getCourse(int $id)
    {
        return $this->courses[$id] ?? null;
    }

    public function getInstructorCourses(int $instructorId)
    {
        return array_filter($this->courses, fn($c) => $c->getInstructorId() === $instructorId);
    }

    public function getCoursesByCategory(string $category)
    {
        return array_filter($this->courses, fn($c) => $c->getCategory() === $category);
    }

    public function searchCourses(string $query)
    {
        $query = strtolower($query);
        return array_filter($this->courses, fn($c) => 
            stripos($c->getTitle(), $query) !== false ||
            stripos($c->getDescription() ?? '', $query) !== false
        );
    }

    public function updateCourse(int $id, array $data)
    {
        $course = $this->getCourse($id);
        if (!$course) return null;

        if (isset($data['description'])) $course->setDescription($data['description']);
        if (isset($data['level'])) $course->setLevel($data['level']);
        if (isset($data['duration'])) $course->setDuration($data['duration']);
        if (isset($data['price'])) $course->setPrice($data['price']);
        if (isset($data['lessons'])) $course->setLessons($data['lessons']);
        if (isset($data['status'])) {
            $data['status'] === 'published' ? $course->publish() : $course->archive();
        }

        return $course;
    }

    public function publishCourse(int $id)
    {
        $course = $this->getCourse($id);
        if ($course) $course->publish();
        return $course;
    }

    public function archiveCourse(int $id)
    {
        $course = $this->getCourse($id);
        if ($course) $course->archive();
        return $course;
    }

    public function getPublishedCourses()
    {
        return array_filter($this->courses, fn($c) => $c->getStatus() === 'published');
    }

    public function getCoursesByLevel(string $level)
    {
        return array_filter($this->courses, fn($c) => $c->getLevel() === $level);
    }

    public function deleteCourse(int $id)
    {
        if (isset($this->courses[$id])) {
            unset($this->courses[$id]);
            return true;
        }
        return false;
    }

    public function getFeaturedCourses(int $limit = 10)
    {
        $published = $this->getPublishedCourses();
        usort($published, fn($a, $b) => $b->getPrice() <=> $a->getPrice());
        return array_slice($published, 0, $limit);
    }
}
