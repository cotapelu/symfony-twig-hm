<?php

declare(strict_types=1);

namespace App\Shared\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class SoftDeleteableFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // Apply filter for entities with a 'deletedAt' timestamp
        if ($targetEntity->hasField('deletedAt')) {
            return sprintf('%s.deleted_at IS NULL', $targetTableAlias);
        }
        // Or for entities with an 'isDeleted' boolean flag
        if ($targetEntity->hasField('isDeleted')) {
            return sprintf('%s.is_deleted = false', $targetTableAlias);
        }
        return '';
    }
}
