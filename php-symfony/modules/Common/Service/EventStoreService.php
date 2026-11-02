<?php
declare(strict_types=1);
namespace App\Common\Service;
/**
 * Service for EventStore operations.
 */
class EventStoreService {
    public function doSomething(string $param) {
        return sprintf('%s: %s', 'EventStoreService', $param);
    }
}
