<?php
declare(strict_types=1);
namespace App\Common\Service;
/**
 * Service for APNSDevice operations.
 */
class APNSDeviceService {
    public function doSomething(string $param) {
        return sprintf('%s: %s', 'APNSDeviceService', $param);
    }
}
