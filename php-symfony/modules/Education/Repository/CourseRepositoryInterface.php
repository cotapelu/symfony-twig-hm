<?php

namespace App\Education\Repository;

use App\Education\Entity\Course;

interface CourseRepositoryInterface
{
    public function findById(int $id): ?Course;
    public function findByInstructorId(int $instructorId): array;
    public function findByCategory(string $category): array;
    public function findPublished(): array;
    public function save(Course $course): Course;
    public function delete(int $id): bool;
}
