<?php
declare(strict_types=1);
namespace App\Common\Service;
/**
 * Service for WorkflowInstance operations.
 */
class WorkflowInstanceService {
    public function doSomething(string $param) {
        return sprintf('%s: %s', 'WorkflowInstanceService', $param);
    }
}
