<?php
declare(strict_types=1);
namespace App\Common\Service;
/**
 * Service for OAuthProvider operations.
 */
class OAuthProviderService {
    public function doSomething(string $param) {
        return sprintf('%s: %s', 'OAuthProviderService', $param);
    }
}
