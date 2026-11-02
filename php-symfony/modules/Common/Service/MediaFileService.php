<?php
declare(strict_types=1);
namespace App\Common\Service;
/**
 * Service for MediaFile operations.
 */
class MediaFileService {
    public function doSomething(string $param) {
        return sprintf('%s: %s', 'MediaFileService', $param);
    }
}
