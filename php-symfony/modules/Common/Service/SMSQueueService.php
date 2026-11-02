<?php
declare(strict_types=1);
namespace App\Common\Service;
/**
 * Service for SMSQueue operations.
 */
class SMSQueueService {
    public function doSomething(string $param) {
        return sprintf('%s: %s', 'SMSQueueService', $param);
    }
}
