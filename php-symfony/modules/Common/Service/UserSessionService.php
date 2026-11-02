<?php
declare(strict_types=1);
namespace App\Common\Service;
/**
 * Service for UserSession operations.
 */
class UserSessionService {
    public function doSomething(string $param) {
        return sprintf('%s: %s', 'UserSessionService', $param);
    }
}
