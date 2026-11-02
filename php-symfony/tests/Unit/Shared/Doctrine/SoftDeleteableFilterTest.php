<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Doctrine;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Doctrine\SoftDeleteableFilter;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]

class SoftDeleteableFilterTest extends TestCase
{
    private SoftDeleteableFilter $filter;
    private EntityManagerInterface&MockObject $emMock;

    protected function setUp(): void
    {
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->filter = new SoftDeleteableFilter($this->emMock);
    }

    private function createClassMetadata(array $fields, string $name = 'TestEntity'): ClassMetadata
    {
        $meta = new ClassMetadata($name);
        foreach ($fields as $field => $type) {
            $meta->mapField(['fieldName' => $field, 'type' => $type]);
        }
        return $meta;
    }

    public function testAddFilterConstraintWithDeletedAtField(): void
    {
        $meta = $this->createClassMetadata(['deletedAt' => 'datetime']);
        $constraint = $this->filter->addFilterConstraint($meta, 't');
        $this->assertEquals('t.deleted_at IS NULL', $constraint);
    }

    public function testAddFilterConstraintWithIsDeletedField(): void
    {
        $meta = $this->createClassMetadata(['isDeleted' => 'boolean']);
        $constraint = $this->filter->addFilterConstraint($meta, 'u');
        $this->assertEquals('u.is_deleted = false', $constraint);
    }

    public function testAddFilterConstraintWithBothFieldsPrioritizesDeletedAt(): void
    {
        $meta = $this->createClassMetadata(['deletedAt' => 'datetime', 'isDeleted' => 'boolean']);
        $constraint = $this->filter->addFilterConstraint($meta, 'e');
        $this->assertEquals('e.deleted_at IS NULL', $constraint);
    }

    public function testAddFilterConstraintWithNoSoftDeleteFields(): void
    {
        $meta = $this->createClassMetadata(['id' => 'integer', 'name' => 'string']);
        $constraint = $this->filter->addFilterConstraint($meta, 'x');
        $this->assertEquals('', $constraint);
    }

    public function testAddFilterConstraintWithDifferentAlias(): void
    {
        $meta = $this->createClassMetadata(['deletedAt' => 'datetime']);
        $constraint = $this->filter->addFilterConstraint($meta, 'my_alias');
        $this->assertEquals('my_alias.deleted_at IS NULL', $constraint);
    }

    public function testAddFilterConstraintWithUnderscoreFieldName(): void
    {
        $meta = $this->createClassMetadata(['deleted_at' => 'datetime']);
        $constraint = $this->filter->addFilterConstraint($meta, 't');
        // filter expects 'deletedAt', not 'deleted_at'
        $this->assertEquals('', $constraint);
    }

    public function testAddFilterConstraintCaseInsensitiveFieldCheck(): void
    {
        // Field names are case-sensitive; 'DeletedAt' won't match 'deletedAt'
        $meta = $this->createClassMetadata(['DeletedAt' => 'datetime']);
        $constraint = $this->filter->addFilterConstraint($meta, 't');
        $this->assertEquals('', $constraint);
    }
}
