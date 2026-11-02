<?php
declare(strict_types=1);
namespace App\Common\Service;
/**
 * Service for EmailQueue operations.
 */
class EmailQueueService {
    public function doSomething(string $param) {
        return sprintf('%s: %s', 'EmailQueueService', $param);
    }
}
