<?php
declare(strict_types=1);
namespace App\Common\Service;
/**
 * Service for NotificationChannel operations.
 */
class NotificationChannelService {
    public function doSomething(string $param) {
        return sprintf('%s: %s', 'NotificationChannelService', $param);
    }
}
